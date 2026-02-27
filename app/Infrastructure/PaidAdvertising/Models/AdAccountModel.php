<?php

declare(strict_types=1);

namespace App\Infrastructure\PaidAdvertising\Models;

use Illuminate\Database\Eloquent\Model;

final class AdAccountModel extends Model
{
    protected $table = 'ad_accounts';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'organization_id',
        'connected_by',
        'provider',
        'provider_account_id',
        'provider_account_name',
        'encrypted_access_token',
        'encrypted_refresh_token',
        'token_expires_at',
        'scopes',
        'status',
        'metadata',
        'connected_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'scopes' => 'array',
            'metadata' => 'array',
            'token_expires_at' => 'datetime',
            'connected_at' => 'datetime',
        ];
    }
}
