<?php

declare(strict_types=1);

use App\Application\SocialListening\DTOs\MarkMentionsReadInput;
use App\Application\SocialListening\UseCases\MarkMentionsReadUseCase;
use App\Domain\SocialListening\Repositories\MentionRepositoryInterface;

beforeEach(function () {
    $this->mentionRepository = Mockery::mock(MentionRepositoryInterface::class);

    $this->useCase = new MarkMentionsReadUseCase(
        $this->mentionRepository,
    );

    $this->orgId = 'f47ac10b-58cc-4372-a567-0e02b2c3d479';
});

it('marks mentions as read successfully', function () {
    $mentionIds = [
        'd4e5f6a7-b8c9-0123-def0-456789012345',
        'e5f6a7b8-c9d0-1234-ef00-567890123456',
    ];

    $this->mentionRepository->shouldReceive('markManyAsRead')->once();

    $input = new MarkMentionsReadInput(
        organizationId: $this->orgId,
        userId: 'a1b2c3d4-e5f6-7890-abcd-ef1234567890',
        mentionIds: $mentionIds,
    );

    $this->useCase->execute($input);

    expect(true)->toBeTrue();
});

it('handles empty mention ids list', function () {
    $this->mentionRepository->shouldReceive('markManyAsRead')->once();

    $input = new MarkMentionsReadInput(
        organizationId: $this->orgId,
        userId: 'a1b2c3d4-e5f6-7890-abcd-ef1234567890',
        mentionIds: [],
    );

    $this->useCase->execute($input);

    expect(true)->toBeTrue();
});
