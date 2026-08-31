<?php

namespace App\Http\Controllers;

use App\Models\Genero;
use App\Models\Plataforma;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Validation\Rule;
use App\Models\Jogo;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class AdminCatalogoController extends Controller
{
    public function index()
    {
        $plataformas = Plataforma::orderBy('nome')->get();

        $generos = Genero::orderBy('genero')->get();

        return view('admin.catalogo', [
            'plataformas' => $plataformas,
            'generos' => $generos,
        ]);
    }

    public function storePlataforma(Request $request)
    {
        $data = $request->validate([
            'nome' => [
                'required',
                'string',
                'max:90',
                'unique:tb_plataforma,nome',
            ],

            'icone' => [
                'required',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:5120',
            ],
        ]);

        Plataforma::create([
            'nome' => $data['nome'],
            'icone' => $this->salvarIcone(
                $request->file('icone')
            ),
        ]);

        return back()->with(
            'success',
            'Plataforma cadastrada.'
        );
    }

    public function updatePlataforma(
        Request $request,
        Plataforma $plataforma
    ) {
        $data = $request->validate([
            'nome' => [
                'required',
                'string',
                'max:90',

                Rule::unique(
                    'tb_plataforma',
                    'nome'
                )->ignore(
                    $plataforma->id_plataforma,
                    'id_plataforma'
                ),
            ],

            'icone' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:5120',
            ],
        ]);

        $plataforma->nome = $data['nome'];

        if ($request->hasFile('icone')) {

            $iconeAnterior = $plataforma->icone;

            $plataforma->icone = $this->salvarIcone(
                $request->file('icone')
            );

            $this->excluirIcone($iconeAnterior);
        }

        $plataforma->save();

        return back()->with(
            'success',
            'Plataforma atualizada.'
        );
    }

    public function destroyPlataforma(
        Plataforma $plataforma
    ) {
        $icone = $plataforma->icone;

        $plataforma->usuarios()->detach();

        $plataforma->delete();

        $this->excluirIcone($icone);

        return back()->with(
            'success',
            'Plataforma excluída.'
        );
    }

    public function storeGeneros(Request $request)
    {
        $data = $request->validate([
            'generos' => [
                'required',
                'string',
            ],
        ]);

        $nomes = explode(
            ';',
            $data['generos']
        );

        $quantidade = 0;

        foreach ($nomes as $nome) {

            $nome = trim($nome);

            if ($nome === '') {
                continue;
            }

            Genero::firstOrCreate([
                'genero' => $nome,
            ]);

            $quantidade++;
        }

        return back()->with(
            'success',
            $quantidade . ' gênero(s) processado(s).'
        );
    }

    public function updateGenero(
        Request $request,
        Genero $genero
    ) {
        $data = $request->validate([
            'genero' => [
                'required',
                'string',
                'max:90',

                Rule::unique(
                    'tb_genero',
                    'genero'
                )->ignore(
                    $genero->id_genero,
                    'id_genero'
                ),
            ],
        ]);

        $genero->genero = $data['genero'];

        $genero->save();

        return back()->with(
            'success',
            'Gênero atualizado.'
        );
    }

    public function destroyGenero(Genero $genero)
    {
        $idGenero = $genero->id_genero;

        DB::transaction(function () use ($genero, $idGenero) {

            $usuarios = User::whereHas(
                'generos',
                function ($query) use ($idGenero) {
                    $query->where(
                        'tb_genero.id_genero',
                        $idGenero
                    );
                }
            )->get();

            foreach ($usuarios as $user) {
                $user->generos()->detach($idGenero);
            }


            $jogos = Jogo::whereHas(
                'generos',
                function ($query) use ($idGenero) {
                    $query->where(
                        'tb_genero.id_genero',
                        $idGenero
                    );
                }
            )->get();

            foreach ($jogos as $jogo) {
                $jogo->generos()->detach($idGenero);
            }


            $genero->delete();
        });

        return back()->with(
            'success',
            'Gênero excluído.'
        );
    }

    private function salvarIcone(
        UploadedFile $file
    ): string {
        $nome =
            'plataforma_'
            . uniqid()
            . '.'
            . $file->getClientOriginalExtension();

        $file->move(
            public_path('uploads/plataformas'),
            $nome
        );

        return '/uploads/plataformas/' . $nome;
    }

    private function excluirIcone(
        ?string $icone
    ): void {
        if (
            ! $icone
            || ! str_starts_with(
                $icone,
                '/uploads/plataformas/'
            )
        ) {
            return;
        }

        File::delete(
            public_path(
                ltrim($icone, '/')
            )
        );
    }
}
