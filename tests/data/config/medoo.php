<?php

declare(strict_types=1);

return [
    'namespace' => '\Wtf\ORM\Tests\Dummy\\',
    'database_type' => 'mysql',
    'database_name' => 'tisuit',
    'server' => '127.0.0.1',
    'username' => 'travis',
    'password' => '',
    'charset' => 'utf8',
    'port' => 3306,
    'option' => [
        PDO::ATTR_CASE => PDO::CASE_NATURAL,
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ],
];
