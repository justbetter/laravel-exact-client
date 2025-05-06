<?php

return [
    'after_auth_location' => '/',

    'prefix' => 'exact',

    'middleware' => [],

    'division' => 'default',

    'connections' => [
        'default' => [
            'client_id' => env('EXACT_CLIENT_ID'),
            'client_secret' => env('EXACT_CLIENT_SECRET'),
            'divisions' => [
                'default' => env('EXACT_DIVISION'),
            ],
        ],
    ],
];
