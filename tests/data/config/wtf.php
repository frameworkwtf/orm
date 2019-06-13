<?php

declare(strict_types=1);

return [
    'providers' => [
        '\Wtf\ORM\Provider',
    ],
    'orm' => [
        'database_type' => ('postgres' === \getenv('DB') ? 'pgsql' : 'mysql'),
        'database_name' => 'wtf',
        'server' => '127.0.0.1',
        'username' => \getenv('DB_USER') ?? 'travis',
        'password' => '',
        'charset' => 'utf8',
        'port' => 3306,
        'option' => [
            PDO::ATTR_CASE => PDO::CASE_NATURAL,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ],
    ],
    'namespace' => [
        'entity' => '\Wtf\ORM\Tests\Dummy\\',
    ],
];
