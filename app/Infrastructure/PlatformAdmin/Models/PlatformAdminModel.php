<?php

declare(strict_types=1);

namespace App\Infrastructure\PlatformAdmin\Models;

use Illuminate\Database\Eloquent\Model;

final class PlatformAdminModel extends Model
{
    protected $table = 'platform_admins';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'user_id',
        'role',
        'permissions',
        'is_active',
        'last_login_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'permissions' => 'array',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }
}
