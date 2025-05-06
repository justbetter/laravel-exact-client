<?php

namespace JustBetter\ExactClient\Tests\Actions;

use Illuminate\Support\Carbon;
use JustBetter\ExactClient\Actions\DetermineRateLimited;
use JustBetter\ExactClient\Models\RateLimit;
use JustBetter\ExactClient\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class DetermineRateLimitedTest extends TestCase
{
    #[Test]
    public function it_can_passes_daily_limit(): void
    {
        Carbon::setTestNow('2024-01-01 00:00:00');

        RateLimit::query()->create([
            'exact_division' => 'default',
            'timestamp' => now()->startOfDay()->getTimestamp(),
            'limit' => 1000,
            'remaining' => 1000,
            'reset_at' => now()->addDay()->startOfDay(),
            'minutely_limit' => 60,
            'minutely_remaining' => 0,
            'minutely_reset_at' => now()->addMinute(),
        ]);

        /** @var DetermineRateLimited $action */
        $action = app(DetermineRateLimited::class);

        $this->assertFalse($action->dailyExceeded('default', 500));
    }

    #[Test]
    public function it_can_passes_daily_limit_on_reset_at(): void
    {
        Carbon::setTestNow('2024-01-01 00:00:00');

        RateLimit::query()->create([
            'exact_division' => 'default',
            'timestamp' => now()->startOfDay()->getTimestamp(),
            'limit' => 1000,
            'remaining' => 0,
            'reset_at' => now()->subDay()->startOfDay(),
            'minutely_limit' => 60,
            'minutely_remaining' => 0,
            'minutely_reset_at' => now()->addMinute(),
        ]);

        /** @var DetermineRateLimited $action */
        $action = app(DetermineRateLimited::class);

        $this->assertFalse($action->dailyExceeded('default'));
    }

    #[Test]
    public function it_can_fails_daily_limit(): void
    {
        Carbon::setTestNow('2024-01-01 00:00:00');

        RateLimit::query()->create([
            'exact_division' => 'default',
            'timestamp' => now()->startOfDay()->getTimestamp(),
            'limit' => 1000,
            'remaining' => 0,
            'reset_at' => now()->addDay()->startOfDay(),
            'minutely_limit' => 60,
            'minutely_remaining' => 0,
            'minutely_reset_at' => now()->addMinute(),
        ]);

        /** @var DetermineRateLimited $action */
        $action = app(DetermineRateLimited::class);

        $this->assertTrue($action->dailyExceeded('default'));
    }

    #[Test]
    public function it_can_passes_minutely_limit(): void
    {
        Carbon::setTestNow('2024-01-01 00:00:00');

        RateLimit::query()->create([
            'exact_division' => 'default',
            'timestamp' => now()->startOfDay()->getTimestamp(),
            'limit' => 1000,
            'remaining' => 1000,
            'reset_at' => now()->addDay()->startOfDay(),
            'minutely_limit' => 60,
            'minutely_remaining' => 10,
            'minutely_reset_at' => now()->addMinute(),
        ]);

        /** @var DetermineRateLimited $action */
        $action = app(DetermineRateLimited::class);

        $this->assertFalse($action->minutelyExceeded('default', 5));
    }

    #[Test]
    public function it_can_passes_minutely_limit_on_reset_at(): void
    {
        Carbon::setTestNow('2024-01-01 00:00:00');

        RateLimit::query()->create([
            'exact_division' => 'default',
            'timestamp' => now()->startOfDay()->getTimestamp(),
            'limit' => 1000,
            'remaining' => 1000,
            'reset_at' => now()->subDay()->startOfDay(),
            'minutely_limit' => 60,
            'minutely_remaining' => 0,
            'minutely_reset_at' => now()->subDay(),
        ]);

        /** @var DetermineRateLimited $action */
        $action = app(DetermineRateLimited::class);

        $this->assertFalse($action->minutelyExceeded('default'));
    }

    #[Test]
    public function it_can_fails_minutely_limit(): void
    {
        Carbon::setTestNow('2024-01-01 00:00:00');

        RateLimit::query()->create([
            'exact_division' => 'default',
            'timestamp' => now()->startOfDay()->getTimestamp(),
            'limit' => 1000,
            'remaining' => 1000,
            'reset_at' => now()->addDay()->startOfDay(),
            'minutely_limit' => 60,
            'minutely_remaining' => 0,
            'minutely_reset_at' => now()->addMinute(),
        ]);

        /** @var DetermineRateLimited $action */
        $action = app(DetermineRateLimited::class);

        $this->assertTrue($action->minutelyExceeded('default'));
    }
}
