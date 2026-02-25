<?php

declare(strict_types=1);

namespace App\Infrastructure\Engagement\Models;

use Illuminate\Database\Eloquent\Model;

final class AutomationExecutionModel extends Model
{
    protected $table = 'automation_executions';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'id',
        'organization_id',
        'automation_rule_id',
        'comment_id',
        'action_type',
        'response_text',
        'success',
        'error_message',
        'delay_applied',
        'executed_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'success' => 'boolean',
            'delay_applied' => 'integer',
            'executed_at' => 'datetime',
        ];
    }
}
