<?php

declare(strict_types=1);

namespace App\Infrastructure\SocialListening\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

final class ListeningAlertModel extends Model
{
    use HasUuids;

    protected $table = 'listening_alerts';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'organization_id',
        'name',
        'query_ids',
        'condition_type',
        'threshold',
        'window_minutes',
        'channels',
        'cooldown_minutes',
        'is_active',
        'last_triggered_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'query_ids' => 'array',
            'channels' => 'array',
            'is_active' => 'boolean',
            'last_triggered_at' => 'datetime',
        ];
    }
}
