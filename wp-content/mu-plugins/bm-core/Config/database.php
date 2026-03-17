<?php
return [
    'default' => 'mysql',
    'connections' => [
        'mysql' => [
            'driver'    => 'mysql',
            'host'      => 'localhost',
            'database'  => 'u3436142_default',  
            'username'  => 'u3436142_default',  
            'password'  => getenv('DB_PASSWORD'), 
            'charset'   => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix'    => 'bm_',
        ],
    ],
];
