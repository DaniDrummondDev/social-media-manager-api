<?php

declare(strict_types=1);

namespace App\Infrastructure\AIIntelligence\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

final class OrgStyleProfileModel extends Model
{
    use HasUuids;

    protected $table = 'org_style_profiles';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'organization_id',
        'generation_type',
        'sample_size',
        'tone_preferences',
        'length_preferences',
        'vocabulary_preferences',
        'structure_preferences',
        'hashtag_preferences',
        'style_summary',
        'confidence_level',
        'generated_at',
        'expires_at',
        'created_at',
        'updated_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sample_size' => 'integer',
            'tone_preferences' => 'array',
            'length_preferences' => 'array',
            'vocabulary_preferences' => 'array',
            'structure_preferences' => 'array',
            'hashtag_preferences' => 'array',
            'generated_at' => 'datetime',
            'expires_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
