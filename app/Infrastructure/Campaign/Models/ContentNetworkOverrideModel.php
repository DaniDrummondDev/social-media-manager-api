<?php

declare(strict_types=1);

namespace App\Infrastructure\Campaign\Models;

use Illuminate\Database\Eloquent\Model;

final class ContentNetworkOverrideModel extends Model
{
    protected $table = 'content_network_overrides';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'content_id',
        'provider',
        'title',
        'body',
        'hashtags',
        'metadata',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'hashtags' => 'array',
            'metadata' => 'array',
        ];
    }
}
