<?php

namespace JustBetter\ExactClient\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use JustBetter\ExactClient\Client\Exact;
use JustBetter\ExactClient\Http\Requests\CallbackRequest;

class AuthController
{
    public function redirect(Exact $exact, string $connection): RedirectResponse
    {
        return response()->redirectTo($exact->authUrl($connection));
    }

    public function callback(CallbackRequest $request, Exact $exact, string $connection): RedirectResponse
    {
        $exact->token($connection, $request->code);

        return response()->redirectTo(
            config()->string('exact.after_auth_location')
        );
    }
}
