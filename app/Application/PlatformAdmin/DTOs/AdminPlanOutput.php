<?php

declare(strict_types=1);

namespace App\Application\PlatformAdmin\DTOs;

final readonly class AdminPlanOutput
{
    public function __construct(
        public string $id,
        public string $name,
        public string $slug,
        public ?string $description,
        public int $priceMonthly,
        public int $priceYearly,
        public string $currency,
        public array $limits,
        public array $features,
        public bool $isActive,
        public int $sortOrder,
        public int $subscribersCount,
        public string $createdAt,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: (string) $data['id'],
            name: (string) $data['name'],
            slug: (string) $data['slug'],
            description: $data['description'] ?? null,
            priceMonthly: (int) ($data['price_monthly_cents'] ?? 0),
            priceYearly: (int) ($data['price_yearly_cents'] ?? 0),
            currency: (string) ($data['currency'] ?? 'BRL'),
            limits: is_string($data['limits'] ?? null) ? json_decode($data['limits'], true) : ($data['limits'] ?? []),
            features: is_string($data['features'] ?? null) ? json_decode($data['features'], true) : ($data['features'] ?? []),
            isActive: (bool) ($data['is_active'] ?? true),
            sortOrder: (int) ($data['sort_order'] ?? 0),
            subscribersCount: (int) ($data['subscriber_count'] ?? 0),
            createdAt: (string) $data['created_at'],
        );
    }
}
