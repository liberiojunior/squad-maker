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

    // Nome da tabela existente no seu banco
    protected $table = 'tb_usuario';

    // Chave primária da sua tabela
    protected $primaryKey = 'id_usuario';

    // Se a chave primária é auto-increment (sim)
    public $incrementing = true;

    // Tipo da chave primária
    protected $keyType = 'int';

    // Se sua tabela usa timestamps padrão do Laravel (created_at/updated_at)
    // No seu caso existe data_criacao; vamos mapear CREATED_AT para data_criacao
    const CREATED_AT = 'data_criacao';
    const UPDATED_AT = null; // não existe updated_at na DDL que você mostrou

    // Campos que podem ser preenchidos via create/update
    protected $fillable = [
        'nickname', // nome do usuário na sua tabela
        'email',
        'senha',
        'google_id',
        'avatar',
        'bio',
        'status_conta',
        'data_criacao',
    ];

    // Campos ocultos em arrays/JSON
    protected $hidden = [
        'senha',
    ];

    // Casts (conversões)
    protected $casts = [
        'data_criacao' => 'datetime',
    ];

    /**
     * Observação:
     * - Laravel espera 'password' por convenção para autenticação.
     * - Como sua coluna se chama 'senha', vamos adaptar o getter/setter abaixo
     *   para que Auth::attempt e Auth::login funcionem corretamente.
     */

    // Retorna a senha para o sistema de autenticação do Laravel
    public function getAuthPassword()
    {
        return $this->senha;
    }
}
