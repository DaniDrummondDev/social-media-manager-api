<?php

declare(strict_types=1);

namespace App\Infrastructure\AIIntelligence\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

final class CrmConversionAttributionModel extends Model
{
    use HasUuids;

    protected $table = 'crm_conversion_attributions';

    public $incrementing = false;

    public $timestamps = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'organization_id',
        'crm_connection_id',
        'content_id',
        'crm_entity_type',
        'crm_entity_id',
        'attribution_type',
        'attribution_value',
        'currency',
        'crm_stage',
        'interaction_data',
        'attributed_at',
        'created_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'attribution_value' => 'float',
            'interaction_data' => 'array',
            'attributed_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }
}
