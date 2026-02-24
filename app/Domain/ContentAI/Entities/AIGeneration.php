<?php

declare(strict_types=1);

namespace App\Domain\ContentAI\Entities;

use App\Domain\ContentAI\Events\ContentGenerated;
use App\Domain\ContentAI\ValueObjects\AIUsage;
use App\Domain\ContentAI\ValueObjects\GenerationType;
use App\Domain\Shared\Events\DomainEvent;
use App\Domain\Shared\ValueObjects\Uuid;
use DateTimeImmutable;

final readonly class AIGeneration
{
    /**
     * @param  array<string, mixed>  $input
     * @param  array<string, mixed>  $output
     * @param  array<DomainEvent>  $domainEvents
     */
    public function __construct(
        public Uuid $id,
        public Uuid $organizationId,
        public Uuid $userId,
        public GenerationType $type,
        public array $input,
        public array $output,
        public AIUsage $usage,
        public DateTimeImmutable $createdAt,
        public array $domainEvents = [],
    ) {}

    /**
     * @param  array<string, mixed>  $input
     * @param  array<string, mixed>  $output
     */
    public static function create(
        Uuid $organizationId,
        Uuid $userId,
        GenerationType $type,
        array $input,
        array $output,
        AIUsage $usage,
    ): self {
        $id = Uuid::generate();

        return new self(
            id: $id,
            organizationId: $organizationId,
            userId: $userId,
            type: $type,
            input: $input,
            output: $output,
            usage: $usage,
            createdAt: new DateTimeImmutable,
            domainEvents: [
                new ContentGenerated(
                    aggregateId: (string) $id,
                    organizationId: (string) $organizationId,
                    userId: (string) $userId,
                    generationType: $type->value,
                ),
            ],
        );
    }

    /**
     * @param  array<string, mixed>  $input
     * @param  array<string, mixed>  $output
     */
    public static function reconstitute(
        Uuid $id,
        Uuid $organizationId,
        Uuid $userId,
        GenerationType $type,
        array $input,
        array $output,
        AIUsage $usage,
        DateTimeImmutable $createdAt,
    ): self {
        return new self(
            id: $id,
            organizationId: $organizationId,
            userId: $userId,
            type: $type,
            input: $input,
            output: $output,
            usage: $usage,
            createdAt: $createdAt,
        );
    }

    public function releaseEvents(): self
    {
        return new self(
            id: $this->id,
            organizationId: $this->organizationId,
            userId: $this->userId,
            type: $this->type,
            input: $this->input,
            output: $this->output,
            usage: $this->usage,
            createdAt: $this->createdAt,
        );
    }
}
