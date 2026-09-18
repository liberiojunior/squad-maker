<?php

namespace App\Http\Controllers;

use App\Models\Genero;
use App\Models\Jogo;
use App\Services\SteamService;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class AdminJogoController extends Controller
{
    public function index(Request $request)
    {
        $query = Jogo::with('generos');

        if ($request->filled('q')) {
            $query->where(
                'nome',
                'like',
                '%' . trim($request->input('q')) . '%'
            );
        }

        if ($request->input('origem') === 'steam') {
            $query->whereNotNull('steam_app_id');
        }

        if ($request->input('origem') === 'manual') {
            $query->whereNull('steam_app_id');
        }

        switch ($request->input('ordem')) {
            case 'za':
                $query->orderBy('nome', 'desc');
                break;

            case 'recentes':
                $query->orderBy('id_jogo', 'desc');
                break;

            default:
                $query->orderBy('nome');
                break;
        }

        $jogos = $query
            ->paginate(12)
            ->withQueryString();

        $generosDisponiveis = Genero::orderBy('genero')->get();

        return view('admin.jogos', [
            'jogos' => $jogos,
            'generosDisponiveis' => $generosDisponiveis,
        ]);
    }

    public function storeManual(Request $request)
    {
        $data = $request->validate([
            'nome' => [
                'required',
                'string',
                'max:150',
            ],

            'capa' => [
                'required',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:5120',
            ],

            'dt_lancamento' => [
                'required',
                'date',
            ],

            'descricao' => [
                'nullable',
                'string',
            ],
        ], [
            'nome.required' =>
                'Informe o nome do jogo.',

            'capa.required' =>
                'Escolha uma capa.',

            'capa.image' =>
                'O arquivo selecionado deve ser uma imagem.',

            'dt_lancamento.required' =>
                'Informe a data de lançamento.',
        ]);

        $capa = $this->salvarCapaLocal(
            $request->file('capa')
        );

        Jogo::create([
            'steam_app_id' => null,
            'nome' => $data['nome'],
            'capa' => $capa,
            'dt_lancamento' => $data['dt_lancamento'],
            'qtd_jogadores' => null,
            'descricao' => $data['descricao'] ?? null,
        ]);

        return back()->with([
            'success' => 'Jogo cadastrado com sucesso.',
            'open_form' => 'manual',
        ]);
    }

    public function storeSteam(
        Request      $request,
        SteamService $steam
    )
    {
        $data = $request->validate([
            'steam_app_ids' => [
                'required',
                'string',
                'max:500',
            ],
        ], [
            'steam_app_ids.required' =>
                'Informe pelo menos um AppID da Steam.',
        ]);

        $entradas = preg_split(
            '/\s*[;,]\s*/',
            trim($data['steam_app_ids']),
            -1,
            PREG_SPLIT_NO_EMPTY
        );

        $appIds = [];
        $invalidos = [];

        foreach ($entradas as $entrada) {
            $entrada = trim($entrada);

            if (!ctype_digit($entrada)) {
                $invalidos[] = $entrada;
                continue;
            }

            $appIds[] = (int)$entrada;
        }

        if (!empty($invalidos)) {
            return back()
                ->withErrors([
                    'steam_app_ids' =>
                        'Foram encontrados AppIDs inválidos: '
                        . implode(', ', $invalidos),
                ])
                ->withInput()
                ->with('open_form', 'steam');
        }

        $appIds = array_values(
            array_unique($appIds)
        );

        if (count($appIds) > 20) {
            return back()
                ->withErrors([
                    'steam_app_ids' =>
                        'Importe no máximo 20 jogos por vez.',
                ])
                ->withInput()
                ->with('open_form', 'steam');
        }

        $importados = 0;
        $falhas = [];

        foreach ($appIds as $appId) {
            $resultado = $this->importarJogoSteam(
                $appId,
                $steam
            );

            if ($resultado['success']) {
                $importados++;
                continue;
            }

            $falhas[] =
                'AppID '
                . $appId
                . ': '
                . $resultado['message'];
        }

        $response = back()
            ->with('open_form', 'steam');

        if ($importados > 0) {
            $response->with(
                'success',
                $importados
                . ' jogo(s) importado(s) da Steam com sucesso.'
            );
        }

        if (!empty($falhas)) {
            $response->withErrors([
                'steam_app_ids' =>
                    implode(' | ', $falhas),
            ]);
        }

        return $response;
    }

    public function update(
        Request $request,
        Jogo $jogo
    ) {
        $data = $request->validate([
            'nome' => [
                'required',
                'string',
                'max:150',
            ],

            'dt_lancamento' => [
                'required',
                'date',
            ],

            'descricao' => [
                'nullable',
                'string',
            ],

            'capa' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:5120',
            ],

            'generos' => [
                'nullable',
                'array',
            ],

            'generos.*' => [
                'integer',
                'exists:tb_genero,id_genero',
            ],
        ]);

        $jogo->nome = $data['nome'];
        $jogo->dt_lancamento = $data['dt_lancamento'];
        $jogo->descricao = $data['descricao'] ?? null;

        if ($request->hasFile('capa')) {
            $capaAnterior = $jogo->capa;

            $jogo->capa = $this->salvarCapaLocal(
                $request->file('capa')
            );

            $this->excluirCapaLocal($capaAnterior);
        }

        $jogo->save();

        $jogo->generos()->sync(
            $data['generos'] ?? []
        );

        return back()->with(
            'success',
            'Jogo atualizado.'
        );
    }

    public function destroy(Jogo $jogo)
    {
        $capa = $jogo->capa;

        DB::transaction(function () use ($jogo) {
            $jogo->generos()->detach();
            $jogo->usuarios()->detach();

            $jogo->delete();
        });

        $this->excluirCapaLocal($capa);

        return back()->with(
            'success',
            'Jogo excluído.'
        );
    }

    private function salvarCapaLocal(
        UploadedFile $file
    ): string {
        $fileName =
            'jogo_'
            . uniqid()
            . '.'
            . $file->getClientOriginalExtension();

        $file->move(
            public_path('uploads/jogos'),
            $fileName
        );

        return '/uploads/jogos/' . $fileName;
    }

    private function excluirCapaLocal(
        ?string $capa
    ): void {
        if (
            ! $capa
            || ! str_starts_with(
                $capa,
                '/uploads/jogos/'
            )
        ) {
            return;
        }

        File::delete(
            public_path(
                ltrim($capa, '/')
            )
        );
    }

    private function importarJogoSteam(
        int          $appId,
        SteamService $steam
    ): array
    {
        $jogoExistente = Jogo::where(
            'steam_app_id',
            $appId
        )->exists();

        if ($jogoExistente) {
            return [
                'success' => false,
                'message' => 'este jogo já está cadastrado.',
            ];
        }

        $detalhes = $steam->buscarDetalhesJogo(
            $appId
        );

        if (!$detalhes) {
            return [
                'success' => false,
                'message' =>
                    'não foi possível obter os dados na Steam.',
            ];
        }

        if (($detalhes['type'] ?? null) !== 'game') {
            return [
                'success' => false,
                'message' =>
                    'o AppID não pertence a um jogo.',
            ];
        }

        $dataLancamento =
            $this->dataLancamentoSteam(
                $detalhes['release_date']['date']
                ?? null
            );

        if (!$dataLancamento) {
            return [
                'success' => false,
                'message' =>
                    'não foi possível identificar a data de lançamento.',
            ];
        }

        $jogo = DB::transaction(
            function () use (
                $appId,
                $detalhes,
                $dataLancamento
            ) {
                $jogo = Jogo::create([
                    'steam_app_id' => $appId,
                    'nome' => $detalhes['name'],
                    'capa' => $detalhes['header_image'],
                    'dt_lancamento' => $dataLancamento,
                    'qtd_jogadores' => null,
                    'descricao' =>
                        $detalhes['short_description']
                        ?? null,
                ]);

                $generosIds = [];

                foreach (
                    $detalhes['genres'] ?? []
                    as $generoSteam
                ) {
                    $nome =
                        $generoSteam['description']
                        ?? null;

                    if (!$nome) {
                        continue;
                    }

                    $genero = Genero::firstOrCreate([
                        'genero' => $nome,
                    ]);

                    $generosIds[] =
                        $genero->id_genero;
                }

                $jogo->generos()->sync(
                    array_unique($generosIds)
                );

                return $jogo;
            }
        );

        return [
            'success' => true,
            'message' => $jogo->nome,
        ];
    }

    private function dataLancamentoSteam(
        ?string $data
    ): ?string {
        if (! $data) {
            return null;
        }

        $data = mb_strtolower(
            trim($data)
        );

        $data = str_replace(
            ['/', ',', '.', ' de '],
            [' ', ' ', '', ' '],
            $data
        );

        $meses = [
            'jan' => '01',

            'feb' => '02',
            'fev' => '02',

            'mar' => '03',

            'apr' => '04',
            'abr' => '04',

            'may' => '05',
            'mai' => '05',

            'jun' => '06',
            'jul' => '07',

            'aug' => '08',
            'ago' => '08',

            'sep' => '09',
            'set' => '09',

            'oct' => '10',
            'out' => '10',

            'nov' => '11',

            'dec' => '12',
            'dez' => '12',
        ];

        foreach ($meses as $mes => $numero) {
            $data = preg_replace(
                '/\b'
                . preg_quote($mes, '/')
                . '\b/u',
                $numero,
                $data
            );
        }

        $partes = preg_split(
            '/\s+/',
            trim($data)
        );

        if (count($partes) !== 3) {
            return null;
        }

        if (strlen($partes[0]) === 4) {
            $ano = $partes[0];
            $mes = $partes[1];
            $dia = $partes[2];
        } else {
            $dia = $partes[0];
            $mes = $partes[1];
            $ano = $partes[2];
        }

        if (
            ! is_numeric($dia)
            || ! is_numeric($mes)
            || ! is_numeric($ano)
        ) {
            return null;
        }

        if (
            ! checkdate(
                (int) $mes,
                (int) $dia,
                (int) $ano
            )
        ) {
            return null;
        }

        return sprintf(
            '%04d-%02d-%02d',
            $ano,
            $mes,
            $dia
        );
    }
}
