<?php
/**
 * Created by PhpStorm.
 * User: Inhere
 * Date: 15-4-1
 * Time: 上午10:08
 * Used: CliInteract 命令行交互
 * file: CliInteract.php
 */

namespace ulue\cli;

// fcgi doesn't have STDIN and STDOUT defined by default
// defined('STDIN') or define('STDIN', fopen('php://stdin', 'r'));
// defined('STDOUT') or define('STDOUT', fopen('php://stdout', 'w'));

/**
 *
 */
class Interact
{

    const STAR_LINE = "*************************************%s*************************************\n";

    const TAB    = '    ';
    const NL_TAB = "\n    ";// new line + tab

//////////////////////////////////////// Interactive ////////////////////////////////////////

    /**
     * 多行信息展示
     * @param  mixed $data
     * @param  string $title
     * @return void
     */
    static public function panel($data, $title='Info panel')
    {
        $data = is_array($data) ? array_filter($data) : [trim($data)];
        $tab = "    ";

        echo PHP_EOL . self::TAB . sprintf(self::STAR_LINE,$title) ;

        foreach ($data as $label => $value) {
            $line = self::TAB . '* ';
            if (!is_numeric($label)) {
                $line .= "$label: ";
            }

            echo ($line . $value) . PHP_EOL;
        }

        $star = $title ? substr(self::STAR_LINE, 0, strlen($title)): '';

        echo self::TAB . sprintf(self::STAR_LINE, $star );
    }

    /**
     * 多选一
     * @param  string $question 说明
     * @param  mixed $option  选项数据
     * @param  bool $allowExit  有退出选项 默认 true
     * @return string
     */
    static public function choice($question, $option, $allowExit=true)
    {
        echo self::NL_TAB . $question;

        $option    = is_array($option) ? $option : explode(',', $option);
        $isNumeric = isset($option[0]);
        $keys = [];

        foreach ($option as $key => $value) {

            $isNumeric && $key++;
            $keys[] = $key;

            echo self::NL_TAB . " $key) $value";
        }

        if ($allowExit) {
            $keys[] = 'q';

            echo self::NL_TAB . " q) quit";
        }

        echo self::NL_TAB . "You choice : ";

        $r = self::readRow();

        if ( !in_array($r, $keys) ) {
            echo self::TAB . "warning! option $r) don't exists! please entry again! :";

            $r = self::readRow();
        }

        if ($r == 'q' || !in_array($r, $keys) ) {
            exit("\n\n Quit,ByeBye.\n");
        }

        return $r;
    }

    /**
     * 确认, 发出信息要求确认；返回 true | false
     * @param  string $question 发出的信息
     * @return bool
     */
    static public function confirm($question)
    {
        $question = ucfirst(trim($question));

        echo "\n    $question  \n    Please confirm [y|n] : ";

        $answer = self::readRow();

        return !strncasecmp($answer, 'y', 1);
    }

    /**
     * 询问，提出问题；返回 输入的结果
     * @param  string $question 问题
     * @return string
     */
    static public function ask($question)
    {
        if ($question) {
            $question = ucfirst(trim($question));

            echo "\n    $question ";
        }

        return self::readRow();
    }

    /**
     * 持续询问，提出问题；
     * 若输入了值且验证成功则返回 输入的结果
     * 否则，会连续询问 $allowed 次， 若任然错误，退出
     * @param  string $question 问题
     * @param callable $callbackVerify (默认验证输入是否为空)自定义回调验证输入是否符合要求; 验证成功返回true 否则 可返回错误消息
     * e.g.
     * CliInteract::loopAsk('please entry you age?', function($age)
     * {
     *     if ($age<1 || $age>100) {
     *         return 'Allow the input range is 1-100';
     *     }
     *
     *     return true;
     * } );
     *
     * @param int $allowed 允许错误次数
     * @return string
     */
    static public function loopAsk($question, callable $callbackVerify = null, $allowed=3)
    {
        $question = ucfirst(trim($question));
        $allowed = ((int)$allowed > 4 || $allowed < 1) ? 3 : $allowed;
        $loop  = true;
        $key  = 1;

        while ($loop) {
            echo "\n    $question ";
            $answer = self::readRow();

            if ($callbackVerify && is_callable($callbackVerify)) {
                $msg = call_user_func($callbackVerify, $answer);

                if ($msg === true) {
                    break;
                }

                echo self::TAB.self::space(2).($msg ?: 'Verify failure!!');
            } else if ( $answer !== '') {
                break;
            }

            if ($key == $allowed) {
                exit(self::NL_TAB."You've entered incorrectly $allowed times in a row !!\n");
            }

            $key++;
        }

        /** @var string $answer */
        return $answer;
    }

    /**
     * 读取输入信息
     * @return string
     */
    static public function readRow()
    {
        return trim(fgets(STDIN));
    }

    /**
     * 原样输出，不添加换行符
     * @param  string  $text
     * @param  boolean $exit
     */
    static public function rawOut($text, $exit=false)
    {
        self::out($text, false, $exit);
    }

    /**
     * 输出，会在前添加换行符并自动缩进
     * @param  string  $text
     * @param  boolean $exit
     */
    static public function out($text, $newLine=true, $exit=false)
    {
        echo  ($newLine ? self::NL_TAB : null) . $text;

        $exit && exit();
    }

    public static function space($number=2)
    {
        for ($i=0; $i<$number; $i++) {
            echo ' ';
        }
    }

} // end class