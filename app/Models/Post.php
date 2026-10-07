<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    protected $table = 'tb_post';
    protected $primaryKey = 'id_post';
    public $timestamps = false;

    protected $fillable = [
        'foto',
        'data_publicacao',
        'fixado_em',
        'status_post',
        'descricao',
        'id_usuario',
    ];

    protected $casts = [
        'data_publicacao' => 'datetime',
        'fixado_em' => 'datetime',
    ];

    public function usuario()
    {
        return $this->belongsTo(User::class, 'id_usuario', 'id_usuario');
    }

    public function reacoes()
    {
        return $this->belongsToMany(
            User::class,
            'tb_post_reacao',
            'id_post',
            'id_usuario'
        )->withPivot('data_reacao');
    }
}
