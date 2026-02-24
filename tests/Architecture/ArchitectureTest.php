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

arch('domain does not depend on Eloquent')
    ->expect('App\Domain')
    ->not->toUse('Illuminate\Database\Eloquent');

// Application Layer must not depend on Infrastructure
arch('application does not depend on infrastructure')
    ->expect('App\Application')
    ->not->toUse('App\Infrastructure');

// No controllers outside Infrastructure
arch('no controllers in domain')
    ->expect('App\Domain')
    ->not->toHaveSuffix('Controller');

arch('no controllers in application')
    ->expect('App\Application')
    ->not->toHaveSuffix('Controller');

// Value Objects are final and readonly
arch('value objects are final readonly')
    ->expect('App\Domain\Shared\ValueObjects')
    ->classes()
    ->toBeFinal()
    ->toBeReadonly();

// Middleware classes are final
arch('middleware is final')
    ->expect('App\Infrastructure\Shared\Http\Middleware')
    ->classes()
    ->toBeFinal();
