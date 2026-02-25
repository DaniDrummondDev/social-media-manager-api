<?php

declare(strict_types=1);

use App\Domain\ClientFinance\ValueObjects\Address;

it('creates an Address from array', function () {
    $data = [
        'street' => 'Rua das Flores',
        'number' => '123',
        'complement' => 'Sala 4',
        'neighborhood' => 'Centro',
        'city' => 'São Paulo',
        'state' => 'SP',
        'zip_code' => '01001-000',
        'country' => 'BR',
    ];

    $address = Address::fromArray($data);

    expect($address->street)->toBe('Rua das Flores')
        ->and($address->number)->toBe('123')
        ->and($address->complement)->toBe('Sala 4')
        ->and($address->neighborhood)->toBe('Centro')
        ->and($address->city)->toBe('São Paulo')
        ->and($address->state)->toBe('SP')
        ->and($address->zipCode)->toBe('01001-000')
        ->and($address->country)->toBe('BR');
});

it('returns correct array from toArray', function () {
    $data = [
        'street' => 'Rua das Flores',
        'number' => '123',
        'complement' => 'Sala 4',
        'neighborhood' => 'Centro',
        'city' => 'São Paulo',
        'state' => 'SP',
        'zip_code' => '01001-000',
        'country' => 'BR',
    ];

    $address = Address::fromArray($data);

    expect($address->toArray())->toBe($data);
});

it('allows all nullable fields', function () {
    $address = Address::fromArray([]);

    expect($address->street)->toBeNull()
        ->and($address->number)->toBeNull()
        ->and($address->complement)->toBeNull()
        ->and($address->neighborhood)->toBeNull()
        ->and($address->city)->toBeNull()
        ->and($address->state)->toBeNull()
        ->and($address->zipCode)->toBeNull()
        ->and($address->country)->toBeNull();
});

it('returns nulls in toArray when fields are empty', function () {
    $address = Address::fromArray([]);

    $array = $address->toArray();

    expect($array)->toBe([
        'street' => null,
        'number' => null,
        'complement' => null,
        'neighborhood' => null,
        'city' => null,
        'state' => null,
        'zip_code' => null,
        'country' => null,
    ]);
});
