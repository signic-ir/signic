<?php

return [

    // CORS is owned by nginx (nginx/conf.d/cors.conf) — it applies the policy
    // to every path uniformly, including the module prefixes that are not
    // under /api/ (e.g. /access/checkin, /registration/...). Enabling it here
    // as well would emit two Access-Control-Allow-Origin headers for the same
    // request, which browsers reject when the values differ and which makes
    // the allowed origin ambiguous when they match.
    'paths' => [],

    'allowed_methods' => ['*'],

    'allowed_origins' => [env('FRONTEND_URL', 'http://localhost:3000')],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,

];
