<?php

declare(strict_types=1);

namespace App\Infrastructure\Engagement\Models;

use Illuminate\Database\Eloquent\Model;

final class WebhookEndpointModel extends Model
{
    protected $table = 'webhook_endpoints';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $hidden = [
        'secret',
    ];

    protected $fillable = [
        'id',
        'organization_id',
        'name',
        'url',
        'secret',
        'events',
        'headers',
        'is_active',
        'last_delivery_at',
        'last_delivery_status',
        'failure_count',
        'deleted_at',
        'purge_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'events' => 'array',
            'headers' => 'array',
            'is_active' => 'boolean',
            'last_delivery_at' => 'datetime',
            'last_delivery_status' => 'integer',
            'failure_count' => 'integer',
            'deleted_at' => 'datetime',
            'purge_at' => 'datetime',
        ];
    }
}
