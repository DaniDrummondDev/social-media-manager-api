<?php

declare(strict_types=1);

namespace App\Infrastructure\Engagement\Models;

use Illuminate\Database\Eloquent\Model;

final class CrmFieldMappingModel extends Model
{
    protected $table = 'crm_field_mappings';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'crm_connection_id',
        'smm_field',
        'crm_field',
        'transform',
        'position',
    ];

    protected function casts(): array
    {
        return [
            'position' => 'integer',
        ];
    }
}
