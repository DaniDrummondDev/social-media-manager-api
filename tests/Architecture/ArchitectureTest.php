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

// Application contracts are interfaces
arch('identity contracts are interfaces')
    ->expect('App\Application\Identity\Contracts')
    ->toBeInterfaces();

arch('shared contracts are interfaces')
    ->expect('App\Application\Shared\Contracts')
    ->toBeInterfaces();

// Jobs delegate to Use Cases — they must not contain domain logic
arch('jobs do not use domain directly')
    ->expect([
        'App\Infrastructure\Publishing\Jobs',
        'App\Infrastructure\Analytics\Jobs',
        'App\Infrastructure\Engagement\Jobs',
        'App\Infrastructure\Media\Jobs',
        'App\Infrastructure\Billing\Jobs',
        'App\Infrastructure\PlatformAdmin\Jobs',
        'App\Infrastructure\ContentAI\Jobs',
    ])
    ->not->toUse('App\Domain');
