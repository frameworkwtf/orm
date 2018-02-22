<?php

declare(strict_types=1);

namespace Wtf\ORM\Tests\Dummy;

use Respect\Validation\Validator as v;

class User extends \Wtf\ORM\Entity
{
    /**
     * {@inheritdoc}
     */
    public function getTable(): string
    {
        return 'users';
    }

    /**
     * {@inheritdoc}
     */
    public function getValidators(): array
    {
        return [
            'save' => [
                'name' => v::stringType()->length(1, 255),
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getRelations(): array
    {
        return [
            'articles' => [
                'entity' => 'article',
                'type' => 'has_many',
                'foreign_key' => 'author_id',
            ],
            'error_relation' => [],
        ];
    }
}
