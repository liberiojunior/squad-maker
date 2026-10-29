<?php

namespace App\Http\Controllers;

use App\Models\Banimento;
use App\Models\Denuncia;
use App\Models\Noticia;
use App\Models\User;
use App\Support\CloudinaryMedia;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AdminController extends Controller
{
    public function geral()
    {
        $totalUsuarios = User::where('status_conta', '!=', 'excluido')->count();
        $usuariosAtivos = User::where('status_conta', 'ativo')
            ->whereNotNull('email_verified_at')
            ->whereNotNull('cadastro_jogos_concluido_em')
            ->count();
        $usuariosBanidos = User::where('status_conta', 'banido')->count();
        $denunciasPendentes = Denuncia::where('status_denuncia', 'pendente')->count();

        return view('admin.geral', [
            'totalUsuarios' => $totalUsuarios,
            'usuariosAtivos' => $usuariosAtivos,
            'usuariosBanidos' => $usuariosBanidos,
            'denunciasPendentes' => $denunciasPendentes,
        ]);
    }

    public function dashboard(Request $request)
    {
        $data = $request->validate([
            'periodo' => ['nullable', 'in:7,30,90,todos'],
        ]);

        $periodo = $data['periodo'] ?? '30';

        $inicio = match ($periodo) {
            '7' => now()->subDays(6)->startOfDay(),
            '30' => now()->subDays(29)->startOfDay(),
            '90' => now()->subDays(89)->startOfDay(),
            default => null,
        };

        $novosUsuarios = User::where('status_conta', '!=', 'excluido')
            ->when($inicio, fn($query) => $query->where('data_criacao', '>=', $inicio))
            ->count();

        $mensagensEnviadas = DB::table('tb_mensagem')
            ->when($inicio, fn($query) => $query->where('data_envio', '>=', $inicio))
            ->count();

        $postsPublicados = DB::table('tb_post')
            ->when($inicio, fn($query) => $query->where('data_publicacao', '>=', $inicio))
            ->count();

        $jogosAdicionados = DB::table('tb_jogo_usuario')
            ->when($inicio, fn($query) => $query->where('data_adicao', '>=', $inicio))
            ->count();

        $catalogo = DB::table('tb_jogo')
            ->selectRaw('COUNT(*) as total, COUNT(steam_app_id) as steam')
            ->first();

        $totalJogos = (int) $catalogo->total;
        $totalJogosSteam = (int) $catalogo->steam;
        $totalJogosManuais = $totalJogos - $totalJogosSteam;
        $totalNoticiasPublicadas = Noticia::query()
            ->when($inicio, fn($query) => $query->where('data_noticia', '>=', $inicio))
            ->count();

        if ($periodo === 'todos') {
            $cadastros = User::query()
                ->selectRaw("DATE_FORMAT(data_criacao, '%Y-%m') as periodo, COUNT(*) as total")
                ->where('status_conta', '!=', 'excluido')
                ->groupByRaw("DATE_FORMAT(data_criacao, '%Y-%m')")
                ->orderBy('periodo')
                ->get();

            $cadastrosLabels = $cadastros
                ->map(fn($item) => Carbon::createFromFormat('Y-m', $item->periodo)->format('m/Y'))
                ->values();

            $cadastrosValores = $cadastros
                ->pluck('total')
                ->map(fn($total) => (int)$total)
                ->values();
        } else {
            $cadastros = User::query()
                ->selectRaw('DATE(data_criacao) as periodo, COUNT(*) as total')
                ->where('status_conta', '!=', 'excluido')
                ->where('data_criacao', '>=', $inicio)
                ->groupByRaw('DATE(data_criacao)')
                ->orderBy('periodo')
                ->get()
                ->keyBy('periodo');

            $cadastrosLabels = collect();
            $cadastrosValores = collect();

            foreach (CarbonPeriod::create($inicio, now()->startOfDay()) as $dia) {
                $chave = $dia->format('Y-m-d');
                $cadastrosLabels->push($dia->format('d/m'));
                $cadastrosValores->push((int)($cadastros[$chave]->total ?? 0));
            }
        }

        $jogosPopulares = DB::table('tb_jogo_usuario as jogo_usuario')
            ->join('tb_jogo as jogo', 'jogo.id_jogo', '=', 'jogo_usuario.id_jogo')
            ->when($inicio, fn($query) => $query->where('jogo_usuario.data_adicao', '>=', $inicio))
            ->select('jogo.nome')
            ->selectRaw('COUNT(*) as total')
            ->groupBy('jogo.id_jogo', 'jogo.nome')
            ->orderByDesc('total')
            ->orderBy('jogo.nome')
            ->limit(5)
            ->get();

        $plataformasPopulares = DB::table('tb_usuario_plataforma as usuario_plataforma')
            ->join('tb_plataforma as plataforma', 'plataforma.id_plataforma', '=', 'usuario_plataforma.id_plataforma')
            ->when($inicio, fn($query) => $query->where('usuario_plataforma.data_adicao', '>=', $inicio))
            ->select('plataforma.nome')
            ->selectRaw('COUNT(*) as total')
            ->groupBy('plataforma.id_plataforma', 'plataforma.nome')
            ->orderByDesc('total')
            ->orderBy('plataforma.nome')
            ->limit(5)
            ->get();

        return view('admin.dashboard', [
            'periodo' => $periodo,
            'novosUsuarios' => $novosUsuarios,
            'mensagensEnviadas' => $mensagensEnviadas,
            'postsPublicados' => $postsPublicados,
            'jogosAdicionados' => $jogosAdicionados,
            'totalJogos' => $totalJogos,
            'totalJogosSteam' => $totalJogosSteam,
            'totalJogosManuais' => $totalJogosManuais,
            'totalNoticiasPublicadas' => $totalNoticiasPublicadas,
            'cadastrosLabels' => $cadastrosLabels,
            'cadastrosValores' => $cadastrosValores,
            'jogosPopulares' => $jogosPopulares,
            'plataformasPopulares' => $plataformasPopulares,
        ]);
    }

    public function usuarios(Request $request)
    {
        $data = $request->validate([
            'q' => ['nullable', 'string', 'max:150'],
            'status' => ['nullable', 'in:ativo,pendente,banido'],
            'ordem' => ['nullable', 'in:recentes,antigos,az,za'],
            'data_inicio' => ['nullable', 'date'],
            'data_fim' => ['nullable', 'date', 'after_or_equal:data_inicio'],
        ]);

        $query = User::query()
            ->where('status_conta', '!=', 'excluido');

        $busca = trim($data['q'] ?? '');

        if ($busca !== '') {
            $query->where(function ($query) use ($busca) {
                $query->where('nickname', 'like', '%' . $busca . '%')
                    ->orWhere('email', 'like', '%' . $busca . '%');
            });
        }

        if (($data['status'] ?? null) === 'pendente') {
            $query->where(function ($query) {
                $query->where('status_conta', 'pendente')
                    ->orWhere(function ($query) {
                        $query->where('status_conta', 'ativo')
                            ->where(function ($query) {
                                $query->whereNull('email_verified_at')
                                    ->orWhereNull('cadastro_jogos_concluido_em');
                            });
                    });
            });
        } elseif (($data['status'] ?? null) === 'ativo') {
            $query->where('status_conta', 'ativo')
                ->whereNotNull('email_verified_at')
                ->whereNotNull('cadastro_jogos_concluido_em');
        } elseif (!empty($data['status'])) {
            $query->where('status_conta', $data['status']);
        }

        if (!empty($data['data_inicio'])) {
            $query->whereDate('data_criacao', '>=', $data['data_inicio']);
        }

        if (!empty($data['data_fim'])) {
            $query->whereDate('data_criacao', '<=', $data['data_fim']);
        }

        switch ($data['ordem'] ?? 'recentes') {
            case 'antigos':
                $query->orderBy('data_criacao');
                break;
            case 'az':
                $query->orderBy('nickname');
                break;
            case 'za':
                $query->orderBy('nickname', 'desc');
                break;
            default:
                $query->orderBy('data_criacao', 'desc');
                break;
        }

        $usuarios = $query
            ->paginate(10)
            ->withQueryString();

        return view('admin.usuarios', [
            'usuarios' => $usuarios,
        ]);
    }

    public function noticias()
    {
        $noticias = Noticia::query()
            ->with('administrador:id_administrador,nome')
            ->orderByDesc('data_noticia')
            ->orderByDesc('id_noticia')
            ->paginate(10);

        return view('admin.noticias', [
            'noticias' => $noticias,
        ]);
    }

    public function criarNoticia()
    {
        return view('admin.noticia-form');
    }

    public function salvarNoticia(Request $request)
    {
        $data = $this->validarNoticia($request, true);
        $data['imagem'] = $this->salvarImagemNoticia($request->file('imagem'));
        $data['data_noticia'] = now();
        $data['id_administrador'] = Auth::guard('admin')->id();

        Noticia::create($data);

        return redirect()
            ->route('admin.noticias')
            ->with('success', 'Notícia publicada com sucesso.');
    }

    public function uploadImagemNoticiaConteudo(Request $request)
    {
        $data = $request->validate([
            'imagem' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120', 'dimensions:max_width=3840,max_height=3840'],
        ], [
            'imagem.required' => 'Selecione uma imagem.',
            'imagem.image' => 'O arquivo precisa ser uma imagem válida.',
            'imagem.mimes' => 'Envie uma imagem JPG, PNG ou WebP.',
            'imagem.max' => 'A imagem pode ter no máximo 5 MB.',
            'imagem.dimensions' => 'A imagem enviada é grande demais.',
        ]);

        return response()->json([
            'url' => $this->salvarImagemConteudoNoticia($data['imagem']),
        ]);
    }

    public function editarNoticia(Noticia $noticia)
    {
        return view('admin.noticia-form', [
            'noticia' => $noticia,
        ]);
    }

    public function atualizarNoticia(Request $request, Noticia $noticia)
    {
        $data = $this->validarNoticia($request, false);
        $imagemAnterior = $noticia->imagem;
        $imagensConteudoAnteriores = $this->imagensConteudoNoticia($noticia->conteudo);

        if ($request->hasFile('imagem')) {
            $data['imagem'] = $this->salvarImagemNoticia($request->file('imagem'));
        }

        $noticia->update($data);

        if (isset($data['imagem'])) {
            $this->excluirImagemNoticia($imagemAnterior);
        }

        $imagensMantidas = $this->imagensConteudoNoticia($noticia->conteudo);
        $this->excluirImagensConteudoNoticia(array_diff($imagensConteudoAnteriores, $imagensMantidas));

        return redirect()
            ->route('admin.noticias')
            ->with('success', 'Notícia atualizada com sucesso.');
    }

    public function excluirNoticia(Noticia $noticia)
    {
        $imagem = $noticia->imagem;
        $imagensConteudo = $this->imagensConteudoNoticia($noticia->conteudo);

        $noticia->delete();
        $this->excluirImagemNoticia($imagem);
        $this->excluirImagensConteudoNoticia($imagensConteudo);

        return back()->with('success', 'Notícia excluída com sucesso.');
    }

    private function validarNoticia(Request $request, bool $imagemObrigatoria): array
    {
        $request->merge([
            'conteudo' => $this->sanitizarConteudoNoticia((string)$request->input('conteudo', '')),
        ]);

        $regrasImagem = $imagemObrigatoria
            ? ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120', 'dimensions:max_width=3840,max_height=3840']
            : ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120', 'dimensions:max_width=3840,max_height=3840'];

        $data = $request->validate([
            'titulo' => ['required', 'string', 'max:100'],
            'descricao' => ['required', 'string', 'max:255'],
            'conteudo' => ['required', 'string', 'max:50000'],
            'fonte' => ['required', 'string', 'max:255'],
            'fonte_url' => ['nullable', 'url', 'max:500'],
            'imagem' => $regrasImagem,
        ], [
            'titulo.required' => 'Informe o título da notícia.',
            'descricao.required' => 'Informe um resumo para a notícia.',
            'conteudo.required' => 'Informe o conteúdo da notícia.',
            'fonte.required' => 'Informe a fonte da notícia.',
            'imagem.required' => 'Adicione uma imagem de capa.',
            'imagem.image' => 'A capa precisa ser uma imagem válida.',
            'imagem.mimes' => 'Envie uma capa JPG, PNG ou WebP.',
            'imagem.max' => 'A imagem pode ter no máximo 5 MB.',
            'imagem.dimensions' => 'A imagem enviada é grande demais.',
            'fonte_url.url' => 'Informe uma URL de fonte válida.',
        ]);

        $texto = trim(html_entity_decode(strip_tags($data['conteudo']), ENT_QUOTES | ENT_HTML5, 'UTF-8'));

        if ($texto === '') {
            throw ValidationException::withMessages([
                'conteudo' => 'Escreva o conteúdo da notícia antes de publicar.',
            ]);
        }

        return $data;
    }

    private function sanitizarConteudoNoticia(string $html): string
    {
        $html = trim($html);

        if ($html === '') {
            return '';
        }

        if (!str_contains($html, '<')) {
            $paragrafos = preg_split('/\r\n|\r|\n/', $html);
            $saida = [];

            foreach ($paragrafos as $paragrafo) {
                if (trim($paragrafo) === '') {
                    continue;
                }

                $saida[] = '<p>' . htmlspecialchars($paragrafo, ENT_QUOTES | ENT_HTML5, 'UTF-8') . '</p>';
            }

            return implode('', $saida);
        }

        $html = preg_replace('~<!--.*?-->~s', '', $html) ?? '';
        $html = preg_replace('~<(script|style)[^>]*>.*?</\1>~is', '', $html) ?? '';
        $html = preg_replace_callback(
            '~<iframe\b([^>]*)>.*?</iframe>~is',
            function (array $match) {
                $src = trim($this->atributoHtmlNoticia($match[1] ?? '', 'src'));
                $youtubeId = $this->youtubeIdDoEmbed($src);

                if (!$youtubeId) {
                    return '';
                }

                return '<iframe src="https://www.youtube.com/embed/'
                    . $youtubeId
                    . '"></iframe>';
            },
            $html
        ) ?? '';
        $html = strip_tags(
            $html,
            '<div><p><h2><h3><strong><b><em><i><u><ul><ol><li><a><img><br><blockquote><font><span><iframe>'
        );

        $fontesPermitidas = ['Arial', 'Georgia', 'Trebuchet MS', 'Courier New'];

        $html = preg_replace_callback(
            '~<([a-z0-9]+)(\s[^>]*)?>~i',
            function (array $match) use ($fontesPermitidas) {
                $tag = strtolower($match[1]);
                $atributos = $match[2] ?? '';

                if ($tag === 'br') {
                    return '<br>';
                }

                if ($tag === 'a') {
                    $href = trim($this->atributoHtmlNoticia($atributos, 'href'));

                    if (!preg_match('~^https?://~i', $href)) {
                        return '<a>';
                    }

                    $classe = trim($this->atributoHtmlNoticia($atributos, 'class'));
                    $tamanho = strtolower($this->atributoHtmlNoticia($atributos, 'data-size'));
                    $atributosPermitidos = '';

                    if ($classe === 'news-image-link') {
                        if (!in_array($tamanho, ['small', 'medium', 'large', 'full'], true)) {
                            $tamanho = 'medium';
                        }

                        $atributosPermitidos = ' class="news-image-link" data-size="' . $tamanho . '"';
                    }

                    return '<a href="'
                        . htmlspecialchars($href, ENT_QUOTES | ENT_HTML5, 'UTF-8')
                        . '" target="_blank" rel="noopener noreferrer"'
                        . $atributosPermitidos
                        . '>';
                }

                if ($tag === 'iframe') {
                    $src = trim($this->atributoHtmlNoticia($atributos, 'src'));
                    $youtubeId = $this->youtubeIdDoEmbed($src);

                    if (!$youtubeId) {
                        return '';
                    }

                    return '<iframe class="news-youtube-embed" src="https://www.youtube.com/embed/'
                        . $youtubeId
                        . '" title="Vídeo do YouTube" loading="lazy" referrerpolicy="strict-origin-when-cross-origin" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen>';
                }

                if ($tag === 'div') {
                    $classe = $this->atributoHtmlNoticia($atributos, 'class');

                    if (preg_match('/(^|\s)news-video-embed(\s|$)/', $classe)) {
                        return '<div class="news-video-embed">';
                    }
                }

                if ($tag === 'img') {
                    $src = trim($this->atributoHtmlNoticia($atributos, 'src'));
                    $imagemPermitida = CloudinaryMedia::isManagedUrl($src)
                        || str_starts_with($src, '/uploads/noticias/conteudo/');

                    if (!$imagemPermitida) {
                        return '';
                    }

                    $alt = mb_substr(trim($this->atributoHtmlNoticia($atributos, 'alt')), 0, 180);
                    $tamanho = strtolower($this->atributoHtmlNoticia($atributos, 'data-size'));

                    if (!in_array($tamanho, ['small', 'medium', 'large', 'full'], true)) {
                        $tamanho = 'medium';
                    }

                    return '<img src="'
                        . htmlspecialchars($src, ENT_QUOTES | ENT_HTML5, 'UTF-8')
                        . '" alt="'
                        . htmlspecialchars($alt, ENT_QUOTES | ENT_HTML5, 'UTF-8')
                        . '" data-size="'
                        . $tamanho
                        . '" loading="lazy">';
                }

                if (in_array($tag, ['div', 'p', 'h2', 'h3', 'blockquote'], true)) {
                    $estilo = $this->atributoHtmlNoticia($atributos, 'style');
                    $alinhamento = strtolower($this->atributoHtmlNoticia($atributos, 'align'));

                    if (preg_match('/text-align\s*:\s*(left|center|right|justify)/i', $estilo, $alinhamentoEncontrado)) {
                        $alinhamento = strtolower($alinhamentoEncontrado[1]);
                    }

                    if (in_array($alinhamento, ['left', 'center', 'right', 'justify'], true)) {
                        return '<' . $tag . ' style="text-align: ' . $alinhamento . ';">';
                    }
                }

                if ($tag === 'font') {
                    $tamanho = $this->atributoHtmlNoticia($atributos, 'size');
                    $fonte = $this->atributoHtmlNoticia($atributos, 'face');
                    $permitidos = [];

                    if (in_array($tamanho, ['2', '3', '4', '5'], true)) {
                        $permitidos[] = 'size="' . $tamanho . '"';
                    }

                    if (in_array($fonte, $fontesPermitidas, true)) {
                        $permitidos[] = 'face="' . htmlspecialchars($fonte, ENT_QUOTES | ENT_HTML5, 'UTF-8') . '"';
                    }

                    return '<font' . ($permitidos ? ' ' . implode(' ', $permitidos) : '') . '>';
                }

                if ($tag === 'span') {
                    $estilo = $this->atributoHtmlNoticia($atributos, 'style');

                    if (preg_match('/font-size\s*:\s*(\d{1,2})px/i', $estilo, $tamanhoEncontrado)) {
                        $tamanho = (int)$tamanhoEncontrado[1];

                        if ($tamanho >= 10 && $tamanho <= 48) {
                            return '<span style="font-size: ' . $tamanho . 'px;">';
                        }
                    }
                }

                return '<' . $tag . '>';
            },
            $html
        ) ?? '';

        return trim($html);
    }

    private function youtubeIdDoEmbed(string $url): ?string
    {
        if (!preg_match(
            '~^https://(?:www\.)?(?:youtube-nocookie\.com|youtube\.com)/embed/([A-Za-z0-9_-]{11})(?:[?&/#].*)?$~i',
            $url,
            $match
        )) {
            return null;
        }

        return $match[1];
    }

    private function atributoHtmlNoticia(string $atributos, string $nome): string
    {
        $nome = preg_quote($nome, '~');

        if (!preg_match(
            '~\b' . $nome . '\s*=\s*(?:"([^"]*)"|\'([^\']*)\'|([^\s>]+))~i',
            $atributos,
            $match
        )) {
            return '';
        }

        return html_entity_decode($match[1] ?? $match[2] ?? $match[3] ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    private function salvarImagemNoticia(UploadedFile $imagem): string
    {
        return CloudinaryMedia::upload(
            $imagem,
            'squadmaker/noticias/capas',
            'noticia'
        );
    }

    private function salvarImagemConteudoNoticia(UploadedFile $imagem): string
    {
        return CloudinaryMedia::upload(
            $imagem,
            'squadmaker/noticias/conteudo',
            'conteudo'
        );
    }

    private function imagensConteudoNoticia(?string $conteudo): array
    {
        if (!$conteudo) {
            return [];
        }

        preg_match_all(
            '~<img[^>]+src=["\']([^"\']+)["\']~i',
            $conteudo,
            $matches
        );

        $imagens = array_filter($matches[1] ?? [], function ($imagem) {
            return CloudinaryMedia::isManagedUrl($imagem)
                || str_starts_with($imagem, '/uploads/noticias/conteudo/');
        });

        return array_values(array_unique($imagens));
    }

    private function excluirImagensConteudoNoticia(array $imagens): void
    {
        foreach ($imagens as $imagem) {
            CloudinaryMedia::delete($imagem);
        }
    }

    private function excluirImagemNoticia(?string $imagem): void
    {
        CloudinaryMedia::delete($imagem);
    }

    public function banir(Request $request, User $user)
    {
        $user->sincronizarStatusBanimento();

        if ($user->status_conta === 'excluido') {
            return back()->withErrors(['usuario' => 'Este usuário já foi excluído.']);
        }

        if ($user->status_conta === 'banido') {
            return back()->withErrors(['usuario' => 'Este usuário já possui um banimento ativo.']);
        }

        $data = $request->validate([
            'motivo' => ['required', 'string', 'max:500'],
            'justificativa' => ['required', 'string', 'max:2000'],
            'data_fim' => ['required', 'date', 'after:today'],
            'id_denuncia' => ['nullable', 'integer', 'exists:tb_denuncia,id_denuncia'],
        ], [
            'motivo.required' => 'Informe o motivo do banimento.',
            'justificativa.required' => 'Informe uma justificativa.',
            'data_fim.required' => 'Informe quando o banimento termina.',
            'data_fim.after' => 'A data final deve ser posterior a hoje.',
        ]);

        DB::transaction(function () use ($data, $user) {
            $usuario = User::where('id_usuario', $user->id_usuario)->lockForUpdate()->firstOrFail();
            $usuario->sincronizarStatusBanimento();

            if ($usuario->status_conta === 'excluido') {
                throw ValidationException::withMessages(['usuario' => 'Este usuário já foi excluído.']);
            }

            if ($usuario->status_conta === 'banido') {
                throw ValidationException::withMessages(['usuario' => 'Este usuário já possui um banimento ativo.']);
            }

            $denuncia = null;

            if (!empty($data['id_denuncia'])) {
                $denuncia = Denuncia::where('id_denuncia', $data['id_denuncia'])->lockForUpdate()->firstOrFail();

                if ($denuncia->id_denunciado !== $usuario->id_usuario || $denuncia->status_denuncia !== 'pendente') {
                    throw ValidationException::withMessages(['denuncia' => 'Esta denúncia não pode ser vinculada a este banimento.']);
                }
            }

            Banimento::create([
                'motivo' => $data['motivo'],
                'data_inicio' => now(),
                'data_fim' => $data['data_fim'],
                'justificativa' => $data['justificativa'],
                'status_banimento' => 'ativo',
                'id_administrador' => Auth::guard('admin')->id(),
                'id_usuario_banido' => $usuario->id_usuario,
            ]);

            $usuario->update(['status_conta' => 'banido']);

            if ($denuncia) {
                $denuncia->update([
                    'status_denuncia' => 'aceita',
                    'data_resolucao' => now(),
                    'justificativa' => trim($data['justificativa']),
                    'id_administrador' => Auth::guard('admin')->id(),
                ]);
            }
        });

        if (!empty($data['id_denuncia'])) {
            return redirect()->route('admin.denuncias.show', $data['id_denuncia'])->with('success', 'Usuário banido e denúncia concluída.');
        }

        return back()->with('success', 'Usuário banido com sucesso.');
    }

    public function desbanir(User $user)
    {
        $user->sincronizarStatusBanimento();

        if ($user->status_conta === 'excluido') {
            return back()->withErrors([
                'usuario' => 'Este usuário já foi excluído.',
            ]);
        }

        if ($user->status_conta !== 'banido') {
            return back()->withErrors([
                'usuario' => 'Este usuário não possui um banimento ativo.',
            ]);
        }

        DB::transaction(function () use ($user) {
            $usuario = User::where('id_usuario', $user->id_usuario)
                ->lockForUpdate()
                ->firstOrFail();

            $usuario->sincronizarStatusBanimento();

            if ($usuario->status_conta !== 'banido') {
                return;
            }

            Banimento::where('id_usuario_banido', $usuario->id_usuario)
                ->where('status_banimento', 'ativo')
                ->update([
                    'status_banimento' => 'encerrado',
                    'data_fim' => now(),
                ]);

            $usuario->update([
                'status_conta' => 'ativo',
            ]);
        });

        return back()->with('success', 'Usuário desbanido com sucesso.');
    }

    public function excluirUsuario(Request $request, User $user)
    {
        $user->sincronizarStatusBanimento();

        if ($user->status_conta === 'excluido') {
            return back()->withErrors([
                'usuario' => 'Este usuário já foi excluído.',
            ]);
        }

        if ($user->status_conta === 'banido') {
            return back()->withErrors([
                'usuario' => 'Um usuário banido não pode ser excluído. Encerre o banimento primeiro.',
            ]);
        }

        $data = $request->validate([
            'confirmacao' => ['required', 'string'],
        ]);

        if ($data['confirmacao'] !== $user->nickname) {
            return back()->withErrors([
                'confirmacao' => 'O nome digitado não corresponde ao usuário.',
            ]);
        }

        DB::transaction(function () use ($user) {
            $usuario = User::where('id_usuario', $user->id_usuario)
                ->lockForUpdate()
                ->firstOrFail();

            $usuario->sincronizarStatusBanimento();

            if ($usuario->status_conta === 'banido') {
                throw ValidationException::withMessages([
                    'usuario' => 'Um usuário banido não pode ser excluído. Encerre o banimento primeiro.',
                ]);
            }

            if ($usuario->status_conta === 'excluido') {
                throw ValidationException::withMessages([
                    'usuario' => 'Este usuário já foi excluído.',
                ]);
            }

            $usuario->excluirConta();
        });

        return back()->with('success', 'Usuário excluído com sucesso.');
    }

    public function logout(Request $request)
    {
        Auth::guard('admin')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
