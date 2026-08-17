<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\GoogleAuthController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\RegisterController;

Route::view('/', 'login')->name('login');

Route::post('/login', [LoginController::class, 'store'])
    ->name('login.submit');

Route::view('/sobre-nos', 'sobre-nos')->name('sobre-nos');

Route::view('/equipe', 'equipe')->name('equipe');

Route::view('/contato', 'contato')->name('contato');

Route::get('/auth/google', [GoogleAuthController::class, 'redirect'])
    ->name('google.redirect');

Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback'])
    ->name('google.callback');

Route::view('/perfil', 'perfil')
    ->middleware('auth')
    ->name('perfil');

Route::patch('/perfil', [ProfileController::class, 'update'])
    ->middleware('auth')
    ->name('perfil.update');

Route::patch('/perfil/avatar', [ProfileController::class, 'updateAvatar'])
    ->middleware('auth')
    ->name('perfil.avatar.update');

Route::post('/logout', function (Request $request) {
    Auth::logout();

    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect()->route('login');
})->middleware('auth')->name('logout');

Route::get('/cadastro', [RegisterController::class, 'show'])
    ->name('cadastro');

Route::post('/cadastro', [RegisterController::class, 'store'])
    ->name('cadastro.store');
