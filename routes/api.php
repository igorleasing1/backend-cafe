<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\FilaController;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\ComprasController;

// ROTAS PÚBLICAS
Route::post('/usuarios/cadastro', [UsuarioController::class, 'criar']);
Route::post('/usuarios/login', [UsuarioController::class, 'login']);

// ROTAS PROTEGIDAS (Exigem Token JWT)
Route::middleware('auth:api')->group(function () {
    
    // Usuários
    Route::prefix('/usuarios')->group(function () {
Route::get('me', [UsuarioController::class, 'me']);
    Route::get('gerenciar', [UsuarioController::class, 'listarGerenciamento']); 
    Route::get('/', [UsuarioController::class, 'listar']); 
    Route::get('filtro', [UsuarioController::class, 'buscarPorEmail']);
    Route::get('{id}', [UsuarioController::class, 'buscarPorId']);
    Route::patch('{id}', [UsuarioController::class, 'atualizar']);
    Route::delete('{id}', [UsuarioController::class, 'excluir']);
   
    });

    // Fila
    Route::prefix('/fila')->group(function () {
        Route::get('', [FilaController::class, 'listar']);
        Route::get('/{pos}', [FilaController::class, 'buscarPorPosicao']);
        Route::post('/entrar', [FilaController::class, 'entrarNaFila']);
        Route::delete('/sair/{usuario_id}', [FilaController::class, 'sairDaFila']);
        Route::patch('/atualizar/{usuario_id}', [FilaController::class, 'atualizarItem']);
        Route::patch('/remover-item/{usuario_id}', [FilaController::class, 'removerItem']);
    });

    // Compras
    Route::prefix('/compras')->group(function () {
        Route::get('', [ComprasController::class, 'listar']);
        Route::post('', [ComprasController::class, 'comprar']);
        Route::patch('/{id}', [ComprasController::class, 'atualizar']);
        Route::get('/ultima', [App\Http\Controllers\ComprasController::class, 'ultimaCompra']);
    });
});