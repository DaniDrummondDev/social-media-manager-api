<?php

declare(strict_types=1);

namespace App\Infrastructure\SocialAccount\Models;

use Illuminate\Database\Eloquent\Model;

final class SocialAccountModel extends Model
{
    protected $table = 'social_accounts';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'organization_id',
        'connected_by',
        'provider',
        'provider_user_id',
        'username',
        'display_name',
        'profile_picture_url',
        'access_token',
        'refresh_token',
        'token_expires_at',
        'scopes',
        'status',
        'last_synced_at',
        'connected_at',
        'disconnected_at',
        'metadata',
        'deleted_at',
        'purge_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'token_expires_at' => 'datetime',
            'scopes' => 'json',
            'last_synced_at' => 'datetime',
            'connected_at' => 'datetime',
            'disconnected_at' => 'datetime',
            'metadata' => 'json',
            'deleted_at' => 'datetime',
            'purge_at' => 'datetime',
        ];
    }
}
