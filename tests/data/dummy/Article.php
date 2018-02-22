<?php

declare(strict_types=1);

namespace Wtf\ORM\Tests\Dummy;

use Respect\Validation\Validator as v;

class Article extends \Wtf\ORM\Entity
{
    /**
     * {@inheritdoc}
     */
    public function getTable(): string
    {
        return 'articles';
    }

    /**
     * {@inheritdoc}
     */
    public function getValidators(): array
    {
        return [
            'save' => [
                'title' => v::stringType()->length(1, 255),
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getRelations(): array
    {
        return [
            'author' => [
                'entity' => 'user',
                'key' => 'author_id',
                'foreign_key' => 'id',
            ],
        ];
    }
}
