<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Fila extends Model
{
    protected $table = "fila";

    protected $primaryKey = "id";

    public $timestamps = false;

    public function usuario():BelongsTo{
        return $this->belongsTo(Fila::class);
    }
}