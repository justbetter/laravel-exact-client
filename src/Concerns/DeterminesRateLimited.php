<?php

namespace JustBetter\ExactClient\Concerns;

use JustBetter\ExactClient\Models\RateLimit;

interface DeterminesRateLimited
{
    public function dailyExceeded(string $division, int $expectedCalls = 1): bool;

    public function minutelyExceeded(string $division, int $expectedCalls = 1): bool;

    public function limit(string $division): ?RateLimit;
}
