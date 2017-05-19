<?php
/**
 * @author Tianfeng.Han
 * @from https://github.com/matyhtf/framework/blob/master/libs/Swoole/Queue/MsgQ.php
 */

namespace inhere\library\process;

/**
 * 是对Linux Sysv系统消息队列的封装，单台服务器推荐使用
 */
class MsgQueue
{
    protected $msgId;

    protected $msgType = 1;

    /**
     * @var resource
     */
    private $queue;

    private $errCode = 0;

    private $config = [
        'msgId' => null,
        'msgType' => null,
        'blocking' => 1,
        'serialize' => false,
        'bufferSize' => 8192, // 65525
    ];

    public function __construct(array $config = [])
    {
        if (!function_exists('msg_receive')) {
            throw new \RuntimeException("Is not support msg queue of the current system.", -500);
        }

        $this->config = array_merge($this->config, $config);

        if (!empty($config['msgId'])) {
            $this->msgId = $config['msgId'];
        } else {
            $this->msgId = ftok(__FILE__, 0);
        }

        if (isset($config['msgType'])) {
            $this->msgType = (int)$config['msgType'];
        }

        $this->queue = msg_get_queue($this->msgId);
    }

    public function pop()
    {
        // bool msg_receive(
        //      resource $queue, int $desiredmsgtype, int &$msgtype, int $maxsize,
        //      mixed &$message [, bool $unserialize = true [, int $flags = 0 [, int &$errorcode ]]]
        //  )
        $ret = msg_receive(
            $this->queue,
            0,
            $this->msgType,
            $this->config['bufferSize'],
            $data,
            $this->config['serialize'],
            0,
            $this->errCode
        );

        if ($ret) {
            return $data;
        }

        return false;
    }

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

    public function getStat()
    {
        return msg_stat_queue($this->msgId);
    }

    public function getConfig()
    {
        return $this->config;
    }

    public function getMsgId()
    {
        return $this->msgId;
    }

    public function getErrCode()
    {
        return $this->errCode;
    }
}
