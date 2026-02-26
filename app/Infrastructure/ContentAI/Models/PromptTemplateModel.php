<?php

declare(strict_types=1);

namespace App\Infrastructure\ContentAI\Models;

use Illuminate\Database\Eloquent\Model;

final class PromptTemplateModel extends Model
{
    protected $table = 'prompt_templates';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'organization_id',
        'generation_type',
        'version',
        'name',
        'system_prompt',
        'user_prompt_template',
        'variables',
        'is_active',
        'is_default',
        'performance_score',
        'total_uses',
        'total_accepted',
        'total_edited',
        'total_rejected',
        'created_by',
        'created_at',
        'updated_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'variables' => 'array',
            'is_active' => 'boolean',
            'is_default' => 'boolean',
            'performance_score' => 'float',
            'total_uses' => 'integer',
            'total_accepted' => 'integer',
            'total_edited' => 'integer',
            'total_rejected' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
