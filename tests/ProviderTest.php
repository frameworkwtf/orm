<?php

declare(strict_types=1);

namespace Wtf\ORM\Tests;

use PHPUnit\Framework\TestCase;

class ProviderTest extends TestCase
{
    protected $container;

    protected function setUp(): void
    {
        $dir = __DIR__.'/data/config';
        $app = new \Wtf\App($dir);
        $this->container = $app->getContainer();
    }

    public function testMedoo(): void
    {
        $this->assertInstanceOf('\Medoo\Medoo', $this->container->get('medoo'));
    }

    public function testEntityFactory(): void
    {
        $factory = $this->container->get('entity');
        $this->assertInstanceOf('\Wtf\ORM\Factory', $factory);
        $this->assertInstanceOf('\Wtf\ORM\Entity', $factory('dummy_entity'));
    }
}
