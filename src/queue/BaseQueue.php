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
     * @var string|int
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
     * @var array
     */
    private static $channels = [];

    /**
     * @var array
     */
    private static $intChannels = [];

    /**
     * @return array
     */
    public function getChannels()
    {
        if (!self::$channels) {
            self::$channels = [
                'high' => $this->id . self::PRIORITY_HIGH_SUFFIX,
                'norm' => $this->id,
                'low' => $this->id . self::PRIORITY_LOW_SUFFIX,
            ];
        }

        return self::$channels;
    }

    /**
     * @return array
     */
    public function getIntChannels()
    {
        if (!self::$intChannels) {
                self::$intChannels = [
                'high' => $this->id . self::PRIORITY_HIGH,
                'norm' => $this->id,
                'low' => $this->id . self::PRIORITY_LOW,
            ];
        }

        return self::$intChannels;
    }

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
