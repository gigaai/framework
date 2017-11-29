<?php

namespace GigaAI\Conversation;

/**
 * Natural Language Processing using built-in Facebook functions.
 *
 * Class Nlp
 * @package GigaAI\Conversation
 * @since 3.0
 */
class Nlp
{
    protected $entities;

    protected $entity;

    /**
     * We'll use `nlp` key in the request to parse
     *
     * @param $nlp
     */
    public function __construct($nlp)
    {
        // Sort entities by their confidences. From highest to lowest
        $this->entities = collect($nlp['entities'])->sortByDesc(function ($entity) {
            return $entity[0]['confidence'];
        });
    }

    /**
     * Search entity by their position or name
     *
     * @param null $name
     *
     * @return $this|Nlp
     */
    public function entities($name = null)
    {
        if ($name === ':first' || $name === ':highest' || $name === null) {
            return $this->first();
        }

        if ($name === ':last' || $name === ':lowest') {
            return $this->last();
        }

        $this->entity = $this->entities->first(function ($entity, $key) use ($name) {
            return $key === $name;
        });

        return $this;
    }

    /**
     * Alias method of entities()
     *
     * @param null $name
     *
     * @return Nlp
     */
    public function entity($name = null)
    {
        return $this->entities($name);
    }

    /**
     * Get the first entity
     *
     * @return $this
     */
    public function first()
    {
        $this->entity = $this->entities->first();

        return $this;
    }

    /**
     * Highest is the alias method of first()
     *
     * @return $this
     */
    public function highest()
    {
        return $this->first();
    }

    /**
     * Get the last entity
     *
     * @return $this
     */
    public function last()
    {
        $this->entity = $this->entities->last();

        return $this;
    }

    /**
     * lowest() is the alias of last()
     */
    public function lowest()
    {
        return $this->last();
    }

    /**
     * Get a fields value
     *
     * @param $field
     *
     * @return mixed
     */
    public function get($field)
    {
        return isset($this->entity[0][$field]) ? $this->entity[0][$field] : null;
    }

    public function __call($name, $arguments)
    {
        if (starts_with($name, 'get')) {
            $name = snake_case(ltrim($name, 'get'));
        }

        return $this->get($name);
    }

    public function toArray()
    {
        return $this->entity[0];
    }
}