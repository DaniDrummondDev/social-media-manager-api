<?php

declare(strict_types=1);

namespace App\Infrastructure\Engagement\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class AutomationRuleModel extends Model
{
    protected $table = 'automation_rules';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'organization_id',
        'name',
        'priority',
        'action_type',
        'response_template',
        'webhook_id',
        'delay_seconds',
        'daily_limit',
        'is_active',
        'applies_to_networks',
        'applies_to_campaigns',
        'deleted_at',
        'purge_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'priority' => 'integer',
            'delay_seconds' => 'integer',
            'daily_limit' => 'integer',
            'is_active' => 'boolean',
            'applies_to_networks' => 'array',
            'applies_to_campaigns' => 'array',
            'deleted_at' => 'datetime',
            'purge_at' => 'datetime',
        ];
    }

    /**
     * @return HasMany<AutomationRuleConditionModel, $this>
     */
    public function conditions(): HasMany
    {
        return $this->hasMany(AutomationRuleConditionModel::class, 'automation_rule_id');
    }
}
