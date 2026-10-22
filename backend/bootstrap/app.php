<?php

use App\Http\Middleware\EnsureRole;
use App\Http\Middleware\UpdateLastSeen;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    // Sanctum bearer token bilan: POST /api/broadcasting/auth
    ->withBroadcasting(__DIR__.'/../routes/channels.php', ['prefix' => 'api', 'middleware' => ['auth:sanctum', 'last_seen']])
    ->withMiddleware(function (Middleware $middleware): void {
        // Saytda sessiya yo'q (API token), sessiya faqat admin panel uchun.
        $middleware->alias(['has_role' => EnsureRole::class, 'last_seen' => UpdateLastSeen::class]);
        // Productionda so'rovlar Nginx -> Caddy orqali keladi (ichki tarmoq): https va mijoz IP'si X-Forwarded-* dan olinadi.
        $middleware->trustProxies(at: ['127.0.0.1', '10.0.0.0/8', '172.16.0.0/12', '192.168.0.0/16']);
        $middleware->redirectGuestsTo(fn (Request $request) => $request->is('api/*') ? null : route('filament.admin.auth.login'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
