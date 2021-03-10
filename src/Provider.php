<?php

declare(strict_types=1);

namespace Wtf\ORM;

use Pimple\Container;
use Pimple\ServiceProviderInterface;

class Provider implements ServiceProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function register(Container $container): void
    {
        $container['medoo'] = $this->setMedoo($container);
        $container['entity'] = $this->setEntityLoader($container);
    }

    /**
     * Set Medoo into container.
     */
    protected function setMedoo(Container $container): callable
    {
        return function ($container) {
            $config = $container['config']('medoo');

            return new \Medoo\Medoo($config);
        };
    }

    /**
     * Set entity() function into container.
     */
    protected function setEntityLoader(Container $container): callable
    {
        return $container->protect(function (string $name) use ($container) {
            $parts = \explode('_', $name);
            $class = $container['config']('medoo.namespace');
            foreach ($parts as $part) {
                $class .= \ucfirst($part);
            }
            if (!$container->has('entity_'.$class)) {
                $container['entity_'.$class] = $container->factory(function ($container) use ($class) {
                    return new $class($container);
                });
            }

            return $container['entity_'.$class];
        });
    }
}
