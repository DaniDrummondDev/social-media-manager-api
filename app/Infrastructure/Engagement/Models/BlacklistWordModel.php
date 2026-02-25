<?php

declare(strict_types=1);

namespace App\Infrastructure\Engagement\Models;

use Illuminate\Database\Eloquent\Model;

final class BlacklistWordModel extends Model
{
    protected $table = 'automation_blacklist_words';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'id',
        'organization_id',
        'word',
        'is_regex',
        'created_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_regex' => 'boolean',
            'created_at' => 'datetime',
        ];
    }
}
