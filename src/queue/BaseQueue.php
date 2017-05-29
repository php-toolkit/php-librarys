<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/5/21
 * Time: 上午1:45
 */

namespace inhere\library\queue;

use inhere\library\event\TraitSimpleEvent;

/**
 * Class BaseQueue
 * @package inhere\library\queue
 */
abstract class BaseQueue implements QueueInterface
{
    use TraitSimpleEvent;

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
     * {@inheritDoc}
     */
    public function push($data, $priority = self::PRIORITY_NORM)
    {
        $status = false;
        $this->fire(self::EVENT_BEFORE_PUSH, [$data, $priority]);

        try {
            $status = $this->doPush($data, $priority);
        } catch (\Exception $e) {
            $this->errCode = $e->getCode() > 0 ? $e->getCode() : __LINE__;
            $this->errMsg = $e->getMessage();
        }

        $this->fire(self::EVENT_AFTER_PUSH, [$data, $priority, $status]);

        return $status;
    }

    /**
     * @param $data
     * @param int $priority
     * @return bool
     */
    abstract protected function doPush($data, $priority = self::PRIORITY_NORM);

    /**
     * {@inheritDoc}
     */
    public function pop()
    {
        $data = null;
        $this->fire(self::EVENT_BEFORE_POP, [$this]);

        try {
            $data = $this->doPop();
        } catch (\Exception $e) {
            $this->errCode = $e->getCode() > 0 ? $e->getCode() : __LINE__;
            $this->errMsg = $e->getMessage();
        }

        $this->fire(self::EVENT_AFTER_POP, [$data, $this]);

        return $data;
    }

    /**
     * @return mixed
     */
    abstract protected function doPop();

    /**
     * get Priorities
     * @return array
     */
    public function getPriorities()
    {
        return [
            self::PRIORITY_HIGH,
            self::PRIORITY_NORM,
            self::PRIORITY_LOW,
        ];
    }

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
                'high' => $this->id + self::PRIORITY_HIGH,
                'norm' => (int)$this->id,
                'low' => $this->id + self::PRIORITY_LOW,
            ];
        }

        return self::$intChannels;
    }

    /**
     * __destruct
     */
    public function __destruct()
    {
        $this->close();
    }

    /**
     * close
     */
    public function close()
    {
        $this->clearEvents();
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
     * @return $this
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
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
