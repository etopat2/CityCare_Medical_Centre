<?php

use App\Http\Middleware\AuditTrailMiddleware;
use App\Http\Middleware\EnsureUserIsActive;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;

// This file is the entry point for the Laravel application. It configures the application, including routing, middleware, and exception handling.
return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Trust only local reverse proxies and append auditing to browser requests.
        $middleware->trustProxies(at: ['127.0.0.1', '::1']);
        $middleware->web(append: [AuditTrailMiddleware::class]);
        // Give route declarations short names for account status and Spatie access checks.
        $middleware->alias([
            'active' => EnsureUserIsActive::class,
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Return structured errors to API clients while keeping browser requests on HTML responses.
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // After authorization expires or a different account signs in, send browser navigation to
        // the signed-in user's role-aware dashboard instead of rendering a stale-page 403.
        $exceptions->render(function (HttpExceptionInterface $exception, Request $request) {
            if ($exception->getStatusCode() !== 403
                || ! $request->user()
                || $request->expectsJson()
                || $request->is('api/*')
                || $request->routeIs('dashboard')) {
                return null;
            }

            return redirect()->route('dashboard');
        });
    })->create();
