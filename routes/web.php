<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\GoogleAuthController;

Route::view('/', 'login')->name('login');

Route::post('/login', function (Request $request) {
    $request->validate([
        'email' => ['required', 'email'],
        'password' => ['required'],
    ], [
        'email.required' => 'Informe seu e-mail.',
        'email.email' => 'Informe um e-mail válido.',
        'password.required' => 'Informe sua senha.',
    ]);

    return back();
})->name('login.submit');

Route::view('/sobre-nos', 'sobre-nos')->name('sobre-nos');

Route::view('/equipe', 'equipe')->name('equipe');

Route::view('/contato', 'contato')->name('contato');

Route::get('/auth/google', [GoogleAuthController::class, 'redirect'])
    ->name('google.redirect');

Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback'])
    ->name('google.callback');
