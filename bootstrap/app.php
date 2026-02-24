<?php

use App\Infrastructure\Shared\Http\Middleware\ForceJsonResponse;
use App\Infrastructure\Shared\Http\Middleware\SetCorrelationId;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            // API v1 routes
            \Illuminate\Support\Facades\Route::middleware('api')
                ->prefix('api/v1')
                ->group(function () {
                    require __DIR__.'/../routes/api/v1/health.php';
                    require __DIR__.'/../routes/api/v1/auth.php';
                });
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->api(prepend: [
            ForceJsonResponse::class,
            SetCorrelationId::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\App\Domain\Shared\Exceptions\DomainException $e, Request $request) {
            return \App\Infrastructure\Shared\Http\Resources\ApiResponse::fail(
                code: $e->errorCode,
                message: $e->getMessage(),
                status: 422,
            );
        });

        $exceptions->render(function (\Illuminate\Auth\AuthenticationException $e, Request $request) {
            return \App\Infrastructure\Shared\Http\Resources\ApiResponse::fail(
                code: 'AUTHENTICATION_ERROR',
                message: 'Unauthenticated.',
                status: 401,
            );
        });

        $exceptions->render(function (\Illuminate\Auth\Access\AuthorizationException $e, Request $request) {
            return \App\Infrastructure\Shared\Http\Resources\ApiResponse::fail(
                code: 'AUTHORIZATION_ERROR',
                message: $e->getMessage(),
                status: 403,
            );
        });

        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e, Request $request) {
            return \App\Infrastructure\Shared\Http\Resources\ApiResponse::fail(
                code: 'RESOURCE_NOT_FOUND',
                message: 'Resource not found.',
                status: 404,
            );
        });

        $exceptions->render(function (\Illuminate\Validation\ValidationException $e, Request $request) {
            $errors = [];
            foreach ($e->errors() as $field => $messages) {
                foreach ($messages as $message) {
                    $errors[] = [
                        'code' => 'VALIDATION_ERROR',
                        'message' => $message,
                        'field' => $field,
                    ];
                }
            }

            return \App\Infrastructure\Shared\Http\Resources\ApiResponse::error($errors, 422);
        });
    })->create();
