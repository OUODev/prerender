<?php

return [

    'enabled' => (bool) env('PRERENDER_ENABLED', true),

    'prerender_url' => env('PRERENDER_URL', 'http://127.0.0.1'),

    'timeout' => (int) env('PRERENDER_TIMEOUT', 5),

    'blacklist' => [
        'api/*',
        'admin/*',
        'uploads/*',
        'assets/*',
        'admin_assets/*',
        'member_assets/*',
        'img/*',
    ],

];
