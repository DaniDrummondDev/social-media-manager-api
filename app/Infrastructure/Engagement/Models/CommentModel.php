<?php

declare(strict_types=1);

namespace App\Infrastructure\Engagement\Models;

use Illuminate\Database\Eloquent\Model;

final class CommentModel extends Model
{
    protected $table = 'comments';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'organization_id',
        'content_id',
        'social_account_id',
        'provider',
        'external_comment_id',
        'author_name',
        'author_external_id',
        'author_profile_url',
        'text',
        'sentiment',
        'sentiment_score',
        'is_read',
        'is_from_owner',
        'replied_at',
        'replied_by',
        'replied_by_automation',
        'reply_text',
        'reply_external_id',
        'commented_at',
        'captured_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sentiment_score' => 'float',
            'is_read' => 'boolean',
            'is_from_owner' => 'boolean',
            'replied_by_automation' => 'boolean',
            'replied_at' => 'datetime',
            'commented_at' => 'datetime',
            'captured_at' => 'datetime',
        ];
    }
}
