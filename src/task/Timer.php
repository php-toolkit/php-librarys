<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/5/21
 * Time: 上午10:05
 */

namespace inhere\library\task;

/**
 * Class Timer
 * @package inhere\library\task
 */
class Timer
{
    /**
     * 保存所有定时任务
     * @var array
     */
    public static $tasks = [];

    /**
     * 定时间隔
     * @var int
     */
    public static $time = 1;

    /**
     * 开启服务
     * @param $time int
     */
    public static function run($time = null)
    {
        if ($time) {
            self::$time = $time;
        }

        self::installHandler();

        pcntl_alarm(1);

        while(true) {
            pcntl_signal_dispatch();
            usleep(100000);
        }
    }

    /**
     * 注册信号处理函数
     */
    public static function installHandler()
    {
        pcntl_signal(SIGALRM, array('Timer', 'signalHandler'));
    }

    /**
     * 信号处理函数
     */
    public static function signalHandler()
    {
        self::task();

        // 一次信号事件执行完成后,再触发下一次
        pcntl_alarm(self::$time);
    }

    /**
     *执行回调
     */
    public static function task()
    {
        if (empty(self::$tasks)) {//没有任务,返回
            return;
        }

        foreach (self::$tasks as $time => $arr) {
            $current = time();

            //遍历每一个任务
            foreach ($arr as $k => $job) {
                $func = $job['func']; /*回调函数*/
                $argv = $job['argv']; /*回调函数参数*/
                $interval = $job['interval']; /*时间间隔*/
                $persist = $job['persist']; /*持久化*/

                // 当前时间有执行任务
                if ($current == $time) {

                    //调用回调函数,并传递参数
                    call_user_func_array($func, $argv);

                    //删除该任务
                    unset(self::$tasks[$time][$k]);
                }

                // 如果做持久化,则写入数组,等待下次唤醒
                if ($persist) {
                    self::$tasks[$current + $interval][] = $job;
                }
            }

            if (empty(self::$tasks[$time])) {
                unset(self::$tasks[$time]);
            }
        }
    }

    /**
     *添加任务
     */
    public static function add($interval, $func, $argv = [], $persist = false)
    {
        if (is_null($interval)) {
            return;
        }
        $time = time() + $interval;
        //写入定时任务
        self::$tasks[$time][] = array('func' => $func, 'argv' => $argv, 'interval' => $interval, 'persist' => $persist);
    }

    /**
     *删除所有定时器任务
     */
    public function dellAll()
    {
        self::$tasks = [];
    }
}