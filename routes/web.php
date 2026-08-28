<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AdminJogoController;
use App\Http\Controllers\ForgotPasswordController;
use App\Http\Controllers\GoogleAuthController;
use App\Http\Controllers\JogoController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RegisterController;
use App\Http\Controllers\ResetPasswordController;
use App\Http\Middleware\VerificarStatusUsuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// Rotas públicas
Route::view('/', 'login')
    ->name('login');

Route::post('/login', [LoginController::class, 'store'])
    ->name('login.submit');


Route::view('/sobre-nos', 'sobre-nos')
    ->name('sobre-nos');

Route::view('/equipe', 'equipe')
    ->name('equipe');

Route::view('/contato', 'contato')
    ->name('contato');


Route::get('/cadastro', [RegisterController::class, 'show'])
    ->name('cadastro');

Route::post('/cadastro', [RegisterController::class, 'store'])
    ->name('cadastro.store');


Route::get('/auth/google', [GoogleAuthController::class, 'redirect'])
    ->name('google.redirect');

Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback'])
    ->name('google.callback');


Route::get('/esqueci-senha', [ForgotPasswordController::class, 'show'])
    ->name('password.request');

Route::post('/esqueci-senha', [ForgotPasswordController::class, 'send'])
    ->name('password.email');

Route::get(
    '/redefinir-senha/{token}',
    [ResetPasswordController::class, 'show']
)->name('password.reset');

Route::post(
    '/redefinir-senha',
    [ResetPasswordController::class, 'update']
)->name('password.update');


//Rotas de usuário autenticado
Route::middleware([
    'auth',
    VerificarStatusUsuario::class,
])->group(function () {

    Route::get('/perfil', [ProfileController::class, 'show'])
        ->name('perfil');

    Route::patch('/perfil', [ProfileController::class, 'update'])
        ->name('perfil.update');

    Route::patch(
        '/perfil/avatar',
        [ProfileController::class, 'updateAvatar']
    )->name('perfil.avatar.update');

    Route::patch(
        '/perfil/generos',
        [ProfileController::class, 'updateGeneros']
    )->name('perfil.generos.update');

    Route::patch(
        '/perfil/jogos',
        [ProfileController::class, 'updateJogos']
    )->name('perfil.jogos.update');

    Route::get(
        '/buscar-jogos',
        [JogoController::class, 'index']
    )->name('jogos.buscar');
});

//Logout do usuário
Route::post('/logout', function (Request $request) {
    Auth::logout();

    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect()->route('login');
})
    ->middleware('auth')
    ->name('logout');

//Rotas administrativas
Route::middleware('auth:admin')
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {

        Route::get('/', [AdminController::class, 'dashboard'])
            ->name('dashboard');

        Route::post(
            '/usuarios/{user}/banir',
            [AdminController::class, 'banir']
        )->name('usuarios.banir');

        Route::patch(
            '/usuarios/{user}/desbanir',
            [AdminController::class, 'desbanir']
        )->name('usuarios.desbanir');

        Route::get(
            '/jogos',
            [AdminJogoController::class, 'index']
        )->name('jogos.index');

        Route::post(
            '/jogos/manual',
            [AdminJogoController::class, 'storeManual']
        )->name('jogos.manual');

        Route::post(
            '/jogos/steam',
            [AdminJogoController::class, 'storeSteam']
        )->name('jogos.steam');

        Route::post(
            '/logout',
            [AdminController::class, 'logout']
        )->name('logout');
    });


Route::fallback(function () {
    return response()->view('errors.404', [], 404);
});
