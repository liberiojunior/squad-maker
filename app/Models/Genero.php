<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Genero extends Model
{
    protected $table = 'tb_genero';

    protected $primaryKey = 'id_genero';

    public $timestamps = false;

    protected $fillable = [
        'genero',
    ];
}
