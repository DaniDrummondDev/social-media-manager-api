<?php

declare(strict_types=1);

namespace App\Domain\PaidAdvertising\ValueObjects;

use InvalidArgumentException;

final readonly class DemographicFilter
{
    /**
     * @param  array<string>  $genders
     * @param  array<string>  $languages
     */
    private function __construct(
        public ?int $ageMin,
        public ?int $ageMax,
        public array $genders,
        public array $languages,
    ) {}

    /**
     * @param  array<string>  $genders
     * @param  array<string>  $languages
     */
    public static function create(
        ?int $ageMin = null,
        ?int $ageMax = null,
        array $genders = [],
        array $languages = [],
    ): self {
        if ($ageMin !== null && ($ageMin < 13 || $ageMin > 65)) {
            throw new InvalidArgumentException('Idade minima deve ser entre 13 e 65.');
        }

        if ($ageMax !== null && ($ageMax < 13 || $ageMax > 65)) {
            throw new InvalidArgumentException('Idade maxima deve ser entre 13 e 65.');
        }

        if ($ageMin !== null && $ageMax !== null && $ageMin > $ageMax) {
            throw new InvalidArgumentException('Idade minima nao pode ser maior que idade maxima.');
        }

        return new self($ageMin, $ageMax, $genders, $languages);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return self::create(
            ageMin: isset($data['age_min']) ? (int) $data['age_min'] : null,
            ageMax: isset($data['age_max']) ? (int) $data['age_max'] : null,
            genders: (array) ($data['genders'] ?? []),
            languages: (array) ($data['languages'] ?? []),
        );
    }

    public function isEmpty(): bool
    {
        return $this->ageMin === null
            && $this->ageMax === null
            && $this->genders === []
            && $this->languages === [];
    }

    public function equals(self $other): bool
    {
        return $this->ageMin === $other->ageMin
            && $this->ageMax === $other->ageMax
            && $this->genders === $other->genders
            && $this->languages === $other->languages;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'age_min' => $this->ageMin,
            'age_max' => $this->ageMax,
            'genders' => $this->genders,
            'languages' => $this->languages,
        ];
    }
}
