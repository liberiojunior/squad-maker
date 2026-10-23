<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;

class ForgotPasswordController extends Controller
{
    public function show()
    {
        return view('auth.esqueci-senha');
    }

    public function send(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
        ], [
            'email.required' => 'Informe seu e-mail.',
            'email.email' => 'Informe um e-mail válido.',
        ]);

        try {
            $status = Password::sendResetLink(
                $request->only('email')
            );
        } catch (\Throwable $exception) {
            report($exception);

            return back()
                ->withErrors([
                    'email' => 'Não foi possível enviar o e-mail agora. Tente novamente em alguns instantes.',
                ])
                ->onlyInput('email');
        }

        if ($status === Password::RESET_LINK_SENT) {
            return redirect()->route('password.sent');
        }

        if ($status === Password::INVALID_USER) {
            return back()
                ->withErrors([
                    'email' => 'Esse e-mail não está em nosso cadastro.',
                ])
                ->onlyInput('email');
        }

        if ($status === Password::RESET_THROTTLED) {
            return back()
                ->withErrors([
                    'email' => 'Aguarde um pouco antes de solicitar outro link.',
                ])
                ->onlyInput('email');
        }

        return back()
            ->withErrors([
                'email' => 'Não foi possível enviar o link de recuperação agora.',
            ])
            ->onlyInput('email');
    }
}
