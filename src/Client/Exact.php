<?php

namespace JustBetter\ExactClient\Client;

use Closure;
use Generator;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\LazyCollection;
use JustBetter\ExactClient\Collections\ExactCollection;
use JustBetter\ExactClient\Data\ConnectionData;
use JustBetter\ExactClient\Enums\ErrorCode;
use JustBetter\ExactClient\Events\ExactResponseEvent;
use JustBetter\ExactClient\Exceptions\ExactAuthException;
use JustBetter\ExactClient\Exceptions\ExactException;
use JustBetter\ExactClient\Models\Credentials;

class Exact
{
    public const BASE_URL = 'https://start.exactonline.nl';

    public const API_URL = '/api/v1';

    public const AUTH_URL = '/api/oauth2/auth';

    public const TOKEN_URL = '/api/oauth2/token';

    protected string $division;

    public function __construct(string $division)
    {
        $this->division($division);
    }

    public function division(string $division): static
    {
        $this->division = $division;

        return $this;
    }

    public function getDivision(): string
    {
        return $this->division;
    }

    public function get(string $path, ?array $data = null): Response
    {
        return $this->response(
            fn (PendingRequest $request): Response => $request->get($path, $data)
        );
    }

    public function post(string $path, array $data = []): Response
    {
        return $this->response(
            fn (PendingRequest $request): Response => $request->post($path, $data)
        );
    }

    public function put(string $path, array $data = []): Response
    {
        return $this->response(
            fn (PendingRequest $request): Response => $request->put($path, $data)
        );
    }

    public function delete(string $path, array $data = []): Response
    {
        return $this->response(
            fn (PendingRequest $request): Response => $request->delete($path, $data)
        );
    }

    /** @return LazyCollection<int, array> */
    public function lazy(string $path, array $data = []): LazyCollection
    {
        return LazyCollection::make(function () use ($path, $data): Generator {
            $nextPage = $path;

            while ($nextPage !== null) {
                $response = $this->get($nextPage, $data)->throw();

                $d = $response->json('d', []);

                if (! array_key_exists('results', $d)) {
                    throw new ExactException(
                        'The key "results" is missing. You should not add "$top" to the lazy method.'
                    );
                }

                foreach ($d['results'] as $result) {
                    yield $result;
                }

                $nextPage = $d['__next'] ?? null;
                $data = null; // All filters are included in the __next link.
            }
        });
    }

    public function divisions(string $connection): array
    {
        $results = $this
            ->request($connection, 'current')
            ->get('Me')
            ->throw()
            ->collect()
            ->pipeInto(ExactCollection::class);

        if ($results->isEmpty()) {
            throw new ExactException('There are no results of the current user.');
        }

        $currentDivision = $results[0]['CurrentDivision'];

        return $this
            ->request($connection, $currentDivision)
            ->get('system/Divisions')
            ->throw()
            ->collect()
            ->pipeInto(ExactCollection::class)
            ->toArray();
    }

    public function requiresAuthentication(string $connection): bool
    {
        /** @var ?Credentials $credentials */
        $credentials = Credentials::query()
            ->where('exact_connection', '=', $connection)
            ->first();

        if ($credentials === null || $credentials->expires_at === null) {
            return true;
        }

        // Refresh token expires after 30 days
        return $credentials->expires_at->lessThan(now()->subDays(29));
    }

    protected function request(string $connection, int|string $division): PendingRequest
    {
        if ($this->expired($connection)) {
            $this->refresh($connection);
        }

        $request = Http::baseUrl(static::BASE_URL.static::API_URL.'/'.$division)
            ->acceptJson()
            ->asJson();

        /** @var Credentials $credentials */
        $credentials = Credentials::query()
            ->where('exact_connection', '=', $connection)
            ->firstOrFail();

        return $request->withToken($credentials->access_token);
    }

    /** @return Collection<string, ConnectionData> */
    public function connections(): Collection
    {
        /** @var array<string, array<string, mixed>> $connections */
        $connections = config()->array('exact.connections', []);

        return collect($connections)->map(function (array $connection, string $code): ConnectionData {
            return ConnectionData::of(
                array_merge($connection, [
                    'code' => $code,
                ])
            );
        });
    }

    public function connection(string $code): ConnectionData
    {
        return $this
            ->connections()
            ->firstOrFail(fn (ConnectionData $connection): bool => $connection->code() === $code);
    }

    public function connectionByDivision(string $division): ConnectionData
    {
        return $this
            ->connections()
            ->firstOrFail(fn (ConnectionData $connection): bool => array_key_exists($division, $connection->divisions()));
    }

