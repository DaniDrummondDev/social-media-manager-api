<?php

declare(strict_types=1);

namespace App\Infrastructure\Engagement\Models;

use Illuminate\Database\Eloquent\Model;

final class AutomationRuleConditionModel extends Model
{
    protected $table = 'automation_rule_conditions';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'id',
        'automation_rule_id',
        'field',
        'operator',
        'value',
        'is_case_sensitive',
        'position',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_case_sensitive' => 'boolean',
            'position' => 'integer',
        ];
    }
}
