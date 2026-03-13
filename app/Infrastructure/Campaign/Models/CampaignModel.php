<?php

declare(strict_types=1);

namespace App\Infrastructure\Campaign\Models;

use Illuminate\Database\Eloquent\Model;

final class CampaignModel extends Model
{
    protected $table = 'campaigns';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'organization_id',
        'created_by',
        'name',
        'description',
        'brief_text',
        'brief_target_audience',
        'brief_restrictions',
        'brief_cta',
        'starts_at',
        'ends_at',
        'status',
        'tags',
        'deleted_at',
        'purge_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tags' => 'array',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'deleted_at' => 'datetime',
            'purge_at' => 'datetime',
        ];
    }
}
