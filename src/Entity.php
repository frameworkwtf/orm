<?php

declare(strict_types=1);

namespace Wtf\ORM;

use Exception;
use Respect\Validation\Exceptions\NestedValidationException;
use Slim\Collection;

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
            $raw = $this->medoo->query('DESCRIBE '.$this->getTable())->fetchAll();
            $this->scheme = [];
            foreach ($raw as $field) {
                $this->scheme[$field['Field']] = $field;
            }
        }

        return $this->scheme;
    }

    /**
     * Return all entity data as array.
     *
     * @return array
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * Set all data to entity.
     *
     * @param array $data
     *
     * @return Entity
     */
    public function setData(array $data)
    {
        $this->data = \array_merge($this->data, $data);

        return $this;
    }

    /**
     * Save entity data in db.
     *
     * @param bool $validate
     *
     * @throws Exception if entity data is not valid
     *
     * @return Entity
     */
    public function save(bool $validate = true): self
    {
        if ($validate && $this->validate()) {
            throw new Exception('Entity '.$this->__getEntityName().' data is not valid');
        }

        /**
         * Remove fields that not exists in DB table scheme,
         * to avoid thrown exceptions on saving garbadge fields.
         */
        $scheme = \array_keys($this->getScheme());
        foreach ($this->data as $key => $value) {
            if (!\in_array($key, $scheme, true)) {
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
     * Validate entity data.
     *
     * @param string $method Validation for method, default: save
     *
     * @return array [['field' => 'error message']]
     */
    public function validate(string $method = 'save'): array
    {
        $errors = [];
        foreach ($this->getValidators()[$method] ?? [] as $field => $validator) {
            try {
                $validator->setName($field)->assert($this->get($field));
            } catch (NestedValidationException $e) {
                $errors[$field] = $e->getMessages();
            }
        }

        return $errors;
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
     * @param array $where Where clause
     * @param bool  $assoc Return collection of entity objects OR of assoc arrays
     *
     * @return Collection
     */
    public function loadAll(array $where = [], bool $assoc = false): Collection
    {
        $allData = $this->medoo->select($this->getTable(), '*', $where);
        $this->sentry->breadcrumbs->record([
            'message' => 'Entity '.$this->__getEntityName().'::loadAll('.\print_r($where, true).')',
            'data' => ['query' => $this->medoo->last()],
            'category' => 'Database',
            'level' => 'info',
        ]);
        $items = [];
        foreach ($allData as $data) {
            $items[] = ($assoc) ? $data : $this->container['entity']($this->__getEntityName())->setData($data);
        }

        return new Collection($items);
    }

    /**
     * Load realated entity by relation name.
     *
     * @param string $name Relation name
     *
     * @return null|Collection|Entity
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
     * Get list of field validations
     * Structure:
     * <code>
     * [
     *     '<method>' => [
     *         '<field_name>' => v::stringType()->length(1, 255),
     *          //...
     *     ],
     * ];
     * </code>
     * Example: ['save' => ['name' => v::stringType()->length(1,255)]].
     *
     * @return array
     */
    abstract public function getValidators(): array;

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
