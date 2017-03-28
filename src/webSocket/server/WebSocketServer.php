<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2016/10/10
 * Time: 下午8:29
 */

namespace inhere\librarys\webSocket\server;

/**
 * Class WebSocketServer
 *
 * ```
 * $ws = new WebSocketServer($host, $port);
 *
 * // bind events
 * $ws->on('open', callback);
 *
 * $ws->start();
 * ```
 */
class WebSocketServer
{
    /**
     * Websocket blob type.
     */
    const BINARY_TYPE_BLOB = "\x81";

    /**
     * Websocket array buffer type.
     */
    const BINARY_TYPE_ARRAY_BUFFER = "\x82";

    /**
     * the connection header line data end char
     */
    const HEADER_LINE_END = "\r\n";

    /**
     * the connection header end char
     */
    const HEADER_END = "\r\n\r\n";

    const ON_HANDSHAKE = 'handshake';
    const ON_OPEN      = 'open';
    const ON_MESSAGE   = 'message';
    const ON_CLOSE     = 'close';
    const ON_ERROR     = 'error';

    const DEFAULT_HOST = '0.0.0.0';
    const DEFAULT_PORT = 80;

    /**
     * @var resource
     */
    private $socket;
    private $host;
    private $port;

    /**
     * settings
     * @var array
     */
    protected $settings = [
        'debug'    => false,
        'log_file' => '',
        'sleep_ms' => 800, // millisecond. 1s = 1000ms = 1000 000us
        'max_conn' => 25,
    ];

    /**
     * supported Events
     * @var array
     */
    protected $supportedEvents = [
        'handshake', 'open', 'message', 'close', 'error'
    ];

    /**
     * 4个事件(@see $supportedEvents)的回调函数，分别在新用户连接、有消息到达、用户断开、发生错误时触发
     * @var array
     */
    private $callbacks = [];

    /**
     * 连接的客户端列表
     * @var resource[]
     * [
     *  index => socket,
     * ]
     */
    public $clients = [];

    /**
     * 循环连接池
     * @var array
     */
    private $cycle = [];

    /**
     * 连接的客户端握手状态列表
     * @var array
     * [
     *  index => bool, // bool: handshake status.
     * ]
     */
    private $hands = [];


    /**
     * WebSocket constructor.
     * @param string $host
     * @param int $port
     * @param array $settings
     */
    public function __construct(string $host = '0.0.0.0', int $port = 80, array $settings = array())
    {
        if ( !extension_loaded('sockets') ) {
            throw new \InvalidArgumentException("the extension [sockets] is required for run the server.");
        }

        $this->host = $host ?: self::DEFAULT_HOST;
        $this->port = $port ?: self::DEFAULT_PORT;

        $this->wsHandlers = new \SplFixedArray( count($this->supportedEvents) );
        $this->setSettings($settings);
    }

    /////////////////////////////////////////////////////////////////////////////////////////
    /// start server
    /////////////////////////////////////////////////////////////////////////////////////////

    protected function beforeStart()
    {
        if ( count($this->callbacks) < 1 ) {
            $sup = implode(',', $this->supportedEvents);
            $this->print('[ERROR] Please register event handle callback before start. supported events: ' . $sup, true, -500);
        }
    }

    // 挂起socket
    public function start()
    {
        $this->beforeStart();

        // AF_INET: IPv4 网络协议。TCP 和 UDP 都可使用此协议。
        // more see http://php.net/manual/en/function.socket-create.php
        $this->socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);

        // 允许使用本地地址
        socket_set_option($this->socket, SOL_SOCKET, SO_REUSEADDR, TRUE);
        socket_bind($this->socket, $this->getHost(), $this->getPort());

        $max = $this->getSetting('max_conn');
        $this->log("Started WebSocket server on {$this->host}:{$this->port} (max allow connection: $max)");

        // 最多 $this->maxUser 个人连接，超过的客户端连接会返回 WSAECONNREFUSED 错误
        socket_listen($this->socket, $max);

