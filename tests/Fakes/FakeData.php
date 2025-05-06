<?php

namespace JustBetter\ExactClient\Tests\Fakes;

use JustBetter\ExactClient\Data\Data;

/** @extends Data<string, mixed> */
class FakeData extends Data
{
    public array $rules = [
        'code' => 'required|string',
    ];
}
