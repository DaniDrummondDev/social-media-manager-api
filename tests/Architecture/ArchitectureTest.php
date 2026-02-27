<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Architecture Tests
|--------------------------------------------------------------------------
|
| These tests validate that the codebase follows the DDD + Clean Architecture
| rules defined in the project's architectural decisions.
|
| Rules:
|   - Domain Layer has no external dependencies
|   - Application Layer does not depend on Infrastructure
|   - Controllers live in Infrastructure
|   - Entities are final and readonly
|   - Jobs do not contain business logic (only call Use Cases)
|
*/

// Domain Layer must not depend on Application or Infrastructure
arch('domain does not depend on application')
    ->expect('App\Domain')
    ->not->toUse('App\Application');

arch('domain does not depend on infrastructure')
    ->expect('App\Domain')
    ->not->toUse('App\Infrastructure');

arch('domain does not depend on Illuminate')
    ->expect('App\Domain')
    ->not->toUse('Illuminate');

// Application Layer must not depend on Infrastructure or Illuminate
arch('application does not depend on infrastructure')
    ->expect('App\Application')
    ->not->toUse('App\Infrastructure');

arch('application does not depend on Illuminate')
    ->expect('App\Application')
    ->not->toUse('Illuminate');

// No controllers outside Infrastructure
arch('no controllers in domain')
    ->expect('App\Domain')
    ->not->toHaveSuffix('Controller');

arch('no controllers in application')
    ->expect('App\Application')
    ->not->toHaveSuffix('Controller');

// Value Objects (classes, not enums) are final and readonly
arch('value objects are final readonly')
    ->expect([
        'App\Domain\Shared\ValueObjects',
        'App\Domain\Identity\ValueObjects\Email',
        'App\Domain\Identity\ValueObjects\HashedPassword',
        'App\Domain\Identity\ValueObjects\TwoFactorSecret',
        'App\Domain\Organization\ValueObjects\OrganizationSlug',
        'App\Domain\SocialAccount\ValueObjects\EncryptedToken',
        'App\Domain\SocialAccount\ValueObjects\OAuthCredentials',
        'App\Domain\Media\ValueObjects\MimeType',
        'App\Domain\Media\ValueObjects\FileSize',
        'App\Domain\Media\ValueObjects\Dimensions',
        'App\Domain\Media\ValueObjects\Compatibility',
        'App\Domain\Publishing\ValueObjects\ScheduleTime',
        'App\Domain\Publishing\ValueObjects\PublishError',
        'App\Domain\Analytics\ValueObjects\MetricPeriod',
        'App\Domain\Engagement\ValueObjects\RuleCondition',
        'App\Domain\Engagement\ValueObjects\WebhookSecret',
        'App\Domain\Billing\ValueObjects\Money',
        'App\Domain\Billing\ValueObjects\PlanLimits',
        'App\Domain\Billing\ValueObjects\PlanFeatures',
        'App\Domain\ClientFinance\ValueObjects\TaxId',
        'App\Domain\ClientFinance\ValueObjects\Address',
        'App\Domain\ClientFinance\ValueObjects\YearMonth',
        'App\Domain\AIIntelligence\ValueObjects\TimeSlotScore',
        'App\Domain\AIIntelligence\ValueObjects\TopSlot',
        'App\Domain\AIIntelligence\ValueObjects\SafetyCheckResult',
        'App\Domain\AIIntelligence\ValueObjects\PredictionScore',
        'App\Domain\AIIntelligence\ValueObjects\CalendarItem',
        'App\Domain\ContentAI\ValueObjects\DiffSummary',
        'App\Domain\ContentAI\ValueObjects\PerformanceScore',
        'App\Domain\AIIntelligence\ValueObjects\PredictionAccuracy',
        'App\Domain\AIIntelligence\ValueObjects\StylePreferences',
        'App\Domain\Engagement\ValueObjects\CrmFieldMapping',
        'App\Domain\Engagement\ValueObjects\CrmSyncResult',
        'App\Domain\PaidAdvertising\ValueObjects\AdBudget',
        'App\Domain\PaidAdvertising\ValueObjects\DemographicFilter',
        'App\Domain\PaidAdvertising\ValueObjects\LocationFilter',
        'App\Domain\PaidAdvertising\ValueObjects\InterestFilter',
        'App\Domain\PaidAdvertising\ValueObjects\TargetingSpec',
        'App\Domain\PaidAdvertising\ValueObjects\AdAccountCredentials',
    ])
    ->classes()
    ->toBeFinal()
    ->toBeReadonly();

