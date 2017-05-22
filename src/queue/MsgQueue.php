<?php
/**
 * @from https://github.com/matyhtf/framework/blob/master/libs/Swoole/Queue/MsgQ.php
 */

namespace inhere\library\queue;

/**
 * Class MsgQueue
 * @package inhere\library\queue
 */
class MsgQueue extends BaseQueue
{
    /**
     * @var int
     */
    private $msgType = 1;

    /**
     * @var array
     */
    private $queues = [];

    /**
     * @var array
     */
    private $config = [
        'uniKey' => 0, // int|string
        'msgType' => 1,
        'blocking' => 1, // 0|1
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
            throw new \RuntimeException(
                'Is not support msg queue of the current system(must enable sysvmsg for php).',
                -500
            );
        }

        $this->config = array_merge($this->config, $config);
        
        $this->id = !empty($config['id']) ? (int)$config['id'] : ftok(__FILE__, $this->config['uniKey']);
        $this->msgType = (int)$this->config['msgType'];

        // create queues
        foreach ($this->getIntChannels() as $id) {
            $this->queues[] = msg_get_queue($id);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function push($data, $priority = self::PRIORITY_NORM)
    {
        // 如果队列满了，这里会阻塞
        // bool msg_send(
        //      resource $queue, int $msgtype, mixed $message [, bool $serialize = true [, bool $blocking = true [, int &$errorcode ]]]
        // )

        if (isset($this->queues[$priority])) {
            return msg_send(
                $this->queues[$priority],
                $this->msgType,
                $data,
                $this->config['serialize'],
                $this->config['blocking'],
                $this->errCode
            );
        }

        return false;
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

        $data = null;

        foreach ($this->queues as $queue) {
            $success = msg_receive(
                $queue,
                0,
                $this->msgType,
                $this->config['bufferSize'],
                $data,
                $this->config['serialize'],
                0,
                $this->errCode
            );

            if ($success) {
                break;
            }
        }

        return $data;
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
     * Setting the queue option
     * @param array $options
     * @param int $queue
     */
    public function setOptions(array $options = [], $queue = self::PRIORITY_NORM)
    {
        msg_set_queue($this->queues[$queue], $options);
    }
    
    /**
     * @param int $id
     * @return bool
     */
    public function exist($id)
    {
        return msg_queue_exists($id);
    }

    /**
     * close
     */
    public function close()
    {
        foreach ($this->queues as $queue) {
            if ($queue) {
                msg_remove_queue($queue);
            }
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
     * @param int $queue
     * @return array
     */
    public function getStat($queue = self::PRIORITY_NORM)
    {
        return msg_stat_queue($this->queues[$queue]);
    }

    /**
     * @return array
     */
    public function getConfig()
    {
        return $this->config;
    }
}
