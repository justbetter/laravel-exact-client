<?php

namespace JustBetter\ExactClient\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Http\Client\Response;

class ExactResponseEvent
{
    use Dispatchable;

    public function __construct(
        public Response $response,
        public string $connection,
        public string $division,
    ) {}
}
