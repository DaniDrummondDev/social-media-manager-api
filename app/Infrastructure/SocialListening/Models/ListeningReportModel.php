<?php

declare(strict_types=1);

namespace App\Infrastructure\SocialListening\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

final class ListeningReportModel extends Model
{
    use HasUuids;

    protected $table = 'listening_reports';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'organization_id',
        'query_ids',
        'period_from',
        'period_to',
        'total_mentions',
        'sentiment_breakdown',
        'top_authors',
        'top_keywords',
        'platform_breakdown',
        'status',
        'file_path',
        'generated_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'query_ids' => 'array',
            'sentiment_breakdown' => 'array',
            'top_authors' => 'array',
            'top_keywords' => 'array',
            'platform_breakdown' => 'array',
            'period_from' => 'datetime',
            'period_to' => 'datetime',
            'generated_at' => 'datetime',
        ];
    }
}
