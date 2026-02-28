<?php

declare(strict_types=1);

namespace App\Infrastructure\Engagement\Models;

use Illuminate\Database\Eloquent\Model;

final class CrmConnectionModel extends Model
{
    protected $table = 'crm_connections';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $hidden = [
        'access_token',
        'refresh_token',
    ];

    protected $fillable = [
        'id',
        'organization_id',
        'provider',
        'access_token',
        'refresh_token',
        'token_expires_at',
        'external_account_id',
        'account_name',
        'status',
        'settings',
        'connected_by',
        'last_sync_at',
        'disconnected_at',
    ];

    protected function casts(): array
    {
        return [
            'settings' => 'array',
            'token_expires_at' => 'datetime',
            'last_sync_at' => 'datetime',
            'disconnected_at' => 'datetime',
        ];
    }
}
