<?php

declare(strict_types=1);

namespace App\Infrastructure\AIIntelligence\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

final class BrandSafetyCheckModel extends Model
{
    use HasUuids;

    protected $table = 'brand_safety_checks';

    public $incrementing = false;

    public $timestamps = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'organization_id',
        'content_id',
        'provider',
        'overall_status',
        'overall_score',
        'checks',
        'model_used',
        'tokens_input',
        'tokens_output',
        'checked_at',
        'created_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'checks' => 'array',
            'overall_score' => 'integer',
            'tokens_input' => 'integer',
            'tokens_output' => 'integer',
            'checked_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }
}
