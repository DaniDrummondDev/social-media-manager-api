<?php

declare(strict_types=1);

use App\Domain\Engagement\ValueObjects\CrmFieldMapping;

it('creates with factory method', function () {
    $mapping = CrmFieldMapping::create(
        smmField: 'name',
        crmField: 'contact_name',
        transform: 'uppercase',
        position: 1,
    );

    expect($mapping->smmField)->toBe('name')
        ->and($mapping->crmField)->toBe('contact_name')
        ->and($mapping->transform)->toBe('uppercase')
        ->and($mapping->position)->toBe(1);
});

it('creates with default position', function () {
    $mapping = CrmFieldMapping::create(
        smmField: 'email',
        crmField: 'email_address',
    );

    expect($mapping->position)->toBe(0)
        ->and($mapping->transform)->toBeNull();
});

it('detects transform presence', function () {
    $with = CrmFieldMapping::create('name', 'nome', 'uppercase');
    $without = CrmFieldMapping::create('name', 'nome');
    $empty = CrmFieldMapping::create('name', 'nome', '');

    expect($with->hasTransform())->toBeTrue()
        ->and($without->hasTransform())->toBeFalse()
        ->and($empty->hasTransform())->toBeFalse();
});

it('compares equality ignoring position', function () {
    $a = CrmFieldMapping::create('name', 'contact_name', 'uppercase', 0);
    $b = CrmFieldMapping::create('name', 'contact_name', 'uppercase', 5);
    $c = CrmFieldMapping::create('name', 'contact_name', 'lowercase', 0);
    $d = CrmFieldMapping::create('email', 'contact_name', 'uppercase', 0);

    expect($a->equals($b))->toBeTrue()
        ->and($a->equals($c))->toBeFalse()
        ->and($a->equals($d))->toBeFalse();
});

it('converts to array', function () {
    $mapping = CrmFieldMapping::create('name', 'contact_name', 'trim', 2);

    expect($mapping->toArray())->toBe([
        'smm_field' => 'name',
        'crm_field' => 'contact_name',
        'transform' => 'trim',
        'position' => 2,
    ]);
});
