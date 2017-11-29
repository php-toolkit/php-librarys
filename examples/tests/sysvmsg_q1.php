<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/5/30
 * Time: 上午11:57
 */

/**
 * 是对Linux Sysv系统消息队列的封装，单台服务器推荐使用
 * @from https://github.com/matyhtf/framework/blob/master/libs/Swoole/Queue/MsgQ.php
 * @author Tianfeng.Han
 */
class MsgQ
{
    protected $msgid;
    protected $msgtype = 1;
    protected $msg;

    public function __construct($config)
    {
        if (!empty($config['msgid']))
        {
            $this->msgid = $config['msgid'];
        }
        else
        {
            $this->msgid = ftok(__FILE__, 0);
        }
        if (!empty($config['msgtype']))
        {
            $this->msgtype = $config['msgtype'];
        }
        $this->msg = msg_get_queue($this->msgid);
    }

    public function pop()
    {
        $ret = msg_receive($this->msg, 0, $this->msgtype, 65525, $data);

        if ($ret) {
            return $data;
        }
        return false;
    }

    public function push($data)
    {
        return msg_send($this->msg, $this->msgtype, $data);
    }

    /**
     * @return array
     */
    public function getStat()
    {
        return msg_stat_queue($this->msg);
    }
}

$q = new MsgQ([
    'msgtype' => 2,
]);

var_dump($q);

$q->push('n1');
$q->push('n2');
$q->push(['array-value']);
//var_dump($q);

var_dump($q->getStat());

$i = 5;

while ($i--) {
    var_dump($q->pop());
    usleep(50000);
}
