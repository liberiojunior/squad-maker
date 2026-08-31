<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

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

    public function excluirConta(): void
    {
        $this->nickname = 'Usuário excluído #'.$this->id_usuario;

        $this->email =
            'excluido_'
            .$this->id_usuario
            .'_'
            .time()
            .'@squadmaker.local';

        $this->senha = Hash::make(Str::random(40));

        $this->google_id = null;
        $this->avatar = null;
        $this->bio = null;
        $this->email_verified_at = null;
        $this->remember_token = null;

        $this->status_conta = 'excluido';
        $this->data_exclusao = now();

        $this->save();
    }

    public function plataformas()
    {
        return $this->belongsToMany(
            Plataforma::class,
            'tb_usuario_plataforma',
            'id_usuario',
            'id_plataforma'
        )->withPivot('data_adicao');
    }
}
