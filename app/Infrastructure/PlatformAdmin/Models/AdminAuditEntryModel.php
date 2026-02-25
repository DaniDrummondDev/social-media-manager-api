<?php

declare(strict_types=1);

namespace App\Infrastructure\PlatformAdmin\Models;

use Illuminate\Database\Eloquent\Model;

final class AdminAuditEntryModel extends Model
{
    protected $table = 'admin_audit_entries';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    public const UPDATED_AT = null;

    protected $fillable = [
        'id',
        'admin_id',
        'action',
        'resource_type',
        'resource_id',
        'context',
        'ip_address',
        'user_agent',
        'created_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'context' => 'array',
            'created_at' => 'datetime',
        ];
    }
}
