<?php

namespace JustBetter\ExactClient\Tests\Data;

use Illuminate\Validation\ValidationException;
use JustBetter\ExactClient\Tests\Fakes\FakeData;
use JustBetter\ExactClient\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class DataTest extends TestCase
{
    #[Test]
    public function it_can_interact_with_data(): void
    {
        $data = FakeData::of([
            'code' => '::code::',
        ]);

        $this->assertTrue(isset($data['code']));
        $this->assertEquals('::code::', $data['code']);

        $data['code'] = '::updated::';

        $this->assertEquals('::updated::', $data['code']);

        unset($data['code']);

        $this->assertNull($data['code']);
    }

    #[Test]
    public function it_can_throw_exceptions(): void
    {
        $this->expectException(ValidationException::class);

        FakeData::of([]);
    }

    #[Test]
    public function it_can_be_converted_into_an_array(): void
    {
        $payload = [
            'code' => '::code::',
        ];

        $data = FakeData::of($payload);

        $this->assertEquals($payload, $data->toArray());
    }
}
