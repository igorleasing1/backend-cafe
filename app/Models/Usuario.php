<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class Usuario extends Authenticatable implements JWTSubject
{
    protected $table = "usuarios";

    protected $primaryKey = "id";


    protected $fillable = [
        'nome',
        'email',
        'senha',
        'admin',
        'status',
    ];
    
    public function getAuthPassword()
    {
        return $this->senha;
    }

   
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }
}