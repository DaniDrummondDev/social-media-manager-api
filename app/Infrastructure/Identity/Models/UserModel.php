<?php

declare(strict_types=1);

namespace App\Infrastructure\Identity\Models;

use Illuminate\Database\Eloquent\Model;

final class UserModel extends Model
{
    protected $table = 'users';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'name',
        'email',
        'password',
        'phone',
        'timezone',
        'email_verified_at',
        'two_factor_enabled',
        'two_factor_secret',
        'recovery_codes',
        'status',
        'last_login_at',
        'last_login_ip',
    ];

    protected $hidden = [
        'password',
        'two_factor_secret',
        'recovery_codes',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'two_factor_enabled' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }
}
