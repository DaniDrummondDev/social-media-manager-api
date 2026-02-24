<?php

declare(strict_types=1);

namespace App\Infrastructure\ContentAI\Models;

use Illuminate\Database\Eloquent\Model;

final class AISettingsModel extends Model
{
    protected $table = 'ai_settings';

    protected $primaryKey = 'organization_id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'organization_id',
        'default_tone',
        'custom_tone_description',
        'default_language',
        'monthly_generation_limit',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'monthly_generation_limit' => 'integer',
        ];
    }
}
