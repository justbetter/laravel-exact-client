<?php

namespace JustBetter\ExactClient\Tests\Commands;

use Illuminate\Testing\PendingCommand;
use JustBetter\ExactClient\Client\Exact;
use JustBetter\ExactClient\Tests\TestCase;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;

class ListDivisionsCommandTest extends TestCase
{
    #[Test]
    public function it_can_list_divisions(): void
    {
        $this->mock(Exact::class, function (MockInterface $mock): void {
            $mock
                ->shouldReceive('divisions')
                ->with('::connection::')
                ->once()
                ->andReturn([
                    [
                        'Code' => '::code-1::',
                        'Description' => '::description-1::',
                    ],
                    [
                        'Code' => '::code-2::',
                        'Description' => '::description-2::',
                    ],
                ]);
        });

        /** @var PendingCommand $command */
        $command = $this->artisan('exact:list-divisions', [
            'connection' => '::connection::',
        ]);

        $command
            ->assertSuccessful()
            ->expectsTable(['Code', 'Description'], [
                ['::code-1::', '::description-1::'],
                ['::code-2::', '::description-2::'],
            ])
            ->run();
    }
}
