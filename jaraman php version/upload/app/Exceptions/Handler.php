<?php

namespace App\Exceptions;

use App\Exceptions\GeneralException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * Exceptions never written to the log.
     *
     * ValidationException   — wrong password / missing field. Not a bug, user error.
     * AuthenticationException — unauthenticated API call. Expected, not exceptional.
     * ModelNotFoundException  — 404, not a system error.
     * GeneralException        — business-logic (insufficient balance etc.).
     */
    protected $dontReport = [
        ValidationException::class,
        AuthenticationException::class,
        ModelNotFoundException::class,
        NotFoundHttpException::class,
        GeneralException::class,
    ];

    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    public function register(): void
    {
        $this->renderable(function (Throwable $e, Request $request) {

            if (! $request->expectsJson()) {
                return null; // let web exceptions handle normally
            }

            if ($e instanceof ValidationException) {
                return response()->json([
                    'status'  => false,
                    'message' => $e->getMessage(),
                    'errors'  => $e->errors(),
                ], 422);
            }

            if ($e instanceof GeneralException) {
                return response()->json([
                    'status'  => false,
                    'message' => $e->getMessage(),
                ], $e->getCode() ?: 400);
            }

            if ($e instanceof BindingResolutionException) {
                return response()->json([
                    'status'  => false,
                    'message' => 'A required service is not configured. Please contact support.',
                ], 503);
            }

            if ($e instanceof AuthenticationException) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Unauthenticated. Please log in.',
                ], 401);
            }

            if ($e instanceof ModelNotFoundException) {
                return response()->json([
                    'status'  => false,
                    'message' => class_basename($e->getModel()) . ' not found.',
                ], 404);
            }

            if ($e instanceof HttpException) {
                return response()->json([
                    'status'  => false,
                    'message' => $e->getMessage() ?: 'HTTP error.',
                ], $e->getStatusCode());
            }

            return response()->json([
                'status'  => false,
                'message' => 'An unexpected error occurred. Please try again.',
            ], 500);
        });
    }
}
