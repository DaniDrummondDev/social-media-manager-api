<?php

declare(strict_types=1);

namespace App\Infrastructure\AIIntelligence\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

final class AIGenerationContextModel extends Model
{
    use HasUuids;

    protected $table = 'ai_generation_context';

    public $incrementing = false;

    public $timestamps = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'organization_id',
        'context_type',
        'context_data',
        'max_tokens',
        'updated_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'context_data' => 'array',
            'max_tokens' => 'integer',
            'updated_at' => 'datetime',
        ];
    }
}
