<?php

declare(strict_types=1);

namespace App\Domain\PaidAdvertising\ValueObjects;

final readonly class TargetingSpec
{
    private function __construct(
        public DemographicFilter $demographics,
        public LocationFilter $locations,
        public InterestFilter $interests,
    ) {}

    public static function create(
        DemographicFilter $demographics,
        LocationFilter $locations,
        InterestFilter $interests,
    ): self {
        return new self($demographics, $locations, $interests);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return self::create(
            demographics: DemographicFilter::fromArray((array) ($data['demographics'] ?? [])),
            locations: LocationFilter::fromArray((array) ($data['locations'] ?? [])),
            interests: InterestFilter::fromArray((array) ($data['interests'] ?? [])),
        );
    }

    public function isEmpty(): bool
    {
        return $this->demographics->isEmpty()
            && $this->locations->isEmpty()
            && $this->interests->isEmpty();
    }

    public function equals(self $other): bool
    {
        return $this->demographics->equals($other->demographics)
            && $this->locations->equals($other->locations)
            && $this->interests->equals($other->interests);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'demographics' => $this->demographics->toArray(),
            'locations' => $this->locations->toArray(),
            'interests' => $this->interests->toArray(),
        ];
    }
}
