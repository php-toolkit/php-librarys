<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 16/9/2
 * Time: 上午11:49
 */

namespace inhere\librarys\utils;

use inhere\librarys\collections\FixedData;

/**
 * Class AlertMessage
 * @package slimExt\helpers
 */
class AlertMessage extends FixedData
{
    /**
     * @var string
     */
    public $type = 'info';

    /**
     * @var string
     */
    public $title = 'Notice!';

    /**
     * @var string
     */
    public $msg = '';

    /**
     * @var array
     */
    public $closeBtn = true;

    public function toArray()
    {
        return $this->all(true);
    }
}