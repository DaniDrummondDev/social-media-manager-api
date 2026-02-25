<?php

declare(strict_types=1);

namespace App\Infrastructure\Engagement\Providers;

use App\Application\Engagement\Contracts\AiSuggestionInterface;
use App\Application\Engagement\Contracts\SocialEngagementFactoryInterface;
use App\Application\Engagement\Contracts\WebhookHttpClientInterface;
use App\Domain\Engagement\Events\CommentCaptured;
use App\Domain\Engagement\Events\CommentReplied;
use App\Domain\Engagement\Repositories\AutomationExecutionRepositoryInterface;
use App\Domain\Engagement\Repositories\AutomationRuleRepositoryInterface;
use App\Domain\Engagement\Repositories\BlacklistWordRepositoryInterface;
use App\Domain\Engagement\Repositories\CommentRepositoryInterface;
use App\Domain\Engagement\Repositories\WebhookDeliveryRepositoryInterface;
use App\Domain\Engagement\Repositories\WebhookEndpointRepositoryInterface;
use App\Infrastructure\Engagement\Listeners\DispatchWebhooksOnCommentCaptured;
use App\Infrastructure\Engagement\Listeners\DispatchWebhooksOnCommentReplied;
use App\Infrastructure\Engagement\Listeners\EvaluateAutomationOnCommentCaptured;
use App\Infrastructure\Engagement\Repositories\EloquentAutomationExecutionRepository;
use App\Infrastructure\Engagement\Repositories\EloquentAutomationRuleRepository;
use App\Infrastructure\Engagement\Repositories\EloquentBlacklistWordRepository;
use App\Infrastructure\Engagement\Repositories\EloquentCommentRepository;
use App\Infrastructure\Engagement\Repositories\EloquentWebhookDeliveryRepository;
use App\Infrastructure\Engagement\Repositories\EloquentWebhookEndpointRepository;
use App\Infrastructure\Engagement\Services\LaravelWebhookHttpClient;
use App\Infrastructure\Engagement\Services\SocialEngagementFactory;
use App\Infrastructure\Engagement\Services\StubAiSuggestion;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

final class EngagementServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(CommentRepositoryInterface::class, EloquentCommentRepository::class);
        $this->app->bind(AutomationRuleRepositoryInterface::class, EloquentAutomationRuleRepository::class);
        $this->app->bind(AutomationExecutionRepositoryInterface::class, EloquentAutomationExecutionRepository::class);
        $this->app->bind(BlacklistWordRepositoryInterface::class, EloquentBlacklistWordRepository::class);
        $this->app->bind(WebhookEndpointRepositoryInterface::class, EloquentWebhookEndpointRepository::class);
        $this->app->bind(WebhookDeliveryRepositoryInterface::class, EloquentWebhookDeliveryRepository::class);
        $this->app->bind(SocialEngagementFactoryInterface::class, SocialEngagementFactory::class);
        $this->app->bind(AiSuggestionInterface::class, StubAiSuggestion::class);
        $this->app->bind(WebhookHttpClientInterface::class, LaravelWebhookHttpClient::class);
    }

    public function boot(): void
    {
        Event::listen(CommentCaptured::class, EvaluateAutomationOnCommentCaptured::class);
        Event::listen(CommentCaptured::class, DispatchWebhooksOnCommentCaptured::class);
        Event::listen(CommentReplied::class, DispatchWebhooksOnCommentReplied::class);
    }
}
