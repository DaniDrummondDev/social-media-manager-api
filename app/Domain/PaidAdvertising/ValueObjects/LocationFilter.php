<?php

declare(strict_types=1);

namespace App\Domain\PaidAdvertising\ValueObjects;

final readonly class LocationFilter
{
    /**
     * @param  array<string>  $countries  ISO 3166-1 alpha-2 codes
     * @param  array<string>  $regions
     * @param  array<string>  $cities
     */
    private function __construct(
        public array $countries,
        public array $regions,
        public array $cities,
    ) {}

    /**
     * @param  array<string>  $countries
     * @param  array<string>  $regions
     * @param  array<string>  $cities
     */
    public static function create(
        array $countries = [],
        array $regions = [],
        array $cities = [],
    ): self {
        return new self(
            array_map('strtoupper', $countries),
            $regions,
            $cities,
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return self::create(
            countries: (array) ($data['countries'] ?? []),
            regions: (array) ($data['regions'] ?? []),
            cities: (array) ($data['cities'] ?? []),
        );
    }

    public function isEmpty(): bool
    {
        return $this->countries === []
            && $this->regions === []
            && $this->cities === [];
    }

    public function equals(self $other): bool
    {
        return $this->countries === $other->countries
            && $this->regions === $other->regions
            && $this->cities === $other->cities;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'countries' => $this->countries,
            'regions' => $this->regions,
            'cities' => $this->cities,
        ];
    }
}
