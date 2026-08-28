<?php

namespace App\Http\Controllers;

use App\Models\Jogo;
use App\Services\SteamService;
use Illuminate\Http\Request;
use App\Models\Genero;

class AdminJogoController extends Controller
{
    public function index(Request $request)
    {
        $query = Jogo::with('generos');

        if ($request->filled('q')) {
            $query->where(
                'nome',
                'like',
                '%' . $request->q . '%'
            );
        }

        if ($request->origem === 'steam') {
            $query->whereNotNull('steam_app_id');
        }

        if ($request->origem === 'manual') {
            $query->whereNull('steam_app_id');
        }

        switch ($request->ordem) {
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

        return view('admin.jogos', [
            'jogos' => $jogos,
        ]);
    }

    public function storeManual(Request $request)
    {
        $data = $request->validate([
            'nome' => ['required', 'string', 'max:150'],
            'capa' => [
                'required',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:5120',
            ],
            'dt_lancamento' => ['required', 'date'],
            'descricao' => ['nullable', 'string'],
        ], [
            'nome.required' => 'Informe o nome do jogo.',
            'capa.required' => 'Escolha uma capa.',
            'capa.image' => 'O arquivo selecionado deve ser uma imagem.',
            'dt_lancamento.required' => 'Informe a data de lançamento.',
        ]);

        $file = $request->file('capa');

        $fileName = 'jogo_' . time()
            . '.' . $file->getClientOriginalExtension();

        $file->move(
            public_path('uploads/jogos'),
            $fileName
        );

        Jogo::create([
            'steam_app_id' => null,
            'nome' => $data['nome'],
            'capa' => '/uploads/jogos/' . $fileName,
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
        Request $request,
        SteamService $steam
    ) {
        $data = $request->validate([
            'steam_app_id' => [
                'required',
                'integer',
                'unique:tb_jogo,steam_app_id',
            ],
        ], [
            'steam_app_id.required' => 'Informe o AppID da Steam.',
            'steam_app_id.integer' => 'O AppID deve ser um número.',
            'steam_app_id.unique' => 'Este jogo já foi cadastrado.',
        ]);

        $appId = (int) $data['steam_app_id'];

        $detalhes = $steam->buscarDetalhesJogo($appId);

        if (! $detalhes) {
            return back()->withErrors([
                'steam_app_id' => 'Não foi possível encontrar este jogo na Steam.',
            ]);
        }

        if (($detalhes['type'] ?? null) !== 'game') {
            return back()->withErrors([
                'steam_app_id' => 'O AppID informado não pertence a um jogo.',
            ]);
        }

        // dd($detalhes['release_date'] ?? null);
        $dataLancamento = $this->dataLancamentoSteam(
            $detalhes['release_date']['date'] ?? null
        );

        if (! $dataLancamento) {
            return back()->withErrors([
                'steam_app_id' => 'Não foi possível identificar a data de lançamento deste jogo.',
            ]);
        }

        $jogo = Jogo::create([
            'steam_app_id' => $appId,
            'nome' => $detalhes['name'],
            'capa' => $detalhes['header_image'],
            'dt_lancamento' => $dataLancamento,
            'qtd_jogadores' => null,
            'descricao' => $detalhes['short_description'] ?? null,
        ]);

        $generosIds = [];

        foreach ($detalhes['genres'] ?? [] as $generoSteam) {

            $nome = $generoSteam['description'] ?? null;

            if (! $nome) {
                continue;
            }

            $genero = Genero::firstOrCreate([
                'genero' => $nome,
            ]);

            $generosIds[] = $genero->id_genero;
        }

        $jogo->generos()->sync(array_unique($generosIds));

        return back()->with([
            'success' => 'Jogo importado da Steam com sucesso.',
            'open_form' => 'steam',
        ]);
    }

    private function dataLancamentoSteam(?string $data): ?string
    {
        if (! $data) {
            return null;
        }

        $data = mb_strtolower(trim($data));

        $data = str_replace(
            ['/', ',', '.', ' de '],
            [' ', ' ', '', ' '],
            $data
        );

        $meses = [
            'jan' => '01',
            'fev' => '02',
            'mar' => '03',
            'abr' => '04',
            'mai' => '05',
            'jun' => '06',
            'jul' => '07',
            'ago' => '08',
            'set' => '09',
            'out' => '10',
            'nov' => '11',
            'dez' => '12',

            'jan' => '01',
            'feb' => '02',
            'mar' => '03',
            'apr' => '04',
            'may' => '05',
            'jun' => '06',
            'jul' => '07',
            'aug' => '08',
            'sep' => '09',
            'oct' => '10',
            'nov' => '11',
            'dec' => '12',
        ];

        foreach ($meses as $mes => $numero) {
            $data = preg_replace(
                '/\b' . preg_quote($mes, '/') . '\b/u',
                $numero,
                $data
            );
        }

        $partes = preg_split('/\s+/', trim($data));

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

        if (! checkdate((int) $mes, (int) $dia, (int) $ano)) {
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
