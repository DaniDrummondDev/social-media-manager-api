<?php

declare(strict_types=1);

namespace App\Infrastructure\Media\Jobs;

use App\Application\Media\Contracts\ChunkedStorageInterface;
use App\Domain\Media\Repositories\MediaUploadRepositoryInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

final class CleanupAbandonedUploadsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(
        MediaUploadRepositoryInterface $repository,
        ChunkedStorageInterface $chunkedStorage,
    ): void {
        $expiredUploads = $repository->findExpired();

        foreach ($expiredUploads as $upload) {
            try {
                $expired = $upload->expire();
                $repository->update($expired);

                if ($upload->s3UploadId !== null) {
                    try {
                        $chunkedStorage->abort($upload->s3UploadId, $upload->fileName);
                    } catch (Throwable $e) {
                        Log::warning('Failed to abort S3 multipart upload', [
                            'upload_id' => (string) $upload->id,
                            's3_upload_id' => $upload->s3UploadId,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            } catch (Throwable $e) {
                Log::warning('Failed to cleanup abandoned upload', [
                    'upload_id' => (string) $upload->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
