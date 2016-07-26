<?php
/**
 * Created by PhpStorm.
 * User: Inhere
 * Date: 15-4-1
 * Time: 上午10:08
 * Used: create html Element
 * file: Element.php
 */

namespace inhere\library\html;

use inhere\library\StdBase;

class Elements extends StdBase implements \IteratorAggregate
{
    public $elements = [];

    public function __construct(array $elements=[])
    {
        $this->elements = $elements;
    }

    public function getString()
    {
        $string = '';

        foreach ($this->elements as $key => $element) {
            $string .= (string)$element;
        }

        return $string;
    }

    public function __toString()
    {
        return $this->getString();
    }

    public function getIterator()
    {
        return new \ArrayIterator($this->elements);
    }

    public function getElements()
    {
        return $this->elements;
    }

    public function setElements(array $elements)
    {
        $this->elements = $elements;

        return $this;
    }
}