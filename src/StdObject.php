<?php
/**
 * Created by sublime 3.
 * Auth: Inhere
 * Date: 14-9-28
 * Time: 10:35
 */

namespace inhere\library;

use inhere\library\helpers\ObjectHelper;
use inhere\library\traits\StdObjectTrait;

/**
 * Class StdBase
 * @package inhere\library
 */
abstract class StdObject
{
    use StdObjectTrait;

    /**
     * StdObject constructor.
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        ObjectHelper::setAttrs($this, $config);

        $this->init();
    }

    /**
     * init
     */
    protected function init()
    {
        // init something ...
    }
}
