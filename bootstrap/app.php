<?php

use App\Http\Middleware\CheckAppVersion;
use App\Http\Middleware\JwtMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        
        $middleware->append([
            
        ]);

        $middleware->alias([
            'jwt.verify' => JwtMiddleware::class,
            // 'jwt.auth' => JwtMiddleware::class,
            'check.app.version' => CheckAppVersion::class
        ]);

        $middleware->group('web', [

        ]);

        $middleware->group('api', [
            
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
