<?php

namespace App\Http\Controllers;

use App\Models\Compras;
use App\Models\Fila; // Importante para remover da fila após comprar
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ComprasController extends Controller
{
    public function listar(Request $request)
    {
        try {
           $query = Compras::with(['usuario']);
            if ($request->filled('usuario_id')) {
                $query->where('usuario_id', $request->usuario_id);
            }

            if ($request->filled('item')) {
                $query->where('item', 'like', '%' . $request->item . '%');
            }

            if ($request->filled('data_inicio') && $request->filled('data_fim')) {
                $query->whereBetween('data_compra', [
                    $request->data_inicio . ' 00:00:00',
                    $request->data_fim . ' 23:59:59'
                ]);
            }

      $compras = $query->orderBy('data_compra', 'desc')->get();
       return response()->json($compras, 200);
    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }
    }

  public function comprar(Request $request)
{
    try {
        $request->validate([
            'usuario_id' => 'required|exists:usuarios,id', 
        ]);

        $userLogado = Auth::user();
        $usuarioIdPedido = $request->usuario_id;

        if (!$userLogado->admin && $userLogado->id != $usuarioIdPedido) {
            return response()->json(['message' => 'Você só pode concluir sua própria compra.'], 403);
        }

        $itemFila = \App\Models\Fila::where('usuario_id', $usuarioIdPedido)->first();

        if (!$itemFila) {
            return response()->json(['message' => 'Usuário não está na fila.'], 404);
        }

        $compra = DB::transaction(function () use ($itemFila, $usuarioIdPedido) {
            // 1. Registrar a Compra no Histórico
            $descritivo = [];
            if ($itemFila->cafe > 0) $descritivo[] = "{$itemFila->cafe} Café(s)";
            if ($itemFila->filtro > 0) $descritivo[] = "{$itemFila->filtro} Filtro(s)";
            
            \App\Models\Compras::create([
                'usuario_id' => $usuarioIdPedido,
                'item' => implode(', ', $descritivo),
                'quantidade' => ($itemFila->cafe + $itemFila->filtro),
                'data_compra' => now("America/Sao_Paulo")
            ]);

            // 2. MOVER PARA O FINAL DA FILA
            // Resetamos para o pedido inicial (1 café, 0 filtro)
            // E atualizamos o timestamp para ele ir para o fim da ordenação
            $itemFila->update([
                'cafe' => 1,
                'filtro' => 0,
                'created_at' => now() // A fila geralmente é ordenada por este campo ASC
            ]);

            return $itemFila;
        });

        return response()->json([
            "message" => "Compra concluída! Você voltou para o fim da fila.",
            "data" => $compra
        ], 201);

    } catch (\Exception $e) {
        return response()->json(["message" => "Erro ao efetuar compra.", "error" => $e->getMessage()], 500);
    }
}

public function ultimaCompra()
{
    try {
        $usuarioId = Auth::id();

        $ultimaCompra = \App\Models\Compras::where('usuario_id', $usuarioId)
            ->orderBy('data_compra', 'desc')
            ->first();

        if (!$ultimaCompra) {
            return response()->json(null, 200);
        }

        return response()->json($ultimaCompra, 200);
    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }
}

    public function atualizar(Request $request, $id)
    {
        try {
            $compra = Compras::findOrFail($id);

            // Apenas administradores editam o histórico
            if (!Auth::user() || !Auth::user()->admin) {
                return response()->json([
                    'message' => 'Acesso negado. Apenas administradores podem editar o histórico.'
                ], 403);
            }

            $compra->item = $request->item ?? $compra->item;
            $compra->quantidade = $request->quantidade ?? $compra->quantidade;
            $compra->ultima_alteracao_por = Auth::id();
            $compra->ultima_alteracao_em = now('America/Sao_Paulo');

            $compra->save();

            return response()->json([
                'message' => 'Compra atualizada com sucesso.',
                'data' => $compra
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao atualizar compra.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}