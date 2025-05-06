<?php

namespace JustBetter\ExactClient\Listeners;

use Illuminate\Support\Carbon;
use JustBetter\ExactClient\Events\ExactResponseEvent;
use JustBetter\ExactClient\Models\RateLimit;

class StoreRateLimitsListener
{
    public function handle(ExactResponseEvent $event): void
    {
        $response = $event->response;

        if (! $response->successful()) {
            return;
        }

        RateLimit::query()->updateOrCreate([
            'exact_division' => $event->division,
            'timestamp' => now()->startOfDay()->getTimestamp(),
        ], [
            'limit' => $response->header('X-RateLimit-Limit'),
            'remaining' => $response->header('X-RateLimit-Remaining'),
            'reset_at' => Carbon::createFromTimestampMs($response->header('X-RateLimit-Reset')),
            'minutely_limit' => $response->header('X-RateLimit-Minutely-Limit'),
            'minutely_remaining' => $response->header('X-RateLimit-Minutely-Remaining'),
            'minutely_reset_at' => Carbon::createFromTimestampMs($response->header('X-RateLimit-Minutely-Reset')),
        ]);
    }
}
