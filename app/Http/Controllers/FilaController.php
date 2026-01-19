<?php

namespace App\Http\Controllers;

use App\Http\Requests\FilaRequest;
use App\Models\Fila;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

    public function entrarNaFila(FilaRequest $request)
    {
        try {
            $jaEstaNaFila = Fila::where('usuario_id', $request->usuario_id)->exists();
            if ($jaEstaNaFila) {
                return response()->json(['message' => 'Usuário já está na fila.'], 400);
            }

            // Regra: Não pode entrar só com filtro
            if ($request->tipo_item === 'filtro') {
                return response()->json(['message' => 'Adicione um café antes do filtro.'], 400);
            }

            $ultimaPosicao = Fila::max('posicao') ?? 0;
            
            $fila = Fila::create([
                'usuario_id' => $request->usuario_id,
                'posicao' => $ultimaPosicao + 1,
                'cafe' => $request->tipo_item === 'cafe' ? 1 : 0,
                'filtro' => 0
            ]);

            return response()->json(['message' => 'Entrou na fila!', 'dados' => $fila], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Erro ao entrar na fila.'], 500);
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

    public function sairDaFila(int $usuario_id)
    {
        DB::beginTransaction();
        try {
            $fila = Fila::where('usuario_id', $usuario_id)->first();
            if (!$fila) return response()->json(['message' => 'Não encontrado'], 404);

            $posicaoRemovida = $fila->posicao;
            $fila->delete();

            Fila::where('posicao', '>', $posicaoRemovida)->decrement('posicao');

            DB::commit();
            return response()->json(['message' => 'Saiu da fila com sucesso!']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Erro ao sair.'], 500);
        }
    }
}