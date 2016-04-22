<?php

namespace inhere\tools\traits;

trait TraitGetOwner
{
    static private $owner   = null;

    static public function owner($options = [])
    {
        if (is_null(self::$owner)){
            self::$owner = new self($options);
        }

        return self::$owner;
    }
    static public function own($options = [])
    {
        return self::owner($options);
    }
    static public function me($options = [])
    {
        return self::owner($options);
    }

    // 是否已激活
    final static public function hasActive()
    {
        return self::$owner !== null;
    }

    private function __clone()
    {}

}