        // interval time
        $setTime = (int)$this->getSetting('sleep_ms', 800);
        $sleepTime = $setTime > 50 ? $setTime : 800;
        $sleepTime = $sleepTime* 1000; // ms -> us

        while(TRUE) {
            $this->cycle = $this->clients;
            $this->cycle[] = $this->socket;

            // 阻塞用，有新连接时才会结束
            socket_select($this->cycle, $write, $except, null);

            foreach ($this->cycle as $k => $v) {

                // 每次循环检查到 $this->socket 时，都会用 socket_accept() 去检查是否有新的连接进入，有就加入连接列表
                if($v === $this->socket) {
                    // Accepts a connection on a socket
                    if ( false === ($sock = socket_accept($v)) ) {
                        $msg = socket_strerror(socket_last_error());

                        $this->trigger(self::ON_ERROR, [$msg, $this]);
                        continue;
                    }

                    // 如果请求来自监听端口那个 socket，则创建一个新的 socket 用于通信
                    $this->addClient($sock);
                    continue;
                }

                $index = array_search($v, $this->clients, true);

                // 不在已经记录的client列表中
                if ($index === false) {
                    continue;
                }

                // 没消息的socket就跳过
                if (!@socket_recv($v, $data, 1024, 0) || !$data) {
                    $this->close($v, $index);
                    continue;
                }

                // 是否已经握手
                if ( !$this->hands[$index] ) {
                    $this->upgrade($v, $data, $index);
                    continue;
                }

                // call on message handler
                $this->trigger(self::ON_MESSAGE, [$this->decode($data), $this, $index]);
            }

            // sleep(1);
            usleep($sleepTime);
        }
    }

    /**
     * 增加一个初次连接的客户端 同时记录到握手列表，标记为未握手
     * @param resource $socket
     */
    private function addClient($socket)
    {
        $this->clients[] = $socket;

//        $index = array_keys($this->clients);
//        $index = end($index);
        $index = count($this->clients) - 1;

        $this->hands[$index] = false;
    }

    /**
     * 关闭一个连接
     * @param $index
     * @param null|resource $socket
     * @return mixed
     */
    public function close($index, $socket = null)
    {
        if (null === $socket) {
            $socket = $this->clients[$index];
        }

        // close socket connection
        socket_close($socket);

        unset($this->clients[$index], $this->hands[$index]);

        // call close handler
        return $this->trigger(self::ON_CLOSE, [$this, $index]);
    }

    /**
     * 响应升级协议(握手)
     * Response to upgrade agreement (handshake)
     * @param resource $socket
     * @param string $data
     * @param int $index
     * @return bool|mixed
     */
    private function upgrade($socket, string $data, int $index)
    {
        if ( preg_match("/Sec-WebSocket-Key: (.*)\r\n/",$data, $match) ) {

            // call handshake handler
            if ( false === $this->trigger(self::ON_HANDSHAKE, [$this, $data, $socket, $index]) ) {
                return $this->close($socket, $index);
            }

            $key = base64_encode(sha1($match[1] . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11', true));

//            $upgrade  = "HTTP/1.1 101 Switching Protocol\r\n" .
//                "Upgrade: websocket\r\n" .
//                "Connection: Upgrade\r\n" .
//                "Server: web-socket-server\r\n" .
//                "Sec-WebSocket-Accept: {$key}\r\n\r\n";  //必须以两个回车结尾
            $upgrade  = $this->buildResponse(101, 'Switching Protocol', '', [
                'Upgrade' => 'websocket',
                'Connection' => 'Upgrade',
                'Sec-WebSocket-Accept' => $key,
            ]);

            // response data to client
            $this->writeTo($socket, $upgrade);

            $this->hands[$index] = true;

            // call open handler
            return $this->trigger(self::ON_OPEN, [$this, $data, $index]);
        }

        $this->log("handle handshake failed! DATA: \n $data", 'error');

        $this->sendTo($index,
            "HTTP/1.1 400 Bad Request" . self::HEADER_END .
            "<b>400 Bad Request</b><br>Sec-WebSocket-Key not found.<br>This is a WebSocket service and can not be accessed via HTTP.",
            -1);

        return $this->close($socket, $index);
    }

    /**
     * build response data
     * @param $httpCode
     * @param $httpCodeMsg
     * @param string $body
     * @param array $headers
     * @return string
     */
    public function buildResponse($httpCode, $httpCodeMsg, $body = '', array $headers = [])
    {
        $response = "HTTP/1.1 {$httpCode} {$httpCodeMsg}" . self::HEADER_LINE_END;

        foreach ($headers as $name => $value) {
            $response .= "$name: $value" . self::HEADER_LINE_END;
        }

        $response = trim($response) . self::HEADER_END;
        $response .= $body;

        return $response;
    }

    /////////////////////////////////////////////////////////////////////////////////////////
    /// process method
    /////////////////////////////////////////////////////////////////////////////////////////

    /*
    $ws = new WebSocketServer;
    $ws->asDaemon();
    $ws->changeIdentity(65534, 65534); // nobody/nogroup
    $ws->registerSignals();
     */

    /**
     * run as daemon process
     * @return $this
     */
    public function asDaemon()
    {
        $this->checkPcntlExtension();

        // Forks the currently running process
        $pid = pcntl_fork();

        // 父进程和子进程都会执行下面代码
        if ( $pid === -1) {
            /* fork failed */
            $this->print("fork sub-process failure!", true, - __LINE__);

        } elseif ($pid) {
            // 父进程会得到子进程号，所以这里是父进程执行的逻辑
            // 即 fork 进程成功，这是在父进程（自己通过命令行调用启动的进程）内，得到了fork的进程(子进程)的pid

            // pcntl_wait($status); //等待子进程中断，防止子进程成为僵尸进程。

            // 关闭当前进程，所有逻辑交给在后台的子进程处理 -- 在后台运行
            $this->print("Server run on the background.[PID: $pid]", true, 0);

        } else {
            // fork 进程成功，子进程得到的$pid为0, 所以这里是子进程执行的逻辑。
            /* child becomes our daemon */
            posix_setsid();

            chdir('/');
            umask(0);

            //return posix_getpid();
        }

        return $this;
    }

    /**
     * Change the identity to a non-priv user
     * @param int $uid
     * @param int $gid
     * @return $this
     */
    public function changeIdentity(int $uid, int $gid )
    {
        $this->checkPcntlExtension();

        if( !posix_setgid( $gid ) ) {
            $this->print("Unable to set group id to [$gid]", true, - __LINE__);
        }

        if( !posix_setuid( $uid ) ) {
            $this->print("Unable to set user id to [$uid]", true, - __LINE__);
        }

        return $this;
    }

    public function registerSignals()
    {
        $this->checkPcntlExtension();

        /* handle signals */
        pcntl_signal(SIGTERM, [ $this, 'sigHandler']);
        pcntl_signal(SIGINT, [ $this, 'sigHandler']);
        pcntl_signal(SIGCHLD, [ $this, 'sigHandler']);

        // eg: 向当前进程发送SIGUSR1信号
        // posix_kill(posix_getpid(), SIGUSR1);

        return $this;
    }

    /**
     * Signal handler
     * @param $sig
     */
    public function sigHandler($sig)
    {
        $this->checkPcntlExtension();

        switch($sig) {
            case SIGTERM:
            case SIGINT:
                exit();
                break;

            case SIGCHLD:
                pcntl_waitpid(-1, $status);
                break;
        }
    }

    private function checkPcntlExtension()
    {
        if ( ! function_exists('pcntl_fork') ) {
            throw new \RuntimeException('PCNTL functions not available on this PHP installation, please install pcntl extension.');
        }
    }

    /////////////////////////////////////////////////////////////////////////////////////////
    /// events method
    /////////////////////////////////////////////////////////////////////////////////////////

    /**
     * register a event callback
     * @param string    $event    event name
     * @param callable  $cb       event callback
     * @param bool      $replace  replace exists's event cb
     * @return WebSocketServer
     */
    public function on(string $event, callable $cb, bool $replace = false): self
    {
        if ( false === ($key = array_search($event, $this->supportedEvents, true)) ) {
            $sup = implode(',', $this->supportedEvents);

            throw new \InvalidArgumentException("The want registered event is not supported. Supported: $sup");
        }

        if ( !$replace && isset($this->callbacks[$key]) ) {
            throw new \InvalidArgumentException("The want registered event [$event] have been registered! don't allow replace.");
        }

        $this->callbacks[$event] = $cb;

        return $this;
    }

    /**
     * @param string $event
     * @param array $args
     * @return mixed
     */
    protected function trigger(string $event, array $args = [])
    {
        if ( false === ($key = array_search($event, $this->supportedEvents, true)) ) {
            throw new \InvalidArgumentException("Trigger a not exists's event: $event.");
        }

        if ( !isset($this->callbacks[$key]) || !($cb = $this->callbacks[$key]) ) {
            return '';
        }

        return call_user_func_array($cb, $args);
    }

    /**
     * @param string $event
     * @return bool
     */
    public function isSupportedEvent(string $event): bool
    {
        return in_array($event, $this->supportedEvents);
    }

    /**
     * @return array
     */
    public function getSupportedEvents(): array
    {
        return $this->supportedEvents;
    }

    /////////////////////////////////////////////////////////////////////////////////////////
    /// send message to client
    /////////////////////////////////////////////////////////////////////////////////////////

    /**
     * @param string $data
     * @param null|int $from
     * @param int|array|null $target
     * @param array $expected
     * @return bool|int
     */
    public function send(string $data, $from = null, $target = null, array $expected = [])
    {
        return is_int($target) ?
            $this->sendTo($target, $data, $from) :
            $this->broadcast($data, $from, (array)$target,  $expected);
    }

    /**
     * @param int $target 发送目标
     * @param string $data
     * @param int $from 发送者
     * @return bool|int
     */
    public function sendTo(int $target, string $data, int $from = -1)
    {
        if ( !isset($this->hands[$target]) ) {
            $this->log("The target user #$target not connected!", 'error');

            return 1703;
        }

        $res = $this->frame($data);
        $socket = $this->clients[$target];

        $this->log("The #{$from} send message to #{$target}. Data: {$data}");

        return $this->writeTo($socket, $res);
    }

    /**
     * @param string $data
     * // param int $group
     * @param null|string $from
     * @param array $expected
     * @param array $targets
     * @return int
     */
    public function broadcast(string $data, $from = null, array $targets = [], array $expected = []): int
    {
        $res = $this->frame($data);
        $len = strlen($res);
        $from = $from === null ? 'SYSTEM' : '#' . $from;

        // to all
        if ( !$expected && !$targets) {
            $this->log("(broadcast)The {$from} send message to all users. Data: {$data}");

            foreach ($this->clients as $socket) {
                $this->writeTo($socket, $res, $len);
            }

        } else {
            $this->log("(broadcast)The {$from} gave some specified user sending a message. Data: {$data}");
            foreach ($this->clients as $index => $socket) {
                if ( isset($expected[$index]) ) {
                    continue;
                }

                if ( $targets && !isset($targets[$index]) ) {
                    continue;
                }

                $this->writeTo($socket, $res, $len);
            }
        }

        // $msg = socket_strerror(socket_last_error());
        return socket_last_error();
    }

    /**
     * response data to client by socket connection
     * @param resource $socket
     * @param string $data
     * @param int $length
     * @return int
     */
    public function writeTo($socket, string $data, int $length = 0)
    {
        // response data to client
        return socket_write($socket, $data, $length > 0 ? $length : strlen($data));
    }

    /////////////////////////////////////////////////////////////////////////////////////////
    /// helper method
    /////////////////////////////////////////////////////////////////////////////////////////

    //体力活
    public function frame($s)
    {
        $a = str_split($s, 125);
        $prefix = self::BINARY_TYPE_BLOB;

        if (count($a) === 1){
            return $prefix . chr(strlen($a[0])) . $a[0];
        }

        $ns = '';

        foreach ($a as $o){
            $ns .= $prefix . chr(strlen($o)) . $o;
        }

        return $ns;
    }

    // 体力活
    public function decode($buffer)
    {
        /*$len = $masks = $data =*/ $decoded = '';
        $len = ord($buffer[1]) & 127;

        if ($len === 126) {
            $masks = substr($buffer, 4, 4);
            $data = substr($buffer, 8);
        } else if ($len === 127) {
            $masks = substr($buffer, 10, 4);
            $data = substr($buffer, 14);
        } else {
            $masks = substr($buffer, 2, 4);
            $data = substr($buffer, 6);
        }

        $dataLen = strlen($data);
        for ($index = 0; $index < $dataLen; $index++) {
            $decoded .= $data[$index] ^ $masks[$index % 4];
        }

        return $decoded;
    }

    /**
     * @param string $message
     * @param string $type
     * @param array $data
     */
    public function log(string $message, string $type = 'info', array $data = [])
    {
        $date = date('Y-m-d H:i:s');
        $type = strtoupper(trim($type));

        $this->print("[$date] [$type] $message " . ( $data ? json_encode($data) : '' ) );
    }

    /**
     * @param mixed $messages
     * @param bool $nl
     * @param null|int $exit
     */
    public function print($messages, $nl = true, $exit = null)
    {
        $text = is_array($messages) ? implode(($nl ? "\n" : ''), $messages) : $messages;

        fwrite(\STDOUT, $text . ($nl ? "\n" : ''));

        if ( $exit !== null ) {
            exit((int)$exit);
        }
    }

    /**
     * check it a accepted client and handshake completed  client
     * @param int $index
     * @return bool
     */
    public function isHanded(int $index): bool
    {
        return $this->hands[$index] ?? false;
    }

    /**
     * @return array
     */
    public function getHands(): array
    {
        return $this->hands;
    }

    /**
     * @return int
     */
    public function count(): int
    {
        return count($this->hands);
    }

    /**
     * @return int
     */
    public function countHanded(): int
    {
        $count = 0;

        foreach ($this->hands as $handStatus) {
            if ($handStatus) {
                $count++;
            }
        }

        return $count;
    }

    /**
     *  check it is a accepted client 
     * @notice maybe don't complete handshake
     * @param $index
     * @return bool
     */
    public function isClient($index)
    {
        return isset($this->hands[$index]);
    }

    /**
     * check it is a accepted client 
     * @notice maybe don't complete handshake
     * @param  resource $socket
     * @return bool
     */
    public function isClientSocket($socket)
    {
        return false !== array_search($socket, $this->clients, true);
    }

    /**
     * get client socket connection by index
     * @param $index
     * @return resource|false
     */
    public function getClient($index)
    {
        if ( $this->isClient($index) ) {
            return $this->clients[$index];
        }

        return false;
    }

    /**
     * @return array
     */
    public function getClients(): array
    {
        return $this->clients;
    }

    /**
     * @return int
     */
    public function getClientCount(): int
    {
        return count($this->clients);
    }

    /**
     * @return string
     */
    public function getHost(): string
    {
        if ( !$this->host ) {
            $this->host = self::DEFAULT_HOST;
        }

        return $this->host;
    }

    /**
     * @return int
     */
    public function getPort(): int
    {
        if ( !$this->port || $this->port <= 0 ) {
            $this->port = self::DEFAULT_PORT;
        }

        return $this->port;
    }

    /**
     * @return array
     */
    public function getSettings(): array
    {
        return $this->settings;
    }

    /**
     * @param array $settings
     */
    public function setSettings(array $settings)
    {
        if ( $settings) {
            $this->settings = array_merge($this->settings, $settings);
        }
    }

    /**
     * @param string $name
     * @param mixed $default
     * @return mixed
     */
    public function getSetting(string $name, $default = null)
    {
        return $this->settings[$name] ?? $default;
    }
}
