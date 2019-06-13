# ORM
[![Build Status](https://travis-ci.org/frameworkwtf/orm.svg?branch=2.x)](https://travis-ci.org/frameworkwtf/orm) [![Coverage Status](https://coveralls.io/repos/frameworkwtf/orm/badge.svg?branch=2.x&service=github)](https://coveralls.io/github/frameworkwtf/orm?branch=2.x)

ORM, based on [medoo.in](http://medoo.in)

[Documentation](https://framework.wtf/orm)

## Changes in 2.x public api

1. **Validations** moved from dependencies to suggests, `Entity::validate()` method removed.
2. **Collection** removed (as it removed from Slim 4.x). Returns `array` instead `\Slim\Collection`
3. If `wtf/middleware-filters` middleware is used, it automatically applies on `Entity::loadAll()` method
4. Configuration moved from `config/medoo.php` to `config/wtf.php`:

```php
<?php
return [
    // ...
    'namespace' => [
        // ...
        'entity' => '\App\Entity',
    ],
    // ...
    'orm' => [
        'database_type' => 'mysql',
        'database_name' => 'wtf',
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
    ],
    // ...
];
```
