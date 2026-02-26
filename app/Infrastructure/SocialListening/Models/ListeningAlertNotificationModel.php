<?php

declare(strict_types=1);

namespace App\Infrastructure\SocialListening\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

final class ListeningAlertNotificationModel extends Model
{
    use HasUuids;

    protected $table = 'listening_alert_notifications';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'alert_id',
        'channel',
        'status',
        'payload',
    ];
}
