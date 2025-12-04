<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\FilaController;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\ComprasController;


Route::prefix('/usuarios')->group(function () { 
Route::post('/cadastro', [UsuarioController::class, 'criar']);
    Route::post('/login', [UsuarioController::class, 'login']); 
});


Route::prefix('/usuarios')->group(function () {
            // Rota `/usuarios/me` adicionada:
       Route::get('/me', [UsuarioController::class, 'me']); // <--- ADICIONE ESTA LINHA
       Route::get('', [UsuarioController::class, 'listar']);
       Route::get('/filtro', [UsuarioController::class, 'buscarPorEmail']);
       Route::get('/{id}', [UsuarioController::class, 'buscarPorId']);
    Route::patch('/{id}', [UsuarioController::class, 'atualizar']);
});

   
    Route::prefix('/fila')->group(function () {
        Route::get('', [FilaController::class, 'listar']);
        Route::get('/{pos}', [FilaController::class, 'buscarPorPosicao']);
        Route::post('/entrar', [FilaController::class, 'entrarNaFila']);
        Route::delete('/sair/{usuario_id}', [FilaController::class, 'sairDaFila']);
    });

 
    Route::prefix('/compras')->group(function () {
        Route::get('', [ComprasController::class, 'listar']);          
        Route::post('', [ComprasController::class, 'comprar']);       
        Route::patch('/{id}', [ComprasController::class, 'atualizar']); 
    });
});