// Value Object enums are backed enums
arch('value object enums are enums')
    ->expect([
        'App\Domain\Identity\ValueObjects\UserStatus',
        'App\Domain\Organization\ValueObjects\OrganizationRole',
        'App\Domain\Organization\ValueObjects\OrganizationStatus',
        'App\Domain\SocialAccount\ValueObjects\SocialProvider',
        'App\Domain\SocialAccount\ValueObjects\ConnectionStatus',
        'App\Domain\Media\ValueObjects\MediaType',
        'App\Domain\Media\ValueObjects\ScanStatus',
        'App\Domain\Media\ValueObjects\UploadStatus',
        'App\Domain\Campaign\ValueObjects\ContentStatus',
        'App\Domain\Campaign\ValueObjects\CampaignStatus',
        'App\Domain\Publishing\ValueObjects\PublishingStatus',
        'App\Domain\Analytics\ValueObjects\ExportFormat',
        'App\Domain\Analytics\ValueObjects\ReportType',
        'App\Domain\Analytics\ValueObjects\ExportStatus',
        'App\Domain\Engagement\ValueObjects\Sentiment',
        'App\Domain\Engagement\ValueObjects\ActionType',
        'App\Domain\Engagement\ValueObjects\ConditionOperator',
        'App\Domain\Billing\ValueObjects\BillingCycle',
        'App\Domain\Billing\ValueObjects\SubscriptionStatus',
        'App\Domain\Billing\ValueObjects\InvoiceStatus',
        'App\Domain\Billing\ValueObjects\UsageResourceType',
        'App\Domain\Billing\ValueObjects\CancelFeedback',
        'App\Domain\PlatformAdmin\ValueObjects\PlatformRole',
        'App\Domain\ClientFinance\ValueObjects\Currency',
        'App\Domain\ClientFinance\ValueObjects\ClientStatus',
        'App\Domain\ClientFinance\ValueObjects\ContractType',
        'App\Domain\ClientFinance\ValueObjects\ContractStatus',
        'App\Domain\ClientFinance\ValueObjects\InvoiceStatus',
        'App\Domain\ClientFinance\ValueObjects\PaymentMethod',
        'App\Domain\ClientFinance\ValueObjects\ResourceType',
        'App\Domain\AIIntelligence\ValueObjects\ConfidenceLevel',
        'App\Domain\AIIntelligence\ValueObjects\SafetyStatus',
        'App\Domain\AIIntelligence\ValueObjects\SafetyCategory',
        'App\Domain\AIIntelligence\ValueObjects\SafetyRuleType',
        'App\Domain\AIIntelligence\ValueObjects\RuleSeverity',
        'App\Domain\AIIntelligence\ValueObjects\SuggestionStatus',
        'App\Domain\ContentAI\ValueObjects\GenerationType',
        'App\Domain\ContentAI\ValueObjects\FeedbackAction',
        'App\Domain\ContentAI\ValueObjects\ExperimentStatus',
        'App\Domain\AIIntelligence\ValueObjects\AttributionType',
        'App\Domain\AIIntelligence\ValueObjects\AdInsightType',
        'App\Domain\Engagement\ValueObjects\CrmProvider',
        'App\Domain\Engagement\ValueObjects\CrmConnectionStatus',
        'App\Domain\Engagement\ValueObjects\CrmSyncDirection',
        'App\Domain\Engagement\ValueObjects\CrmEntityType',
        'App\Domain\Engagement\ValueObjects\CrmSyncStatus',
        'App\Domain\PaidAdvertising\ValueObjects\AdProvider',
        'App\Domain\PaidAdvertising\ValueObjects\AdAccountStatus',
        'App\Domain\PaidAdvertising\ValueObjects\AdStatus',
        'App\Domain\PaidAdvertising\ValueObjects\AdObjective',
        'App\Domain\PaidAdvertising\ValueObjects\BudgetType',
        'App\Domain\PaidAdvertising\ValueObjects\MetricPeriod',
    ])
    ->toBeEnums();

