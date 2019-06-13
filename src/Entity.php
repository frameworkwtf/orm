<?php

declare(strict_types=1);

namespace Wtf\ORM;

abstract class Entity extends \Wtf\Root
{
    protected $relationObjects = [];
    protected $scheme;

    /**
     * Get short entity name (without namespace)
     * Helper function, required for lazy load.
     *
     * @return string
     */
    protected function __getEntityName(): string
    {
        return ($pos = \strrpos(\get_class($this), '\\')) ? \substr(\get_class($this), $pos + 1) : \get_class($this);
    }

    /**
     * Magic relation getter.
     *
     * @param null|string $method
     * @param array       $params
     */
    public function __call(?string $method = null, array $params = [])
    {
        $parts = \preg_split('/([A-Z][^A-Z]*)/', $method, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
        $type = \array_shift($parts);
        $relation = \strtolower(\implode('_', $parts));

        if ('get' === $type && isset($this->getRelations()[$relation])) {
            return $this->loadRelation($relation);
        }

        return parent::__call($method, $params);
    }

    /**
     * Get entity scheme.
     *
     * @return array
     */
    public function getScheme(): array
    {
        if (null === $this->scheme) {
            switch ($this->config('wtf.orm.database_type')) {
            case 'pgsql':
                $query = 'SELECT column_name AS "Field" FROM information_schema.COLUMNS WHERE table_name=\''.$this->getTable().'\'';
                break;
            default:
                $query = 'DESCRIBE '.$this->getTable();
                break;
            }
            $raw = $this->medoo->query($query)->fetchAll();
            $this->scheme = [];
            foreach ($raw as $field) {
                $this->scheme[] = $field['Field'];
            }
        }

        return $this->scheme;
    }

    /**
     * Save entity data in db.
     *
     * @return Entity
     */
    public function save(): self
    {
        /*
         * Remove fields that not exists in DB table scheme,
         * to avoid thrown exceptions on saving garbadge fields.
         */
        foreach ($this->data as $key => $value) {
            if (!\in_array($key, $this->getScheme(), true)) {
                unset($this->data[$key]);
            }
        }

        if ($this->getId()) {
            $this->medoo->update($this->getTable(), $this->data, ['id' => $this->getId()]);
        } else {
            $this->medoo->insert($this->getTable(), $this->data);
            $this->setId($this->medoo->id());
        }
        $this->sentry->breadcrumbs->record([
            'message' => 'Entity '.$this->__getEntityName().'::save()',
            'data' => ['query' => $this->medoo->last()],
            'category' => 'Database',
            'level' => 'info',
        ]);

        return $this;
    }

    /**
     * Load entity (data from db).
     *
     * @param mixed  $value  Field value (eg: id field with value = 10)
     * @param string $field  Field name, default: id
     * @param array  $fields Fields (columns) to load, default: all
     *
     * @return Entity
     */
    public function load($value, $field = 'id', array $fields = null): self
    {
        $data = $this->medoo->get($this->getTable(), $fields ?? '*', [$field => $value]);
        $this->data = \is_array($data) ? $data : []; //handle empty result gracefuly
        $this->sentry->breadcrumbs->record([
            'message' => 'Entity '.$this->__getEntityName().'::load('.$value.', '.$field.', ['.\implode(', ', $fields ?? []).')',
            'data' => ['query' => $this->medoo->last()],
            'category' => 'Database',
            'level' => 'info',
        ]);

        return $this;
    }

    /**
     * Get all entities from db.
     *
     * @param array $where  Where clause
     * @param bool  $assoc  Return collection of entity objects OR of assoc arrays
     * @param array $fields Fields to load, default is all
     *
     * @return array
     */
    public function loadAll(array $where = [], bool $assoc = false, array $fields = null): array
    {
        //autoapply filters from wtf/middleware-filters package
        if ($this->container->has('__wtf_orm_filters')) {
            $where = \array_merge($this->container->get('__wtf_orm_filters'), $where);
        }
        $allData = $this->medoo->select($this->getTable(), $fields ? $fields : '*', $where);
        $this->sentry->breadcrumbs->record([
            'message' => 'Entity '.$this->__getEntityName().'::loadAll('.\print_r($where, true).', '.$assoc.', '.\print_r($fields, true).')',
            'data' => ['query' => $this->medoo->last()],
            'category' => 'Database',
            'level' => 'info',
        ]);
        $items = [];
        foreach ($allData as $data) {
            $items[] = ($assoc) ? $data : $this->container['entity']($this->__getEntityName())->setData($data);
        }

        return $items;
    }

    /**
     * Load realated entity by relation name.
     *
     * @param string $name Relation name
     *
     * @return null|array|Entity
     */
    public function loadRelation(string $name)
    {
        if (!isset($this->relationObjects[$name]) || empty($this->relationObjects[$name])) {
            $relation = $this->getRelations()[$name];
            if (!$relation || !$relation['entity'] || !$this->get($relation['key'] ?? 'id')) {
                return null;
            }

            $entity = $this->entity($relation['entity']);
            $type = $relation['type'] ?? 'has_one';
            $key = $relation['key'] ?? ('has_one' === $type ? $this->__getEntityName().'_id' : 'id');
            $foreignKey = $relation['foreign_key'] ?? ('has_one' === $type ? 'id' : $this->__getEntityName().'_id');
            $assoc = $relation['assoc'] ?? false;
            $this->relationObjects[$name] = ('has_one' === $type) ? $entity->load($this->get($key), $foreignKey) : $entity->loadAll([$foreignKey => $this->get($key)], $assoc);
        }

        return $this->relationObjects[$name] ?? null;
    }

    /**
     * Determine whether the target data existed.
     *
     * @param array $where
     *
     * @return bool
     */
    public function has(array $where = []): bool
    {
        return $this->medoo->has($this->getTable(), $where);
    }

    /**
     * Get count of items by $where conditions.
     *
     * @param array $where Where clause
     *
     * @return int
     */
    public function count(array $where = []): int
    {
        return $this->medoo->count($this->getTable(), $where);
    }

    /**
     * Delete entity row from db.
     *
     * @return bool
     */
    public function delete(): bool
    {
        return (bool) $this->medoo->delete($this->getTable(), ['id' => $this->getId()]);
    }

    /**
     * Return entity table name.
     *
     * @return string
     */
    abstract public function getTable(): string;

    /**
     * Return array of entity relations
     * <code>
     * //structure
     * [
     *     'relation__name' => [
     *         'entity' => 'another_entity_name',
     *         'type' => 'has_one', //default, other options: has_many
     *         'key' => 'current_entity_key', //optional, default for has_one: <current_entity>_id, for has_many: id
     *         'foreign_key' => 'another_entity_key', //optional, default for has_one: id, for has_many: '<current_entity>_id'
     *         'assoc' => false, //optional, return data arrays instead of objects on "has_many", default: false
     *      ],
     * ];.
     *
     * //Example (current entity: blog post, another entity: user)
     * [
     *     'author' => [ //has_one
     *         'entity' => 'user',
     *         'key' => 'author_id',
     *         'foreign_key' => 'id'
     *     ],
     * ];
     * //Example (same as above, but with default values)
     * [
     *     'author' => [
     *         'entity' => 'user',
     *     ],
     * ];
     * //This example can be called like $blogPostEntity->getAuthor()
     *
     * //Example (current entity: user, another entity: blog post)
     * [
     *     'posts' => [
     *         'entity' => 'post',
     *         'type' => 'has_many',
     *         'foreign_key' => 'author_id',
     *     ],
     * ]
     * //This example can be called like $userEntity->getPosts()
     * </code>
     *
     * @return array
     */
    abstract public function getRelations(): array;
}
