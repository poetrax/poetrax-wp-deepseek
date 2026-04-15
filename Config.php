<?php
return [
    'database' => [
        'dictionary_row_limit' => 1000,
        'cache_row_limit' => 5000,
    ],
    'cache' => [
        'ttl' => 3600,
        'prefix' => 'poetrax_',
        'warmup_ttl' => 86400,
    ],
    'pagination' => [
        'default_page' => 1,
        'default_limit' => 20,
        'max_limit' => 100,
    ],
	'track' => [
    'fields' => [
        'is_approved' => 'is_approved',
        'is_active' => 'is_active',
        'created_at' => 'created_at',
    ],
    'recent_limit' => 10,
	],
];