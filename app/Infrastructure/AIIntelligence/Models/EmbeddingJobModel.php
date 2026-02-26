<?php

declare(strict_types=1);

namespace App\Infrastructure\AIIntelligence\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

final class EmbeddingJobModel extends Model
{
    use HasUuids;

    protected $table = 'embedding_jobs';

    public $incrementing = false;

    public $timestamps = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'organization_id',
        'entity_type',
        'entity_id',
        'status',
        'model_used',
        'tokens_used',
        'error_message',
        'created_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'tokens_used' => 'integer',
            'created_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }
}
