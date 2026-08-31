<?php

namespace App\Http\Middleware;

use App\Models\Banimento;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class VerificarStatusUsuario
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user->status_conta === 'excluido') {

            Auth::logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()
                ->route('login')
                ->with(
                    'error',
                    'Esta conta foi excluída.'
                );
        }

        if (! $user) {
            return $next($request);
        }

        if ($user->status_conta === 'banido') {

            $banimento = Banimento::where(
                'id_usuario_banido',
                $user->id_usuario
            )
                ->where('status_banimento', 'ativo')
                ->orderByDesc('data_inicio')
                ->first();

            if (
                $banimento
                && $banimento->data_fim
                && now()->greaterThanOrEqualTo($banimento->data_fim)
            ) {
                $banimento->update([
                    'status_banimento' => 'encerrado',
                ]);

                $user->update([
                    'status_conta' => 'ativo',
                ]);

                return $next($request);
            }

            Auth::logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()
                ->route('login')
                ->with(
                    'error',
                    'Sua conta está temporariamente banida.'
                );
        }

        return $next($request);
    }
}
