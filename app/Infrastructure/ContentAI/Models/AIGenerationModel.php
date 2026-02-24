<?php

declare(strict_types=1);

namespace App\Infrastructure\ContentAI\Models;

use Illuminate\Database\Eloquent\Model;

final class AIGenerationModel extends Model
{
    protected $table = 'ai_generations';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'id',
        'organization_id',
        'user_id',
        'type',
        'input',
        'output',
        'model_used',
        'tokens_input',
        'tokens_output',
        'cost_estimate',
        'duration_ms',
        'prompt_template_id',
        'experiment_id',
        'rag_context_used',
        'style_context_used',
        'created_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'input' => 'array',
            'output' => 'array',
            'tokens_input' => 'integer',
            'tokens_output' => 'integer',
            'cost_estimate' => 'float',
            'duration_ms' => 'integer',
            'rag_context_used' => 'array',
            'style_context_used' => 'boolean',
            'created_at' => 'datetime',
        ];
    }
}
