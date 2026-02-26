<?php

declare(strict_types=1);

use App\Domain\AIIntelligence\ValueObjects\StylePreferences;

function sampleTone(): array
{
    return ['preferred' => 'casual', 'avoids' => 'formal', 'detected_patterns' => ['emoji-heavy']];
}

function sampleLength(): array
{
    return ['avg_preferred_length' => 280, 'shortens_by_pct' => 10.0, 'extends_by_pct' => 5.0];
}

function sampleVocabulary(): array
{
    return ['added_words' => ['awesome'], 'removed_words' => ['synergy'], 'preferred_phrases' => ['check it out']];
}

function sampleStructure(): array
{
    return ['uses_emojis' => true, 'uses_questions' => false, 'preferred_cta_style' => 'link_in_bio'];
}

function sampleHashtag(): array
{
    return ['avg_count' => 5, 'preferred_tags' => ['#tech'], 'avoided_tags' => ['#ad'], 'style' => 'inline'];
}

it('creates with all preferences', function () {
    $prefs = StylePreferences::create(
        sampleTone(),
        sampleLength(),
        sampleVocabulary(),
        sampleStructure(),
        sampleHashtag(),
    );

    expect($prefs->tonePreferences['preferred'])->toBe('casual')
        ->and($prefs->lengthPreferences['avg_preferred_length'])->toBe(280)
        ->and($prefs->vocabularyPreferences['added_words'])->toBe(['awesome'])
        ->and($prefs->structurePreferences['uses_emojis'])->toBeTrue()
        ->and($prefs->hashtagPreferences['avg_count'])->toBe(5);
});

it('roundtrips through fromArray and toArray', function () {
    $original = StylePreferences::create(
        sampleTone(),
        sampleLength(),
        sampleVocabulary(),
        sampleStructure(),
        sampleHashtag(),
    );

    $reconstituted = StylePreferences::fromArray($original->toArray());

    expect($reconstituted->toArray())->toBe($original->toArray());
});

it('handles empty sub-arrays via fromArray', function () {
    $prefs = StylePreferences::fromArray([]);

    expect($prefs->tonePreferences)->toBe([])
        ->and($prefs->lengthPreferences)->toBe([])
        ->and($prefs->vocabularyPreferences)->toBe([])
        ->and($prefs->structurePreferences)->toBe([])
        ->and($prefs->hashtagPreferences)->toBe([]);
});

it('toArray uses snake_case keys', function () {
    $prefs = StylePreferences::create(
        sampleTone(),
        sampleLength(),
        sampleVocabulary(),
        sampleStructure(),
        sampleHashtag(),
    );

    $array = $prefs->toArray();

    expect($array)->toHaveKeys([
        'tone_preferences',
        'length_preferences',
        'vocabulary_preferences',
        'structure_preferences',
        'hashtag_preferences',
    ]);
});
