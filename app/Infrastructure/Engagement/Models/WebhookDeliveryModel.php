<?php

declare(strict_types=1);

namespace App\Infrastructure\Engagement\Models;

use Illuminate\Database\Eloquent\Model;

final class WebhookDeliveryModel extends Model
{
    protected $table = 'webhook_deliveries';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'id',
        'webhook_endpoint_id',
        'event',
        'payload',
        'response_status',
        'response_body',
        'response_time_ms',
        'attempts',
        'max_attempts',
        'next_retry_at',
        'delivered_at',
        'failed_at',
        'created_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'response_status' => 'integer',
            'response_time_ms' => 'integer',
            'attempts' => 'integer',
            'max_attempts' => 'integer',
            'next_retry_at' => 'datetime',
            'delivered_at' => 'datetime',
            'failed_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }
}
