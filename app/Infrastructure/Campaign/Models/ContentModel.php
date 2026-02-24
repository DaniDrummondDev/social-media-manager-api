<?php

declare(strict_types=1);

namespace App\Infrastructure\Campaign\Models;

use Illuminate\Database\Eloquent\Model;

final class ContentModel extends Model
{
    protected $table = 'contents';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'organization_id',
        'campaign_id',
        'created_by',
        'title',
        'body',
        'hashtags',
        'status',
        'ai_generation_id',
        'deleted_at',
        'purge_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'hashtags' => 'array',
            'deleted_at' => 'datetime',
            'purge_at' => 'datetime',
        ];
    }
}
