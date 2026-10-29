<?php

use App\Http\Middleware\VerificarEtapasCadastro;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Routing\Exceptions\InvalidSignatureException;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');

        $middleware->web(append: [
            AddLinkHeadersForPreloadedAssets::class,
            VerificarEtapasCadastro::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) =>
                $request->is('api/*')
                || $request->expectsJson(),
        );

        $exceptions->render(function (InvalidSignatureException $exception, Request $request) {
            if ($request->routeIs('verification.verify')) {
                return redirect()->route($request->user() ? 'verification.notice' : 'login')
                    ->withErrors(['email' => 'O link de confirmação expirou ou é inválido. Solicite um novo e-mail.']);
            }
        });

        $exceptions->dontFlash('cpf');
    })->create();
