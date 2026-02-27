<?php

declare(strict_types=1);

use App\Application\AIIntelligence\DTOs\AttributeCrmConversionInput;
use App\Application\AIIntelligence\UseCases\AttributeCrmConversionUseCase;
use App\Application\Shared\Contracts\EventDispatcherInterface;
use App\Domain\AIIntelligence\Repositories\CrmConversionAttributionRepositoryInterface;
use App\Domain\Engagement\Entities\CrmConnection;
use App\Domain\Engagement\Repositories\CrmConnectionRepositoryInterface;
use App\Domain\Engagement\ValueObjects\CrmConnectionStatus;
use App\Domain\Engagement\ValueObjects\CrmProvider;
use App\Domain\Shared\ValueObjects\Uuid;

function createMockConnection(string $id): CrmConnection
{
    return CrmConnection::reconstitute(
        id: Uuid::fromString($id),
        organizationId: Uuid::generate(),
        provider: CrmProvider::ActiveCampaign,
        accessToken: 'token',
        refreshToken: null,
        tokenExpiresAt: null,
        externalAccountId: 'activecampaign',
        accountName: 'Test AC',
        status: CrmConnectionStatus::Connected,
        settings: [],
        connectedBy: Uuid::generate(),
        lastSyncAt: null,
        createdAt: new DateTimeImmutable,
        updatedAt: new DateTimeImmutable,
        disconnectedAt: null,
    );
}

it('creates attribution and dispatches events', function () {
    $orgId = (string) Uuid::generate();
    $userId = (string) Uuid::generate();
    $connId = (string) Uuid::generate();
    $contentId = (string) Uuid::generate();

    $connRepo = Mockery::mock(CrmConnectionRepositoryInterface::class);
    $connRepo->shouldReceive('findById')->once()->andReturn(createMockConnection($connId));

    $attrRepo = Mockery::mock(CrmConversionAttributionRepositoryInterface::class);
    $attrRepo->shouldReceive('create')->once();

    $dispatcher = Mockery::mock(EventDispatcherInterface::class);
    $dispatcher->shouldReceive('dispatch')->once();

    $useCase = new AttributeCrmConversionUseCase($attrRepo, $connRepo, $dispatcher);

    $output = $useCase->execute(new AttributeCrmConversionInput(
        organizationId: $orgId,
        userId: $userId,
        crmConnectionId: $connId,
        contentId: $contentId,
        crmEntityType: 'deal',
        crmEntityId: 'deal-ext-123',
        attributionType: 'deal_closed',
        attributionValue: 5000.00,
        currency: 'BRL',
        crmStage: 'closed_won',
        interactionData: ['source' => 'instagram'],
    ));

    expect($output->crmEntityType)->toBe('deal')
        ->and($output->attributionType)->toBe('deal_closed')
        ->and($output->attributionValue)->toBe(5000.00)
        ->and($output->currency)->toBe('BRL')
        ->and($output->crmStage)->toBe('closed_won');
});

it('throws when crm connection not found', function () {
    $connRepo = Mockery::mock(CrmConnectionRepositoryInterface::class);
    $connRepo->shouldReceive('findById')->once()->andReturn(null);

    $attrRepo = Mockery::mock(CrmConversionAttributionRepositoryInterface::class);
    $dispatcher = Mockery::mock(EventDispatcherInterface::class);

    $useCase = new AttributeCrmConversionUseCase($attrRepo, $connRepo, $dispatcher);

    $useCase->execute(new AttributeCrmConversionInput(
        organizationId: (string) Uuid::generate(),
        userId: (string) Uuid::generate(),
        crmConnectionId: (string) Uuid::generate(),
        contentId: (string) Uuid::generate(),
        crmEntityType: 'deal',
        crmEntityId: 'deal-ext-456',
        attributionType: 'deal_closed',
    ));
})->throws(DomainException::class);

it('creates lead capture attribution without monetary value', function () {
    $connId = (string) Uuid::generate();

    $connRepo = Mockery::mock(CrmConnectionRepositoryInterface::class);
    $connRepo->shouldReceive('findById')->once()->andReturn(createMockConnection($connId));

    $attrRepo = Mockery::mock(CrmConversionAttributionRepositoryInterface::class);
    $attrRepo->shouldReceive('create')->once();

    $dispatcher = Mockery::mock(EventDispatcherInterface::class);
    $dispatcher->shouldReceive('dispatch')->once();

    $useCase = new AttributeCrmConversionUseCase($attrRepo, $connRepo, $dispatcher);

    $output = $useCase->execute(new AttributeCrmConversionInput(
        organizationId: (string) Uuid::generate(),
        userId: (string) Uuid::generate(),
        crmConnectionId: $connId,
        contentId: (string) Uuid::generate(),
        crmEntityType: 'contact',
        crmEntityId: 'contact-ext-789',
        attributionType: 'lead_capture',
    ));

    expect($output->attributionType)->toBe('lead_capture')
        ->and($output->attributionValue)->toBeNull()
        ->and($output->crmEntityType)->toBe('contact');
});
