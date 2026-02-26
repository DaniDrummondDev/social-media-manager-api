<?php

declare(strict_types=1);

namespace App\Infrastructure\Engagement\Models;

use Illuminate\Database\Eloquent\Model;

final class CrmSyncLogModel extends Model
{
    protected $table = 'crm_sync_logs';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'id',
        'organization_id',
        'crm_connection_id',
        'direction',
        'entity_type',
        'action',
        'status',
        'external_id',
        'error_message',
        'payload',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'created_at' => 'datetime',
        ];
    }
}
