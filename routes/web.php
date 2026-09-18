<?php

use App\Http\Controllers\AdminCatalogoController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AdminJogoController;
use App\Http\Controllers\ForgotPasswordController;
use App\Http\Controllers\GoogleAuthController;
use App\Http\Controllers\JogoController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RegisterController;
use App\Http\Controllers\RegisterJogosController;
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
    ->middleware('throttle:5,1')
    ->name('cadastro.store');

Route::view('/termos', 'register.termos')
    ->name('termos');

// Login com Google

Route::get('/auth/google', [GoogleAuthController::class, 'redirect'])
    ->name('google.redirect');

Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback'])
    ->name('google.callback');

// Recuperação de senha

Route::get(
    '/esqueci-senha',
    [ForgotPasswordController::class, 'show']
)->name('password.request');

Route::post(
    '/esqueci-senha',
    [ForgotPasswordController::class, 'send']
)->name('password.email');

Route::get(
    '/redefinir-senha/{token}',
    [ResetPasswordController::class, 'show']
)->name('password.reset');

Route::post(
    '/redefinir-senha',
    [ResetPasswordController::class, 'update']
)->name('password.update');

// Rotas do usuário autenticado

Route::middleware([
    'auth',
    VerificarStatusUsuario::class,
])->group(function () {

    // Etapa de jogos do cadastro

    Route::get(
        '/cadastro/jogos',
        [RegisterJogosController::class, 'show']
    )->name('cadastro.jogos');

    Route::post(
        '/cadastro/jogos/selecionar',
        [RegisterJogosController::class, 'selectGame']
    )->name('cadastro.jogos.selecionar');

    Route::delete(
        '/cadastro/jogos/{jogo}/selecionar',
        [RegisterJogosController::class, 'removeGame']
    )->name('cadastro.jogos.remover');

    Route::post(
        '/cadastro/jogos',
        [RegisterJogosController::class, 'store']
    )->name('cadastro.jogos.store');

    // Perfil

    Route::get(
        '/perfil',
        [ProfileController::class, 'show']
    )->name('perfil');

    Route::patch(
        '/perfil',
        [ProfileController::class, 'update']
    )->name('perfil.update');

    Route::patch(
        '/perfil/avatar',
        [ProfileController::class, 'updateAvatar']
    )->name('perfil.avatar.update');

    Route::patch(
        '/perfil/generos',
        [ProfileController::class, 'updateGeneros']
    )->name('perfil.generos.update');

    Route::get(
        '/perfil/jogos/buscar',
        [ProfileController::class, 'buscarJogos']
    )->name('perfil.jogos.buscar');

    Route::patch(
        '/perfil/jogos',
        [ProfileController::class, 'updateJogos']
    )->name('perfil.jogos.update');

    Route::patch(
        '/perfil/jogos/ordem',
        [ProfileController::class, 'updateOrdemJogos']
    )->name('perfil.jogos.ordem.update');

    Route::patch(
        '/perfil/jogos/{jogo}/nivel',
        [ProfileController::class, 'updateNivelJogo']
    )->name('perfil.jogos.nivel.update');

    Route::delete(
        '/perfil/jogos/{jogo}',
        [ProfileController::class, 'removeJogo']
    )->name('perfil.jogos.remove');

    Route::patch(
        '/perfil/plataformas',
        [ProfileController::class, 'updatePlataformas']
    )->name('perfil.plataformas.update');

    Route::get(
        '/buscar-jogos',
        [JogoController::class, 'index']
    )->name('jogos.buscar');

    Route::delete(
        '/conta',
        [ProfileController::class, 'destroy']
    )->name('conta.destroy');
});

// Logout do usuário

Route::post('/logout', function (Request $request) {
    Auth::logout();

    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect()->route('login');
})
    ->middleware('auth')
    ->name('logout');

// Rotas administrativas

Route::middleware('auth:admin')
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {

        Route::get(
            '/',
            [AdminController::class, 'geral']
        )->name('geral');

        Route::get(
            '/dashboard',
            [AdminController::class, 'dashboard']
        )->name('dashboard');

        Route::post(
            '/usuarios/{user}/banir',
            [AdminController::class, 'banir']
        )->name('usuarios.banir');

        Route::patch(
            '/usuarios/{user}/desbanir',
            [AdminController::class, 'desbanir']
        )->name('usuarios.desbanir');

        Route::delete(
            '/usuarios/{user}',
            [AdminController::class, 'excluirUsuario']
        )->name('usuarios.excluir');

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

        Route::patch(
            '/jogos/{jogo}',
            [AdminJogoController::class, 'update']
        )->name('jogos.update');

        Route::delete(
            '/jogos/{jogo}',
            [AdminJogoController::class, 'destroy']
        )->name('jogos.destroy');

        Route::get(
            '/catalogo',
            [AdminCatalogoController::class, 'index']
        )->name('catalogo.index');

        Route::post(
            '/catalogo/plataformas',
            [AdminCatalogoController::class, 'storePlataforma']
        )->name('plataformas.store');

        Route::patch(
            '/catalogo/plataformas/{plataforma}',
            [AdminCatalogoController::class, 'updatePlataforma']
        )->name('plataformas.update');

        Route::delete(
            '/catalogo/plataformas/{plataforma}',
            [AdminCatalogoController::class, 'destroyPlataforma']
        )->name('plataformas.destroy');

        Route::post(
            '/catalogo/generos',
            [AdminCatalogoController::class, 'storeGeneros']
        )->name('generos.store');

        Route::patch(
            '/catalogo/generos/{genero}',
            [AdminCatalogoController::class, 'updateGenero']
        )->name('generos.update');

        Route::delete(
            '/catalogo/generos/{genero}',
            [AdminCatalogoController::class, 'destroyGenero']
        )->name('generos.destroy');

        Route::post(
            '/logout',
            [AdminController::class, 'logout']
        )->name('logout');
    });

// Página não encontrada

Route::fallback(function () {
    return response()->view('errors.404', [], 404);
});