// Repository interfaces are interfaces
arch('repository interfaces are interfaces')
    ->expect([
        'App\Domain\Identity\Repositories\UserRepositoryInterface',
        'App\Domain\Organization\Repositories\OrganizationRepositoryInterface',
        'App\Domain\Organization\Repositories\OrganizationMemberRepositoryInterface',
        'App\Domain\Organization\Repositories\OrganizationInviteRepositoryInterface',
        'App\Domain\SocialAccount\Repositories\SocialAccountRepositoryInterface',
        'App\Domain\Media\Repositories\MediaRepositoryInterface',
        'App\Domain\Media\Repositories\MediaUploadRepositoryInterface',
        'App\Domain\Campaign\Contracts\ContentRepositoryInterface',
        'App\Domain\Campaign\Contracts\CampaignRepositoryInterface',
        'App\Domain\Publishing\Contracts\ScheduledPostRepositoryInterface',
        'App\Domain\Analytics\Repositories\ContentMetricRepositoryInterface',
        'App\Domain\Analytics\Repositories\ContentMetricSnapshotRepositoryInterface',
        'App\Domain\Analytics\Repositories\AccountMetricRepositoryInterface',
        'App\Domain\Analytics\Repositories\ReportExportRepositoryInterface',
        'App\Domain\Engagement\Repositories\CommentRepositoryInterface',
        'App\Domain\Engagement\Repositories\AutomationRuleRepositoryInterface',
        'App\Domain\Engagement\Repositories\AutomationExecutionRepositoryInterface',
        'App\Domain\Engagement\Repositories\BlacklistWordRepositoryInterface',
        'App\Domain\Engagement\Repositories\WebhookEndpointRepositoryInterface',
        'App\Domain\Engagement\Repositories\WebhookDeliveryRepositoryInterface',
        'App\Domain\Billing\Repositories\PlanRepositoryInterface',
        'App\Domain\Billing\Repositories\SubscriptionRepositoryInterface',
        'App\Domain\Billing\Repositories\UsageRecordRepositoryInterface',
        'App\Domain\Billing\Repositories\InvoiceRepositoryInterface',
        'App\Domain\Billing\Repositories\StripeWebhookEventRepositoryInterface',
        'App\Domain\PlatformAdmin\Repositories\PlatformAdminRepositoryInterface',
        'App\Domain\PlatformAdmin\Repositories\SystemConfigRepositoryInterface',
        'App\Domain\PlatformAdmin\Repositories\AdminAuditEntryRepositoryInterface',
        'App\Domain\PlatformAdmin\Repositories\PlatformMetricsCacheRepositoryInterface',
        'App\Domain\ClientFinance\Repositories\ClientRepositoryInterface',
        'App\Domain\ClientFinance\Repositories\ClientContractRepositoryInterface',
        'App\Domain\ClientFinance\Repositories\ClientInvoiceRepositoryInterface',
        'App\Domain\ClientFinance\Repositories\CostAllocationRepositoryInterface',
        'App\Domain\AIIntelligence\Repositories\PostingTimeRecommendationRepositoryInterface',
        'App\Domain\AIIntelligence\Repositories\BrandSafetyCheckRepositoryInterface',
        'App\Domain\AIIntelligence\Repositories\BrandSafetyRuleRepositoryInterface',
        'App\Domain\AIIntelligence\Repositories\CalendarSuggestionRepositoryInterface',
        'App\Domain\AIIntelligence\Repositories\ContentProfileRepositoryInterface',
        'App\Domain\AIIntelligence\Repositories\PerformancePredictionRepositoryInterface',
        'App\Domain\AIIntelligence\Repositories\PredictionValidationRepositoryInterface',
        'App\Domain\AIIntelligence\Repositories\OrgStyleProfileRepositoryInterface',
        'App\Domain\AIIntelligence\Repositories\AudienceInsightRepositoryInterface',
        'App\Domain\AIIntelligence\Repositories\ContentGapAnalysisRepositoryInterface',
        'App\Domain\AIIntelligence\Repositories\CrmConversionAttributionRepositoryInterface',
        'App\Domain\AIIntelligence\Repositories\AdPerformanceInsightRepositoryInterface',
        'App\Domain\ContentAI\Contracts\AIGenerationRepositoryInterface',
        'App\Domain\ContentAI\Contracts\AISettingsRepositoryInterface',
        'App\Domain\ContentAI\Contracts\GenerationFeedbackRepositoryInterface',
        'App\Domain\ContentAI\Contracts\PromptTemplateRepositoryInterface',
        'App\Domain\ContentAI\Contracts\PromptExperimentRepositoryInterface',
        'App\Domain\Engagement\Repositories\CrmConnectionRepositoryInterface',
        'App\Domain\Engagement\Repositories\CrmFieldMappingRepositoryInterface',
        'App\Domain\Engagement\Repositories\CrmSyncLogRepositoryInterface',
        'App\Domain\PaidAdvertising\Repositories\AdAccountRepositoryInterface',
        'App\Domain\PaidAdvertising\Repositories\AudienceRepositoryInterface',
        'App\Domain\PaidAdvertising\Repositories\AdBoostRepositoryInterface',
        'App\Domain\PaidAdvertising\Repositories\AdMetricSnapshotRepositoryInterface',
    ])
    ->toBeInterfaces();

