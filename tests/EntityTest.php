<?php

declare(strict_types=1);

namespace Wtf\ORM\Tests;

use PHPUnit\Framework\TestCase;

class EntityTest extends TestCase
{
    protected $container;

    protected function setUp(): void
    {
        //Init app
        $dir = __DIR__.'/data/config';
        $app = new \Wtf\App(['config_dir' => $dir]);
        $this->container = $app->getContainer();
        //Init db
        \ob_start();
        include $dir.'/../dump.sql';
        $query = \ob_get_clean();
        $this->container->medoo->pdo->exec($query);
    }

    public function testSave(): void
    {
        $data = ['email' => 'example@example.com', 'name' => 'Test user', 'notexisting' => 'field'];
        $entity = $this->container['entity']('user');
        $entity->setData($data);
        $this->assertNull($entity->getId());
        $entity->save();
        $this->assertInternalType('numeric', $entity->getId());
        $this->assertNull($entity->get('notexisting'));
        $entity->setData(['name' => 'New test']);
        $entity->save();
        $this->assertEquals('New test', $entity->getName());
    }

    public function testSaveValidationFailed(): void
    {
        $data = ['email' => 'example@example.com', 'name' => ['fail' => 'me']];
        $entity = $this->container['entity']('user');
        $entity->setData($data);
        $this->expectException(\Exception::class);
        $entity->save();
    }

    public function testLoad(): void
    {
        $this->testSave();
        $entity = $this->container['entity']('user');
        $this->assertEquals([], $entity->getData());
        $entity->load('example@example.com', 'email');
        $this->assertEquals('example@example.com', $entity->getEmail());
    }

    public function testLoadAll(): void
    {
        $this->testSave();
        $entity = $this->container['entity']('user');
        $collection = $entity->loadAll(['email' => 'example@example.com']);
        $this->assertInstanceOf('\Slim\Interfaces\CollectionInterface', $collection);
        $this->assertTrue(($collection->count() > 0));
    }

    public function testHas(): void
    {
        $this->testSave();
        $entity = $this->container['entity']('user');
        $this->assertTrue($entity->has(['email' => 'example@example.com']));
    }

    public function testCount(): void
    {
        $this->testSave();
        $entity = $this->container['entity']('user');
        $this->assertTrue(($entity->count(['email' => 'example@example.com']) > 0));
    }

    public function testDelete(): void
    {
        $this->testSave();
        $entity = $this->container['entity']('user')->load('example@example.com', 'email');
        $this->assertTrue((bool) $entity->getId());
        $id = $entity->getId();
        $this->assertTrue($entity->delete());
        $this->assertFalse($entity->has(['id' => $id]));
    }

    public function testLoadRelation(): void
    {
        //create some articles
        $user = $this->container['entity']('user')->load('example@example.com', 'email');
        $this->assertTrue((bool) $user->getId());
        $article = $this->container['entity']('article');
        $article->setData([
            'author_id' => $user->getId(),
            'title' => 'Test',
            'description' => 'test',
        ]);
        foreach (\range(1, 5) as $i) {
            $article->setId(null);
            $article->save();
        }

        $collection = $user->getArticles();
        $this->assertInstanceOf('\Slim\Interfaces\CollectionInterface', $collection);
        $this->assertTrue(($collection->count() > 0));
        $this->assertNull($user->getErrorRelation());
    }

    public function testGetScheme(): void
    {
        $this->assertInternalType('array', $this->container['entity']('user')->getScheme());
    }
}
