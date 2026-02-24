<?php

declare(strict_types=1);

namespace App\Infrastructure\Media\Services;

use App\Application\Media\Contracts\ChunkedStorageInterface;
use Aws\S3\S3Client;
use RuntimeException;

final class S3ChunkedStorageService implements ChunkedStorageInterface
{
    public function __construct(
        private readonly S3Client $s3Client,
        private readonly string $bucket,
    ) {}

    public function initiate(string $key, string $contentType): string
    {
        $result = $this->s3Client->createMultipartUpload([
            'Bucket' => $this->bucket,
            'Key' => $key,
            'ContentType' => $contentType,
        ]);

        $uploadId = $result->get('UploadId');

        if (! is_string($uploadId) || $uploadId === '') {
            throw new RuntimeException('S3 createMultipartUpload did not return a valid UploadId.');
        }

        return $uploadId;
    }

    public function uploadPart(string $s3UploadId, string $key, int $partNumber, string $data): string
    {
        $result = $this->s3Client->uploadPart([
            'Bucket' => $this->bucket,
            'Key' => $key,
            'UploadId' => $s3UploadId,
            'PartNumber' => $partNumber,
            'Body' => $data,
        ]);

        $etag = $result->get('ETag');

        if (! is_string($etag) || $etag === '') {
            throw new RuntimeException("S3 uploadPart did not return a valid ETag for part {$partNumber}.");
        }

        return $etag;
    }

    /**
     * @param  array<int, string>  $parts
     */
    public function complete(string $s3UploadId, string $key, array $parts): string
    {
        $multipartUpload = [];

        foreach ($parts as $partNumber => $etag) {
            $multipartUpload[] = [
                'ETag' => $etag,
                'PartNumber' => $partNumber,
            ];
        }

        $this->s3Client->completeMultipartUpload([
            'Bucket' => $this->bucket,
            'Key' => $key,
            'UploadId' => $s3UploadId,
            'MultipartUpload' => [
                'Parts' => $multipartUpload,
            ],
        ]);

        return $key;
    }

    public function abort(string $s3UploadId, string $key): void
    {
        $this->s3Client->abortMultipartUpload([
            'Bucket' => $this->bucket,
            'Key' => $key,
            'UploadId' => $s3UploadId,
        ]);
    }
}
