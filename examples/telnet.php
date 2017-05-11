<?php
/**
 * usage: php examples/telnet.php 127.0.0.1 4730
 */

use inhere\gearman\Helper;

error_reporting(E_ALL | E_STRICT);

require dirname(__DIR__) . '/../../autoload.php';
date_default_timezone_set('Asia/Shanghai');

global $argv;
$opts = getopt('h', ['help']);

if (isset($opts['h']) || isset($opts['help'])) {
    $script = array_shift($argv);
    $script = Helper::color($script, Helper::$styles['light_green']);
    $help = <<<EOF
Start a telnet client.

Usage:
  $script HOST [PORT]

Options:
  -h,--help  Show this help information
\n
EOF;
    exit($help);
}

$host = isset($argv[1]) ? $argv[1] : '127.0.0.1';
$port = isset($argv[2]) ? $argv[2] : 80;

printf("Connect to the server {$host}:{$port}");

$tt = new \inhere\library\network\Telnet($host, $port);

// var_dump($tt);die;

//echo $tt->command('status');
$tt->interactive();
