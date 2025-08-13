<?php

namespace JustBetter\ExactClient\Tests\Listeners;

use GuzzleHttp\Psr7\Response as Psr7Response;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Carbon;
use JustBetter\ExactClient\Events\ExactResponseEvent;
use JustBetter\ExactClient\Models\RateLimit;
use JustBetter\ExactClient\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class StoreRateLimitsListenerTest extends TestCase
{
    #[Test]
    public function it_stores_rate_limits(): void
    {
        Carbon::setTestNow('2024-01-01 00:00:00');

        $response = new Response(
            new Psr7Response(200, [
                'X-RateLimit-Limit' => '1000',
                'X-RateLimit-Remaining' => '500',
                'X-RateLimit-Reset' => (string) now()->addMinute()->getTimestampMs(),
                'X-RateLimit-Minutely-Limit' => '60',
                'X-RateLimit-Minutely-Remaining' => '30',
                'X-RateLimit-Minutely-Reset' => (string) now()->addMinute()->getTimestampMs(),
            ])
        );

        ExactResponseEvent::dispatch($response, '::connection::', '::division::');

        /** @var RateLimit $rateLimit */
        $rateLimit = RateLimit::query()
            ->where('exact_division', '=', '::division::')
            ->where('timestamp', '=', now()->startOfDay()->getTimestamp())
            ->firstOrFail();

        $this->assertEquals(1000, $rateLimit->limit);
        $this->assertEquals(500, $rateLimit->remaining);
        $this->assertEquals(1704067260000, $rateLimit->reset_at->getTimestampMs());
        $this->assertEquals(60, $rateLimit->minutely_limit);
        $this->assertEquals(30, $rateLimit->minutely_remaining);
        $this->assertEquals(1704067260000, $rateLimit->minutely_reset_at->getTimestampMs());
    }

    #[Test]
    public function it_can_skip_failures(): void
    {
        Carbon::setTestNow('2024-01-01 00:00:00');

        $response = new Response(
            new Psr7Response(500, [
                'X-RateLimit-Limit' => '1000',
                'X-RateLimit-Remaining' => '500',
                'X-RateLimit-Reset' => (string) now()->addMinute()->getTimestampMs(),
                'X-RateLimit-Minutely-Limit' => '60',
                'X-RateLimit-Minutely-Remaining' => '30',
                'X-RateLimit-Minutely-Reset' => (string) now()->addMinute()->getTimestampMs(),
            ])
        );

        ExactResponseEvent::dispatch($response, '::connection::', '::division::');

        /** @var ?RateLimit $rateLimit */
        $rateLimit = RateLimit::query()
            ->where('exact_division', '=', '::division::')
            ->where('timestamp', '=', now()->getTimestamp())
            ->first();

        $this->assertNull($rateLimit);
    }

    #[Test]
    public function it_can_skip_when_headers_are_missing(): void
    {
        Carbon::setTestNow('2024-01-01 00:00:00');

        $response = new Response(
            new Psr7Response(200, [
                'X-RateLimit-Limit' => '1000',
                'X-RateLimit-Remaining' => '500',
                'X-RateLimit-Reset' => (string) now()->addMinute()->getTimestampMs(),
            ])
        );

        ExactResponseEvent::dispatch($response, '::connection::', '::division::');

        /** @var ?RateLimit $rateLimit */
        $rateLimit = RateLimit::query()
            ->where('exact_division', '=', '::division::')
            ->where('timestamp', '=', now()->getTimestamp())
            ->first();

        $this->assertNull($rateLimit);
    }
}
