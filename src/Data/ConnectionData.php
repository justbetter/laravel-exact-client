<?php

namespace JustBetter\ExactClient\Data;

/** @extends Data<string, mixed> */
class ConnectionData extends Data
{
    public array $rules = [
        'code' => 'required|string',
        'client_id' => 'required|string',
        'client_secret' => 'required|string',
        'divisions' => 'array',
    ];

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
        return $this['divisions'][$code];
    }
}