// Domain contracts (adapter interfaces) are interfaces
arch('social account contracts are interfaces')
    ->expect('App\Domain\SocialAccount\Contracts')
    ->toBeInterfaces();

arch('engagement domain contracts are interfaces')
    ->expect('App\Domain\Engagement\Contracts')
    ->toBeInterfaces();

arch('billing contracts are interfaces')
    ->expect('App\Domain\Billing\Contracts')
    ->toBeInterfaces();

arch('platform admin domain contracts are interfaces')
    ->expect('App\Domain\PlatformAdmin\Contracts')
    ->toBeInterfaces();

arch('ai intelligence domain contracts are interfaces')
    ->expect('App\Domain\AIIntelligence\Contracts')
    ->toBeInterfaces();

arch('paid advertising domain contracts are interfaces')
    ->expect('App\Domain\PaidAdvertising\Contracts')
    ->toBeInterfaces();

// Exceptions extend DomainException
arch('domain exceptions extend DomainException')
    ->expect([
        'App\Domain\Identity\Exceptions',
        'App\Domain\Organization\Exceptions',
        'App\Domain\SocialAccount\Exceptions',
        'App\Domain\Media\Exceptions',
        'App\Domain\Campaign\Exceptions',
        'App\Domain\Publishing\Exceptions',
        'App\Domain\Analytics\Exceptions',
        'App\Domain\Engagement\Exceptions',
        'App\Domain\Billing\Exceptions',
        'App\Domain\PlatformAdmin\Exceptions',
        'App\Domain\ClientFinance\Exceptions',
        'App\Domain\SocialListening\Exceptions',
        'App\Domain\ContentAI\Exceptions',
        'App\Domain\AIIntelligence\Exceptions',
        'App\Domain\PaidAdvertising\Exceptions',
    ])
    ->classes()
    ->toExtend('App\Domain\Shared\Exceptions\DomainException');

// Domain Events extend DomainEvent
arch('domain events extend DomainEvent')
    ->expect([
        'App\Domain\Identity\Events',
        'App\Domain\Organization\Events',
        'App\Domain\SocialAccount\Events',
        'App\Domain\Media\Events',
        'App\Domain\Publishing\Events',
        'App\Domain\Analytics\Events',
        'App\Domain\Engagement\Events',
        'App\Domain\Billing\Events',
        'App\Domain\PlatformAdmin\Events',
        'App\Domain\ClientFinance\Events',
        'App\Domain\SocialListening\Events',
        'App\Domain\ContentAI\Events',
        'App\Domain\AIIntelligence\Events',
        'App\Domain\PaidAdvertising\Events',
    ])
    ->classes()
    ->toExtend('App\Domain\Shared\Events\DomainEvent');

// Middleware classes are final
arch('middleware is final')
    ->expect('App\Infrastructure\Shared\Http\Middleware')
    ->classes()
    ->toBeFinal();

// Entities are final and readonly
arch('entities are final readonly')
    ->expect([
        'App\Domain\Identity\Entities',
        'App\Domain\Organization\Entities',
        'App\Domain\SocialAccount\Entities',
        'App\Domain\Campaign\Entities',
        'App\Domain\ContentAI\Entities',
        'App\Domain\Publishing\Entities',
        'App\Domain\Analytics\Entities',
        'App\Domain\Engagement\Entities',
        'App\Domain\Media\Entities',
        'App\Domain\Billing\Entities',
        'App\Domain\PlatformAdmin\Entities',
        'App\Domain\ClientFinance\Entities',
        'App\Domain\SocialListening\Entities',
        'App\Domain\AIIntelligence\Entities',
        'App\Domain\PaidAdvertising\Entities',
    ])
    ->classes()
    ->toBeFinal()
    ->toBeReadonly();

// Application Use Cases are final
arch('identity use cases are final')
    ->expect('App\Application\Identity\UseCases')
    ->classes()
    ->toBeFinal();

arch('organization use cases are final')
    ->expect('App\Application\Organization\UseCases')
    ->classes()
    ->toBeFinal();

arch('socialaccount use cases are final')
    ->expect('App\Application\SocialAccount\UseCases')
    ->classes()
    ->toBeFinal();

