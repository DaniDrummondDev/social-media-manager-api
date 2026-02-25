<?php

declare(strict_types=1);

namespace App\Infrastructure\Billing\Models;

use Illuminate\Database\Eloquent\Model;

final class SubscriptionModel extends Model
{
    protected $table = 'subscriptions';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'organization_id',
        'plan_id',
        'status',
        'billing_cycle',
        'current_period_start',
        'current_period_end',
        'trial_ends_at',
        'canceled_at',
        'cancel_at_period_end',
        'cancel_reason',
        'cancel_feedback',
        'external_subscription_id',
        'external_customer_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'cancel_at_period_end' => 'boolean',
            'current_period_start' => 'datetime',
            'current_period_end' => 'datetime',
            'trial_ends_at' => 'datetime',
            'canceled_at' => 'datetime',
        ];
    }
}
