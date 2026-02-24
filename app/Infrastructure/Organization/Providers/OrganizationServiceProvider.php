<?php

declare(strict_types=1);

namespace App\Infrastructure\Organization\Providers;

use App\Domain\Organization\Repositories\OrganizationInviteRepositoryInterface;
use App\Domain\Organization\Repositories\OrganizationMemberRepositoryInterface;
use App\Domain\Organization\Repositories\OrganizationRepositoryInterface;
use App\Infrastructure\Organization\Repositories\EloquentOrganizationInviteRepository;
use App\Infrastructure\Organization\Repositories\EloquentOrganizationMemberRepository;
use App\Infrastructure\Organization\Repositories\EloquentOrganizationRepository;
use Illuminate\Support\ServiceProvider;

final class OrganizationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(OrganizationRepositoryInterface::class, EloquentOrganizationRepository::class);
        $this->app->bind(OrganizationMemberRepositoryInterface::class, EloquentOrganizationMemberRepository::class);
        $this->app->bind(OrganizationInviteRepositoryInterface::class, EloquentOrganizationInviteRepository::class);
    }
}
