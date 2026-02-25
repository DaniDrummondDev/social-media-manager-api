<?php

declare(strict_types=1);

namespace App\Infrastructure\Billing\Models;

use Illuminate\Database\Eloquent\Model;

final class PlanModel extends Model
{
    protected $table = 'plans';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'name',
        'slug',
        'description',
        'price_monthly_cents',
        'price_yearly_cents',
        'currency',
        'limits',
        'features',
        'is_active',
        'sort_order',
        'stripe_price_monthly_id',
        'stripe_price_yearly_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price_monthly_cents' => 'integer',
            'price_yearly_cents' => 'integer',
            'limits' => 'array',
            'features' => 'array',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }
}
