<?php

return [

    'default' => env('DB_CONNECTION', 'pgsql'),

    'connections' => [

        // ── PostgreSQL (users, regions, default) ──
        'pgsql' => [
            'driver'         => 'pgsql',
            'host'           => env('DB_HOST', 'localhost'),
            'port'           => env('DB_PORT', '5432'),
            'database'       => env('DB_DATABASE', 'rentvly_db'),
            'username'       => env('DB_USERNAME', 'rentvly'),
            'password'       => env('DB_PASSWORD', 'rentvly'),
            'charset'        => 'utf8',
            'prefix'         => '',
            'prefix_indexes' => true,
            'search_path'    => 'public',
            'sslmode'        => 'prefer',
        ],

    ],

    'migrations' => [
        'table' => 'migrations',
        'update_date_on_change' => true,
    ],

];