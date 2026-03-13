<?php

declare(strict_types=1);

namespace App\Infrastructure\Identity\Models;

use Illuminate\Database\Eloquent\Model;

final class UserModel extends Model
{
    protected $table = 'users';

    public $incrementing = false;

    protected $keyType = 'string';

    /**
     * SECURITY FIX (MODEL-001): Remove sensitive fields from $fillable
     * 
     * Sensitive fields like password, two_factor_secret, recovery_codes
     * should NOT be mass-assignable. Use explicit setters instead.
     */
    protected $fillable = [
        'id',
        'name',
        'email',
        'phone',
        'timezone',
        'email_verified_at',
        'two_factor_enabled',
        'status',
        'last_login_at',
        'last_login_ip',
    ];

    /**
     * Ensure sensitive fields are never exposed in JSON responses
     */
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
            'recovery_codes' => 'array',
        ];
    }
}
