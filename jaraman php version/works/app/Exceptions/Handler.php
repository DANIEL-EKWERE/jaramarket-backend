<?php

namespace App\Exceptions;

use App\Http\Responses\ApiResponse;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Container\BindingResolutionException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * Exceptions that should NEVER be written to the log.
     *
     * ValidationException  — user input errors (wrong password, missing field, etc.)
     *                        These are expected, not bugs. Logging them floods the log
     *                        and leaks credential attempts.
     *
     * AuthenticationException — unauthenticated request, also expected.
     *
     * ModelNotFoundException / NotFoundHttpException — 404s, not errors.
     */
    protected $dontReport = [
        ValidationException::class,
        AuthenticationException::class,
        ModelNotFoundException::class,
        NotFoundHttpException::class,
    ];

    /**
     * Exceptions that should not be reported BUT also should not flash
     * their messages to the session (keeps the dontFlash list clean).
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register custom rendering logic for API responses.
     */
    public function register(): void
    {
        $this->renderable(function (Throwable $e, Request $request) {

            // Only intercept JSON / API requests
            if (! $request->expectsJson()) {
                return null; // fall through to default web handling
            }

            /* ── 1. Validation errors (including wrong password / email) ──
               These are NOT logged (see $dontReport above).
               Return 422 with the validation messages.
            ── */
            if ($e instanceof ValidationException) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                    'errors'  => $e->errors(),
                ], 422);
            }

            /* ── 2. PaymentGatewayInterface not bound ──
               BindingResolutionException means a service provider is missing.
               This IS a real bug so it gets logged, but we catch it here so
               the user gets a clean JSON error instead of a 500 stack trace.
            ── */
            if ($e instanceof BindingResolutionException) {
                // Still logs because it's not in $dontReport
                return response()->json([
                    'success' => false,
                    'message' => 'Payment service is currently unavailable. Please try again later.',
                ], 503);
            }

            /* ── 3. Authentication (token missing / expired) ── */
            if ($e instanceof AuthenticationException) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated. Please log in.',
                ], 401);
            }

            /* ── 4. Model not found → 404 ── */
            if ($e instanceof ModelNotFoundException) {
                $model = class_basename($e->getModel());
                return response()->json([
                    'success' => false,
                    'message' => "{$model} not found.",
                ], 404);
            }

            /* ── 5. Generic HTTP exceptions (403, 404, 429, etc.) ── */
            if ($e instanceof HttpException) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage() ?: 'HTTP error.',
                ], $e->getStatusCode());
            }

            /* ── 6. Everything else — log it and return a safe 500 ── */
            // Let the parent handle logging, then return clean JSON
            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred. Please try again.',
            ], 500);
        });
    }
}
