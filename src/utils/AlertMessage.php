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
    // info success primary warning danger
    const INFO = 'info';
    const SUCCESS = 'success';
    const PRIMARY = 'primary';
    const WARNING = 'warning';
    const DANGER = 'danger';

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

    /**
     * @param $type
     * @return $this
     */
    public function type($type)
    {
        $this->type = $type;
        $this->title = ucfirst($type) . '!';

        return $this;
    }

    /**
     * @param $msg
     * @return $this
     */
    public function msg($msg)
    {
        $this->msg = $msg;

        return $this;
    }

    /**
     * @param $title
     * @return $this
     */
    public function title($title)
    {
        $this->title = $title;

        return $this;
    }

    public function all($toArray=true)
    {
        // add a new alert message
        return [
            'type'      => $this->type ?: 'info', // info success primary warning danger
            'title'     => $this->title ?:'Info!',
            'msg'       => $this->msg,
            'closeBtn'  => (bool)$this->closeBtn
        ];
    }

    public function toArray()
    {
        return $this->all();
    }
}