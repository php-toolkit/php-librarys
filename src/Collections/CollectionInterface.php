<?php

namespace Inhere\Library\Collections;

/**
 * Collection Interface
 */
interface CollectionInterface extends \Serializable, \ArrayAccess, \Countable, \IteratorAggregate, \JsonSerializable
{
    public function set($key, $value);

    public function get(string $key, $default = null);

    /**
     * @param array $items
     */
    public function replace(array $items);

    /**
     * @return array
     */
    public function all();

    /**
     * @param string $key
     * @return bool
     */
    public function has(string $key);

    /**
     * @param $key
     * @return mixed
     */
    public function remove($key);

    /**
     * clear all data
     */
    public function clear();
}
