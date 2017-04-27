<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/4/27
 * Time: 下午9:31
 */

namespace inhere\library\http;

use inhere\library\traits\TraitSimpleOption;
use inhere\library\utils\LiteLogger;

/**
 * Class LiteServer - a lite http server
 * @package inhere\library\http
 */
class LiteServer
{
    use TraitSimpleOption;

    /**
     * the master socket
     * @var resource
     */
    protected $socket;

    /**
     * @var int
     */
    private $errNo = 0;

    /**
     * @var string
     */
    private $errMsg = '';

    /**
     * @var LiteLogger
     */
    private $logger;

    /**
     * WebSocket constructor.
     * @param string $host
     * @param int $port
     * @param array $options
     */
    public function __construct(string $host = '0.0.0.0', int $port = 8080, array $options = [])
    {
        $this->options = $this->getDefaultOptions();

        $this->setOptions($options, true);

        $this->init();

        if (!static::isSupported()) {
            // throw new \InvalidArgumentException("The extension [$this->name] is required for run the server.");
            $this->cliOut->error("Your system is not supported the driver: {$this->name}, by " . static::class, -200);
        }

        $this->host = $host;
        $this->port = $port;

        $this->cliOut->write("The webSocket server power by [<info>{$this->name}</info>], driver class: <default>" . static::class . '</default>', 'info');
    }

    protected function init()
    {
        // create log service instance
        if ($config = $this->getOption('log_service')) {
            $this->logger = LiteLogger::make($config);
        }
    }

    /**
     * start server
     */
    public function start()
    {
        $max = (int)$this->getOption('max_connect', self::MAX_CONNECT);
        $this->log("Started WebSocket server on <info>{$this->getHost()}:{$this->getPort()}</info> (max allow connection: $max)", 'info');

        // create and prepare
        $this->prepareWork($max);

        // if `$this->beforeStartCb` exists.
        if ($cb = $this->beforeStartCb) {
            $cb($this);
        }

        $this->doStart();
    }

    /**
     * @param \Closure $closure
     */
    public function beforeStart(\Closure $closure = null)
    {
        $this->beforeStartCb = $closure;
    }

    /**
     * create and prepare socket resource
     * @param int $maxConnect
     */
    protected function prepareWork(int $maxConnect)
    {
        if (count($this->callbacks) < 1) {
            $sup = implode(',', $this->getSupportedEvents());
            $this->cliOut->error('Please register event handle callback before start. supported events: ' . $sup, -500);
        }

        // reset
        socket_clear_error();
        $this->metas = $this->clients = [];

        // 创建一个 TCP socket
        // AF_INET: IPv4 网络协议。TCP 和 UDP 都可使用此协议。
        // AF_UNIX: 使用 Unix 套接字. 例如 /tmp/my.sock
        // more see http://php.net/manual/en/function.socket-create.php
        $this->socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);

        if (!is_resource($this->socket)) {
            $this->fetchError();
            $this->cliOut->error('Unable to create socket: ' . $this->errMsg, $this->errNo);
        }

        // 设置IP和端口重用,在重启服务器后能重新使用此端口;
        socket_set_option($this->socket, SOL_SOCKET, SO_REUSEADDR, TRUE);
        // socket_set_option($this->socket,SOL_SOCKET, SO_RCVTIMEO, ['sec' =>0, 'usec' =>100]);

        // 给套接字绑定名字
        socket_bind($this->socket, $this->getHost(), $this->getPort());

        // 监听套接字上的连接. 最多允许 $max 个连接，超过的客户端连接会返回 WSAECONNREFUSED 错误
        socket_listen($this->socket, $maxConnect);
    }

    /**
     * {@inheritDoc}
     */
    protected function doStart()
    {
        $maxLen = (int)$this->getOption('max_data_len', 2048);

        // interval time
        $setTime = (int)$this->getOption('sleep_ms', 800);
        $sleepTime = $setTime > 50 ? $setTime : 500;
        $sleepTime *= 1000; // ms -> us

        while (true) {
            $write = $except = null;
            // copy， 防止 $this->clients 的变动被 socket_select() 接收到
            $read = $this->clients;
            $read[] = $this->socket;

            // 会监控 $read 中的 socket 是否有变动
            // $tv_sec =0 时此函数立即返回，可以用于轮询机制
            // $tv_sec =null 将会阻塞程序执行，直到有新连接时才会继续向下执行
            if (false === socket_select($read, $write, $except, null)) {
                $this->fetchError();
                $this->log('socket_select() failed, reason: ' . $this->errMsg, 'error');
                continue;
            }

            // handle ...
            foreach ($read as $sock) {
                $this->handleSocket($sock, $maxLen);
            }

            //sleep(1);
            usleep($sleepTime);
        }
    }

    /**
     * @param resource $sock
     * @param int $len
     * @return bool
     */
    protected function handleSocket($sock, $len)
    {
        // 每次循环检查到 $this->socket 时，都会用 socket_accept() 去检查是否有新的连接进入，有就加入连接列表
        if ($sock === $this->socket) {
            // 从已经监控的socket中接受新的客户端请求
            if (false === ($newSock = socket_accept($sock))) {
                $this->fetchError();
                $this->error($this->errMsg);

                return false;
            }

            $this->connect($newSock);
            return true;
        }

        $cid = (int)$sock;

        // 不在已经记录的client列表中
        if (!isset($this->metas[$cid], $this->clients[$cid])) {
            return $this->close($cid, $sock);
        }

        $data = null;
        // 函数 socket_recv() 从 socket 中接受长度为 len 字节的数据，并保存在 $data 中。
        $bytes = socket_recv($sock, $data, $len, 0);

        // 没有发送数据或者小于7字节
        if (false === $bytes || $bytes < 7 || !$data) {
            $this->log("Failed to receive data or not received data(client close connection) from #$cid client, will close the socket.");
            return $this->close($cid, $sock);
        }

        // 是否已经握手
        if (!$this->metas[$cid]['handshake']) {
            return $this->handshake($sock, $data, $cid);
        }

        $this->message($cid, $data, $bytes, $this->metas[$cid]);

        return true;
    }


    /**
     * @return bool
     */
    public function isDebug(): bool
    {
        return (bool)$this->getOption('debug', false);
    }

    /**
     * get Logger service
     * @return LiteLogger
     */
    public function getLogger()
    {
        return $this->logger;
    }

    /**
     * output and record websocket log message
     * @param  string $msg
     * @param  array $data
     * @param string $type
     */
    public function log(string $msg, string $type = 'debug', array $data = [])
    {
        // if close debug, don't output debug log.
        if ($this->isDebug() || $type !== 'debug') {

            [$time, $micro] = explode('.', microtime(1));

            $time = date('Y-m-d H:i:s', $time);
            $json = $data ? json_encode($data) : '';
            $type = strtoupper(trim($type));

            fwrite(\STDIN,"[{$time}.{$micro}] [$type] $msg {$json}");

            if ($logger = $this->getLogger()) {
                $logger->$type(strip_tags($msg), $data);
            }
        }
    }

    /**
     * output debug log message
     * @param string $message
     * @param array $data
     */
    public function debug(string $message, array $data = [])
    {
        $this->log($message, 'debug', $data);
    }

}