<?php

declare(strict_types=1);
// Enable Composer autoloader
/** @var \Composer\Autoload\ClassLoader $autoloader */
$autoloader = require \dirname(__DIR__).'/vendor/autoload.php';
// Register test classes
$autoloader->addPsr4('Wtf\\ORM\\Tests\\', __DIR__);
$autoloader->addPsr4('Wtf\\ORM\\Tests\\Dummy\\', __DIR__.'/data/dummy');
