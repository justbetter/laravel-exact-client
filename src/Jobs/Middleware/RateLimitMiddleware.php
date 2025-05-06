<?php

namespace JustBetter\ExactClient\Jobs\Middleware;

use Closure;
use JustBetter\ExactClient\Concerns\DeterminesRateLimited;
use JustBetter\ExactClient\Exceptions\ExactRateLimitedException;

class RateLimitMiddleware
{
    public function __construct(
        protected string $division,
        protected int $expectedCalls,
    ) {}

    public static function division(string $division, int $expectedCalls = 1): self
    {
        return new self($division, $expectedCalls);
    }

    public function handle(object $job, Closure $next): void
    {
        /** @var DeterminesRatelimited $determineRatelimited */
        $determineRatelimited = app(DeterminesRateLimited::class);

        // Daily limit has been exceeded.
        throw_if(
            $determineRatelimited->dailyExceeded($this->division, $this->expectedCalls),
            ExactRateLimitedException::class
        );

        if (! $determineRatelimited->minutelyExceeded($this->division, $this->expectedCalls)) {
            $next($job);
        } else {
            if (method_exists($job, 'release')) {
                $limit = $determineRatelimited->limit($this->division);

                $seconds = $limit === null
                    ? 60
                    : (int) now()->diffInSeconds($limit->minutely_reset_at);

                $job->release($seconds);
            }
        }
    }
}
