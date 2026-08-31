<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Plataforma extends Model
{
    protected $table = 'tb_plataforma';

    protected $primaryKey = 'id_plataforma';

    public $timestamps = false;

    protected $fillable = [
        'nome',
        'icone',
    ];

    public function usuarios()
    {
        return $this->belongsToMany(
            User::class,
            'tb_usuario_plataforma',
            'id_plataforma',
            'id_usuario'
        )->withPivot('data_adicao');
    }
}
