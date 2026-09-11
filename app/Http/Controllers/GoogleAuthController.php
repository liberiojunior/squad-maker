<?php

namespace App\Http\Controllers;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class GoogleAuthController extends Controller
{
    public function redirect()
    {
        return Socialite::driver('google')
            ->redirect();
    }

    public function callback(Request $request)
    {
        $googleUser = Socialite::driver('google')->user();

        $googleId = $googleUser->getId();
        $name = $googleUser->getName();
        $email = $googleUser->getEmail();
        $avatar = $googleUser->getAvatar();

        $user = User::where(
            'google_id',
            $googleId
        )->first();

        if (! $user && $email) {
            $user = User::where(
                'email',
                $email
            )->first();
        }

        if ($user) {
            $user->update([
                'email' => $email ?? $user->email,
                'google_id' => $user->google_id ?? $googleId,
                'avatar' => $user->avatar ?? $avatar,
                'email_verified_at' => $user->email_verified_at
                    ?? Carbon::now(),
            ]);
        } else {
            $user = User::create([
                'nickname' => $name ?? 'Usuário Google',

                'email' => $email,

                'senha' => Hash::make(
                    Str::random(32)
                ),

                'google_id' => $googleId,

                'avatar' => $avatar,

                'email_verified_at' => Carbon::now(),

                'data_criacao' => Carbon::now(),

                'status_conta' => 'ativo',
            ]);
        }

        Auth::login($user, true);

        $request->session()->regenerate();

        return redirect()
            ->route('perfil');
    }
}
