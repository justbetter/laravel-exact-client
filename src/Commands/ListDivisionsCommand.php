<?php

declare(strict_types=1);

namespace JustBetter\ExactClient\Commands;

use Illuminate\Console\Command;
use JustBetter\ExactClient\Client\Exact;

class ListDivisionsCommand extends Command
{
    protected $signature = 'exact:list-divisions {connection}';

    protected $description = 'List divisions in Exact';

    public function handle(Exact $exact): int
    {
        /** @var string $connection */
        $connection = $this->argument('connection');

        $divisions = $exact->divisions($connection);

        $rows = collect($divisions)->map(fn (array $division): array => [
            $division['Code'],
            $division['Description'],
        ])->toArray();

        $this->table(['Code', 'Description'], $rows);

        return static::SUCCESS;
    }
}
