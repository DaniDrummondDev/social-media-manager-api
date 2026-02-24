<?php

declare(strict_types=1);

use App\Domain\Organization\Exceptions\InvalidSlugException;
use App\Domain\Organization\ValueObjects\OrganizationSlug;

it('creates a valid slug', function () {
    $slug = OrganizationSlug::fromString('my-organization');

    expect((string) $slug)->toBe('my-organization');
});

it('normalizes slug to lowercase', function () {
    $slug = OrganizationSlug::fromString('My-Organization');

    expect((string) $slug)->toBe('my-organization');
});

it('accepts slug with numbers', function () {
    $slug = OrganizationSlug::fromString('org-123');

    expect((string) $slug)->toBe('org-123');
});

it('accepts minimum length slug (3 chars)', function () {
    $slug = OrganizationSlug::fromString('abc');

    expect((string) $slug)->toBe('abc');
});

it('rejects slug shorter than 3 chars', function () {
    OrganizationSlug::fromString('ab');
})->throws(InvalidSlugException::class);

it('rejects slug with special characters', function () {
    OrganizationSlug::fromString('my_org!');
})->throws(InvalidSlugException::class);

it('rejects slug starting with hyphen', function () {
    OrganizationSlug::fromString('-my-org');
})->throws(InvalidSlugException::class);

it('rejects slug ending with hyphen', function () {
    OrganizationSlug::fromString('my-org-');
})->throws(InvalidSlugException::class);

it('compares two equal slugs', function () {
    $slug1 = OrganizationSlug::fromString('my-org');
    $slug2 = OrganizationSlug::fromString('my-org');

    expect($slug1->equals($slug2))->toBeTrue();
});

it('compares two different slugs', function () {
    $slug1 = OrganizationSlug::fromString('org-one');
    $slug2 = OrganizationSlug::fromString('org-two');

    expect($slug1->equals($slug2))->toBeFalse();
});
