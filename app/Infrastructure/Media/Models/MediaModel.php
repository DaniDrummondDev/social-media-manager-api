<?php

declare(strict_types=1);

namespace App\Infrastructure\Media\Models;

use Illuminate\Database\Eloquent\Model;

final class MediaModel extends Model
{
    protected $table = 'media';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'organization_id',
        'uploaded_by',
        'file_name',
        'original_name',
        'mime_type',
        'file_size',
        'width',
        'height',
        'duration_seconds',
        'storage_path',
        'thumbnail_path',
        'disk',
        'checksum',
        'scan_status',
        'scanned_at',
        'deleted_at',
        'purge_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
            'width' => 'integer',
            'height' => 'integer',
            'duration_seconds' => 'integer',
            'scanned_at' => 'datetime',
            'deleted_at' => 'datetime',
            'purge_at' => 'datetime',
        ];
    }
}
