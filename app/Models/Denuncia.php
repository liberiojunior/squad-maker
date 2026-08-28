<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Denuncia extends Model
{
    protected $table = 'tb_denuncia';

    protected $primaryKey = 'id_denuncia';

    public $timestamps = false;

    public function denunciante()
    {
        return $this->belongsTo(
            User::class,
            'id_denunciante',
            'id_usuario'
        );
    }

    public function denunciado()
    {
        return $this->belongsTo(
            User::class,
            'id_denunciado',
            'id_usuario'
        );
    }
}