arch('media use cases are final')
    ->expect('App\Application\Media\UseCases')
    ->classes()
    ->toBeFinal();

// Application DTOs are final and readonly
arch('identity DTOs are final readonly')
    ->expect('App\Application\Identity\DTOs')
    ->classes()
    ->toBeFinal()
    ->toBeReadonly();

arch('organization DTOs are final readonly')
    ->expect('App\Application\Organization\DTOs')
    ->classes()
    ->toBeFinal()
    ->toBeReadonly();

arch('socialaccount DTOs are final readonly')
    ->expect('App\Application\SocialAccount\DTOs')
    ->classes()
    ->toBeFinal()
    ->toBeReadonly();

arch('media DTOs are final readonly')
    ->expect('App\Application\Media\DTOs')
    ->classes()
    ->toBeFinal()
    ->toBeReadonly();

// Application contracts are interfaces
arch('identity contracts are interfaces')
    ->expect('App\Application\Identity\Contracts')
    ->toBeInterfaces();

arch('shared contracts are interfaces')
    ->expect('App\Application\Shared\Contracts')
    ->toBeInterfaces();

arch('socialaccount app contracts are interfaces')
    ->expect('App\Application\SocialAccount\Contracts')
    ->toBeInterfaces();

arch('media app contracts are interfaces')
    ->expect('App\Application\Media\Contracts')
    ->toBeInterfaces();

// Application exceptions extend ApplicationException
arch('socialaccount app exceptions extend ApplicationException')
    ->expect('App\Application\SocialAccount\Exceptions')
    ->classes()
    ->toExtend('App\Application\Shared\Exceptions\ApplicationException');

arch('media app exceptions extend ApplicationException')
    ->expect('App\Application\Media\Exceptions')
    ->classes()
    ->toExtend('App\Application\Shared\Exceptions\ApplicationException');

// Infrastructure Models are final
arch('socialaccount models are final')
    ->expect('App\Infrastructure\SocialAccount\Models')
    ->classes()
    ->toBeFinal();

arch('media models are final')
    ->expect('App\Infrastructure\Media\Models')
    ->classes()
    ->toBeFinal();

// Infrastructure Controllers are final
arch('socialaccount controllers are final')
    ->expect('App\Infrastructure\SocialAccount\Controllers')
    ->classes()
    ->toBeFinal();

arch('media controllers are final')
    ->expect('App\Infrastructure\Media\Controllers')
    ->classes()
    ->toBeFinal();

// Infrastructure Providers are final
arch('socialaccount providers are final')
    ->expect('App\Infrastructure\SocialAccount\Providers')
    ->classes()
    ->toBeFinal();

arch('media providers are final')
    ->expect('App\Infrastructure\Media\Providers')
    ->classes()
    ->toBeFinal();

// Infrastructure Resources are final readonly
arch('socialaccount resources are final readonly')
    ->expect('App\Infrastructure\SocialAccount\Resources')
    ->classes()
    ->toBeFinal()
    ->toBeReadonly();

arch('media resources are final readonly')
    ->expect('App\Infrastructure\Media\Resources')
    ->classes()
    ->toBeFinal()
    ->toBeReadonly();

// Publishing
arch('publishing use cases are final')
    ->expect('App\Application\Publishing\UseCases')
    ->classes()
    ->toBeFinal();

arch('publishing DTOs are final readonly')
    ->expect('App\Application\Publishing\DTOs')
    ->classes()
    ->toBeFinal()
    ->toBeReadonly();

arch('publishing app contracts are interfaces')
    ->expect('App\Application\Publishing\Contracts')
    ->toBeInterfaces();

arch('publishing app exceptions extend ApplicationException')
    ->expect('App\Application\Publishing\Exceptions')
    ->classes()
    ->toExtend('App\Application\Shared\Exceptions\ApplicationException');

arch('publishing models are final')
    ->expect('App\Infrastructure\Publishing\Models')
    ->classes()
    ->toBeFinal();

arch('publishing controllers are final')
    ->expect('App\Infrastructure\Publishing\Controllers')
    ->classes()
    ->toBeFinal();

arch('publishing providers are final')
    ->expect('App\Infrastructure\Publishing\Providers')
    ->classes()
    ->toBeFinal();

arch('publishing resources are final readonly')
    ->expect('App\Infrastructure\Publishing\Resources')
    ->classes()
    ->toBeFinal()
    ->toBeReadonly();

// Campaign
arch('campaign use cases are final')
    ->expect('App\Application\Campaign\UseCases')
    ->classes()
    ->toBeFinal();

