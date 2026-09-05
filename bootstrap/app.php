<?php

use App\Http\Middleware\AuthenticateAndEnsureUserIsNotBlocked;
use App\Http\Middleware\AuthenticateMobileToken;
use App\Http\Middleware\EnsureAgentAccess;
use App\Http\Middleware\EnsureUserIsNotBlocked;
use App\Http\Middleware\EnsureUserIsReseller;
use App\Http\Middleware\SecurityHeaders;
use App\Support\AgentUploadLimits;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\PostTooLargeException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        then: function (): void {
            // Rotas dos quatro ambientes Velaro (site, portal, vitrine, backend).
            // Carregadas depois de web.php: o Velaro e o dono da raiz do dominio.
            //
            // O grupo `web` e obrigatorio aqui: `then` nao aplica grupo nenhum.
            // Sem ele nao ha sessao, nem CSRF nos POST publicos (contato e
            // pre-cadastro), nem `$errors` compartilhado — e toda view com
            // @error morre com "Undefined variable $errors".
            Route::middleware('web')->group(__DIR__.'/../routes/velaro.php');
        },
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');
        $middleware->append(SecurityHeaders::class);

        $middleware->alias([
            'agent' => EnsureAgentAccess::class,
            'auth.blocked' => AuthenticateAndEnsureUserIsNotBlocked::class,
            'mobile.auth' => AuthenticateMobileToken::class,
            'not_blocked' => EnsureUserIsNotBlocked::class,
            'reseller' => EnsureUserIsReseller::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (PostTooLargeException $exception, Request $request) {
            if (! $request->expectsJson()) {
                return null;
            }

            $limits = app(AgentUploadLimits::class);
            $message = __('O envio excede o limite do agente de exemplo. Envie ate :file por PDF e ate :request por envio.', [
                'file' => $limits->maxFileLabel(),
                'request' => $limits->maxRequestLabel(),
            ]);

            return response()->json([
                'message' => $message,
                'errors' => [
                    'files' => [$message],
                ],
            ], 413);
        });
    })->create();
