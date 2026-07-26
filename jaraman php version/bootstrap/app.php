<?php

use App\Http\Middleware\AdminMiddleware;
use App\Http\Middleware\CheckPermission;
use App\Http\Middleware\IsVendor;
use App\Http\Middleware\PaystackWebhook;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        then: function () {
            // Vendor API routes (old vendor.php)
            \Illuminate\Support\Facades\Route::prefix('api')
                ->middleware('api')
                ->group(base_path('routes/vendor.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware) {

        // ── Rate limiting ─────────────────────────────────────────────
        $middleware->throttleApi();

        // ── Custom middleware aliases ─────────────────────────────────
        // These replace the $middlewareAliases array in the old Kernel.php
        $middleware->alias([
            'admin'            => AdminMiddleware::class,
            'permission'       => CheckPermission::class,
            'vendor'           => IsVendor::class,
            'paystack-webhook' => PaystackWebhook::class,
        ]);

        // ── API middleware ────────────────────────────────────────────
        $middleware->statefulApi();

    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })
    ->create();
