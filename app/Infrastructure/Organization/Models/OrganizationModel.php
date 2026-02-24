<?php

declare(strict_types=1);

namespace App\Infrastructure\Organization\Models;

use Illuminate\Database\Eloquent\Model;

final class OrganizationModel extends Model
{
    protected $table = 'organizations';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'name',
        'slug',
        'timezone',
        'status',
    ];
}
