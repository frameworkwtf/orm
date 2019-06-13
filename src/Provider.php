<?php

declare(strict_types=1);

namespace Wtf\ORM;

use League\Container\ServiceProvider\AbstractServiceProvider;

class Provider extends AbstractServiceProvider
{
    protected $provides = [
        'medoo',
        'entity',
    ];

    /**
     * {@inheritdoc}
     */
    public function register(): void
    {
        $container = $this->getContainer();
        $container->add('medoo', \Medoo\Medoo::class)->addArgument($container->get('config')('wtf.orm'));
        $container->add('entity', new Factory($container));
    }
}
