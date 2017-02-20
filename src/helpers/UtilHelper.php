<?php

/**
*
*/
abstract class UtilHelper
{

    /**
     * 支持查看指定目录，默认当前目录
     * CLI:
     *     php test.php -d=path
     *     php test.php --dir=path
     * WEB:
     *    /test.php?dir=path
     */
    static public function gitCheck()
    {
        if ( PHP_SAPI === 'cli' ) {
            $_GET = getopt('d::', ['dir::']);
        }

        // 获取要查看的目录，没有则检测当前目录
        $dir = !empty($_GET['d']) ? $_GET['d'] : ( !empty($_GET['dir']) ? $_GET['dir'] : __DIR__);

        if ( !is_dir($dir) ) {
            trigger_error($dir);
        }

        ob_start();
        system("cd $dir && git branch -v");
        $c = ob_get_clean();

        $result = preg_match('#\* (?<brName>[^\s]+)(?:\s+)(?<logNum>[0-9a-z]{7})(?<ciText>.*)#i',$c, $data);
        $data['projectName'] = basename($dir);

        // var_dump($c,$result, $data);
        return ( $result === 1 ) ? $data : null;
    }

    /**
     * Display a var dump in firebug console
     *
     * @param object $object Object to display
     * @param string $type
     */
    static public function fd($object, $type = 'log')
    {
        $types = array('log', 'debug', 'info', 'warn', 'error', 'assert');

        if (!in_array($type, $types)) {
            $type = 'log';
        }

        $data = json_encode($object);

        echo <<<EOF
    <script type="text/javascript">console.$type($data);</script>
EOF;
    }

}