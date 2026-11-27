<?php

declare(strict_types=1);

namespace JustBetter\ExactClient\Data;

/** @extends Data<string, mixed> */
class ConnectionData extends Data
{
    public array $rules = [
        'base_url' => 'string|nullable',
        'code' => 'required|string',
        'client_id' => 'required|string',
        'client_secret' => 'required|string',
        'divisions' => 'array',
        'timeout' => 'integer',
        'connect_timeout' => 'integer',
    ];

    public function baseUrl(): string
    {
        return $this['base_url'] ?? 'https://start.exactonline.nl';
    }

    public function code(): string
    {
        return $this['code'];
    }

    public function clientId(): string
    {
        return $this['client_id'];
    }

    public function clientSecret(): string
    {
        return $this['client_secret'];
    }

    public function divisions(): array
    {
        return $this['divisions'];
    }

    public function division(string $code): int
    {
        return (int) $this['divisions'][$code];
    }

    public function timeout(): int
    {
        return $this['timeout'] ?? 30;
    }

    public function connectTimeout(): int
    {
        return $this['connect_timeout'] ?? 10;
    }
}
