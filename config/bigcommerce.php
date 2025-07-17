<?php

return [
    /*
    |--------------------------------------------------------------------------
    | BigCommerce App Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for BigCommerce app integration
    |
    */

    'app' => [
        'client_id' => env('BC_APP_CLIENT_ID'),
        'secret' => env('BC_APP_SECRET'),
    ],

    'local' => [
        'client_id' => env('BC_LOCAL_CLIENT_ID'),
        'secret' => env('BC_LOCAL_SECRET'),
        'access_token' => env('BC_LOCAL_ACCESS_TOKEN'),
        'store_hash' => env('BC_LOCAL_STORE_HASH'),
    ],
];
