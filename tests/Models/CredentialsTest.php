<?php

namespace JustBetter\ExactClient\Tests\Models;

use Illuminate\Support\Carbon;
use JustBetter\ExactClient\Models\Credentials;
use JustBetter\ExactClient\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

class CredentialsTest extends TestCase
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

    public static function experations(): array
    {
        return [
            'expired' => [
                'expiresAt' => '2024-08-16 09:50:00',
                'expired' => true,
            ],
            'not expired' => [
                'expiresAt' => '2024-08-16 10:10:00',
                'expired' => false,
            ],
            'just expired' => [
                'expiresAt' => '2024-08-16 10:00:29',
                'expired' => true,
            ],
            'about to expire' => [
                'expiresAt' => '2024-08-16 10:00:31',
                'expired' => false,
            ],
        ];
    }
}
