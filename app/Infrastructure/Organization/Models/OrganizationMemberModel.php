<?php

declare(strict_types=1);

namespace App\Infrastructure\Organization\Models;

use Illuminate\Database\Eloquent\Model;

final class OrganizationMemberModel extends Model
{
    protected $table = 'organization_members';

    public $incrementing = false;

    public $timestamps = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'organization_id',
        'user_id',
        'role',
        'invited_by',
        'joined_at',
    ];

    protected function casts(): array
    {
        return [
            'joined_at' => 'datetime',
        ];
    }
}
