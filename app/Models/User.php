<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;

#[Hidden(['senha'])]
class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $table = 'tb_usuario';
    protected $primaryKey = 'id_usuario';
    public $incrementing = true;
    protected $keyType = 'int';
    const CREATED_AT = 'data_criacao';
    const UPDATED_AT = null;

    protected $fillable = [
        'nickname',
        'email',
        'senha',
        'google_id',
        'avatar',
        'bio',
        'status_conta',
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

    public function generos()
    {
        return $this->belongsToMany(
            Genero::class,
            'tb_usuario_genero',
            'id_usuario',
            'id_genero'
        )->withPivot('data_adicao');
    }

    public function jogos()
    {
        return $this->belongsToMany(
            Jogo::class,
            'tb_jogo_usuario',
            'id_usuario',
            'id_jogo'
        )->withPivot('data_adicao');
    }
}
