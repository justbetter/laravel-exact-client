<?php

use Illuminate\Http\Client\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use JustBetter\ExactClient\Client\Exact;
use JustBetter\ExactClient\Enums\ErrorCode;
use JustBetter\ExactClient\Exceptions\ExactAuthException;
use JustBetter\ExactClient\Exceptions\ExactException;
use JustBetter\ExactClient\Models\Credentials;
use JustBetter\ExactClient\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class ExactTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Event::fake();
        Exact::fake();
    }

    #[Test]
    public function it_can_set_the_division(): void
    {
        /** @var Exact $exact */
        $exact = app(Exact::class);
        $exact->division('::division::');

        $this->assertEquals('::division::', $exact->getDivision());
    }

    #[Test]
    public function it_can_make_get_calls(): void
    {
        Http::fake([
            'https://start.exactonline.nl/api/v1/0/endpoint' => Http::response(),
        ])->preventStrayRequests();

        /** @var Exact $exact */
        $exact = app(Exact::class);
        $exact->get('endpoint');

        Http::assertSent(function (Request $request): bool {
            return $request->url() === 'https://start.exactonline.nl/api/v1/0/endpoint'
                && $request->method() === 'GET';
        });
    }

    #[Test]
    public function it_can_make_post_calls(): void
    {
        Http::fake([
            'https://start.exactonline.nl/api/v1/0/endpoint' => Http::response(),
        ])->preventStrayRequests();

        /** @var Exact $exact */
        $exact = app(Exact::class);
        $exact->post('endpoint');

        Http::assertSent(function (Request $request): bool {
            return $request->url() === 'https://start.exactonline.nl/api/v1/0/endpoint'
                && $request->method() === 'POST';
        });
    }

    #[Test]
    public function it_can_make_put_calls(): void
    {
        Http::fake([
            'https://start.exactonline.nl/api/v1/0/endpoint' => Http::response(),
        ])->preventStrayRequests();

        /** @var Exact $exact */
        $exact = app(Exact::class);
        $exact->put('endpoint');

        Http::assertSent(function (Request $request): bool {
            return $request->url() === 'https://start.exactonline.nl/api/v1/0/endpoint'
                && $request->method() === 'PUT';
        });
    }

    #[Test]
    public function it_can_make_delete_calls(): void
    {
        Http::fake([
            'https://start.exactonline.nl/api/v1/0/endpoint' => Http::response(),
        ])->preventStrayRequests();

        /** @var Exact $exact */
        $exact = app(Exact::class);
        $exact->delete('endpoint');

        Http::assertSent(function (Request $request): bool {
            return $request->url() === 'https://start.exactonline.nl/api/v1/0/endpoint'
                && $request->method() === 'DELETE';
        });
    }

    #[Test]
    public function it_can_fetch_results_lazily(): void
    {
        Http::fake([
            'https://start.exactonline.nl/api/v1/0/endpoint?%24select=ID' => Http::response([
                'd' => [
                    'results' => [
                        [
                            'ID' => '1',
                        ],
                        [
                            'ID' => '2',
                        ],
                        [
                            'ID' => '3',
                        ],
                    ],
                    '__next' => 'https://start.exactonline.nl/api/v1/0/endpoint?%24select=ID&%24skiptoken=guid\'3\'',
                ],
            ]),
            'https://start.exactonline.nl/api/v1/0/endpoint?%24select=ID&%24skiptoken=guid\'3\'' => Http::response([
                'd' => [
                    'results' => [
                        [
                            'ID' => '4',
                        ],
                        [
                            'ID' => '5',
                        ],
                    ],
                    '__next' => null,
                ],
            ]),
        ])->preventStrayRequests();

        /** @var Exact $exact */
        $exact = app(Exact::class);

        $lazy = $exact->lazy('endpoint', [
            '$select' => 'ID',
        ]);

        $this->assertEquals(5, $lazy->collect()->count());

        Http::assertSentInOrder([
            function (Request $request): bool {
                return $request->url() === 'https://start.exactonline.nl/api/v1/0/endpoint?%24select=ID'
                    && $request->method() === 'GET';
            },
            function (Request $request): bool {
                return $request->url() === 'https://start.exactonline.nl/api/v1/0/endpoint?%24select=ID&%24skiptoken=guid\'3\''
                    && $request->method() === 'GET';
            },
        ]);
    }

    #[Test]
    public function it_can_throw_exceptions_if_a_top_is_used_when_fetching_results_lazily(): void
    {
        $this->expectException(ExactException::class);

        Http::fake([
            'https://start.exactonline.nl/api/v1/0/endpoint?%24select=ID&%24top=3' => Http::response([
                'd' => [
                    [
                        'ID' => '1',
                    ],
                    [
                        'ID' => '2',
                    ],
                    [
                        'ID' => '3',
                    ],
                ],
            ]),
        ])->preventStrayRequests();

        /** @var Exact $exact */
        $exact = app(Exact::class);

        $lazy = $exact->lazy('endpoint', [
            '$select' => 'ID',
            '$top' => 3,
        ]);

        $lazy->collect();
    }

    #[Test]
    public function it_can_require_authentication_when_credentials_are_unavailable(): void
    {
        /** @var Credentials $credentials */
        $credentials = Credentials::query()->firstOrFail();
        $credentials->delete();

        /** @var Exact $exact */
        $exact = app(Exact::class);

        $this->assertTrue(
            $exact->requiresAuthentication('default')
        );
    }

    #[Test]
    public function it_can_require_authentication_when_the_refresh_token_is_expired(): void
    {
        Carbon::setTestNow('2024-01-31 00:00:00');

        /** @var Credentials $credentials */
        $credentials = Credentials::query()->firstOrFail();
        $credentials->update([
            'expires_at' => '2024-01-01 00:00:00',
        ]);

        /** @var Exact $exact */
        $exact = app(Exact::class);

        $this->assertTrue(
            $exact->requiresAuthentication('default')
        );
    }

    #[Test]
    public function it_does_not_require_authentication_when_the_refresh_token_is_not_expired(): void
    {
        Carbon::setTestNow('2024-01-24 00:00:00');

        /** @var Credentials $credentials */
        $credentials = Credentials::query()->firstOrFail();
        $credentials->update([
            'expires_at' => '2024-01-01 00:00:00',
        ]);

        /** @var Exact $exact */
        $exact = app(Exact::class);

        $this->assertFalse(
            $exact->requiresAuthentication('default')
        );
    }

    #[Test]
    public function it_can_refresh_an_expired_token_before_a_call(): void
    {
        Http::fake([
            'https://start.exactonline.nl/api/oauth2/token' => Http::response([
                'access_token' => '::access-token::',
                'token_type' => '::token-type::',
                'expires_in' => '600',
                'refresh_token' => '::refresh-token::',
            ]),
            'https://start.exactonline.nl/api/v1/0/endpoint' => Http::response(),
        ])->preventStrayRequests();

        /** @var Credentials $credentials */
        $credentials = Credentials::query()->firstOrFail();
        $credentials->expires_at = now()->subYear();
        $credentials->save();

        /** @var Exact $exact */
        $exact = app(Exact::class);
        $exact->get('endpoint');

        Http::assertSentInOrder([
            function (Request $request): bool {
                return $request->url() === 'https://start.exactonline.nl/api/oauth2/token'
                    && $request->method() === 'POST';
            },
            function (Request $request): bool {
                return $request->url() === 'https://start.exactonline.nl/api/v1/0/endpoint'
                    && $request->method() === 'GET';
            },
        ]);
    }

    #[Test]
    public function it_can_fetch_all_divisions(): void
    {
        Http::fake([
            'https://start.exactonline.nl/api/v1/current/Me' => Http::response([
                'd' => [
                    'results' => [
                        [
                            'CurrentDivision' => 1000000,
                        ],
                    ],
                ],
            ]),
            'https://start.exactonline.nl/api/v1/1000000/system/Divisions' => Http::response([
                'd' => [
                    'results' => [
                        [
                            'Code' => '::code-1::',
                            'Description' => '::description-1::',
                        ],
                        [
                            'Code' => '::code-2::',
                            'Description' => '::description-2::',
                        ],
                        [
                            'Code' => '::code-3::',
                            'Description' => '::description-3::',
                        ],
                    ],
                ],
            ]),
        ])->preventStrayRequests();

        /** @var Exact $exact */
        $exact = app(Exact::class);

        $divisions = $exact->divisions('default');

        $this->assertCount(3, $divisions);
    }

    #[Test]
    public function it_can_throw_exceptions_if_there_are_no_divisions(): void
    {
        $this->expectException(ExactException::class);

        Http::fake([
            'https://start.exactonline.nl/api/v1/current/Me' => Http::response([
                'd' => [
                    'results' => [],
                ],
            ]),
        ])->preventStrayRequests();

        /** @var Exact $exact */
        $exact = app(Exact::class);
        $exact->divisions('default');
    }

    #[Test]
    public function it_can_get_the_auth_url(): void
    {
        /** @var Exact $exact */
        $exact = app(Exact::class);

        $authUrl = $exact->authUrl('default');

        $this->assertTrue(
            str_starts_with($authUrl, 'https://start.exactonline.nl/api/oauth2/auth')
        );
    }

    #[Test]
    public function it_can_request_an_access_token(): void
    {
        Http::fake([
            'https://start.exactonline.nl/api/oauth2/token' => Http::response([
                'access_token' => '::access-token::',
                'token_type' => '::token-type::',
                'expires_in' => '600',
                'refresh_token' => '::refresh-token::',
            ]),
        ])->preventStrayRequests();

        /** @var Exact $exact */
        $exact = app(Exact::class);
        $exact->token('default', '::code::');

        /** @var Credentials $credentials */
        $credentials = Credentials::query()->firstOrFail();

        $this->assertEquals('::access-token::', $credentials->access_token);
        $this->assertEquals('::token-type::', $credentials->token_type);
        $this->assertEquals('::refresh-token::', $credentials->refresh_token);

        Http::assertSent(function (Request $request): bool {
            return $request->method() === 'POST'
                && $request->data() === [
                    'code' => '::code::',
                    'redirect_uri' => 'http://localhost/exact/callback/default',
                    'grant_type' => 'authorization_code',
                    'client_id' => '::client-id::',
                    'client_secret' => '::client-secret::',
                ];
        });
    }

    #[Test]
    public function it_can_determine_if_a_token_is_expired(): void
    {
        /** @var Exact $exact */
        $exact = app(Exact::class);

        $this->assertFalse(
            $exact->expired('default')
        );
    }

    #[Test]
    public function it_can_throw_exceptions_if_credentials_are_unavailable(): void
    {
        $this->expectException(ExactAuthException::class);

        /** @var Credentials $credentials */
        $credentials = Credentials::query()->firstOrFail();
        $credentials->delete();

        /** @var Exact $exact */
        $exact = app(Exact::class);
        $exact->expired('default');
    }

    #[Test]
    public function it_can_refresh_tokens(): void
    {
        Http::fake([
            'https://start.exactonline.nl/api/oauth2/token' => Http::response([
                'access_token' => '::access-token-2::',
                'token_type' => '::token-type::',
                'expires_in' => '600',
                'refresh_token' => '::refresh-token-2::',
            ]),
        ])->preventStrayRequests();

        /** @var Credentials $credentials */
        $credentials = Credentials::query()->firstOrFail();
        $credentials->update([
            'expires_at' => now()->subYear(),
        ]);

        /** @var Exact $exact */
        $exact = app(Exact::class);
        $exact->refresh('default');

        $credentials->refresh();

        $this->assertEquals('::access-token-2::', $credentials->access_token);
        $this->assertEquals('::refresh-token-2::', $credentials->refresh_token);

        Http::assertSent(function (Request $request): bool {
            return $request->method() === 'POST'
                && $request->data() === [
                    'refresh_token' => '::refresh-token::',
                    'grant_type' => 'refresh_token',
                    'client_id' => '::client-id::',
                    'client_secret' => '::client-secret::',
                ];
        });
    }

    #[Test]
    public function it_throws_an_exception_if_the_credentials_are_not_available(): void
    {
        $this->expectException(ExactAuthException::class);

        /** @var Credentials $credentials */
        $credentials = Credentials::query()->firstOrFail();
        $credentials->delete();

        /** @var Exact $exact */
        $exact = app(Exact::class);
        $exact->refresh('default');
    }

    #[Test]
    public function it_wont_refresh_valid_tokens(): void
    {
        Http::fake()->preventStrayRequests();

        /** @var Credentials $credentials */
        $credentials = Credentials::query()->firstOrFail();
        $credentials->expires_at = now()->addYear();
        $credentials->save();

        /** @var Exact $exact */
        $exact = app(Exact::class);
        $exact->refresh('default');

        Http::assertNothingSent();
    }

    #[Test]
    public function it_throws_an_exception_if_an_invalid_grant_is_given(): void
    {
        $this->expectException(ExactAuthException::class);

        Http::fake([
            'https://start.exactonline.nl/api/oauth2/token' => Http::response([
                'error' => ErrorCode::InvalidGrant->value,
            ]),
        ])->preventStrayRequests();

        /** @var Credentials $credentials */
        $credentials = Credentials::query()->firstOrFail();
        $credentials->update([
            'expires_at' => now()->subYear(),
        ]);

        /** @var Exact $exact */
        $exact = app(Exact::class);
        $exact->refresh('default');
    }

    #[Test]
    public function it_throws_an_exception_if_a_token_cannot_be_refreshed(): void
    {
        $this->expectException(ExactAuthException::class);

        Http::fake([
            'https://start.exactonline.nl/api/oauth2/token' => Http::response(null, 500),
        ])->preventStrayRequests();

        /** @var Credentials $credentials */
        $credentials = Credentials::query()->firstOrFail();
        $credentials->expires_at = now()->subYear();
        $credentials->save();

        /** @var Exact $exact */
        $exact = app(Exact::class);
        $exact->refresh('default');
    }
}
