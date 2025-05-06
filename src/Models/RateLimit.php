<?php

namespace JustBetter\ExactClient\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @property int $id
 * @property string $exact_division
 * @property int $timestamp
 * @property int $limit
 * @property int $remaining
 * @property Carbon $reset_at
 * @property int $minutely_limit
 * @property int $minutely_remaining
 * @property Carbon $minutely_reset_at
 * @property ?Carbon $created_at
 * @property ?Carbon $updated_at
 */
class RateLimit extends Model
{
    use LogsActivity;

    protected $table = 'exact_rate_limits';

    protected $guarded = [];

    protected $casts = [
        'timestamp' => 'integer',
        'reset_at' => 'datetime',
        'minutely_reset_at' => 'datetime',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
