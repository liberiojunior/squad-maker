<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class RegisterController extends Controller
{
    public function show()
    {
        return view('cadastro');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nickname' => ['required', 'string', 'max:80'],
            'email' => ['required', 'email', 'max:150', 'unique:tb_usuario,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'maior_idade' => ['accepted'],
        ], [
            'nickname.required' => 'Informe o nome que será mostrado.',
            'nickname.max' => 'O nome pode ter no máximo 80 caracteres.',

            'email.required' => 'Informe seu e-mail.',
            'email.email' => 'Informe um e-mail válido.',
            'email.unique' => 'Este e-mail já está cadastrado.',

            'password.required' => 'Informe uma senha.',
            'password.min' => 'A senha deve ter pelo menos 8 caracteres.',
            'password.confirmed' => 'As senhas não coincidem.',

            'maior_idade.accepted' => 'Você precisa confirmar que tem 18 anos ou mais.',
        ]);

        $user = new User();

        $user->nickname = $data['nickname'];
        $user->email = $data['email'];
        $user->senha = Hash::make($data['password']);
        $user->bio = null;
        $user->data_criacao = now();
        $user->status_conta = 'ativo';

        $user->save();

        Auth::login($user);

        $request->session()->regenerate();

        return redirect()->route('perfil');
    }
}
