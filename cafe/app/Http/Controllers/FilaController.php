<?php

namespace App\Http\Controllers;

use App\Models\Fila;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class FilaController extends Controller
{
    /**
     * Retorna todos os registros da fila com os usuários associados.
     */
    public function index(): JsonResponse
    {
        try {
            $filas = Fila::query()->with('usuario')->get();

            return response()->json([
                'success' => true,
                'data' => $filas
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Não foi possível carregar a fila.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show(string $posicao): JsonResponse
    {
        try {
            $registro = Fila::with('usuario')
                ->where('posicao', $posicao)
                ->first();

            if (!$registro) {
                return response()->json([
                    'success' => false,
                    'message' => 'Registro não encontrado para esta posição.'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $registro
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao tentar buscar o registro.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
