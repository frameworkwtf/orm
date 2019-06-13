<?php

declare(strict_types=1);

namespace Wtf\ORM;

class Factory extends \Wtf\Root
{
    /**
     * Entity factory.
     *
     * @param string $name Entity name
     *
     * @return Entity
     */
    public function __invoke(string $name): Entity
    {
        $alias = '__wtf_orm_entity_'.$name;
        if (!$this->container->has($alias)) {
            $parts = \explode('_', $name);
            $class = $this->config('wtf.namespace.entity', '\App\Entity\\');
            foreach ($parts as $part) {
                $class .= \ucfirst($part);
            }
            $this->container->add($alias, $class);
        }

        $class = $this->container->get($alias);

        return new $class($this->container);
    }
}
