<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2016/10/10
 * Time: 下午8:29
 */

namespace inhere\librarys\webSocket\server;

use inhere\librarys\webSocket\server\parts\Request;
use inhere\librarys\webSocket\server\parts\Response;

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

    const ON_CONNECT   = 'connect';
    const ON_HANDSHAKE = 'handshake';
    const ON_OPEN      = 'open';
    const ON_MESSAGE   = 'message';
    const ON_CLOSE     = 'close';
    const ON_ERROR     = 'error';

    const DEFAULT_HOST = '0.0.0.0';
    const DEFAULT_PORT = 80;

    /**
     * the master socket
     * @var resource
     */
    private $master;
    private $host;
    private $port;

    /**
     * 连接的客户端列表
     * @var resource[]
     * [
     *  id => socket,
     * ]
     */
    private $sockets = [];

    /**
     * 连接的客户端握手状态列表
     * @var array
     * [
     *  id => [ ip=> string , port => int, handshake => bool ], // bool: handshake status.
     * ]
     */
    private $clients = [];
    
    /**
     * settings
     * @var array
     */
    protected $settings = [
        'debug'    => false,
        'log_file' => '',
        // while 循环时间间隔 毫秒 millisecond. 1s = 1000ms = 1000 000us
        'sleep_ms' => 800,
        // 最大允许连接数量
        'max_conn' => 25,
        // 最大数据接收长度 1024 2048
        'max_data_len' => 1024,
    ];

    /**
     * default client info data
     * @var array
     */
    protected $defaultInfo = [
        'ip' => '',
        'port' => 0,
        'handshake' => false,
        'path' => '/',
    ];

    /**
     * 5个事件(@see $supportedEvents)的回调函数
     * 分别在新用户连接、连接成功、有消息到达、用户断开、发生错误时触发
     * @var \SplFixedArray
     */
    private $callbacks;

    /**
     * @return array
     */
    public function getSupportedEvents(): array
    {
        return [ self::ON_CONNECT, self::ON_HANDSHAKE, self::ON_OPEN, self::ON_MESSAGE, self::ON_CLOSE, self::ON_ERROR];
    }

    /**
     * WebSocket constructor.
     * @param string $host
     * @param int $port
     * @param array $settings
     */
    public function __construct(string $host = '0.0.0.0', int $port = 80, array $settings = array())
    {
        $this->host = $host;
        $this->port = $port;

        $this->callbacks = new \SplFixedArray( count($this->getSupportedEvents()) );
        $this->setSettings($settings);
    }

    /////////////////////////////////////////////////////////////////////////////////////////
    /// start server
    /////////////////////////////////////////////////////////////////////////////////////////

    protected function beforeStart()
    {
        if ( !extension_loaded('sockets') ) {
            throw new \InvalidArgumentException('the extension [sockets] is required for run the server.');
        }

        if ( count($this->callbacks) < 1 ) {
            $sup = implode(',', $this->getSupportedEvents());
            $this->print('[ERROR] Please register event handle callback before start. supported events: ' . $sup, true, -500);
        }
    }

    /**
     * create and prepare socket resource
     */
    protected function prepareSocket()
    {
        // reset
        socket_clear_error();
        $this->sockets = $this->clients = [];

        // 创建一个 TCP socket
        // AF_INET: IPv4 网络协议。TCP 和 UDP 都可使用此协议。
        // AF_UNIX: 使用 Unix 套接字. 例如 /tmp/my.sock
        // more see http://php.net/manual/en/function.socket-create.php
        $this->master = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);

        if ( !is_resource($this->master) ) {
            $this->print('[ERROR] Unable to create socket: '. $this->getSocketError(), true, socket_last_error());
        }

        // 设置IP和端口重用,在重启服务器后能重新使用此端口;
        socket_set_option($this->master, SOL_SOCKET, SO_REUSEADDR, TRUE);
        // socket_set_option($this->socket,SOL_SOCKET, SO_RCVTIMEO, ['sec' =>0, 'usec' =>100]);

        // 给套接字绑定名字
        socket_bind($this->master, $this->getHost(), $this->getPort());

        $max = $this->getSetting('max_conn');

        // 监听套接字上的连接. 最多允许 $max 个连接，超过的客户端连接会返回 WSAECONNREFUSED 错误
        socket_listen($this->master, $max);

        $this->log("Started WebSocket server on {$this->host}:{$this->port} (max allow connection: $max)");
    }

    /**
     * start server
     */
    public function start()
    {
        $this->beforeStart();

        // create and prepare
        $this->prepareSocket();

        $maxLen = (int)$this->getSetting('max_data_len', 1024);

        // interval time
        $setTime = (int)$this->getSetting('sleep_ms', 800);
        $sleepTime = $setTime > 50 ? $setTime : 800;
        $sleepTime *= 1000; // ms -> us

        while(true) {
            $write = $except = null;
            // copy， 防止 $this->sockets 的变动被 socket_select() 接收到
            $read = $this->sockets;
            $read[] = $this->master;

            // 会监控 $read 中的 socket 是否有变动
            // $tv_sec =0 时此函数立即返回，可以用于轮询机制
            // $tv_sec =null 将会阻塞程序执行，直到有新连接时才会继续向下执行
            if ( false === socket_select($read, $write, $except, null) ) {
                $this->log('socket_select() failed, reason: ' . $this->getSocketError(), 'error');
                continue;
            }

            // handle ...
            foreach ($read as $k => $sock) {
                $this->handleSocket($sock, $k, $maxLen);
            }

            //sleep(1);
            usleep($sleepTime);
        }
    }

    /**
     * @param resource $sock
     * @param int $k
     * @param int $len
     * @return bool
     */
    protected function handleSocket($sock, $k, $len)
    {
        // 每次循环检查到 $this->socket 时，都会用 socket_accept() 去检查是否有新的连接进入，有就加入连接列表
        if($sock === $this->master) {
            // 从已经监控的socket中接受新的客户端请求
            if ( false === ($newSock = socket_accept($sock)) ) {
                $msg = $this->getSocketError();
                $this->log($msg);
                $this->trigger(self::ON_ERROR, [$msg, $this]);
                return false;
            }

            $this->connect($newSock);
            return true;
        }

        $id = (int)$sock;

        // 不在已经记录的client列表中
        if ( !isset($this->sockets[$id], $this->clients[$id])) {
            $this->close($id, $sock);
            return false;
        }

        $data = null;
        // 函数 socket_recv() 从 socket 中接受长度为 len 字节的数据，并保存在 $data 中。
        $bytes = socket_recv($sock, $data, $len, 0);

        if (false === $bytes || !$data) {
            $this->log("Failed to receive data from #$id client or not received data, will close socket connection.");
            $this->close($id, $sock);
            return false;
        }

        // 是否已经握手
        if ( !$this->clients[$id]['handshake'] ) {
            $this->handshake($sock, $data, $id);
            return true;
        }

        $this->message($id, $data);

        return true;
    }

    /**
     * 增加一个初次连接的客户端 同时记录到握手列表，标记为未握手
     * @param resource $socket
     */
    public function connect($socket)
    {
        $id = (int)$socket;
        socket_getpeername($socket, $ip, $port);

        // 初始化客户端信息
        $this->clients[$id] = [
            'ip' => $ip,
            'port' => $port,
            'handshake' => false,
            'path' => '/',
        ];
        // 客户端连接单独保存
        $this->sockets[$id] = $socket;

        $this->log("a new client connected, ID: $id, Count: " . $this->count() . 'Info ', 'info', $this->clients );

        // 触发 connect 事件回调
        $this->trigger(self::ON_CONNECT, [$this, $id]);
    }

    /**
     * 响应升级协议(握手)
     * Response to upgrade agreement (handshake)
     * @param resource $socket
     * @param string $data
     * @param int $id
     * @return bool|mixed
     */
    protected function handshake($socket, string $data, int $id)
    {
        $this->log("Ready to shake hands with the #$id client connection");
        $response = new Response();

        if ( !preg_match("/Sec-WebSocket-Key: (.*)\r\n/",$data, $match) ) {
            $this->log("handle handshake failed! [Sec-WebSocket-Key] not found in header. Data: \n $data", 'error');

            $response
                ->setStatus(404)
                ->setBody('<b>400 Bad Request</b><br>[Sec-WebSocket-Key] not found in header.');

            $this->writeTo($socket, $response->toString());

            return $this->close($id, $socket);
        }

        $request = Request::makeByParseData($data);

        // 触发 handshake 事件回调，如果返回 false -- 拒绝连接，比如需要认证，限定路由，限定ip，限定domain等
        // 就停止继续处理。并返回信息给客户端
        if ( false === $this->trigger(self::ON_HANDSHAKE, [$request, $response, $socket, $id]) ) {
            $this->log("The #$id client handshake's callback return false, will close the connection", 'notice');
            $this->writeTo($socket, $response->toString());

            return $this->close($id, $socket);
        }

        $key = base64_encode(sha1(trim($match[1]) . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11', true));
        $response
            ->setStatus(101)
            ->setHeaders([
                'Upgrade' => 'websocket',
                'Connection' => 'Upgrade',
                'Sec-WebSocket-Accept' => $key,
            ], true);

        // 响应握手成功
        $this->writeTo($socket, $response->toString());

        // 标记已经握手 更新路由 path
        $this->clients[$id]['handshake'] = true;
        $this->clients[$id]['path'] = $request->getPath();

        $this->log("The #$id client connection handshake successful! client count:" . $this->count());

        // 握手成功 触发 open 事件
        return $this->trigger(self::ON_OPEN, [$this, $data, $id]);
    }

    /**
     * handle client message
     * @param int $id
     * @param string $data
     */
    protected function message(int $id, string $data)
    {
        $data = $this->decode($data);

        $this->log("Received a message from #$id, Data: $data");

        // call on message handler
        $this->trigger(self::ON_MESSAGE, [$this, $data, $id]);
    }

    /**
     * alias method of the `close()`
     * @param int $id
     * @param null|resource $socket
     * @return mixed
     */
    public function disconnect(int $id, $socket = null)
    {
        return $this->close($id, $socket);
    }

    /**
     * Closing a connection
     * @param int $id
     * @param null|resource $socket
     * @return bool
     */
    public function close(int $id, $socket = null)
    {
        if ( !is_resource($socket) && !($socket = $this->sockets[$id] ?? null) ) {
            $this->log("Close the client socket connection failed! #$id client socket not exists", 'error');
        }

        // close socket connection
        if ( is_resource($socket)  ) {
            socket_close($socket);
        }

        unset($this->sockets[$id], $this->clients[$id]);

        // call close handler
        $this->trigger(self::ON_CLOSE, [$this, $id]);

        $this->log("The #$id client connection has been closed! client count:" . $this->count());

        return true;
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
            $this->print('fork sub-process failure!', true, - __LINE__);

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
        if ( false === ($key = array_search($event, $this->getSupportedEvents(), true)) ) {
            $sup = implode(',', $this->getSupportedEvents());

            throw new \InvalidArgumentException("The want registered event is not supported. Supported: $sup");
        }

        if ( !$replace && isset($this->callbacks[$key]) ) {
            throw new \InvalidArgumentException("The want registered event [$event] have been registered! don't allow replace.");
        }

        $this->callbacks[$key] = $cb;

        return $this;
    }

    /**
     * @param string $event
     * @param array $args
     * @return mixed
     */
    protected function trigger(string $event, array $args = [])
    {
        if ( false === ($key = array_search($event, $this->getSupportedEvents(), true)) ) {
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
        return in_array($event, $this->getSupportedEvents(), true);
    }

    /////////////////////////////////////////////////////////////////////////////////////////
    /// send message to client
    /////////////////////////////////////////////////////////////////////////////////////////

    /**
     * @param string $data
     * @param null|int $from
     * @param int|array|null $target
     * @param int[] $expected
     * @return bool|int
     */
    public function send(string $data, int $from = -1, $target = null, array $expected = [])
    {
        return is_int($target) && $target >= 0 ?
            $this->sendTo($target, $data, $from) :
            $this->broadcast($data, $from, (array)$target,  $expected);
    }

    /**
     * @param int    $target 发送目标
     * @param string $data
     * @param int    $from 发送者
     * @return int
     */
    public function sendTo(int $target, string $data, int $from = -1)
    {
        if ( !$this->hasClient($target) ) {
            $this->log("The target user #$target not connected!", 'error');

            return 1703;
        }

        $res = $this->frame($data);
        $socket = $this->sockets[$target];
        $fromUser = $from < 0 ? 'SYSTEM' : $from;

        $this->log("The #{$fromUser} send message to #{$target}. Data: {$data}");

        return $this->writeTo($socket, $res);
    }

    /**
     * @param string $data
     * @param int    $from
     * @param int[]  $expected
     * @param int[]  $targets
     * @return int
     */
    public function broadcast(string $data, int $from = -1, array $targets = [], array $expected = []): int
    {
        $res = $this->frame($data);
        $len = strlen($res);
        $fromUser = $from < 0 ? 'SYSTEM' : $from;

        // to all
        if ( !$expected && !$targets) {
            $this->log("(broadcast)The #{$fromUser} send a message to all users. Data: {$data}");

            foreach ($this->sockets as $socket) {
                $this->writeTo($socket, $res, $len);
            }

        } else {
            $this->log("(broadcast)The #{$fromUser} gave some specified user sending a message. Data: {$data}");
            foreach ($this->sockets as $id => $socket) {
                if ( isset($expected[$id]) ) {
                    continue;
                }

                if ( $targets && !isset($targets[$id]) ) {
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
     * @param resource  $socket
     * @param string    $data
     * @param int       $length
     * @return int
     */
    public function writeTo($socket, string $data, int $length = 0)
    {
        // response data to client
        return socket_write($socket, $data, $length > 0 ? $length : strlen($data));
    }

    /**
     * @param null|resource $socket
     * @return string
     */
    public function getSocketError($socket = null)
    {
        return socket_strerror(socket_last_error($socket));
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
     *  check it is a accepted client
     * @notice maybe don't complete handshake
     * @param $id
     * @return bool
     */
    public function hasClient(int $id)
    {
        return isset($this->clients[$id]);
    }

    /**
     * get client info data
     * @param int $id
     * @return mixed
     */
    public function getClient(int $id)
    {
        return $this->clients[$id] ?? $this->defaultInfo;
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
    public function countClient(): int
    {
        return $this->count();
    }
    public function count(): int
    {
        return count($this->clients);
    }

    /**
     * check it a accepted client and handshake completed  client
     * @param int $id
     * @return bool
     */
    public function hasHandshake(int $id): bool
    {
        if ( $this->hasClient($id) ) {
            return $this->getClient($id)['handshake'];
        }

        return false;
    }

    /**
     * count handshake clients
     * @return int
     */
    public function countHandshake(): int
    {
        $count = 0;

        foreach ($this->clients as $info) {
            if ($info['handshake']) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * check it is a accepted client
     * @notice maybe don't complete handshake
     * @param  resource $socket
     * @return bool
     */
    public function isClientSocket($socket)
    {
        return in_array($socket, $this->sockets, true);
    }

    /**
     * get client socket connection by index
     * @param $id
     * @return resource|false
     */
    public function getSocket($id)
    {
        if ( $this->hasClient($id) ) {
            return $this->sockets[$id];
        }

        return false;
    }

    /**
     * @return array
     */
    public function getSockets(): array
    {
        return $this->sockets;
    }

    /**
     * @return resource
     */
    public function getMaster(): resource
    {
        return $this->master;
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
     * @return \SplFixedArray
     */
    public function getCallbacks(): \SplFixedArray
    {
        return $this->callbacks;
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
