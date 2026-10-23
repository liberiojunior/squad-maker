<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Noticia extends Model
{
    protected $table = 'tb_noticia';
    protected $primaryKey = 'id_noticia';
    public $timestamps = false;

    protected $fillable = [
        'titulo',
        'descricao',
        'conteudo',
        'imagem',
        'fonte',
        'fonte_url',
        'data_noticia',
        'id_administrador',
    ];

    protected $casts = [
        'data_noticia' => 'datetime',
    ];

    public function administrador()
    {
        return $this->belongsTo(Admin::class, 'id_administrador', 'id_administrador');
    }
}