    protected function response(Closure $closure): Response
    {
        $connection = $this->connectionByDivision($this->division);

        $request = $this->request(
            $connection->code(),
            $connection->division($this->division),
        );

        $response = call_user_func($closure, $request);

        event(new ExactResponseEvent($response, $connection->code(), $this->division));

        return $response;
    }

    public function authUrl(string $connection): string
    {
        $data = $this->connection($connection);

        return static::BASE_URL.static::AUTH_URL.'?'.http_build_query([
            'client_id' => $data->clientId(),
            'redirect_uri' => route('exact.auth.callback', ['connection' => $connection]),
            'response_type' => 'code',
            'force_login' => 1,
        ]);
    }

    public function token(string $connection, string $code): void
    {
        $data = $this->connection($connection);

        $response = Http::baseUrl(static::BASE_URL)
            ->asForm()
            ->post(static::TOKEN_URL, [
                'code' => $code,
                'redirect_uri' => route('exact.auth.callback', ['connection' => $connection]),
                'grant_type' => 'authorization_code',
                'client_id' => $data->clientId(),
                'client_secret' => $data->clientSecret(),
            ])->throw();

        Credentials::query()->updateOrCreate([
            'exact_connection' => $connection,
        ], [
            'access_token' => $response->json('access_token'),
            'token_type' => $response->json('token_type'),
            'refresh_token' => $response->json('refresh_token'),
            'expires_at' => now()->addSeconds((int) $response->json('expires_in')),
        ]);
    }

    public function expired(string $connection): bool
    {
        /** @var ?Credentials $credentials */
        $credentials = Credentials::query()
            ->where('exact_connection', '=', $connection)
            ->first();

        if ($credentials === null) {
            throw new ExactAuthException('No credentials for connection "'.$connection.'"');
        }

        return $credentials->expired();
    }

    public function refresh(string $connection): void
    {
        cache()->lock('exact-client:refresh:'.$connection, 35)->block(60, function () use ($connection): void {
            /** @var ?Credentials $credentials */
            $credentials = Credentials::query()
                ->where('exact_connection', '=', $connection)
                ->first();

            if ($credentials === null) {
                throw new ExactAuthException('No credentials for connection "'.$connection.'"');
            }

            if (! $credentials->expired()) {
                return;
            }

            $data = $this->connection($connection);

            $response = Http::baseUrl(static::BASE_URL)
                ->timeout(30)
                ->connectTimeout(30)
                ->asForm()
                ->post(static::TOKEN_URL, [
                    'refresh_token' => html_entity_decode($credentials->refresh_token),
                    'grant_type' => 'refresh_token',
                    'client_id' => $data->clientId(),
                    'client_secret' => $data->clientSecret(),
                ]);

            $error = $response->json('error');

            if ($error === ErrorCode::InvalidGrant->value) {
                $credentials->delete();

                logger()->error('[Exact] Failed to refresh credentials for connection "'.$connection.'": '.$response->body());

                throw new ExactAuthException('Connection "'.$connection.'" could not be refreshed.');
            }

            if ($response->failed()) {
                activity()
                    ->on($credentials)
                    ->useLog('exact')
                    ->withProperties([
                        'status' => $response->status(),
                        'response' => $response->body(),
                    ])
                    ->log('Failed to refresh credentials for connection "'.$connection.'"');

                throw new ExactAuthException('Failed to refresh credentials for connection "'.$connection.'"');
            }

            $credentials->update([
                'access_token' => $response->json('access_token'),
                'token_type' => $response->json('token_type'),
                'refresh_token' => $response->json('refresh_token'),
                'expires_at' => now()->addSeconds((int) $response->json('expires_in')),
            ]);
        });
    }

    public static function fake(): void
    {
        $index = 0;

        config()->set('exact.division', 'default');

        foreach (config()->array('exact.connections', []) as $connection => $data) {
            config()->set('exact.connections.'.$connection.'.client_id', '::client-id::');
            config()->set('exact.connections.'.$connection.'.client_secret', '::client-secret::');

            Credentials::query()->create([
                'exact_connection' => $connection,
                'access_token' => '::access-token::',
                'token_type' => '::token-type::',
                'refresh_token' => '::refresh-token::',
                'expires_at' => now()->addYear(),
            ]);

            foreach (config()->array('exact.connections.'.$connection.'.divisions', []) as $code => $division) {
                config()->set('exact.connections.'.$connection.'.divisions.'.$code, $index++);
            }
        }
    }
}
