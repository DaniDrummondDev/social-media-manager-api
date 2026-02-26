<?php

declare(strict_types=1);

namespace App\Infrastructure\SocialListening\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

final class ListeningQueryModel extends Model
{
    use HasUuids;

    protected $table = 'listening_queries';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'organization_id',
        'name',
        'type',
        'value',
        'platforms',
        'status',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'platforms' => 'array',
        ];
    }
}
