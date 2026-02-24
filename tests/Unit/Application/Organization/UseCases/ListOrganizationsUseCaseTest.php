<?php

declare(strict_types=1);

use App\Application\Organization\DTOs\OrganizationListOutput;
use App\Application\Organization\UseCases\ListOrganizationsUseCase;
use App\Domain\Organization\Entities\Organization;
use App\Domain\Organization\Repositories\OrganizationRepositoryInterface;
use App\Domain\Organization\ValueObjects\OrganizationSlug;
use App\Domain\Organization\ValueObjects\OrganizationStatus;
use App\Domain\Shared\ValueObjects\Uuid;

beforeEach(function () {
    $this->organizationRepository = Mockery::mock(OrganizationRepositoryInterface::class);

    $this->useCase = new ListOrganizationsUseCase($this->organizationRepository);
});

it('returns list of organizations', function () {
    $org1 = Organization::reconstitute(
        id: Uuid::generate(),
        name: 'Org One',
        slug: OrganizationSlug::fromString('org-one'),
        timezone: 'America/Sao_Paulo',
        status: OrganizationStatus::Active,
        createdAt: new DateTimeImmutable,
        updatedAt: new DateTimeImmutable,
    );

    $org2 = Organization::reconstitute(
        id: Uuid::generate(),
        name: 'Org Two',
        slug: OrganizationSlug::fromString('org-two'),
        timezone: 'UTC',
        status: OrganizationStatus::Active,
        createdAt: new DateTimeImmutable,
        updatedAt: new DateTimeImmutable,
    );

    $this->organizationRepository->shouldReceive('listByUserId')->once()->andReturn([$org1, $org2]);

    $output = $this->useCase->execute('550e8400-e29b-41d4-a716-446655440000');

    expect($output)->toBeInstanceOf(OrganizationListOutput::class)
        ->and($output->organizations)->toHaveCount(2)
        ->and($output->organizations[0]->name)->toBe('Org One')
        ->and($output->organizations[1]->name)->toBe('Org Two');
});

it('returns empty list when no organizations', function () {
    $this->organizationRepository->shouldReceive('listByUserId')->once()->andReturn([]);

    $output = $this->useCase->execute('550e8400-e29b-41d4-a716-446655440000');

    expect($output)->toBeInstanceOf(OrganizationListOutput::class)
        ->and($output->organizations)->toBeEmpty();
});
