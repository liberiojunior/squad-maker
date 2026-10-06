<?php

namespace App\Http\Controllers;

use App\Models\Conversa;
use App\Models\Denuncia;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Throwable;

class DenunciaController extends Controller
{
    public function conversa(Request $request, Conversa $conversa)
    {
        $usuario = $request->user();

        if (! in_array($usuario->id_usuario, [$conversa->id_usuario_1, $conversa->id_usuario_2], true)) {
            abort(403);
        }

        $idDenunciado = $conversa->id_usuario_1 === $usuario->id_usuario
            ? $conversa->id_usuario_2
            : $conversa->id_usuario_1;

        $denunciado = User::findOrFail($idDenunciado);

        if ($this->possuiDenunciaPendente($usuario->id_usuario, $denunciado->id_usuario, 'conversa', $conversa->id_conversa)) {
            return back()->withErrors(['denuncia' => 'Você já possui uma denúncia pendente para esta conversa.']);
        }

        $data = $request->validate([
            'motivo' => ['required', Rule::in(array_keys(Denuncia::MOTIVOS_CONVERSA))],
            'descricao' => ['required', 'string', 'min:20', 'max:2000'],
            'anexo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120', 'dimensions:max_width=6000,max_height=6000'],
            'confirmar_contexto' => ['accepted'],
        ], [
            'motivo.required' => 'Escolha o motivo da denúncia.',
            'descricao.required' => 'Explique o que aconteceu.',
            'descricao.min' => 'A justificativa deve ter pelo menos 20 caracteres.',
            'descricao.max' => 'A justificativa pode ter no máximo 2000 caracteres.',
            'anexo.image' => 'O anexo precisa ser uma imagem válida.',
            'anexo.mimes' => 'Envie uma imagem JPG, PNG ou WebP.',
            'anexo.max' => 'A imagem pode ter no máximo 5 MB.',
            'anexo.dimensions' => 'A imagem enviada é grande demais.',
            'confirmar_contexto.accepted' => 'Confirme que você entendeu o envio do contexto recente da conversa.',
        ]);

        $anexo = $this->salvarAnexo($request);
        $mensagens = $conversa->mensagens()
            ->with('remetente:id_usuario,nickname')
            ->orderByDesc('data_envio')
            ->orderByDesc('id_mensagem')
            ->limit(20)
            ->get()
            ->sortBy('data_envio')
            ->values()
            ->map(function ($mensagem) {
                return [
                    'id_mensagem' => $mensagem->id_mensagem,
                    'id_remetente' => $mensagem->id_remetente,
                    'remetente' => $mensagem->remetente?->nickname ?? 'Usuário',
                    'mensagem' => $mensagem->mensagem,
                    'data_envio' => $mensagem->data_envio?->toIso8601String(),
                ];
            })
            ->all();

        try {
            Denuncia::create([
                'tipo_denuncia' => 'conversa',
                'motivo' => $data['motivo'],
                'descricao' => trim($data['descricao']),
                'anexo' => $anexo,
                'contexto' => ['mensagens' => $mensagens],
                'data_denuncia' => now(),
                'status_denuncia' => 'pendente',
                'id_denunciante' => $usuario->id_usuario,
                'id_denunciado' => $denunciado->id_usuario,
                'id_conversa' => $conversa->id_conversa,
            ]);
        } catch (Throwable $exception) {
            if ($anexo) {
                Storage::disk('local')->delete($anexo);
            }

            throw $exception;
        }