arch('campaign DTOs are final readonly')
    ->expect('App\Application\Campaign\DTOs')
    ->classes()
    ->toBeFinal()
    ->toBeReadonly();

arch('campaign models are final')
    ->expect('App\Infrastructure\Campaign\Models')
    ->classes()
    ->toBeFinal();

arch('campaign controllers are final')
    ->expect('App\Infrastructure\Campaign\Controllers')
    ->classes()
    ->toBeFinal();

arch('campaign providers are final')
    ->expect('App\Infrastructure\Campaign\Providers')
    ->classes()
    ->toBeFinal();

arch('campaign resources are final readonly')
    ->expect('App\Infrastructure\Campaign\Resources')
    ->classes()
    ->toBeFinal()
    ->toBeReadonly();

// Analytics
arch('analytics use cases are final')
    ->expect('App\Application\Analytics\UseCases')
    ->classes()
    ->toBeFinal();

arch('analytics DTOs are final readonly')
    ->expect('App\Application\Analytics\DTOs')
    ->classes()
    ->toBeFinal()
    ->toBeReadonly();

arch('analytics app contracts are interfaces')
    ->expect('App\Application\Analytics\Contracts')
    ->toBeInterfaces();

arch('analytics app exceptions extend ApplicationException')
    ->expect('App\Application\Analytics\Exceptions')
    ->classes()
    ->toExtend('App\Application\Shared\Exceptions\ApplicationException');

arch('analytics models are final')
    ->expect('App\Infrastructure\Analytics\Models')
    ->classes()
    ->toBeFinal();

arch('analytics controllers are final')
    ->expect('App\Infrastructure\Analytics\Controllers')
    ->classes()
    ->toBeFinal();

arch('analytics providers are final')
    ->expect('App\Infrastructure\Analytics\Providers')
    ->classes()
    ->toBeFinal();

arch('analytics resources are final readonly')
    ->expect('App\Infrastructure\Analytics\Resources')
    ->classes()
    ->toBeFinal()
    ->toBeReadonly();

// Engagement
arch('engagement use cases are final')
    ->expect('App\Application\Engagement\UseCases')
    ->classes()
    ->toBeFinal();

arch('engagement DTOs are final readonly')
    ->expect('App\Application\Engagement\DTOs')
    ->classes()
    ->toBeFinal()
    ->toBeReadonly();

arch('engagement app contracts are interfaces')
    ->expect('App\Application\Engagement\Contracts')
    ->toBeInterfaces();

arch('engagement app exceptions extend ApplicationException')
    ->expect('App\Application\Engagement\Exceptions')
    ->classes()
    ->toExtend('App\Application\Shared\Exceptions\ApplicationException');

arch('engagement models are final')
    ->expect('App\Infrastructure\Engagement\Models')
    ->classes()
    ->toBeFinal();

arch('engagement controllers are final')
    ->expect('App\Infrastructure\Engagement\Controllers')
    ->classes()
    ->toBeFinal();

arch('engagement providers are final')
    ->expect('App\Infrastructure\Engagement\Providers')
    ->classes()
    ->toBeFinal();

arch('engagement resources are final readonly')
    ->expect('App\Infrastructure\Engagement\Resources')
    ->classes()
    ->toBeFinal()
    ->toBeReadonly();

// Billing
arch('billing use cases are final')
    ->expect('App\Application\Billing\UseCases')
    ->classes()
    ->toBeFinal();

arch('billing DTOs are final readonly')
    ->expect('App\Application\Billing\DTOs')
    ->classes()
    ->toBeFinal()
    ->toBeReadonly();

arch('billing app exceptions extend ApplicationException')
    ->expect('App\Application\Billing\Exceptions')
    ->classes()
    ->toExtend('App\Application\Shared\Exceptions\ApplicationException');

arch('billing models are final')
    ->expect('App\Infrastructure\Billing\Models')
    ->classes()
    ->toBeFinal();

arch('billing controllers are final')
    ->expect('App\Infrastructure\Billing\Controllers')
    ->classes()
    ->toBeFinal();

arch('billing providers are final')
    ->expect('App\Infrastructure\Billing\Providers')
    ->classes()
    ->toBeFinal();

arch('billing resources are final readonly')
    ->expect('App\Infrastructure\Billing\Resources')
    ->classes()
    ->toBeFinal()
    ->toBeReadonly();

