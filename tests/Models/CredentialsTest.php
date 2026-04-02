<?php

declare(strict_types=1);

namespace JustBetter\ExactClient\Tests\Models;

use Illuminate\Support\Carbon;
use JustBetter\ExactClient\Models\Credentials;
use JustBetter\ExactClient\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

final class CredentialsTest extends TestCase
{
    #[Test]
    #[DataProvider('experations')]
    public function it_checks_expired(string $expiresAt, bool $expired): void
    {
        Carbon::setTestNow('2024-08-16 10:00:00');

        $credentials = new Credentials([
            'expires_at' => $expiresAt,
        ]);

        $this->assertSame($expired, $credentials->expired());
    }

    public static function experations(): \Iterator
    {
        yield 'expired' => [
            'expiresAt' => '2024-08-16 09:50:00',
            'expired' => true,
        ];
        yield 'not expired' => [
            'expiresAt' => '2024-08-16 10:10:00',
            'expired' => false,
        ];
        yield 'just expired' => [
            'expiresAt' => '2024-08-16 10:00:29',
            'expired' => true,
        ];
        yield 'about to expire' => [
            'expiresAt' => '2024-08-16 10:00:31',
            'expired' => false,
        ];
    }
}
