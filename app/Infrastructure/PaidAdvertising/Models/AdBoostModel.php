<?php

declare(strict_types=1);

namespace App\Infrastructure\PaidAdvertising\Models;

use Illuminate\Database\Eloquent\Model;

final class AdBoostModel extends Model
{
    protected $table = 'ad_boosts';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'organization_id',
        'scheduled_post_id',
        'ad_account_id',
        'audience_id',
        'budget_amount_cents',
        'budget_currency',
        'budget_type',
        'duration_days',
        'objective',
        'status',
        'external_ids',
        'rejection_reason',
        'started_at',
        'completed_at',
        'created_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'external_ids' => 'array',
            'budget_amount_cents' => 'integer',
            'duration_days' => 'integer',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }
}
