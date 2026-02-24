<?php

declare(strict_types=1);

namespace App\Infrastructure\Campaign\Models;

use Illuminate\Database\Eloquent\Model;

final class ContentMediaModel extends Model
{
    protected $table = 'content_media';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'id',
        'content_id',
        'media_id',
        'position',
        'created_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'position' => 'integer',
            'created_at' => 'datetime',
        ];
    }
}
