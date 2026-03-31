<?php

declare(strict_types=1);

namespace JustBetter\ExactClient\Tests\Data;

use JustBetter\ExactClient\Data\ConnectionData;
use JustBetter\ExactClient\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

final class ConnectionDataTest extends TestCase
{
    #[Test]
    public function it_can_interact_with_connection_data(): void
    {
        $data = ConnectionData::of([
            'base_url' => '::base-url::',
            'code' => '::code::',
            'client_id' => '::client-id::',
            'client_secret' => '::client-secret::',
            'divisions' => [
                '::division-1::' => 1000,
            ],
        ]);

        $this->assertSame('::code::', $data->code());
        $this->assertSame('::client-id::', $data->clientId());
        $this->assertSame('::client-secret::', $data->clientSecret());
        $this->assertSame(['::division-1::' => 1000], $data->divisions());

        $this->assertSame(1000, $data->division('::division-1::'));
    }
}
