<?php

declare(strict_types=1);

use App\Domain\PaidAdvertising\ValueObjects\DemographicFilter;
use App\Domain\PaidAdvertising\ValueObjects\InterestFilter;
use App\Domain\PaidAdvertising\ValueObjects\LocationFilter;
use App\Domain\PaidAdvertising\ValueObjects\TargetingSpec;

function createTestTargetingSpec(): TargetingSpec
{
    return TargetingSpec::create(
        demographics: DemographicFilter::create(18, 45, ['male'], ['pt']),
        locations: LocationFilter::create(['BR'], ['SP']),
        interests: InterestFilter::create([['id' => '1', 'name' => 'Tech']]),
    );
}

it('creates targeting spec composing sub-filters', function () {
    $spec = createTestTargetingSpec();

    expect($spec->demographics->ageMin)->toBe(18)
        ->and($spec->locations->countries)->toBe(['BR'])
        ->and($spec->interests->interests)->toHaveCount(1);
});

it('fromArray hydrates nested structure', function () {
    $data = [
        'demographics' => [
            'age_min' => 18,
            'age_max' => 45,
            'genders' => ['male'],
            'languages' => ['pt'],
        ],
        'locations' => [
            'countries' => ['BR'],
            'regions' => ['SP'],
            'cities' => [],
        ],
        'interests' => [
            'interests' => [['id' => '1', 'name' => 'Tech']],
            'behaviors' => [],
            'keywords' => [],
        ],
    ];

    $spec = TargetingSpec::fromArray($data);

    expect($spec->demographics->ageMin)->toBe(18)
        ->and($spec->locations->countries)->toBe(['BR'])
        ->and($spec->interests->interests)->toHaveCount(1);
});

it('toArray serializes hierarchical structure', function () {
    $spec = createTestTargetingSpec();
    $array = $spec->toArray();

    expect($array)->toHaveKeys(['demographics', 'locations', 'interests'])
        ->and($array['demographics'])->toHaveKeys(['age_min', 'age_max', 'genders', 'languages'])
        ->and($array['locations'])->toHaveKeys(['countries', 'regions', 'cities'])
        ->and($array['interests'])->toHaveKeys(['interests', 'behaviors', 'keywords']);
});

it('isEmpty returns true when all sub-filters are empty', function () {
    $empty = TargetingSpec::create(
        demographics: DemographicFilter::create(),
        locations: LocationFilter::create(),
        interests: InterestFilter::create(),
    );

    $nonEmpty = createTestTargetingSpec();

    expect($empty->isEmpty())->toBeTrue()
        ->and($nonEmpty->isEmpty())->toBeFalse();
});

it('equals compares all sub-filters', function () {
    $a = createTestTargetingSpec();
    $b = createTestTargetingSpec();

    $different = TargetingSpec::create(
        demographics: DemographicFilter::create(25, 55),
        locations: LocationFilter::create(['US']),
        interests: InterestFilter::create(),
    );

    expect($a->equals($b))->toBeTrue()
        ->and($a->equals($different))->toBeFalse();
});
