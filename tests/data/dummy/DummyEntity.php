<?php

declare(strict_types=1);

namespace Wtf\ORM\Tests\Dummy;

class DummyEntity extends \Wtf\ORM\Entity
{
    /**
     * {@inheritdoc}
     */
    public function getTable(): string
    {
        return 'dummy';
    }

    /**
     * {@inheritdoc}
     */
    public function getValidators(): array
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getRelations(): array
    {
        return [];
    }
}
