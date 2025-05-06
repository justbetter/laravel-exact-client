<?php

namespace JustBetter\ExactClient\Tests\Data;

use JustBetter\ExactClient\Data\ConnectionData;
use JustBetter\ExactClient\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class ConnectionDataTest extends TestCase
{
    #[Test]
    public function it_can_interact_with_connection_data(): void
    {
        $data = ConnectionData::of([
            'code' => '::code::',
            'client_id' => '::client-id::',
            'client_secret' => '::client-secret::',
            'divisions' => [
                '::division-1::' => 1000,
            ],
        ]);

        $this->assertEquals('::code::', $data->code());
        $this->assertEquals('::client-id::', $data->clientId());
        $this->assertEquals('::client-secret::', $data->clientSecret());
        $this->assertEquals(['::division-1::' => 1000], $data->divisions());

        $this->assertEquals(1000, $data->division('::division-1::'));
    }
}
