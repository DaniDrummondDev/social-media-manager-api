<?php

declare(strict_types=1);

use App\Domain\ClientFinance\Entities\InvoiceItem;
use App\Domain\Shared\ValueObjects\Uuid;

it('calculates totalCents as quantity * unitPriceCents on create', function () {
    $item = InvoiceItem::create(
        description: 'Gestão de Redes Sociais',
        quantity: 3,
        unitPriceCents: 150000,
        position: 1,
    );

    expect($item->description)->toBe('Gestão de Redes Sociais')
        ->and($item->quantity)->toBe(3)
        ->and($item->unitPriceCents)->toBe(150000)
        ->and($item->totalCents)->toBe(450000)
        ->and($item->position)->toBe(1);
});

it('calculates totalCents correctly for single quantity', function () {
    $item = InvoiceItem::create(
        description: 'Setup Inicial',
        quantity: 1,
        unitPriceCents: 200000,
        position: 0,
    );

    expect($item->totalCents)->toBe(200000);
});

it('preserves all values when reconstituted', function () {
    $id = Uuid::generate();

    $item = InvoiceItem::reconstitute(
        id: $id,
        description: 'Criação de Conteúdo',
        quantity: 10,
        unitPriceCents: 25000,
        totalCents: 250000,
        position: 2,
    );

    expect($item->id)->toBe($id)
        ->and($item->description)->toBe('Criação de Conteúdo')
        ->and($item->quantity)->toBe(10)
        ->and($item->unitPriceCents)->toBe(25000)
        ->and($item->totalCents)->toBe(250000)
        ->and($item->position)->toBe(2);
});
