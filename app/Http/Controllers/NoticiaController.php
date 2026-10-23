<?php

namespace App\Http\Controllers;

use App\Models\Noticia;

class NoticiaController extends Controller
{
    public function index()
    {
        $noticias = Noticia::query()
            ->with('administrador:id_administrador,nome')
            ->orderByDesc('data_noticia')
            ->orderByDesc('id_noticia')
            ->paginate(8);

        return view('noticias.index', [
            'noticias' => $noticias,
        ]);
    }

    public function show(Noticia $noticia)
    {
        $noticia->load('administrador:id_administrador,nome');

        return view('noticias.show', [
            'noticia' => $noticia,
        ]);
    }
}