// Jobs delegate to Use Cases — they must not contain domain logic
arch('jobs do not use domain directly')
    ->expect([
        'App\Infrastructure\Publishing\Jobs',
        'App\Infrastructure\Analytics\Jobs',
        'App\Infrastructure\Engagement\Jobs',
        'App\Infrastructure\Billing\Jobs',
        'App\Infrastructure\PlatformAdmin\Jobs',
        'App\Infrastructure\ContentAI\Jobs',
        'App\Infrastructure\ClientFinance\Jobs',
        'App\Infrastructure\SocialListening\Jobs',
        'App\Infrastructure\AIIntelligence\Jobs',
    ])
    ->not->toUse('App\Domain');

// Platform Admin
arch('platform admin use cases are final')
    ->expect('App\Application\PlatformAdmin\UseCases')
    ->classes()
    ->toBeFinal();

arch('platform admin DTOs are final readonly')
    ->expect('App\Application\PlatformAdmin\DTOs')
    ->classes()
    ->toBeFinal()
    ->toBeReadonly();

arch('platform admin app contracts are interfaces')
    ->expect('App\Application\PlatformAdmin\Contracts')
    ->toBeInterfaces();

arch('platform admin app exceptions extend ApplicationException')
    ->expect('App\Application\PlatformAdmin\Exceptions')
    ->classes()
    ->toExtend('App\Application\Shared\Exceptions\ApplicationException');

arch('platform admin models are final')
    ->expect('App\Infrastructure\PlatformAdmin\Models')
    ->classes()
    ->toBeFinal();

arch('platform admin controllers are final')
    ->expect('App\Infrastructure\PlatformAdmin\Controllers')
    ->classes()
    ->toBeFinal();

arch('platform admin providers are final')
    ->expect('App\Infrastructure\PlatformAdmin\Providers')
    ->classes()
    ->toBeFinal();

arch('platform admin resources are final readonly')
    ->expect('App\Infrastructure\PlatformAdmin\Resources')
    ->classes()
    ->toBeFinal()
    ->toBeReadonly();

// Client Finance
arch('client finance domain services are final readonly')
    ->expect('App\Domain\ClientFinance\Services')
    ->classes()
    ->toBeFinal()
    ->toBeReadonly();

arch('client finance use cases are final')
    ->expect('App\Application\ClientFinance\UseCases')
    ->classes()
    ->toBeFinal();

arch('client finance DTOs are final readonly')
    ->expect('App\Application\ClientFinance\DTOs')
    ->classes()
    ->toBeFinal()
    ->toBeReadonly();

arch('client finance app exceptions extend ApplicationException')
    ->expect('App\Application\ClientFinance\Exceptions')
    ->classes()
    ->toExtend('App\Application\Shared\Exceptions\ApplicationException');

arch('client finance models are final')
    ->expect('App\Infrastructure\ClientFinance\Models')
    ->classes()
    ->toBeFinal();

arch('client finance controllers are final')
    ->expect('App\Infrastructure\ClientFinance\Controllers')
    ->classes()
    ->toBeFinal();

arch('client finance providers are final')
    ->expect('App\Infrastructure\ClientFinance\Providers')
    ->classes()
    ->toBeFinal();

arch('client finance resources are final readonly')
    ->expect('App\Infrastructure\ClientFinance\Resources')
    ->classes()
    ->toBeFinal()
    ->toBeReadonly();

arch('client finance requests are final')
    ->expect('App\Infrastructure\ClientFinance\Requests')
    ->classes()
    ->toBeFinal();

// Social Listening
arch('social listening domain services are final readonly')
    ->expect('App\Domain\SocialListening\Services')
    ->classes()
    ->toBeFinal()
    ->toBeReadonly();

arch('social listening use cases are final')
    ->expect('App\Application\SocialListening\UseCases')
    ->classes()
    ->toBeFinal();

arch('social listening DTOs are final readonly')
    ->expect('App\Application\SocialListening\DTOs')
    ->classes()
    ->toBeFinal()
    ->toBeReadonly();

arch('social listening app contracts are interfaces')
    ->expect('App\Application\SocialListening\Contracts')
    ->toBeInterfaces();

arch('social listening app exceptions extend ApplicationException')
    ->expect('App\Application\SocialListening\Exceptions')
    ->classes()
    ->toExtend('App\Application\Shared\Exceptions\ApplicationException');

arch('social listening models are final')
    ->expect('App\Infrastructure\SocialListening\Models')
    ->classes()
    ->toBeFinal();

arch('social listening controllers are final')
    ->expect('App\Infrastructure\SocialListening\Controllers')
    ->classes()
    ->toBeFinal();

