<?php

namespace JustBetter\ExactClient\Commands;

use Illuminate\Console\Command;
use JustBetter\ExactClient\Client\Exact;

class RefreshTokenCommand extends Command
{
    protected $signature = 'exact:refresh-token {connection}';

    protected $description = 'Acquire token if expired';

    public function handle(Exact $exact): int
    {
        /** @var string $connection */
        $connection = $this->argument('connection');

        $exact->refresh($connection);

        return static::SUCCESS;
    }
}
