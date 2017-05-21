<?php
/**
 * @from https://github.com/matyhtf/framework/blob/master/libs/Swoole/Queue/MsgQ.php
 */

namespace inhere\library\queue;

/**
 * Class MsgQueue
 * @package inhere\library\queue
 */
class MsgQueue implements QueueInterface
{
    /**
     * @var int[]
     */
//    private static $msgIds = [];

    /**
     * @var int
     */
    private $msgId;

    /**
     * @var int
     */
    private $msgType = 1;

    /**
     * @var resource
     */
    private $queue;

    /**
     * @var int
     */
    private $errCode = 0;

    /**
     * @var array
     */
    private $config = [
        'msgId' => null,
        'uniKey' => 0,
        'msgType' => 1,
        'blocking' => 1,
        'serialize' => false,
        'bufferSize' => 8192, // 65525
    ];

    /**
     * MsgQueue constructor.
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        if (!function_exists('msg_receive')) {
            throw new \RuntimeException('Is not support msg queue of the current system(must enable sysvmsg for php).', -500);
        }

        $this->config = array_merge($this->config, $config);
        $this->msgId = !empty($config['msgId']) ? (int)$config['msgId'] : ftok(__FILE__, $this->config['uniKey']);
        $this->msgType = (int)$this->config['msgType'];

//        if (isset(self::$msgIds[$this->msgId])) {
//
//        }

        // create queue
        $this->queue = msg_get_queue($this->msgId);
    }

    /**
     * Setting the queue option
     * @param array $options
     */
    public function setOptions(array $options = [])
    {
        msg_set_queue($this->queue, $options);
    }

    /**
     * pop data
     * @return mixed Return False is failed.
     */
    public function pop()
    {
        // bool msg_receive(
        //      resource $queue, int $desiredmsgtype, int &$msgtype, int $maxsize,
        //      mixed &$message [, bool $unserialize = true [, int $flags = 0 [, int &$errorcode ]]]
        //  )
        $success = msg_receive(
            $this->queue,
            0,
            $this->msgType,
            $this->config['bufferSize'],
            $data,
            $this->config['serialize'],
            0,
            $this->errCode
        );

        return $success ? $data : false;
    }

    /**
     * push data
     * @param mixed $data
     * @return bool
     */
    public function push($data)
    {
        // bool msg_send(
        //      resource $queue, int $msgtype, mixed $message [, bool $serialize = true [, bool $blocking = true [, int &$errorcode ]]]
        // )
        // 如果队列满了，这里会阻塞
        return msg_send(
            $this->queue,
            $this->msgType,
            $data,
            $this->config['serialize'],
            $this->config['blocking'],
            $this->errCode
        );
    }

    /**
     * @return array
     */
    public static function allQueues()
    {
        $aQueues = [];

        exec('ipcs -q | grep "^[0-9]" | cut -d " " -f 1', $aQueues);

        return $aQueues;
    }

    /**
     * @param $msgId
     * @return bool
     */
    public function exist($msgId)
    {
        return msg_queue_exists($msgId);
    }

    /**
     * close
     */
    public function close()
    {
        if ($this->queue) {
            msg_remove_queue($this->queue);
        }
    }

    /**
     * __destruct
     */
    public function __destruct()
    {
        $this->close();
    }

    /**
     * @return array
     */
    public function getStat()
    {
        return msg_stat_queue($this->queue);
    }

    /**
     * @return array
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * @return int
     */
    public function getMsgId()
    {
        return $this->msgId;
    }

    /**
     * @return int
     */
    public function getErrCode()
    {
        return $this->errCode;
    }
}