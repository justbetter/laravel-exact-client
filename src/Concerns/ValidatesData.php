<?php

namespace JustBetter\ExactClient\Concerns;

use Illuminate\Support\Facades\Validator;

trait ValidatesData
{
    public array $rules = [];

    public function validate(array $data): void
    {
        Validator::make($data, $this->rules)->validate();
    }
}
