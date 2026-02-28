<?php

declare(strict_types=1);

use App\Domain\ContentAI\ValueObjects\DiffSummary;
use App\Domain\Shared\Exceptions\DomainException;

it('creates with valid data', function () {
    $changes = [['field' => 'title', 'before' => 'Old', 'after' => 'New']];
    $summary = DiffSummary::create($changes, 0.5);

    expect($summary->changes)->toBe($changes)
        ->and($summary->changeRatio)->toBe(0.5);
});

it('throws on invalid change ratio below 0', function () {
    DiffSummary::create([], -0.1);
})->throws(DomainException::class);

it('throws on invalid change ratio above 1', function () {
    DiffSummary::create([], 1.1);
})->throws(DomainException::class);

it('accepts boundary values 0.0 and 1.0', function () {
    $zero = DiffSummary::create([], 0.0);
    $one = DiffSummary::create([], 1.0);

    expect($zero->changeRatio)->toBe(0.0)
        ->and($one->changeRatio)->toBe(1.0);
});

it('roundtrips through fromArray and toArray', function () {
    $changes = [['field' => 'body', 'before' => 'A', 'after' => 'B']];
    $original = DiffSummary::create($changes, 0.42);
    $reconstituted = DiffSummary::fromArray($original->toArray());

    expect($reconstituted->changes)->toBe($original->changes)
        ->and($reconstituted->changeRatio)->toBe($original->changeRatio);
});

it('identifies minor edits', function () {
    $summary = DiffSummary::create([], 0.29);

    expect($summary->isMinorEdit())->toBeTrue()
        ->and($summary->isMajorRewrite())->toBeFalse();
});

it('identifies major rewrites', function () {
    $summary = DiffSummary::create([], 0.7);

    expect($summary->isMajorRewrite())->toBeTrue()
        ->and($summary->isMinorEdit())->toBeFalse();
});

it('computes diff from original and edited arrays', function () {
    $original = ['title' => 'Hello World', 'body' => 'Same'];
    $edited = ['title' => 'Hello Earth', 'body' => 'Same'];

    $summary = DiffSummary::compute($original, $edited);

    expect($summary->changes)->toHaveCount(1)
        ->and($summary->changes[0]['field'])->toBe('title')
        ->and($summary->changes[0]['before'])->toBe('Hello World')
        ->and($summary->changes[0]['after'])->toBe('Hello Earth')
        ->and($summary->changeRatio)->toBeGreaterThan(0.0);
});

it('computes zero ratio for identical arrays', function () {
    $data = ['title' => 'Same', 'body' => 'Also same'];

    $summary = DiffSummary::compute($data, $data);

    expect($summary->changes)->toBeEmpty()
        ->and($summary->changeRatio)->toBe(0.0);
});

it('computes for empty arrays', function () {
    $summary = DiffSummary::compute([], []);

    expect($summary->changeRatio)->toBe(0.0)
        ->and($summary->changes)->toBeEmpty();
});
