<?php

declare(strict_types=1);

use App\Application\Media\Contracts\MediaStorageInterface;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');
    $this->service = app(MediaStorageInterface::class);
});

it('stores file content', function () {
    $this->service->store('local', 'test/file.txt', 'hello world');

    Storage::disk('local')->assertExists('test/file.txt');
    expect(Storage::disk('local')->get('test/file.txt'))->toBe('hello world');
});

it('deletes stored file', function () {
    Storage::disk('local')->put('test/to-delete.txt', 'content');

    $this->service->delete('local', 'test/to-delete.txt');

    Storage::disk('local')->assertMissing('test/to-delete.txt');
});

it('generates correct path format', function () {
    $path = $this->service->generatePath('org-123', 'abc456.jpg');

    expect($path)->toBe('orgs/org-123/media/abc456.jpg');
});

it('handles deletion of non-existent file gracefully', function () {
    // Should not throw
    $this->service->delete('local', 'does/not/exist.txt');

    expect(true)->toBeTrue();
});
