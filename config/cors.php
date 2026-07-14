<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Only the widget API path is cross-origin enabled.
    | Admin routes, forms, and behavior remain at framework defaults.
    |
    */
    'paths' => ['api/widget/*'],

    'allowed_methods' => ['GET'],

    'allowed_origins' => ['*'],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,

];
