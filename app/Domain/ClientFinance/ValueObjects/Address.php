<?php

declare(strict_types=1);

namespace App\Domain\ClientFinance\ValueObjects;

final readonly class Address
{
    public function __construct(
        public ?string $street = null,
        public ?string $number = null,
        public ?string $complement = null,
        public ?string $neighborhood = null,
        public ?string $city = null,
        public ?string $state = null,
        public ?string $zipCode = null,
        public ?string $country = null,
    ) {}

    /**
     * @param  array<string, string|null>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            street: $data['street'] ?? null,
            number: $data['number'] ?? null,
            complement: $data['complement'] ?? null,
            neighborhood: $data['neighborhood'] ?? null,
            city: $data['city'] ?? null,
            state: $data['state'] ?? null,
            zipCode: $data['zip_code'] ?? null,
            country: $data['country'] ?? null,
        );
    }

    /**
     * @return array<string, string|null>
     */
    public function toArray(): array
    {
        return [
            'street' => $this->street,
            'number' => $this->number,
            'complement' => $this->complement,
            'neighborhood' => $this->neighborhood,
            'city' => $this->city,
            'state' => $this->state,
            'zip_code' => $this->zipCode,
            'country' => $this->country,
        ];
    }
}
