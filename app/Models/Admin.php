<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class Admin extends Authenticatable
{
    protected $table = 'tb_administrador';

    protected $primaryKey = 'id_administrador';

    public $timestamps = false;

    protected $fillable = [
        'email',
        'senha',
        'nome',
        'data_criacao',
    ];

    protected $hidden = [
        'senha',
    ];

    protected $casts = [
        'data_criacao' => 'datetime',
    ];

    public function getAuthPassword()
    {
        return $this->senha;
    }
}
