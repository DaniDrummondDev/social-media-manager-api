<?php

declare(strict_types=1);

namespace App\Infrastructure\PaidAdvertising\Models;

use Illuminate\Database\Eloquent\Model;

final class AudienceModel extends Model
{
    protected $table = 'audiences';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'organization_id',
        'name',
        'targeting_spec',
        'provider_audience_ids',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'targeting_spec' => 'array',
            'provider_audience_ids' => 'array',
        ];
    }
}
