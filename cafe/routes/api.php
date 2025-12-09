<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\FilaController;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\ComprasController;


// GRUPO DE ROTAS DE USUÁRIOS (CADASTRO E LOGIN)
Route::prefix('/usuarios')->group(function () { 
    Route::post('/cadastro', [UsuarioController::class, 'criar']);
    Route::post('/login', [UsuarioController::class, 'login']); 
    // Por clareza, você pode juntar os dois grupos de 'usuarios' em um só
    // ou manter separados como fez, mas sem chaves extras.

    // ROTAS AUTENTICADAS (talvez)
    Route::get('/me', [UsuarioController::class, 'me']);
    Route::get('', [UsuarioController::class, 'listar']);
    Route::get('/filtro', [UsuarioController::class, 'buscarPorEmail']);
    Route::get('/{id}', [UsuarioController::class, 'buscarPorId']);
    Route::patch('/{id}', [UsuarioController::class, 'atualizar']);
}); // <--- AQUI fecha todo o bloco de /usuarios


// GRUPO DE ROTAS DE FILA
Route::prefix('/fila')->group(function () {
    Route::get('', [FilaController::class, 'listar']);
    Route::get('/{pos}', [FilaController::class, 'buscarPorPosicao']);
    Route::post('/entrar', [FilaController::class, 'entrarNaFila']);
    Route::delete('/sair/{usuario_id}', [FilaController::class, 'sairDaFila']);
});


// GRUPO DE ROTAS DE COMPRAS
Route::prefix('/compras')->group(function () {
    Route::get('', [ComprasController::class, 'listar']);
    Route::post('', [ComprasController::class, 'comprar']);
    Route::patch('/{id}', [ComprasController::class, 'atualizar']);
});