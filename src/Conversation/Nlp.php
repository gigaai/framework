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

    protected $filtered;

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
     * Retrieve all entities
     *
     * @return $this|Nlp
     */
    public function entities()
    {
        return $this;
    }

    /**
     * Get entities by their name or position
     *
     * @param null $filter
     *
     * @return Nlp
     */
    public function filter($filter)
    {
        preg_match_all("/(#\w+)/", $filter, $entityNames);
        preg_match_all("/(:\w+)/", $filter, $positions);

        $entityName = is_array($entityNames[0]) && isset($entityNames[0][0]) ? ltrim($entityNames[0][0],
            '#') : null;

        $position = is_array($positions[0]) && isset($positions[0][0]) ? $positions[0][0] : null;

        $i = 0;

        $filtered = [];

        foreach ($this->entities as $name => $entity) {
            if ($i === 0 && ($entityName === $name || $entityName === null) && ($position === ':first' || $position === ':highest' || $position === null)) {
                $filtered[] = $entity;
            }

            if ($position === ':any' && ($entityName === $name || $entityName === null)) {
                $filtered[] = $entity;
            }

            if ($i + 1 === $this->entities->count() && ($entityName === $name || $entityName === null) && ($position === ':last' || $position === ':lowest')) {
                $filtered[] = $entity;
            }

            $i++;
        }

        $this->filtered = $filtered;

        return $this;
    }

    /**
     * Get the first entity
     *
     * @return $this
     */
    public function first()
    {
        $this->filtered = $this->filtered[0];

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
        $this->filtered = $this->filtered[count($this->filtered) - 1];

        return $this;
    }

    /**
     * lowest() is the alias of last()
     */
    public function lowest()
    {
        return $this->last();
    }

    public function exists()
    {
        return $this->filtered !== null && ! empty($this->filtered);
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
        if ( ! is_null($this->filtered)) {
            return isset($this->filtered[0][$field]) ? $this->filtered[0][$field] : null;
        }

        // Todo: Get values of entities
    }

    public function getNames()
    {
        return $this->entities->keys()->toArray();
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
        return $this->filtered;
    }
}