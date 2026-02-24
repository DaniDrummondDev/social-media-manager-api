<?php

declare(strict_types=1);

namespace App\Application\Media\Contracts;

interface ChunkedStorageInterface
{
    /**
     * Initiate a multipart upload session.
     *
     * @return string The S3 upload ID
     */
    public function initiate(string $key, string $contentType): string;

    /**
     * Upload a single part of a multipart upload.
     *
     * @return string The ETag for the uploaded part
     */
    public function uploadPart(string $s3UploadId, string $key, int $partNumber, string $data): string;

    /**
     * Complete a multipart upload by assembling all parts.
     *
     * @param  array<int, string>  $parts  Part number → ETag mapping
     * @return string The final storage path
     */
    public function complete(string $s3UploadId, string $key, array $parts): string;

    /**
     * Abort a multipart upload and clean up uploaded parts.
     */
    public function abort(string $s3UploadId, string $key): void;
}
