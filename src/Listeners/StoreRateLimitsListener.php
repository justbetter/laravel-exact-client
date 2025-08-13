<?php

namespace JustBetter\ExactClient\Listeners;

use Illuminate\Support\Carbon;
use JustBetter\ExactClient\Events\ExactResponseEvent;
use JustBetter\ExactClient\Models\RateLimit;

class StoreRateLimitsListener
{
    protected array $headers = [
        'X-RateLimit-Limit',
        'X-RateLimit-Remaining',
        'X-RateLimit-Reset',
        'X-RateLimit-Minutely-Limit',
        'X-RateLimit-Minutely-Remaining',
        'X-RateLimit-Minutely-Reset',
    ];

    public function handle(ExactResponseEvent $event): void
    {
        $response = $event->response;

        if (! $response->successful()) {
            return;
        }

        $headers = $response->headers();

        $missing = array_diff($this->headers, array_keys($headers));

        if (count($missing) > 0) {
            return;
        }

        RateLimit::query()->updateOrCreate([
            'exact_division' => $event->division,
            'timestamp' => now()->startOfDay()->getTimestamp(),
        ], [
            'limit' => (int) $response->header('X-RateLimit-Limit'),
            'remaining' => (int) $response->header('X-RateLimit-Remaining'),
            'reset_at' => Carbon::createFromTimestampMs($response->header('X-RateLimit-Reset')),
            'minutely_limit' => (int) $response->header('X-RateLimit-Minutely-Limit'),
            'minutely_remaining' => (int) $response->header('X-RateLimit-Minutely-Remaining'),
            'minutely_reset_at' => Carbon::createFromTimestampMs($response->header('X-RateLimit-Minutely-Reset')),
        ]);
    }
}
