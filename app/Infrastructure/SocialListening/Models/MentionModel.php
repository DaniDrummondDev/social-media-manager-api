<?php

declare(strict_types=1);

namespace App\Infrastructure\SocialListening\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

final class MentionModel extends Model
{
    use HasUuids;

    protected $table = 'mentions';

    public $incrementing = false;

    public $timestamps = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'query_id',
        'organization_id',
        'platform',
        'external_id',
        'author_username',
        'author_display_name',
        'author_follower_count',
        'profile_url',
        'content',
        'url',
        'sentiment',
        'sentiment_score',
        'reach',
        'engagement_count',
        'is_flagged',
        'is_read',
        'published_at',
        'detected_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_flagged' => 'boolean',
            'is_read' => 'boolean',
            'sentiment_score' => 'float',
            'published_at' => 'datetime',
            'detected_at' => 'datetime',
        ];
    }
}
