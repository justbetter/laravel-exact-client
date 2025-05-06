<?php

namespace JustBetter\ExactClient\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @property string $code
 */
class CallbackRequest extends FormRequest
{
    /** @return array<string, string> */
    public function rules(): array
    {
        return [
            'code' => 'required|string',
        ];
    }
}
