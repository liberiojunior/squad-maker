<?php

namespace App\Http\Controllers;

use App\Models\Amizade;
use App\Models\Post;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class PostController extends Controller
{
    public function feed(Request $request)
    {
        $usuario = $request->user();
        $idUsuario = (int) $usuario->id_usuario;

        $idsAmigos = Amizade::where('status_amizade', 'aceita')
            ->where(function ($query) use ($idUsuario) {
                $query->where('id_usuario_1', $idUsuario)
                    ->orWhere('id_usuario_2', $idUsuario);
            })
            ->get(['id_usuario_1', 'id_usuario_2'])
            ->map(function ($amizade) use ($idUsuario) {
                return (int) $amizade->id_usuario_1 === $idUsuario
                    ? (int) $amizade->id_usuario_2
                    : (int) $amizade->id_usuario_1;
            })
            ->all();

        $idsAutores = array_values(array_unique([
            ...$idsAmigos,
            $idUsuario,
        ]));

        $posts = Post::query()
            ->where('status_post', 'ativo')
            ->whereIn('id_usuario', $idsAutores)
            ->whereHas('usuario', function ($query) {
                $query->where('status_conta', 'ativo');
            })
            ->with('usuario:id_usuario,nickname,avatar,status_conta')
            ->withCount('reacoes')
            ->orderByDesc('data_publicacao')
            ->orderByDesc('id_post')
            ->paginate(10);

        $postsReagidos = [];

        if ($posts->isNotEmpty()) {
            $postsReagidos = DB::table('tb_post_reacao')
                ->where('id_usuario', $idUsuario)
                ->whereIn('id_post', $posts->getCollection()->pluck('id_post'))
                ->pluck('id_post')
                ->map(fn($id) => (int) $id)
                ->all();
        }

        return view('posts.feed', [
            'posts' => $posts,
            'postsReagidos' => $postsReagidos,
        ]);
    }

    public function index(Request $request, User $user)
    {
        $usuarioAtual = $request->user();
        $isOwner = $usuarioAtual->id_usuario === $user->id_usuario;

        if (! $isOwner) {
            $user->sincronizarStatusBanimento();

            if ($user->status_conta !== 'ativo') {
                abort(404);
            }
        }

        $posts = $user->posts()
            ->where('status_post', 'ativo')
            ->withCount('reacoes')
            ->orderByRaw('fixado_em IS NULL')
            ->orderByDesc('fixado_em')
            ->orderByDesc('data_publicacao')
            ->orderByDesc('id_post')
            ->paginate(12);

        $podeReagir = $isOwner || $this->saoAmigos($usuarioAtual->id_usuario, $user->id_usuario);
        $postsReagidos = [];

        if ($podeReagir && $posts->isNotEmpty()) {
            $postsReagidos = DB::table('tb_post_reacao')
                ->where('id_usuario', $usuarioAtual->id_usuario)
                ->whereIn('id_post', $posts->getCollection()->pluck('id_post'))
                ->pluck('id_post')
                ->map(fn($id) => (int) $id)
                ->all();
        }

        return view('posts.index', [
            'user' => $user,
            'posts' => $posts,
            'isOwner' => $isOwner,
            'podeReagir' => $podeReagir,
            'postsReagidos' => $postsReagidos,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'foto' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120', 'dimensions:max_width=6000,max_height=6000'],
            'descricao' => ['nullable', 'string', 'max:280'],
        ], [
            'foto.required' => 'Escolha uma imagem para publicar.',
            'foto.image' => 'O arquivo precisa ser uma imagem válida.',
            'foto.mimes' => 'Envie uma imagem JPG, PNG ou WebP.',
            'foto.max' => 'A imagem pode ter no máximo 5 MB.',
            'foto.dimensions' => 'A imagem enviada é grande demais.',
            'descricao.max' => 'A legenda pode ter no máximo 280 caracteres.',
        ]);

        $usuario = $request->user();
        $arquivo = $request->file('foto');
        $pasta = public_path('uploads/posts');

        if (! is_dir($pasta)) {
            mkdir($pasta, 0755, true);
        }

        $extensao = strtolower($arquivo->extension());
        $nomeArquivo = Str::uuid() . '.' . $extensao;
        $arquivo->move($pasta, $nomeArquivo);
        $caminho = '/uploads/posts/' . $nomeArquivo;

        try {
            Post::create([
                'foto' => $caminho,
                'descricao' => $this->textoOpcional($data['descricao'] ?? null),
                'data_publicacao' => now(),
                'status_post' => 'ativo',
                'id_usuario' => $usuario->id_usuario,
            ]);
        } catch (Throwable $exception) {
            $this->removerArquivoPublico($caminho);
            throw $exception;
        }

        return back()->with('success', 'Publicação criada.');
    }

    public function update(Request $request, Post $post)
    {
        $usuario = $request->user();
        $this->validarProprietario($usuario, $post);

        $data = $request->validate([
            'descricao' => ['nullable', 'string', 'max:280'],
        ], [
            'descricao.max' => 'A descrição pode ter no máximo 280 caracteres.',
        ]);

        $post->update([
            'descricao' => $this->textoOpcional($data['descricao'] ?? null),
        ]);

        return back()->with('success', 'Publicação atualizada.');
    }

    public function toggleFixacao(Request $request, Post $post)
    {
        $usuario = $request->user();
        $this->validarProprietario($usuario, $post);

        $foiFixado = false;

        DB::transaction(function () use ($usuario, $post, &$foiFixado) {
            $posts = Post::where('id_usuario', $usuario->id_usuario)
                ->where('status_post', 'ativo')
                ->lockForUpdate()
                ->get();

            $postAtual = $posts->firstWhere('id_post', $post->id_post);

            if (! $postAtual) {
                abort(404);
            }

            $foiFixado = $postAtual->fixado_em === null;

            Post::where('id_usuario', $usuario->id_usuario)
                ->where('status_post', 'ativo')
                ->whereNotNull('fixado_em')
                ->update([
                    'fixado_em' => null,
                ]);

            if ($foiFixado) {
                Post::where('id_post', $post->id_post)
                    ->update([
                        'fixado_em' => now(),
                    ]);
            }
        });

        return back()->with(
            'success',
            $foiFixado
                ? 'Publicação fixada no topo.'
                : 'Publicação desafixada.'
        );
    }

    public function destroy(Request $request, Post $post)
    {
        $usuario = $request->user();
        $this->validarProprietario($usuario, $post);

        DB::transaction(function () use ($post) {
            DB::table('tb_post_reacao')
                ->where('id_post', $post->id_post)
                ->delete();

            $post->update([
                'status_post' => 'removido',
            ]);
        });

        $this->removerArquivoPublico($post->foto);

        return back()->with('success', 'Publicação removida.');
    }

    public function reagir(Request $request, Post $post)
    {
        $usuario = $request->user();
        $this->validarReacao($usuario, $post);

        DB::table('tb_post_reacao')->insertOrIgnore([
            'id_post' => $post->id_post,
            'id_usuario' => $usuario->id_usuario,
            'data_reacao' => now(),
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'reagiu' => true,
                'reacoes_count' => $this->contarReacoes($post),
            ]);
        }

        return back();
    }

    public function removerReacao(Request $request, Post $post)
    {
        $usuario = $request->user();
        $this->validarReacao($usuario, $post);

        DB::table('tb_post_reacao')
            ->where('id_post', $post->id_post)
            ->where('id_usuario', $usuario->id_usuario)
            ->delete();

        if ($request->expectsJson()) {
            return response()->json([
                'reagiu' => false,
                'reacoes_count' => $this->contarReacoes($post),
            ]);
        }

        return back();
    }

    private function validarProprietario(User $usuario, Post $post): void
    {
        if ($post->id_usuario !== $usuario->id_usuario) {
            abort(403);
        }

        if ($post->status_post !== 'ativo') {
            abort(404);
        }
    }

    private function validarReacao(User $usuario, Post $post): void
    {
        if ($post->status_post !== 'ativo') {
            abort(404);
        }

        if ($post->id_usuario === $usuario->id_usuario) {
            return;
        }

        $autor = User::findOrFail($post->id_usuario);
        $autor->sincronizarStatusBanimento();

        if ($autor->status_conta !== 'ativo') {
            abort(404);
        }

        if (! $this->saoAmigos($usuario->id_usuario, $autor->id_usuario)) {
            abort(403);
        }
    }

    private function saoAmigos(int $idUsuario1, int $idUsuario2): bool
    {
        $menor = min($idUsuario1, $idUsuario2);
        $maior = max($idUsuario1, $idUsuario2);

        return Amizade::where('id_usuario_1', $menor)
            ->where('id_usuario_2', $maior)
            ->where('status_amizade', 'aceita')
            ->exists();
    }

    private function contarReacoes(Post $post): int
    {
        return DB::table('tb_post_reacao')
            ->where('id_post', $post->id_post)
            ->count();
    }

    private function textoOpcional(?string $texto): ?string
    {
        $texto = trim((string) $texto);

        return $texto === '' ? null : $texto;
    }

    private function removerArquivoPublico(?string $caminho): void
    {
        if (! $caminho || ! str_starts_with($caminho, '/uploads/posts/')) {
            return;
        }

        $arquivo = public_path(ltrim($caminho, '/'));

        if (is_file($arquivo)) {
            unlink($arquivo);
        }
    }
}
