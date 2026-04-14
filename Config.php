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
];