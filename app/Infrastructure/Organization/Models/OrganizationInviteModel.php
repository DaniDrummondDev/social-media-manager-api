<?php

declare(strict_types=1);

namespace App\Infrastructure\Organization\Models;

use Illuminate\Database\Eloquent\Model;

final class OrganizationInviteModel extends Model
{
    protected $table = 'organization_invites';

    public $incrementing = false;

    public $timestamps = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'organization_id',
        'email',
        'token',
        'role',
        'invited_by',
        'accepted_at',
        'expires_at',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'accepted_at' => 'datetime',
            'expires_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }
}