arch('social listening providers are final')
    ->expect('App\Infrastructure\SocialListening\Providers')
    ->classes()
    ->toBeFinal();

arch('social listening resources are final readonly')
    ->expect('App\Infrastructure\SocialListening\Resources')
    ->classes()
    ->toBeFinal()
    ->toBeReadonly();

arch('social listening requests are final')
    ->expect('App\Infrastructure\SocialListening\Requests')
    ->classes()
    ->toBeFinal();

// AI Intelligence
arch('ai intelligence use cases are final')
    ->expect('App\Application\AIIntelligence\UseCases')
    ->classes()
    ->toBeFinal();

arch('ai intelligence DTOs are final readonly')
    ->expect('App\Application\AIIntelligence\DTOs')
    ->classes()
    ->toBeFinal()
    ->toBeReadonly();

arch('ai intelligence app contracts are interfaces')
    ->expect('App\Application\AIIntelligence\Contracts')
    ->toBeInterfaces();

arch('ai intelligence app exceptions extend ApplicationException')
    ->expect('App\Application\AIIntelligence\Exceptions')
    ->classes()
    ->toExtend('App\Application\Shared\Exceptions\ApplicationException');

arch('ai intelligence models are final')
    ->expect('App\Infrastructure\AIIntelligence\Models')
    ->classes()
    ->toBeFinal();

arch('ai intelligence controllers are final')
    ->expect('App\Infrastructure\AIIntelligence\Controllers')
    ->classes()
    ->toBeFinal();

arch('ai intelligence providers are final')
    ->expect('App\Infrastructure\AIIntelligence\Providers')
    ->classes()
    ->toBeFinal();

arch('ai intelligence resources are final readonly')
    ->expect('App\Infrastructure\AIIntelligence\Resources')
    ->classes()
    ->toBeFinal()
    ->toBeReadonly();

arch('ai intelligence requests are final')
    ->expect('App\Infrastructure\AIIntelligence\Requests')
    ->classes()
    ->toBeFinal();

// Content AI
arch('content ai use cases are final')
    ->expect('App\Application\ContentAI\UseCases')
    ->classes()
    ->toBeFinal();

arch('content ai DTOs are final readonly')
    ->expect('App\Application\ContentAI\DTOs')
    ->classes()
    ->toBeFinal()
    ->toBeReadonly();

arch('content ai app contracts are interfaces')
    ->expect('App\Application\ContentAI\Contracts')
    ->toBeInterfaces();

arch('content ai models are final')
    ->expect('App\Infrastructure\ContentAI\Models')
    ->classes()
    ->toBeFinal();

arch('content ai controllers are final')
    ->expect('App\Infrastructure\ContentAI\Controllers')
    ->classes()
    ->toBeFinal();

arch('content ai providers are final')
    ->expect('App\Infrastructure\ContentAI\Providers')
    ->classes()
    ->toBeFinal();

arch('content ai resources are final readonly')
    ->expect('App\Infrastructure\ContentAI\Resources')
    ->classes()
    ->toBeFinal()
    ->toBeReadonly();

arch('content ai requests are final')
    ->expect('App\Infrastructure\ContentAI\Requests')
    ->classes()
    ->toBeFinal();

// Paid Advertising
arch('paid advertising use cases are final')
    ->expect('App\Application\PaidAdvertising\UseCases')
    ->classes()
    ->toBeFinal();

arch('paid advertising DTOs are final readonly')
    ->expect('App\Application\PaidAdvertising\DTOs')
    ->classes()
    ->toBeFinal()
    ->toBeReadonly();

arch('paid advertising app contracts are interfaces')
    ->expect('App\Application\PaidAdvertising\Contracts')
    ->toBeInterfaces();

arch('paid advertising app exceptions extend ApplicationException')
    ->expect('App\Application\PaidAdvertising\Exceptions')
    ->classes()
    ->toExtend('App\Application\Shared\Exceptions\ApplicationException');

arch('paid advertising models are final')
    ->expect('App\Infrastructure\PaidAdvertising\Models')
    ->classes()
    ->toBeFinal();

arch('paid advertising controllers are final')
    ->expect('App\Infrastructure\PaidAdvertising\Controllers')
    ->classes()
    ->toBeFinal();

arch('paid advertising resources are final readonly')
    ->expect('App\Infrastructure\PaidAdvertising\Resources')
    ->classes()
    ->toBeFinal()
    ->toBeReadonly();

arch('paid advertising requests are final')
    ->expect('App\Infrastructure\PaidAdvertising\Requests')
    ->classes()
    ->toBeFinal();
