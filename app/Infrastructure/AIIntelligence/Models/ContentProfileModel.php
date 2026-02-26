<?php

declare(strict_types=1);

namespace App\Infrastructure\AIIntelligence\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

final class ContentProfileModel extends Model
{
    use HasUuids;

    protected $table = 'content_profiles';

    public $incrementing = false;

    public $timestamps = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'organization_id',
        'social_account_id',
        'provider',
        'status',
        'total_contents_analyzed',
        'top_themes',
        'engagement_patterns',
        'content_fingerprint',
        'high_performer_traits',
        'centroid_embedding',
        'generated_at',
        'expires_at',
        'created_at',
        'updated_at',
    ];

    protected function casts(): array
    {
        return [
            'total_contents_analyzed' => 'integer',
            'top_themes' => 'array',
            'engagement_patterns' => 'array',
            'content_fingerprint' => 'array',
            'high_performer_traits' => 'array',
            'generated_at' => 'datetime',
            'expires_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
