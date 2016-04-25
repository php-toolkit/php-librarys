<?php

namespace inhere\tools\interfaces;

/**
 * Collection Interface
 */
interface CollectionInterface extends \Serializable, \ArrayAccess, \Countable, \IteratorAggregate, \JsonSerializable
{
    public function set($key, $value);

    public function get($key, $default = null);

    public function replace(array $items);

    public function all();

    public function has($key);

    public function remove($key);

    public function clear();
}
