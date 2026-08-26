<?php

declare(strict_types=1);

use App\Http\Errors\ErrorResponseFactory;
use App\Http\Middleware\InitializeLogContext;
use App\Http\Middleware\LogRequest;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->api(prepend: [
            InitializeLogContext::class,
            LogRequest::class,
        ]);

        // ALB 配下では trustProxies を有効にしないと $request->ip() が ALB の IP を返す。
        // ECS / ALB 側で X-Forwarded-For を引き渡す構成が確定してから有効化する。
        // $middleware->trustProxies(at: env('TRUSTED_PROXIES', '*'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        $exceptions->render(
            fn (Throwable $e, Request $request) => app(ErrorResponseFactory::class)->make($e, $request),
        );
    })->create();
