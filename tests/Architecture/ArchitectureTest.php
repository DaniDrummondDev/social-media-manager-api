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
    ])
    ->toBeInterfaces();

// Domain contracts (adapter interfaces) are interfaces
arch('social account contracts are interfaces')
    ->expect('App\Domain\SocialAccount\Contracts')
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

// Jobs delegate to Use Cases — they must not contain domain logic
arch('jobs do not use domain directly')
    ->expect([
        'App\Infrastructure\Publishing\Jobs',
        'App\Infrastructure\Analytics\Jobs',
        'App\Infrastructure\Engagement\Jobs',
        'App\Infrastructure\Billing\Jobs',
        'App\Infrastructure\PlatformAdmin\Jobs',
        'App\Infrastructure\ContentAI\Jobs',
    ])
    ->not->toUse('App\Domain');
