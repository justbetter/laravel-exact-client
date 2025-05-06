<?php

namespace JustBetter\ExactClient\Tests\Commands;

use Illuminate\Testing\PendingCommand;
use JustBetter\ExactClient\Client\Exact;
use JustBetter\ExactClient\Tests\TestCase;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;

class RefreshTokenCommandTest extends TestCase
{
    #[Test]
    public function it_can_refresh_token(): void
    {
        $this->mock(Exact::class, function (MockInterface $mock): void {
            $mock
                ->shouldReceive('refresh')
                ->with('::connection::')
                ->once();
        });

        /** @var PendingCommand $command */
        $command = $this->artisan('exact:refresh-token', [
            'connection' => '::connection::',
        ]);

        $command->assertSuccessful();
    }
}
