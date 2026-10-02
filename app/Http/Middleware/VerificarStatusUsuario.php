<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class VerificarStatusUsuario
{
    private const INTERVALO_ATIVIDADE = 60;

    public function handle(
        Request $request,
        Closure $next
    ): Response
    {
        $user = $request->user();

        if (!$user) {
            return $next($request);
        }

        $user->sincronizarStatusBanimento();

        if ($user->status_conta === 'excluido') {
            return $this->encerrarSessao(
                $request,
                'Esta conta foi excluída.'
            );
        }

        if ($user->status_conta === 'banido') {
            return $this->encerrarSessao(
                $request,
                'Sua conta está temporariamente banida.'
            );
        }

        if ($user->status_conta === 'ativo') {
            $this->registrarAtividade($request, $user);
        }

        return $next($request);
    }

    private function registrarAtividade(
        Request $request,
                $user
    ): void
    {
        $ultimaAtualizacao = (int)$request
            ->session()
            ->get('presenca_atualizada_em', 0);

        $agora = now()->timestamp;

        if (($agora - $ultimaAtualizacao) < self::INTERVALO_ATIVIDADE) {
            return;
        }

        $user->ultima_atividade = now();
        $user->saveQuietly();

        $request
            ->session()
            ->put('presenca_atualizada_em', $agora);
    }

    private function encerrarSessao(
        Request $request,
        string  $mensagem
    ): Response
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()
            ->route('login')
            ->with('error', $mensagem);
    }
}
