<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/6/27
 * Time: 下午8:18
 */

namespace Inhere\Library\Helpers;

/**
 * Class SysCall
 * @package Inhere\Library\Helpers
 */
class SysCall
{
    /**
     * @param string $program
     * @return int|string
     */
    public static function getCpuUsage($program)
    {
        if (!$program) {
            return -1;
        }

        $info = exec('ps aux | grep ' . $program . ' | grep -v grep | grep -v su | awk {"print $3"}');

        return $info;
    }

    /**
     * @param $program
     * @return int|string
     */
    public static function getMemUsage($program)
    {
        if (!$program) {
            return -1;
        }

        $info = exec('ps aux | grep ' . $program . ' | grep -v grep | grep -v su | awk {"print $4"}');

        return $info;
    }


    /**
     * 支持查看指定目录，默认当前目录
     * CLI:
     *     php test.php -d=path
     *     php test.php --dir=path
     * WEB:
     *    /test.php?dir=path
     */
    public static function gitCheck()
    {
        if (PHP_SAPI === 'cli') {
            $_GET = getopt('d::', ['dir::']);
        }

        // 获取要查看的目录，没有则检测当前目录
        $dir = $_GET['d'] ?? ($_GET['dir'] ?? __DIR__);

        if (!is_dir($dir)) {
            trigger_error($dir);
        }

        ob_start();
        system("cd $dir && git branch -v");
        $c = ob_get_clean();

        $result = preg_match('#\* (?<brName>[\S]+)(?:\s+)(?<logNum>[0-9a-z]{7})(?<ciText>.*)#i', $c, $data);
        $data['projectName'] = basename($dir);

        // var_dump($c,$result, $data);
        return ($result === 1) ? $data : null;
    }
}
