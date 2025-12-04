<?php

namespace App\Http\Controllers;

use App\Http\Requests\ComprasRequest;
use App\Models\Compras;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;


class ComprasController extends Controller
{
    public function listar(Request $request)
{
    try {
        
        $query = Compras::with(['usuario', 'alteradoPor']);

       
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
        return response()->json([
            'message' => 'Erro ao listar as compras.',
            'error' => $e->getMessage()
        ], 500);
    }
}

    public function comprar(ComprasRequest $request){
        try{
            $validacao = $request->all();
            
            $compra = new Compras();
            $compra->usuario_id = $validacao["usuario_id"];
            $compra->data_compra = now("America/Sao_Paulo");
            $compra->item = $validacao["item"];
            $compra->quantidade = $validacao["quantidade"];

            $compra->save();


        }catch(\Exception $e){
            return response()->json([
                "message" => "Erro ao efetuar compra.",
                "error" => $e->getMessage()
            ], 500);
        }
    }
    public function atualizar(Request $request, $id)
{
    try {
        $compra = Compras::findOrFail($id);

    
        if (!Auth::user() || !Auth::user()->admin) {
    return response()->json([
        'message' => 'Acesso negado. Apenas administradores podem editar compras.'
    ], 403);
}
      
        $compra->item = $request->item ?? $compra->item;
        $compra->quantidade = $request->quantidade ?? $compra->quantidade;

        
       $compra->ultima_alteracao_por = Auth::check() ? Auth::id() : null;
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