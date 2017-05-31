<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/5/31
 * Time: 下午7:41
 */

namespace inhere\library\process;

/**
 * Class IPC - Inter Process Communication
 * @package inhere\library\process
 */
class IPC
{
    const TYPE_PIPE = 'pipe';
    const TYPE_MQ   = 'mq'; // message queue
    const TYPE_SM   = 'sm'; // shared memory
    const TYPE_SOCK  = 'sock';

    /**
     * @var resource
     */
    private $handle;

    private $config = [
        'type' => self::TYPE_PIPE,

        // mq
        'msgKey' => null,

        // pipe
        'name' => 'php_ipc',

        // sm
        'shmKey' => 'php_shm', // shared memory
        'semKey' => 'php_sem', // semaphore

        // sock
        'server' => '127.0.0.1:4455',

        // public
        'blocking' => true,
    ];

    /**
     * IPC constructor.
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        $this->setConfig($config);

        switch ($this->config['type']) {
            case self::TYPE_MQ:

                break;
            case self::TYPE_SOCK:
                break;

            case self::TYPE_PIPE:
            default:

                break;
        }
    }

    public function write($data)
    {
        if ($this->config['msgKey']) {
            $this->config['msgKey'] = (int)$this->config['msgKey'];
        } else {
            $this->config['msgKey'] = ftok(__FILE__, 0);
        }

        $this->handle = msg_get_queue($this->config['msgKey']);
    }

    /**
     * @return bool
     */
    protected function createMsgQueue()
    {

    }

    /**
     * @return bool
     */
    protected function createPipe()
    {
        //创建管道
        $pipeFile = "/tmp/{$this->config['name']}.pipe";

        if(!file_exists($pipeFile) && !posix_mkfifo($pipeFile, 0666)){
            throw new \RuntimeException("Create the pipe failed! PATH: $pipeFile", -200);
        }

        $this->handle = fopen($pipeFile, 'wr');
        // 设置成读取(非)阻塞
        stream_set_blocking($this->handle, (bool)$this->config['blocking']);

        return true;
    }

    protected function createShareMemory()
    {

    }

    /**
     * @return array
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * @param array $config
     */
    public function setConfig(array $config)
    {
        $this->config = array_merge($this->config, $config);
    }
}
