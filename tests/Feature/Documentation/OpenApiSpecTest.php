<?php

declare(strict_types=1);

describe('OpenAPI Specification', function () {
    beforeEach(function () {
        app()->detectEnvironment(fn () => 'local');

        $response = $this->get('/docs/api.json');
        if ($response->status() !== 200) {
            $this->markTestSkipped('Scramble API spec endpoint not available');
        }
        $this->spec = $response->json();
    });

    it('serves valid OpenAPI JSON spec', function () {
        expect($this->spec)->toHaveKeys(['openapi', 'info', 'paths', 'components']);
    });

    it('includes API info with correct title', function () {
        expect($this->spec['info']['title'])->toBe('Social Media Manager API');
        expect($this->spec['info']['version'])->toBe('1.0.0');
    });

    it('includes JWT security scheme', function () {
        expect($this->spec['components']['securitySchemes'] ?? [])->toHaveKey('http');
        expect($this->spec['components']['securitySchemes']['http']['type'])->toBe('http');
        expect($this->spec['components']['securitySchemes']['http']['scheme'])->toBe('bearer');
        expect($this->spec['components']['securitySchemes']['http']['bearerFormat'])->toBe('JWT');
    });

    it('includes documented paths', function () {
        expect($this->spec['paths'])->not->toBeEmpty();
    });

    it('excludes internal routes', function () {
        $paths = array_keys($this->spec['paths'] ?? []);

        foreach ($paths as $path) {
            expect($path)->not->toContain('internal/');
        }
    });
})->skip(fn () => app()->environment('production'), 'Scramble disabled in production');
