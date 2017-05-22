<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/5/21
 * Time: 上午1:45
 */

namespace inhere\library\queue;

/**
 * Class BaseQueue
 * @package inhere\library\queue
 */
abstract class BaseQueue implements QueueInterface
{
    /**
     * The queue id(name)
     * @var string
     */
    protected $id;

    /**
     * @var int
     */
    protected $errCode = 0;

    /**
     * @var string
     */
    protected $errMsg;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return int
     */
    public function getErrCode()
    {
        return $this->errCode;
    }

    /**
     * @return string
     */
    public function getErrMsg()
    {
        return $this->errMsg;
    }
}
