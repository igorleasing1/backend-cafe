<?php

namespace App\Http\Controllers;

use App\Http\Requests\FilaRequest;
use App\Models\Fila;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class FilaController extends Controller
{
    public function listar()
    {
        try {
            $fila = Fila::with('usuario')
                ->orderBy('posicao', 'asc')
                ->get();

            return response()->json($fila, 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Erro ao carregar fila.'], 500);
        }
    }
 public function entrarNaFila(Request $request)
{
    try {
        $usuarioId = auth()->id();
        $validados = $request->validate([
            'cafe' => 'sometimes|integer',
            'filtro' => 'sometimes|integer'
        ]);

        // 1. IMPORTANTE: Remove qualquer registro antigo (inclusive os "soft deleted")
        // Isso limpa o erro de "Duplicate entry" definitivamente
        \App\Models\Fila::where('usuario_id', $usuarioId)->forceDelete();

        // 2. Calcula a nova posição (opcional, para manter a ordem)
        $proximaPosicao = (\App\Models\Fila::max('posicao') ?? 0) + 1;

        // 3. Cria o novo registro limpo
        $itemFila = \App\Models\Fila::create([
            'usuario_id' => $usuarioId,
            'cafe' => $validados['cafe'] ?? 0,
            'filtro' => $validados['filtro'] ?? 0,
            'posicao' => $proximaPosicao
        ]);

        return response()->json([
            'message' => 'Entrou na fila com sucesso!',
            'item' => $itemFila
        ], 201);

    } catch (\Exception $e) {
        return response()->json([
            'message' => 'Erro ao entrar na fila.',
            'error' => $e->getMessage()
        ], 500);
    }
}
    public function atualizarItem(Request $request, $usuario_id)
{
    try {
        $fila = Fila::where('usuario_id', $usuario_id)->first();
        if (!$fila) return response()->json(['message' => 'Registro não encontrado.'], 404);

        if ($request->tipo_item === 'cafe') {
            $fila->increment('cafe');
        } elseif ($request->tipo_item === 'filtro') {
            // REGRA: Não permite filtro se o café for 0
            if ($fila->cafe <= 0) {
                return response()->json(['message' => 'Adicione um café antes de pedir um filtro.'], 400);
            }
            $fila->increment('filtro');
        }

        return response()->json(['message' => 'Item atualizado!', 'dados' => $fila]);
    } catch (\Exception $e) {
        return response()->json(['message' => 'Erro ao atualizar.'], 500);
    }
}

public function removerItem(Request $request, $usuario_id)
{
    try {
        $fila = Fila::where('usuario_id', $usuario_id)->first();
        if (!$fila) return response()->json(['message' => 'Não encontrado'], 404);

        $tipo = $request->tipo_item;

        if ($tipo === 'cafe') {
            // REGRA: Não permite remover o último café se houver filtros ativos
            if ($fila->cafe == 1 && $fila->filtro > 0) {
                return response()->json(['message' => 'Remova os filtros antes de remover o último café.'], 400);
            }
            if ($fila->cafe > 0) $fila->decrement('cafe');
        } elseif ($tipo === 'filtro' && $fila->filtro > 0) {
            $fila->decrement('filtro');
        }

        return response()->json(['message' => 'Item subtraído', 'dados' => $fila]);
    } catch (\Exception $e) {
        return response()->json(['message' => 'Erro ao remover'], 500);
    }
}

    public function sairDaFila($usuario_id)
{
    try {
        // Use where()->delete() para garantir que remove pelo ID do usuário
        $removido = Fila::where('usuario_id', $usuario_id)->delete();

        if (!$removido) {
            return response()->json(['message' => 'Usuário não estava na fila.'], 404);
        }

        return response()->json(['message' => 'Removido com sucesso.'], 200);
    } catch (\Exception $e) {
        return response()->json(['message' => 'Erro ao remover.'], 500);
    }
}
}