<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\FilaController;

Route::prefix('usuarios')->group(function () {
    Route::get('', [UsuarioController::class, 'listar']);
    Route::get('filtro', [UsuarioController::class, 'buscarPorEmail']);
    Route::get('{id}', [UsuarioController::class, 'buscarPorId']);
    Route::post('', [UsuarioController::class, 'criar']);
    Route::patch('{id}', [UsuarioController::class, 'atualizar']);
});

Route::prefix('fila')->group(function () {
    Route::get('', [FilaController::class, 'listar']);
    Route::get('{pos}', [FilaController::class, 'buscarPorPosicao']);
});
