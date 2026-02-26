<?php

declare(strict_types=1);

namespace App\Infrastructure\AIIntelligence\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

final class BrandSafetyRuleModel extends Model
{
    use HasUuids;

    protected $table = 'brand_safety_rules';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'organization_id',
        'rule_type',
        'rule_config',
        'severity',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'rule_config' => 'array',
            'is_active' => 'boolean',
        ];
    }
}