        return back()->with('success', 'Denúncia enviada para análise.');
    }

    public function perfil(Request $request, User $user)
    {
        $usuario = $request->user();

        if ($usuario->id_usuario === $user->id_usuario) {
            abort(422, 'Você não pode denunciar o próprio perfil.');
        }

        $user->sincronizarStatusBanimento();

        if ($user->status_conta !== 'ativo') {
            return back()->withErrors(['denuncia' => 'Este perfil não está disponível para denúncia no momento.']);
        }

        if ($this->possuiDenunciaPendente($usuario->id_usuario, $user->id_usuario, 'perfil')) {
            return back()->withErrors(['denuncia' => 'Você já possui uma denúncia pendente para este perfil.']);
        }

        $data = $request->validate([
            'motivo' => ['required', Rule::in(array_keys(Denuncia::MOTIVOS_PERFIL))],
            'descricao' => ['required', 'string', 'min:20', 'max:2000'],
            'anexo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120', 'dimensions:max_width=6000,max_height=6000'],
        ], [
            'motivo.required' => 'Escolha o motivo da denúncia.',
            'descricao.required' => 'Explique o motivo da denúncia.',
            'descricao.min' => 'A justificativa deve ter pelo menos 20 caracteres.',
            'descricao.max' => 'A justificativa pode ter no máximo 2000 caracteres.',
            'anexo.image' => 'O anexo precisa ser uma imagem válida.',
            'anexo.mimes' => 'Envie uma imagem JPG, PNG ou WebP.',
            'anexo.max' => 'A imagem pode ter no máximo 5 MB.',
            'anexo.dimensions' => 'A imagem enviada é grande demais.',
        ]);

        $anexo = $this->salvarAnexo($request);
        $avatarEvidencia = $this->salvarAvatarAtual($user);

        try {
            Denuncia::create([
                'tipo_denuncia' => 'perfil',
                'motivo' => $data['motivo'],
                'descricao' => trim($data['descricao']),
                'anexo' => $anexo,
                'contexto' => [
                    'perfil' => [
                        'nickname' => $user->nickname,
                        'bio' => $user->bio,
                        'avatar_original' => $user->avatar,
                        'avatar_evidencia' => $avatarEvidencia,
                    ],
                ],
                'data_denuncia' => now(),
                'status_denuncia' => 'pendente',
                'id_denunciante' => $usuario->id_usuario,
                'id_denunciado' => $user->id_usuario,
            ]);
        } catch (Throwable $exception) {
            $arquivos = array_filter([$anexo, $avatarEvidencia]);

            if ($arquivos) {
                Storage::disk('local')->delete($arquivos);
            }

            throw $exception;
        }

        return back()->with('success', 'Denúncia enviada para análise.');
    }

    private function possuiDenunciaPendente(int $idDenunciante, int $idDenunciado, string $tipo, ?int $idConversa = null): bool
    {
        return Denuncia::where('id_denunciante', $idDenunciante)
            ->where('id_denunciado', $idDenunciado)
            ->where('tipo_denuncia', $tipo)
            ->where('status_denuncia', 'pendente')
            ->when($tipo === 'conversa', fn($query) => $query->where('id_conversa', $idConversa))
            ->exists();
    }

    private function salvarAnexo(Request $request): ?string
    {
        if (! $request->hasFile('anexo')) {
            return null;
        }

        return $request->file('anexo')->store('denuncias/anexos', 'local');
    }

    private function salvarAvatarAtual(User $user): ?string
    {
        if (! $user->avatar || str_contains($user->avatar, '://')) {
            return null;
        }

        $relativo = ltrim(str_replace('\\', '/', $user->avatar), '/');
        $origem = realpath(public_path($relativo));
        $publico = realpath(public_path());

        if (! $origem || ! $publico || ! str_starts_with($origem, $publico . DIRECTORY_SEPARATOR) || ! is_file($origem)) {
            return null;
        }

        $extensao = strtolower(pathinfo($origem, PATHINFO_EXTENSION));

        if (! in_array($extensao, ['jpg', 'jpeg', 'png', 'webp'], true)) {
            return null;
        }

        $destino = 'denuncias/perfis/' . Str::uuid() . '.' . $extensao;
        Storage::disk('local')->put($destino, file_get_contents($origem));

        return $destino;
    }
}
