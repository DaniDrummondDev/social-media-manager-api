<?php

declare(strict_types=1);

namespace App\Infrastructure\Publishing\Models;

use Illuminate\Database\Eloquent\Model;

final class ScheduledPostModel extends Model
{
    protected $table = 'scheduled_posts';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'organization_id',
        'content_id',
        'social_account_id',
        'scheduled_by',
        'scheduled_at',
        'status',
        'published_at',
        'external_post_id',
        'external_post_url',
        'attempts',
        'max_attempts',
        'last_attempted_at',
        'last_error',
        'next_retry_at',
        'dispatched_at',
        'idempotency_key',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'published_at' => 'datetime',
            'last_attempted_at' => 'datetime',
            'next_retry_at' => 'datetime',
            'dispatched_at' => 'datetime',
            'last_error' => 'array',
        ];
    }
}
