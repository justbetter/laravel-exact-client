<?php

namespace JustBetter\ExactClient\Collections;

use Illuminate\Support\Collection;

/** @extends Collection<array-key, mixed> */
class ExactCollection extends Collection
{
    /** @param mixed $items */
    public function __construct($items = [])
    {
        if (isset($items['d'])) {
            $items = $items['d'];

            if (isset($items['results'])) {
                $items = $items['results'];
            }
        }

        parent::__construct($items);
    }
}
