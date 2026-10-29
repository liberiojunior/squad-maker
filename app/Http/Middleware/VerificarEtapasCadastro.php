<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerificarEtapasCadastro
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->routeIs('admin.*', 'logout', 'login.submit', 'google.redirect', 'google.callback', 'verification.verify', 'verification.status')) {
            return $next($request);
        }

        $user = $request->user();

        if (! $user || $user->status_conta !== 'ativo') {
            return $next($request);
        }

        if (! $user->hasVerifiedEmail()) {
            if ($request->routeIs('verification.notice', 'verification.send')) {
                return $next($request);
            }

            return $this->redirecionar($request, 'verification.notice', 'Confirme seu e-mail para continuar.');
        }

        if (! $user->cadastro_jogos_concluido_em && ! $request->routeIs('cadastro.jogos*')) {
            return $this->redirecionar($request, 'cadastro.jogos', 'Conclua a escolha de jogos para continuar.');
        }

        return $next($request);
    }

    private function redirecionar(Request $request, string $rota, string $mensagem): Response
    {
        if ($request->expectsJson()) {
            return response()->json([
                'message' => $mensagem,
                'redirect' => route($rota),
            ], 403);
        }

        return redirect()->route($rota);
    }
}
