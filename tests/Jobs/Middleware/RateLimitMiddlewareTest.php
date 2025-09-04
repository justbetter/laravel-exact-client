<?php

namespace JustBetter\ExactClient\Tests\Jobs\Middleware;

use Illuminate\Support\Carbon;
use JustBetter\ExactClient\Client\Exact;
use JustBetter\ExactClient\Concerns\DeterminesRateLimited;
use JustBetter\ExactClient\Exceptions\ExactRateLimitedException;
use JustBetter\ExactClient\Jobs\Middleware\RateLimitMiddleware;
use JustBetter\ExactClient\Models\RateLimit;
use JustBetter\ExactClient\Tests\Fakes\TestJob;
use JustBetter\ExactClient\Tests\TestCase;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;

class RateLimitMiddlewareTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Exact::fake();
    }

    #[Test]
    public function it_can_dispatch_jobs(): void
    {
        $middleware = RateLimitMiddleware::division('default');

        $job = new TestJob;
        $ran = false;

        $middleware->handle($job, function () use (&$ran): void {
            $ran = true;
        });

        $this->assertTrue($ran);
    }

    #[Test]
    public function it_can_throw_exceptions(): void
    {
        $this->expectException(ExactRateLimitedException::class);

        $middleware = RateLimitMiddleware::division('default');

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

        $job = new TestJob;

        $middleware->handle($job, function (): void {
            $this->fail('Job should not have run.');
        });
    }

    #[Test]
    public function it_can_release_jobs(): void
    {
        Carbon::setTestNow('2024-01-01 00:00:00');

        $middleware = RateLimitMiddleware::division('default');

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

        $job = $this->mock(TestJob::class, function (MockInterface $mock): void {
            $mock
                ->shouldReceive('release')
                ->with(60)
                ->once();
        });

        $middleware->handle($job, function (): void {
            $this->fail('Job should not have run.');
        });
    }

    #[Test]
    public function it_can_release_jobs_with_a_default_limit(): void
    {
        Carbon::setTestNow('2024-01-01 00:00:00');

        $middleware = RateLimitMiddleware::division('default');

        $this->mock(DeterminesRateLimited::class, function (MockInterface $mock): void {
            $mock
                ->shouldReceive('dailyExceeded')
                ->with('default', 1)
                ->once()
                ->andReturnFalse();

            $mock
                ->shouldReceive('minutelyExceeded')
                ->with('default', 1)
                ->once()
                ->andReturnTrue();

            $mock
                ->shouldReceive('limit')
                ->with('default')
                ->once()
                ->andReturnNull();
        });

        $job = $this->mock(TestJob::class, function (MockInterface $mock): void {
            $mock
                ->shouldReceive('release')
                ->with(60)
                ->once();
        });

        $middleware->handle($job, function (): void {
            $this->fail('Job should not have run.');
        });
    }

    #[Test]
    public function it_can_release_job_if_the_expected_calls_are_exceeded(): void
    {
        Carbon::setTestNow('2024-01-01 00:00:00');

        RateLimit::query()->create([
            'exact_division' => 'default',
            'timestamp' => now()->startOfDay()->getTimestamp(),
            'limit' => 1000,
            'remaining' => 1000,
            'reset_at' => now()->addDay()->startOfDay(),
            'minutely_limit' => 60,
            'minutely_remaining' => 1,
            'minutely_reset_at' => now()->addMinute(),
        ]);

        $jobOne = $this->mock(TestJob::class, function (MockInterface $mock): void {
            $mock->shouldNotReceive('release');
        });

        $middlewareOne = RateLimitMiddleware::division('default', 1);
        $middlewareOne->handle($jobOne, function (): void {
            //
        });

        $jobTwo = $this->mock(TestJob::class, function (MockInterface $mock): void {
            $mock
                ->shouldReceive('release')
                ->with(60)
                ->once();
        });

        $middlewareTwo = RateLimitMiddleware::division('default', 2);
        $middlewareTwo->handle($jobTwo, function (): void {
            $this->fail('Job should not have run.');
        });
    }
}
