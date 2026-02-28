<?php

declare(strict_types=1);

namespace App\Infrastructure\ContentAI\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Infrastructure\Campaign\Models\ContentModel;

final class ContentEmbeddingModel extends Model
{
    use HasUuids;

    protected $table = 'content_embeddings';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'organization_id',
        'content_id',
        'embedding',
        'model_used',
        'tokens_used',
        'created_at',
        'updated_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tokens_used' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<ContentModel, $this>
     */
    public function content(): BelongsTo
    {
        return $this->belongsTo(ContentModel::class, 'content_id');
    }
}
