<?php

declare(strict_types=1);

namespace App\Infrastructure\PlatformAdmin\Models;

use Illuminate\Database\Eloquent\Model;

final class SystemConfigModel extends Model
{
    protected $table = 'system_configs';

    protected $primaryKey = 'key';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'key',
        'value',
        'value_type',
        'description',
        'is_secret',
        'updated_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_secret' => 'boolean',
        ];
    }
}
