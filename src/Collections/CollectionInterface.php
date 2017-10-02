<?php

namespace Inhere\Library\Collections;

/**
 * Collection Interface
 */
interface CollectionInterface extends \Serializable, \ArrayAccess, \Countable, \IteratorAggregate, \JsonSerializable
{
    public function set($key, $value);

    public function get(string $key, $default = null);

    public function replace(array $items);

    public function all();

    public function has(string $key);

    public function remove($key);

    public function clear();
}
