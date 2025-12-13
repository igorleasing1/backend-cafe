<?php

namespace App\Http\Controllers;

use App\Http\Requests\ComprasRequest;
use App\Models\Compra;
use App\Models\Compras;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Facades\JWTAuth;

class ComprasController extends Controller
{
    public function listar(Request $request)
    {
       try {
            $user = JWTAuth::parseToken()->authenticate();

            if (!$user) {
                return response()->json(['error' => 'Usuário não autenticado.'], 401);
            }

            $query = Compras::with(['usuario', 'alteradoPor']);

            $isAdmin = $user->admin ?? false; // Mantemos o isAdmin para outros filtros

            // REMOVIDO:
            // if (!$isAdmin) {
            //     $query->where('usuario_id', $user->id);
            // } else { 
            //     if ($request->filled('usuario_id')) {
            //         $query->where('usuario_id', $request->usuario_id);
            //     }
            // }

            // LÓGICA ATUALIZADA: Todos podem ver todas as compras, mas o filtro por ID de usuário é aplicado SE solicitado
            if ($request->filled('usuario_id')) {
                // Filtro explícito (usado pela função fetchMinhasCompras do front)
                $query->where('usuario_id', $request->usuario_id);
            }

            // Filtros gerais
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

            if ($compras->isEmpty() && !$isAdmin) {
                return response()->json(['message' => 'Nenhuma compra encontrada para você.'], 200);
            }
            
            return response()->json([
                'compras' => $compras
            ], 200);

        } catch (\Tymon\JWTAuth\Exceptions\JWTException $e) {
            return response()->json(['error' => 'Token de autenticação inválido ou expirado.'], 401);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao listar as compras.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function comprar(ComprasRequest $request)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
            if (!$user) {
                return response()->json(['message' => 'Você precisa estar logado para comprar.'], 401);
            }

            $validacao = $request->validated();
            
            $compra = new Compras();
            
            $compra->usuario_id = $user->id; 
            
            $compra->data_compra = now("America/Sao_Paulo");
            $compra->item = $validacao["item"];
            $compra->quantidade = $validacao["quantidade"];

            $compra->save();
            
            return response()->json(['message' => 'Compra efetuada com sucesso!', 'data' => $compra], 201);

        } catch (\Exception $e) {
            return response()->json([
                "message" => "Erro ao efetuar compra.",
                "error" => $e->getMessage()
            ], 500);
        }
    }

    public function listarPorId($id)
    {
        try {
            // 1. Autenticação JWT
            $user = JWTAuth::parseToken()->authenticate();

            if (!$user) {
                return response()->json(['error' => 'Usuário não autenticado.'], 401);
            }
            
            // 2. Busca a compra com seus relacionamentos (usuario e alteradoPor)
            $compra = Compras::with(['usuario', 'alteradoPor'])->find($id);

            if (!$compra) {
                return response()->json(['message' => 'Compra não encontrada.'], 404);
            }

            // 3. Regra de Autorização: Apenas o comprador ou um administrador pode ver
            $isAdmin = $user->admin ?? false;
            
            if (!$isAdmin && $compra->usuario_id !== $user->id) {
                // Se não for Admin E o ID do usuário não for o mesmo ID do comprador
                return response()->json(['error' => 'Você não tem permissão para visualizar esta compra.'], 403);
            }

            // 4. Retorna a compra
            return response()->json([
                'compra' => $compra
            ], 200);

        } catch (\Tymon\JWTAuth\Exceptions\JWTException $e) {
            return response()->json(['error' => 'Token de autenticação inválido ou expirado.'], 401);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao buscar a compra.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    public function atualizar(Request $request, $id)
    {
        try {
            $compra = Compras::findOrFail($id);

            if (!Auth::check() || !(Auth::user()->admin ?? false)) {
                 return response()->json([
                    'message' => 'Acesso negado. Apenas administradores podem editar compras.'
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