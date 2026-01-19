<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Compras extends Model
{
    protected $table = 'compra'; 

    protected $fillable = [
        'usuario_id',
        'item',
        'quantidade',
        'data_compra',
        'ultima_alteracao_por',
        'ultima_alteracao_em'
    ];

    // ESTE É O MÉTODO QUE ESTÁ FALTANDO:
    public function usuario(): BelongsTo
{
   
    return $this->belongsTo(\App\Models\Usuario::class, 'usuario_id', 'id');
}

    // Se você usa o 'alteradoPor' no listar, adicione este também:
    public function alteradoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ultima_alteracao_por');
    }
}