<?php

declare(strict_types=1);

namespace App\Infrastructure\Media\Models;

use Illuminate\Database\Eloquent\Model;

final class MediaUploadModel extends Model
{
    protected $table = 'media_uploads';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'organization_id',
        'user_id',
        'file_name',
        'mime_type',
        'total_bytes',
        'chunk_size_bytes',
        'total_chunks',
        'received_chunks',
        's3_upload_id',
        's3_parts',
        'status',
        'checksum',
        'expires_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'total_bytes' => 'integer',
            'chunk_size_bytes' => 'integer',
            'total_chunks' => 'integer',
            'received_chunks' => 'json',
            's3_parts' => 'json',
            'expires_at' => 'datetime',
        ];
    }
}
