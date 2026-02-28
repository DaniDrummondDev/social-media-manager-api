<?php

declare(strict_types=1);

use App\Application\AIIntelligence\Contracts\BrandSafetyAnalyzerInterface;
use App\Application\AIIntelligence\DTOs\BrandSafetyAnalysisResult;

beforeEach(function () {
    $this->analyzer = app(BrandSafetyAnalyzerInterface::class);
});

it('should analyze brand safety and return result', function () {
    $content = 'This is a safe, professional marketing message about our new product launch.';
    $result = $this->analyzer->analyze($content);

    expect($result)->toBeInstanceOf(BrandSafetyAnalysisResult::class)
        ->and($result->score)->toBeGreaterThanOrEqual(0)
        ->and($result->score)->toBeLessThanOrEqual(100)
        ->and($result->checks)->toBeArray();
});

it('should check multiple brand safety categories', function () {
    $result = $this->analyzer->analyze('Safe marketing content');

    $categories = array_column($result->checks, 'category');

    expect($categories)->toContain('profanity')
        ->and($categories)->toContain('lgpd_compliance')
        ->and($categories)->toContain('advertising_disclosure');
});

it('should provide status for each check', function () {
    $result = $this->analyzer->analyze('Safe content');

    foreach ($result->checks as $check) {
        expect($check)->toHaveKey('category')
            ->and($check)->toHaveKey('status')
            ->and($check['status'])->toBeIn(['passed', 'failed', 'warning']);
    }
});

it('should handle empty content gracefully', function () {
    $result = $this->analyzer->analyze('');

    expect($result)->toBeInstanceOf(BrandSafetyAnalysisResult::class);
});

it('should accept provider parameter', function () {
    $result = $this->analyzer->analyze('Test content', 'openai');

    expect($result)->toBeInstanceOf(BrandSafetyAnalysisResult::class);
});

it('should return consistent result for same content', function () {
    $content = 'Professional marketing message';
    $result1 = $this->analyzer->analyze($content);
    $result2 = $this->analyzer->analyze($content);

    expect($result1->score)->toBe($result2->score)
        ->and(count($result1->checks))->toBe(count($result2->checks));
});

it('should have score between 0 and 100', function () {
    $result = $this->analyzer->analyze('Test content');

    expect($result->score)->toBeGreaterThanOrEqual(0)
        ->and($result->score)->toBeLessThanOrEqual(100);
});

it('should include platform policy check', function () {
    $result = $this->analyzer->analyze('Marketing content');

    $categories = array_column($result->checks, 'category');

    expect($categories)->toContain('platform_policy');
});

it('should include sensitivity check', function () {
    $result = $this->analyzer->analyze('Marketing content');

    $categories = array_column($result->checks, 'category');

    expect($categories)->toContain('sensitivity');
});
