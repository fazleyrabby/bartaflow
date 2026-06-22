<?php

declare(strict_types=1);

use App\Http\Middleware\EnsureRole;
use App\Http\Middleware\EnsureWorkspace;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        // Health endpoint is defined in routes/web.php via HealthController so it can
        // report DB/cache/queue status (docs/tasks/001-foundation.md).
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'workspace' => EnsureWorkspace::class,
            'role' => EnsureRole::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
