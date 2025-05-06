<?php

namespace JustBetter\ExactClient\Tests\Http\Controllers;

use JustBetter\ExactClient\Client\Exact;
use JustBetter\ExactClient\Tests\TestCase;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;

class AuthControllerTest extends TestCase
{
    #[Test]
    public function it_can_redirect(): void
    {
        $this->mock(Exact::class, function (MockInterface $mock): void {
            $mock
                ->shouldReceive('authUrl')
                ->with('::connection::')
                ->once()
                ->andReturn('https://example.com/auth');
        });

        $this
            ->get(route('exact.auth.redirect', ['connection' => '::connection::']))
            ->assertRedirect('https://example.com/auth');
    }

    #[Test]
    public function it_can_handle_the_callback(): void
    {
        config()->set('exact.after_auth_location', 'https://example.com');

        $this->mock(Exact::class, function (MockInterface $mock): void {
            $mock
                ->shouldReceive('token')
                ->with('::connection::', '::code::')
                ->once();
        });

        $this
            ->get(route('exact.auth.callback', ['connection' => '::connection::', 'code' => '::code::']))
            ->assertRedirect('https://example.com');
    }
}
