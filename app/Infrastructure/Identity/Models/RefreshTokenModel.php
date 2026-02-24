<?php

declare(strict_types=1);

namespace App\Infrastructure\Identity\Models;

use Illuminate\Database\Eloquent\Model;

final class RefreshTokenModel extends Model
{
    protected $table = 'refresh_tokens';

    public $incrementing = false;

    public $timestamps = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'user_id',
        'token_hash',
        'family_id',
        'expires_at',
        'revoked_at',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'revoked_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }
}
