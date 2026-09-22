<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ModoJogo extends Model
{
    protected $table = 'tb_modo_jogo';

    protected $primaryKey = 'id_modo_jogo';

    public $timestamps = false;

    protected $fillable = [
        'nome',
        'slug',
        'descricao',
    ];

    public function jogos()
    {
        return $this->belongsToMany(
            Jogo::class,
            'tb_jogo_modo',
            'id_modo_jogo',
            'id_jogo'
        );
    }
}
