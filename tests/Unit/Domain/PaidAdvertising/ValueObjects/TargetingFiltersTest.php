<?php

declare(strict_types=1);

use App\Domain\PaidAdvertising\ValueObjects\DemographicFilter;
use App\Domain\PaidAdvertising\ValueObjects\InterestFilter;
use App\Domain\PaidAdvertising\ValueObjects\LocationFilter;

// ──────────────────────────────────────────────────────────────────
// DemographicFilter
// ──────────────────────────────────────────────────────────────────

it('creates demographic filter with valid ages', function () {
    $filter = DemographicFilter::create(18, 45, ['male', 'female'], ['pt', 'en']);

    expect($filter->ageMin)->toBe(18)
        ->and($filter->ageMax)->toBe(45)
        ->and($filter->genders)->toBe(['male', 'female'])
        ->and($filter->languages)->toBe(['pt', 'en']);
});

it('rejects age below 13', function () {
    DemographicFilter::create(12, 30);
})->throws(InvalidArgumentException::class);

it('rejects age above 65', function () {
    DemographicFilter::create(18, 70);
})->throws(InvalidArgumentException::class);

it('rejects min age greater than max age', function () {
    DemographicFilter::create(40, 20);
})->throws(InvalidArgumentException::class);

it('allows null ages', function () {
    $filter = DemographicFilter::create(null, null, ['male']);

    expect($filter->ageMin)->toBeNull()
        ->and($filter->ageMax)->toBeNull();
});

it('demographic fromArray toArray roundtrip', function () {
    $data = [
        'age_min' => 18,
        'age_max' => 45,
        'genders' => ['male'],
        'languages' => ['pt'],
    ];

    $filter = DemographicFilter::fromArray($data);

    expect($filter->toArray())->toBe($data);
});

it('demographic isEmpty detects empty filter', function () {
    $empty = DemographicFilter::create();
    $notEmpty = DemographicFilter::create(18, 45);

    expect($empty->isEmpty())->toBeTrue()
        ->and($notEmpty->isEmpty())->toBeFalse();
});

it('demographic equals compares all fields', function () {
    $a = DemographicFilter::create(18, 45, ['male'], ['pt']);
    $b = DemographicFilter::create(18, 45, ['male'], ['pt']);
    $c = DemographicFilter::create(20, 50, ['female'], ['en']);

    expect($a->equals($b))->toBeTrue()
        ->and($a->equals($c))->toBeFalse();
});

// ──────────────────────────────────────────────────────────────────
// LocationFilter
// ──────────────────────────────────────────────────────────────────

it('creates location filter normalizing countries to uppercase', function () {
    $filter = LocationFilter::create(['br', 'us'], ['SP'], ['Sao Paulo']);

    expect($filter->countries)->toBe(['BR', 'US'])
        ->and($filter->regions)->toBe(['SP'])
        ->and($filter->cities)->toBe(['Sao Paulo']);
});

it('location fromArray toArray roundtrip', function () {
    $data = [
        'countries' => ['BR', 'US'],
        'regions' => ['SP'],
        'cities' => ['Sao Paulo'],
    ];

    $filter = LocationFilter::fromArray($data);

    expect($filter->toArray())->toBe($data);
});

it('location isEmpty detects empty filter', function () {
    $empty = LocationFilter::create();
    $notEmpty = LocationFilter::create(['BR']);

    expect($empty->isEmpty())->toBeTrue()
        ->and($notEmpty->isEmpty())->toBeFalse();
});

it('location equals compares all fields', function () {
    $a = LocationFilter::create(['BR'], ['SP'], ['Sao Paulo']);
    $b = LocationFilter::create(['BR'], ['SP'], ['Sao Paulo']);
    $c = LocationFilter::create(['US'], ['CA'], ['Los Angeles']);

    expect($a->equals($b))->toBeTrue()
        ->and($a->equals($c))->toBeFalse();
});

// ──────────────────────────────────────────────────────────────────
// InterestFilter
// ──────────────────────────────────────────────────────────────────

it('creates interest filter storing all arrays', function () {
    $interests = [['id' => '1', 'name' => 'Technology']];
    $behaviors = [['id' => '2', 'name' => 'Online shoppers']];
    $keywords = ['marketing', 'social media'];

    $filter = InterestFilter::create($interests, $behaviors, $keywords);

    expect($filter->interests)->toBe($interests)
        ->and($filter->behaviors)->toBe($behaviors)
        ->and($filter->keywords)->toBe($keywords);
});

it('interest fromArray toArray roundtrip', function () {
    $data = [
        'interests' => [['id' => '1', 'name' => 'Technology']],
        'behaviors' => [['id' => '2', 'name' => 'Shoppers']],
        'keywords' => ['marketing'],
    ];

    $filter = InterestFilter::fromArray($data);

    expect($filter->toArray())->toBe($data);
});

it('interest isEmpty detects empty filter', function () {
    $empty = InterestFilter::create();
    $notEmpty = InterestFilter::create([['id' => '1', 'name' => 'Tech']]);

    expect($empty->isEmpty())->toBeTrue()
        ->and($notEmpty->isEmpty())->toBeFalse();
});

it('interest equals compares all fields', function () {
    $a = InterestFilter::create([['id' => '1', 'name' => 'Tech']], [], ['marketing']);
    $b = InterestFilter::create([['id' => '1', 'name' => 'Tech']], [], ['marketing']);
    $c = InterestFilter::create([], [], ['sports']);

    expect($a->equals($b))->toBeTrue()
        ->and($a->equals($c))->toBeFalse();
});
