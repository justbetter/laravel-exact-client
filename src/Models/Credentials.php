<?php

namespace JustBetter\ExactClient\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @property int $id
 * @property string $exact_connection
 * @property string $token_type
 * @property string $access_token
 * @property string $refresh_token
 * @property Carbon $expires_at
 * @property ?Carbon $created_at
 * @property ?Carbon $updated_at
 */
class Credentials extends Model
{
    use LogsActivity;

    protected $table = 'exact_credentials';

    protected $guarded = [];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    public function expired(): bool
    {
        return now()->addSeconds(30)->greaterThanOrEqualTo($this->expires_at);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
