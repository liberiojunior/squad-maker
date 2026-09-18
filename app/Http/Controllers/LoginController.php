<?php

namespace App\Http\Controllers;

use App\Models\Admin;
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
                    ->withErrors([
                        'email' => 'E-mail ou senha incorretos.',
                    ])
                    ->onlyInput('email');
            }

            Auth::guard('admin')->login($admin);

            $request->session()->regenerate();

            return redirect()->route('admin.geral');
        }

        $logged = Auth::attempt([
            'email' => $data['email'],
            'password' => $data['password'],
            'status_conta' => 'ativo',
        ]);

        if (! $logged) {
            return back()
                ->withErrors([
                    'email' => 'E-mail ou senha incorretos.',
                ])
                ->onlyInput('email');
        }

        $request->session()->regenerate();

        return redirect()->route('register');
    }
}
