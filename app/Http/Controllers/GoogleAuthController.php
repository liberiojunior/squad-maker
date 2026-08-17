<?php

namespace App\Http\Controllers;

use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Str;

class GoogleAuthController extends Controller
{
    public function redirect()
    {
        return Socialite::driver('google')->redirect();
    }

    public function callback()
    {
        $googleUser = Socialite::driver('google')->user();

        $googleId = $googleUser->getId();
        $name = $googleUser->getName();
        $email = $googleUser->getEmail();
        $avatar = $googleUser->getAvatar();

        // Procura por google_id primeiro, depois por email
        $user = User::where('google_id', $googleId)->first();

        if (! $user && $email) {
            $user = User::where('email', $email)->first();
        }

        if ($user) {
            // Atualiza campos relevantes
            $user->update([
                'email' => $email ?? $user->email,
                'google_id' => $user->google_id ?? $googleId,
                'avatar' => $user->avatar ?? $avatar,
                'email_verified_at' => $user->email_verified_at ?? Carbon::now(),
            ]);
        } else {
            // Cria novo usuário na sua tabela tb_usuario
            $user = User::create([
                'nickname' => $name ?? 'Usuário Google',
                'email' => $email,
                'senha' => \Illuminate\Support\Facades\Hash::make(\Illuminate\Support\Str::random(16)),
                'google_id' => $googleId,
                'avatar' => $avatar,
                'email_verified_at' => \Carbon\Carbon::now(),
                'data_criacao' => \Carbon\Carbon::now(),
                'status_conta' => 'ativo',
            ]);
        }

        // Faz login do usuário no Laravel
        \Illuminate\Support\Facades\Auth::login($user, true);

        return redirect()->route('perfil');
    }

    /*DEBUG
    public function callback()
    {
        $googleUser = Socialite::driver('google')->user();

        dd([
            'id' => $googleUser->getId(),
            'name' => $googleUser->getName(),
            'email' => $googleUser->getEmail(),
            'avatar' => $googleUser->getAvatar(),
            'raw' => $googleUser->user ?? null,
        ]);
    }*/
}
