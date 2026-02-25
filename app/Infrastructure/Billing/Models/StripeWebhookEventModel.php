<?php

declare(strict_types=1);

namespace App\Infrastructure\Billing\Models;

use Illuminate\Database\Eloquent\Model;

final class StripeWebhookEventModel extends Model
{
    protected $table = 'stripe_webhook_events';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'id',
        'stripe_event_id',
        'event_type',
        'processed',
        'payload',
        'processed_at',
        'error_message',
        'created_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'processed' => 'boolean',
            'payload' => 'array',
            'processed_at' => 'datetime',
        ];
    }
}
