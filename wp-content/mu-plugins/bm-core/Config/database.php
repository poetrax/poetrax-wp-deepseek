<?php

return [
    'default' => 'mysql',
    'connections' => [
        'mysql' => [
            'driver'    => 'mysql',
            'host'      => 'poetrax_deepseek_mysql',
            'database'  => 'u3436142_poetrax_deepseek_db',
            'username'  => 'u3436142_poetrax_deepseek_user',
            'password'  => getenv('DB_PASSWORD'),
            'charset'   => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
        ],
    ],
];
