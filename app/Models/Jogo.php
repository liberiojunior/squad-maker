<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Jogo extends Model
{
    protected $table = 'tb_jogo';

    protected $primaryKey = 'id_jogo';

    public $timestamps = false;

    protected $fillable = [
        'steam_app_id',
        'nome',
        'capa',
        'dt_lancamento',
        'qtd_jogadores',
        'descricao',
    ];

    protected $casts = [
        'dt_lancamento' => 'date',
    ];

    public function generos()
    {
        return $this->belongsToMany(
            Genero::class,
            'tb_jogo_genero',
            'id_jogo',
            'id_genero'
        );
    }

    public function usuarios()
    {
        return $this->belongsToMany(
            User::class,
            'tb_jogo_usuario',
            'id_jogo',
            'id_usuario'
        )->withPivot('data_adicao');
    }
}
