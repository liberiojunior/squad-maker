<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class LoginController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ], [
            'email.required' => 'Informe seu e-mail.',
            'email.email' => 'Informe um e-mail válido.',
            'password.required' => 'Informe sua senha.',
        ]);

        $admin = Admin::where('email', $data['email'])->first();

        if ($admin) {
            if (! Hash::check($data['password'], $admin->senha)) {
                return back()
                    ->withErrors(['email' => 'E-mail ou senha incorretos.'])
                    ->onlyInput('email');
            }

            Auth::guard('web')->logout();
            Auth::guard('admin')->login($admin);

            $request->session()->regenerate();

            return redirect()->route('admin.geral');
        }

        $user = User::where('email', $data['email'])->first();

        if ($user) {
            $user->sincronizarStatusBanimento();
        }

        $logged = Auth::attempt([
            'email' => $data['email'],
            'password' => $data['password'],
            'status_conta' => 'ativo',
        ]);

        if (! $logged) {
            return back()
                ->withErrors(['email' => 'E-mail ou senha incorretos.',])
                ->onlyInput('email');
        }

        Auth::guard('admin')->logout();
        $request->session()->regenerate();

        if (! $request->user()->hasVerifiedEmail()) {
            return redirect()->route('verification.notice');
        }

        if (! $request->user()->cadastro_jogos_concluido_em) {
            return redirect()->route('cadastro.jogos');
        }

        return redirect()->route('perfil');
    }
}
