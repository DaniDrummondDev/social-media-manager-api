<?php

declare(strict_types=1);

namespace App\Infrastructure\Analytics\Models;

use Illuminate\Database\Eloquent\Model;

final class ReportExportModel extends Model
{
    protected $table = 'report_exports';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'organization_id',
        'user_id',
        'type',
        'format',
        'filters',
        'status',
        'file_path',
        'file_size',
        'error_message',
        'expires_at',
        'completed_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'filters' => 'array',
            'expires_at' => 'datetime',
            'completed_at' => 'datetime',
            'file_size' => 'integer',
        ];
    }
}
