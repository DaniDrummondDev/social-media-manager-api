<?php

declare(strict_types=1);

use App\Application\Organization\DTOs\CreateOrganizationInput;
use App\Application\Organization\DTOs\OrganizationOutput;
use App\Application\Organization\UseCases\CreateOrganizationUseCase;
use App\Application\Shared\Contracts\EventDispatcherInterface;
use App\Domain\Organization\Repositories\OrganizationMemberRepositoryInterface;
use App\Domain\Organization\Repositories\OrganizationRepositoryInterface;

beforeEach(function () {
    $this->organizationRepository = Mockery::mock(OrganizationRepositoryInterface::class);
    $this->memberRepository = Mockery::mock(OrganizationMemberRepositoryInterface::class);
    $this->eventDispatcher = Mockery::mock(EventDispatcherInterface::class);

    $this->useCase = new CreateOrganizationUseCase(
        $this->organizationRepository,
        $this->memberRepository,
        $this->eventDispatcher,
    );
});

it('creates organization with owner member', function () {
    $this->organizationRepository->shouldReceive('create')->once();
    $this->memberRepository->shouldReceive('create')->once();
    $this->eventDispatcher->shouldReceive('dispatch')->once();

    $input = new CreateOrganizationInput(
        userId: '550e8400-e29b-41d4-a716-446655440000',
        name: 'My Company',
        slug: 'my-company',
        timezone: 'America/Sao_Paulo',
    );

    $output = $this->useCase->execute($input);

    expect($output)->toBeInstanceOf(OrganizationOutput::class)
        ->and($output->name)->toBe('My Company')
        ->and($output->slug)->toBe('my-company')
        ->and($output->timezone)->toBe('America/Sao_Paulo')
        ->and($output->status)->toBe('active');
});
