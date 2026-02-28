<?php

use App\Infrastructure\Identity\Middleware\Authenticate;
use App\Infrastructure\Organization\Middleware\CheckRole;
use App\Infrastructure\Organization\Middleware\ResolveOrganizationContext;
use App\Infrastructure\Shared\Http\Middleware\ForceJsonResponse;
use App\Infrastructure\Shared\Http\Middleware\SecurityHeaders;
use App\Infrastructure\Shared\Http\Middleware\SetCorrelationId;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withProviders([
        App\Infrastructure\Shared\Providers\SharedServiceProvider::class,
        App\Infrastructure\Identity\Providers\IdentityServiceProvider::class,
        App\Infrastructure\Organization\Providers\OrganizationServiceProvider::class,
        App\Infrastructure\SocialAccount\Providers\SocialAccountServiceProvider::class,
        App\Infrastructure\Media\Providers\MediaServiceProvider::class,
        App\Infrastructure\Campaign\Providers\CampaignServiceProvider::class,
        App\Infrastructure\ContentAI\Providers\ContentAIServiceProvider::class,
        App\Infrastructure\Publishing\Providers\PublishingServiceProvider::class,
        App\Infrastructure\Analytics\Providers\AnalyticsServiceProvider::class,
        App\Infrastructure\Engagement\Providers\EngagementServiceProvider::class,
        App\Infrastructure\Billing\Providers\BillingServiceProvider::class,
        App\Infrastructure\PlatformAdmin\Providers\PlatformAdminServiceProvider::class,
        App\Infrastructure\ClientFinance\Providers\ClientFinanceServiceProvider::class,
        App\Infrastructure\SocialListening\Providers\SocialListeningServiceProvider::class,
        App\Infrastructure\AIIntelligence\Providers\AIIntelligenceServiceProvider::class,
        App\Infrastructure\PaidAdvertising\Providers\PaidAdvertisingServiceProvider::class,
    ])
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
                    require __DIR__.'/../routes/api/v1/organizations.php';
                    require __DIR__.'/../routes/api/v1/social-accounts.php';
                    require __DIR__.'/../routes/api/v1/media.php';
                    require __DIR__.'/../routes/api/v1/campaigns.php';
                    require __DIR__.'/../routes/api/v1/ai.php';
                    require __DIR__.'/../routes/api/v1/publishing.php';
                    require __DIR__.'/../routes/api/v1/analytics.php';
                    require __DIR__.'/../routes/api/v1/engagement.php';
                    require __DIR__.'/../routes/api/v1/billing.php';
                    require __DIR__.'/../routes/api/v1/admin.php';
                    require __DIR__.'/../routes/api/v1/client-finance.php';
                    require __DIR__.'/../routes/api/v1/social-listening.php';
                    require __DIR__.'/../routes/api/v1/ai-intelligence.php';
                    require __DIR__.'/../routes/api/v1/crm.php';
                    require __DIR__.'/../routes/api/v1/ads.php';
                });
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->api(prepend: [
            ForceJsonResponse::class,
            SetCorrelationId::class,
            SecurityHeaders::class,
        ]);

        $middleware->alias([
            'auth.jwt' => Authenticate::class,
            'org.context' => ResolveOrganizationContext::class,
            'tenant.rls' => \App\Infrastructure\Shared\Http\Middleware\SetTenantContext::class,
            'role' => CheckRole::class,
            'plan.limit' => \App\Infrastructure\Billing\Middleware\CheckPlanLimit::class,
            'admin' => \App\Infrastructure\PlatformAdmin\Middleware\PlatformAdminMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Application-layer authentication errors → 401
        $exceptions->render(function (\App\Application\Identity\Exceptions\AuthenticationException $e, Request $request) {
            return \App\Infrastructure\Shared\Http\Resources\ApiResponse::fail(
                code: $e->errorCode,
                message: $e->getMessage(),
                status: 401,
            );
        });

        // Application-layer authorization errors → 403
        $exceptions->render(function (\App\Application\Organization\Exceptions\AuthorizationException $e, Request $request) {
            return \App\Infrastructure\Shared\Http\Resources\ApiResponse::fail(
                code: $e->errorCode,
                message: $e->getMessage(),
                status: 403,
            );
        });

        // Insufficient admin privilege → 403
        $exceptions->render(function (\App\Application\PlatformAdmin\Exceptions\InsufficientAdminPrivilegeException $e, Request $request) {
            return \App\Infrastructure\Shared\Http\Resources\ApiResponse::fail(
                code: $e->errorCode,
                message: $e->getMessage(),
                status: 403,
            );
        });

        // Plan limit exceeded → 402
        $exceptions->render(function (\App\Application\Billing\Exceptions\PlanLimitExceededException $e, Request $request) {
            return \App\Infrastructure\Shared\Http\Resources\ApiResponse::fail(
                code: $e->errorCode,
                message: $e->getMessage(),
                status: 402,
            );
        });

        // Application-layer general errors → 422
        $exceptions->render(function (\App\Application\Shared\Exceptions\ApplicationException $e, Request $request) {
            return \App\Infrastructure\Shared\Http\Resources\ApiResponse::fail(
                code: $e->errorCode,
                message: $e->getMessage(),
                status: 422,
            );
        });

        // Domain-layer errors → 422
        $exceptions->render(function (\App\Domain\Shared\Exceptions\DomainException $e, Request $request) {
            return \App\Infrastructure\Shared\Http\Resources\ApiResponse::fail(
                code: $e->errorCode,
                message: $e->getMessage(),
                status: 422,
            );
        });

        // Laravel authentication → 401
        $exceptions->render(function (\Illuminate\Auth\AuthenticationException $e, Request $request) {
            return \App\Infrastructure\Shared\Http\Resources\ApiResponse::fail(
                code: 'AUTHENTICATION_ERROR',
                message: 'Unauthenticated.',
                status: 401,
            );
        });

        // Laravel authorization → 403
        $exceptions->render(function (\Illuminate\Auth\Access\AuthorizationException $e, Request $request) {
            return \App\Infrastructure\Shared\Http\Resources\ApiResponse::fail(
                code: 'AUTHORIZATION_ERROR',
                message: $e->getMessage(),
                status: 403,
            );
        });

        // Not found → 404
        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e, Request $request) {
            return \App\Infrastructure\Shared\Http\Resources\ApiResponse::fail(
                code: 'RESOURCE_NOT_FOUND',
                message: 'Resource not found.',
                status: 404,
            );
        });

        // Validation → 422
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

        // Catch-all for unhandled exceptions → 500 (generic, no internal details leaked)
        $exceptions->render(function (\Throwable $e, Request $request) {
            report($e);

            return \App\Infrastructure\Shared\Http\Resources\ApiResponse::fail(
                code: 'INTERNAL_ERROR',
                message: 'An unexpected error occurred.',
                status: 500,
            );
        });
    })->create();
