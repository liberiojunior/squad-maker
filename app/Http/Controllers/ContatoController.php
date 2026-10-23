<?php

namespace App\Http\Controllers;

use App\Support\BrevoMailer;
use Illuminate\Http\Request;

class ContatoController extends Controller
{
    public function send(Request $request)
    {
        $data = $request->validate([
            'nome' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:150'],
            'mensagem' => ['required', 'string', 'max:3000'],
        ], [
            'nome.required' => 'Informe seu nome.',
            'nome.max' => 'O nome pode ter no máximo 100 caracteres.',
            'email.required' => 'Informe seu e-mail.',
            'email.email' => 'Informe um e-mail válido.',
            'mensagem.required' => 'Escreva uma mensagem.',
            'mensagem.max' => 'A mensagem pode ter no máximo 3000 caracteres.',
        ]);

        try {
            BrevoMailer::send(
                config('brevo.support_email'),
                'Suporte Squad Maker',
                'Contato pelo site - '.$data['nome'],
                view('emails.contato', $data)->render(),
                [
                    'email' => $data['email'],
                    'name' => $data['nome'],
                ],
                ['contato-site']
            );
        } catch (\Throwable $exception) {
            report($exception);

            return back()
                ->withErrors([
                    'contato' => 'Não foi possível enviar sua mensagem agora. O texto foi mantido para você tentar novamente.',
                ])
                ->withInput();
        }

        $request->session()->forget('_old_input');

        return redirect()
            ->route('contato')
            ->with('status', 'Sua mensagem foi enviada. Agradecemos o contato! Nossa equipe responderá pelo e-mail informado.');
    }
}
