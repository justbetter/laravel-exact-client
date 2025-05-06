<?php

namespace JustBetter\ExactClient\Tests\Collections;

use JustBetter\ExactClient\Collections\ExactCollection;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ExactCollectionTest extends TestCase
{
    #[Test]
    public function it_can_instantiate_a_collection_with_flat_items(): void
    {
        $items = [
            1,
            2,
            3,
        ];

        $collection = new ExactCollection($items);

        $this->assertCount(3, $collection);
    }

    #[Test]
    public function it_can_instantiate_a_collection_with_single_nested_items(): void
    {
        $items = [
            'd' => [
                1,
                2,
                3,
            ],
        ];

        $collection = new ExactCollection($items);

        $this->assertCount(3, $collection);
    }

    #[Test]
    public function it_can_instantiate_a_collection_with_double_nested_items(): void
    {
        $items = [
            'd' => [
                'results' => [
                    1,
                    2,
                    3,
                ],
            ],
        ];

        $collection = new ExactCollection($items);

        $this->assertCount(3, $collection);
    }
}
