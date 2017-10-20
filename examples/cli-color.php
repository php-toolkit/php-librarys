<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/10/21
 * Time: 上午12:48
 */

use Inhere\Library\Helpers\Cli;

require __DIR__ . '/s-autoload.php';

echo Cli::color('message text', [Cli::FG_BLACK, Cli::BG_BLUE]), PHP_EOL;
echo Cli::color('message text', 'light_blue'), PHP_EOL;
echo Cli::color('message text', 'light_green'), PHP_EOL;
echo Cli::color('message text', 'light_cyan'), PHP_EOL;
echo Cli::color('message text', Cli::FG_LIGHT_GREEN), PHP_EOL;

echo "------------------------------------\n";

echo Cli::color(<<<TAG
<info>info color</info>
<suc>info color</suc>
<error>info color</error>
<danger>info color</danger>
<warning>info color</warning>
------------------------------------
TAG
);

Cli::write(<<<TAG
\n<info>info color</info>
<suc>info color</suc>
<error>info color</error>
<danger>info color</danger>
<warning>info color</warning>
TAG
);
