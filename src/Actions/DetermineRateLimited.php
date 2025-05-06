<?php

namespace JustBetter\ExactClient\Actions;

use JustBetter\ExactClient\Concerns\DeterminesRateLimited;
use JustBetter\ExactClient\Models\RateLimit;

class DetermineRateLimited implements DeterminesRateLimited
{
    public function dailyExceeded(string $division, int $expectedCalls = 1): bool
    {
        $limit = $this->limit($division);

        if ($limit === null) {
            return false;
        }

        if ($limit->reset_at->isPast()) {
            return false;
        }

        return $limit->remaining < $expectedCalls;
    }

    public function minutelyExceeded(string $division, int $expectedCalls = 1): bool
    {
        $limit = $this->limit($division);

        if ($limit === null) {
            return false;
        }

        if ($limit->minutely_reset_at->isPast()) {
            return false;
        }

        return $limit->minutely_remaining < $expectedCalls;
    }

    public function limit(string $division): ?RateLimit
    {
        /** @var ?RateLimit $limit */
        $limit = RateLimit::query()
            ->where('exact_division', '=', $division)
            ->orderByDesc('timestamp')
            ->first();

        return $limit;
    }

    public static function bind(): void
    {
        app()->singleton(DeterminesRateLimited::class, static::class);
    }
}
