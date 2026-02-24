<?php

declare(strict_types=1);

namespace App\Infrastructure\Media\Providers;

use App\Application\Media\Contracts\ChunkedStorageInterface;
use App\Application\Media\Contracts\MediaMetadataExtractorInterface;
use App\Application\Media\Contracts\MediaStorageInterface;
use App\Domain\Media\Repositories\MediaRepositoryInterface;
use App\Domain\Media\Repositories\MediaUploadRepositoryInterface;
use App\Infrastructure\Media\Repositories\EloquentMediaRepository;
use App\Infrastructure\Media\Repositories\EloquentMediaUploadRepository;
use App\Infrastructure\Media\Services\LaravelMediaStorageService;
use App\Infrastructure\Media\Services\MediaMetadataExtractor;
use App\Infrastructure\Media\Services\S3ChunkedStorageService;
use Aws\S3\S3Client;
use Illuminate\Support\ServiceProvider;

final class MediaServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(MediaRepositoryInterface::class, EloquentMediaRepository::class);
        $this->app->bind(MediaUploadRepositoryInterface::class, EloquentMediaUploadRepository::class);
        $this->app->bind(MediaStorageInterface::class, LaravelMediaStorageService::class);
        $this->app->bind(MediaMetadataExtractorInterface::class, MediaMetadataExtractor::class);

        $this->app->singleton(S3Client::class, function (): S3Client {
            /** @var array<string, mixed> $diskConfig */
            $diskConfig = config('filesystems.disks.s3');

            $args = [
                'region' => $diskConfig['region'] ?? 'us-east-1',
                'version' => 'latest',
                'credentials' => [
                    'key' => $diskConfig['key'] ?? '',
                    'secret' => $diskConfig['secret'] ?? '',
                ],
            ];

            $endpoint = $diskConfig['endpoint'] ?? null;

            if (is_string($endpoint) && $endpoint !== '') {
                $args['endpoint'] = $endpoint;
                $args['use_path_style_endpoint'] = (bool) ($diskConfig['use_path_style_endpoint'] ?? false);
            }

            return new S3Client($args);
        });

        $this->app->bind(ChunkedStorageInterface::class, function (): S3ChunkedStorageService {
            /** @var string $bucket */
            $bucket = config('filesystems.disks.s3.bucket', 'social-media');

            return new S3ChunkedStorageService(
                $this->app->make(S3Client::class),
                $bucket,
            );
        });
    }
}
