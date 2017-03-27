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
    const ON_OPEN = 'open';
    const ON_MESSAGE = 'message';
    const ON_CLOSE = 'close';
    const ON_ERROR = 'error';

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
        'log_file' => '',
        'max_conn' => 25,
    ];

    /**
     * settings
     * @var array
     */
    protected $supportEvents = [
        'open', 'message', 'close', 'error'
    ];

    /**
     * 连接的客户端
     * @var resource[]
     */
    public $accepted = [];

    /**
     * 循环连接池
     * @var array
     */
    private $cycle = [];

    private $handed = [];

    /**
     * 接受三个回调函数，分别在新用户连接、有消息到达、用户断开时触发
     * connect, message, close
     * @var array
     */
    private $callbacks = [];

    /**
     * WebSocket constructor.
     * @param string $host
     * @param int $port
     * @param array $settings
     */
    public function __construct(string $host = '0.0.0.0', $port = 80, array $settings = array())
    {
        $this->host = $host ?: self::DEFAULT_HOST;
        $this->port = $port ?: self::DEFAULT_PORT;

        // $this->accepted = new \SplFixedArray(3);
        $this->setSettings($settings);
    }

    /////////////////////////////////////////////////////////////////////////////////////////
    /// start server
    /////////////////////////////////////////////////////////////////////////////////////////

    protected function beforeStart()
    {
        if ( count($this->callbacks) < 1 ) {
            $sup = implode(',', $this->supportEvents);
            $this->write('[ERROR] Please register event handle callback before start. supported events: ' . $sup, true, -500);
        }
    }

    // 挂起socket
    public function start()
    {
        $this->beforeStart();

        $this->socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);

        // 允许使用本地地址
        socket_set_option($this->socket, SOL_SOCKET, SO_REUSEADDR, TRUE);
        socket_bind($this->socket, $this->getHost(), $this->getPort());

        $max = $this->getSetting('max_conn');
        $this->log("Started WebSocket server on {$this->host}:{$this->port} (max allow connection: $max)");

        // 最多 $this->maxUser 个人连接，超过的客户端连接会返回 WSAECONNREFUSED 错误
        socket_listen($this->socket, $max);

        while(TRUE) {
            $this->cycle = $this->accepted;
            $this->cycle[] = $this->socket;

            // 阻塞用，有新连接时才会结束
            socket_select($this->cycle, $write, $except, null);

            foreach ($this->cycle as $k => $v) {
                if($v === $this->socket) {
                    if (($accept = socket_accept($v)) < 0) {
                        $msg = socket_strerror(socket_last_error());

                        $this->trigger(self::ON_ERROR, [$msg, $this]);
                        continue;
                    }

                    // 如果请求来自监听端口那个套接字，则创建一个新的套接字用于通信
                    $this->addAccept($accept);
                    continue;
                }

                $index = array_search($v, $this->accepted, true);

                if ($index === NULL) {
                    continue;
                }

                // 没消息的socket就跳过
                if (!@socket_recv($v, $data, 1024, 0) || !$data) {
                    $this->close($v, $index);
                    continue;
                }

                // 是否已经握手
                if ( !$this->handed[$index] ) {
                    $this->upgrade($v, $data, $index);
                    continue;
                }

                // call on message handler
                $this->trigger(self::ON_MESSAGE, [$this->decode($data), $this, $index]);
            }

            sleep(1);
        }
    }

    // 增加一个初次连接的用户
    private function addAccept($accept)
    {
        $this->accepted[] = $accept;

//        $index = array_keys($this->accepted);
//        $index = end($index);
        $index = count($this->accepted) - 1;

        $this->handed[$index] = false;
    }

    /**
     * 关闭一个连接
     * @param $index
     * @param null|resource $accept
     */
    public function close($index, $accept = null)
    {
        if (null === $accept) {
            $accept = $this->accepted[$index];
        }

        // $index = array_search($accept, $this->accepted, true);
        socket_close($accept);

        unset($this->accepted[$index], $this->handed[$index]);

        // call close handler
        $this->trigger(self::ON_CLOSE, [$this, $index]);
    }

    /**
     * 响应升级协议(握手)
     * Response to upgrade agreement (handshake)
     * @param $accept
     * @param $data
     * @param $index
     */
    private function upgrade($accept, $data, $index)
    {
        if ( preg_match("/Sec-WebSocket-Key: (.*)\r\n/",$data, $match) ) {
            $key = base64_encode(sha1($match[1] . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11', true));
            $upgrade  = "HTTP/1.1 101 Switching Protocol\r\n" .
                "Upgrade: websocket\r\n" .
                "Connection: Upgrade\r\n" .
                'Sec-WebSocket-Accept: ' . $key . "\r\n\r\n";  //必须以两个回车结尾

            socket_write($accept, $upgrade, strlen($upgrade));

            $this->handed[$index] = true;

            // call open handler
            $this->trigger(self::ON_OPEN, [$this, $data, $index]);
        } else {
            $this->log("handler handshake failed! DATA: \n $data", 'error');
            $this->close($accept, $index);
        }
    }

    /////////////////////////////////////////////////////////////////////////////////////////
    /// events method
    /////////////////////////////////////////////////////////////////////////////////////////

    /**
     * @param string $event
     * @param callable $cb
     * @return self
     */
    public function on(string $event, callable $cb): self
    {
        if ( !isset($this->callbacks[$event]) ) {
            $this->callbacks[$event] = $cb;
        }

        return $this;
    }

    /**
     * @param $event
     * @param array $args
     * @return mixed
     */
    protected function trigger($event, array $args = [])
    {
        if ( !isset($this->callbacks[$event]) || !($cb = $this->callbacks[$event]) ) {
            return '';
        }

        return call_user_func_array($cb, $args);
    }

    /////////////////////////////////////////////////////////////////////////////////////////
    /// send message
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
    public function sendTo(int $target, string $data, int $from)
    {
        if ( !isset($this->handed[$target]) ) {
            $this->log("The target user #$target not connected!", 'error');

            return 1703;
        }

        $res = $this->frame($data);
        $socket = $this->accepted[$target];

        $this->log("The #{$from} send message to #{$target}. Data: {$data}");

        return socket_write($socket, $res, strlen($res));
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
        $from = $from === null ? 'SYSTEM' : '#' . $from;

        // to all
        if ( !$expected && !$targets) {
            $this->log("(broadcast)The {$from} send message to all users. Data: {$data}");

            foreach ($this->accepted as $socket) {
                socket_write($socket, $res, strlen($res));
            }

        } else {
            $this->log("(broadcast)The {$from} gave some specified user sending a message. Data: {$data}");
            foreach ($this->accepted as $index => $socket) {
                if ( isset($expected[$index]) ) {
                    continue;
                }

                if ( $targets && !isset($targets[$index]) ) {
                    continue;
                }

                socket_write($socket, $res, strlen($res));
            }
        }

        // $msg = socket_strerror(socket_last_error());
        return socket_last_error();
    }

    /////////////////////////////////////////////////////////////////////////////////////////
    /// helper method
    /////////////////////////////////////////////////////////////////////////////////////////

    //体力活
    public function frame($s)
    {
        $a = str_split($s, 125);

        if (count($a) === 1){
            return "\x81" . chr(strlen($a[0])) . $a[0];
        }

        $ns = '';

        foreach ($a as $o){
            $ns .= "\x81" . chr(strlen($o)) . $o;
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

        $this->write("[$date] [$type] $message " . ( $data ? json_encode($data) : '' ) );
    }

    /**
     * @param mixed $messages
     * @param bool $nl
     * @param null|int $exit
     */
    public function write($messages, $nl = true, $exit = null)
    {
        $text = is_array($messages) ? implode(($nl ? "\n" : ''), $messages) : $messages;

        fwrite(\STDOUT, $text . ($nl ? "\n" : ''));

        if ( $exit !== null ) {
            exit((int)$exit);
        }
    }

    /**
     * @param int $index
     * @return bool
     */
    public function isHanded(int $index): bool
    {
        return $this->handed[$index] ?? false;
    }

    /**
     * @return array
     */
    public function getHanded(): array
    {
        return $this->handed;
    }

    /**
     * @return int
     */
    public function count(): int
    {
        return count($this->handed);
    }

    /**
     * @return array
     */
    public function getAccepted(): array
    {
        return $this->accepted;
    }

    /**
     * @return int
     */
    public function getAcceptedCount(): int
    {
        return count($this->accepted);
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

    /**
     * @return array
     */
    public function getSupportEvents(): array
    {
        return $this->supportEvents;
    }
}
