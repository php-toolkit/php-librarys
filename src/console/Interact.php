<?php
/**
 * Created by PhpStorm.
 * User: Inhere
 * Date: 15-4-1
 * Time: 上午10:08
 * Used: CliInteract 命令行交互
 * file: CliInteract.php
 */

namespace inhere\librarys\console;
use inhere\librarys\exceptions\InvalidArgumentException;

/**
 * Class Interact
 * @package inhere\librarys\console
 */
class Interact
{
    const STAR_LINE = "*************************************%s*************************************\n";

    const NL = "\n";// new line
    const TAB    = '    ';
    const NL_TAB = "\n    ";// new line + tab

/////////////////////////////////////////////////////////////////
/// Interactive
/////////////////////////////////////////////////////////////////

    /**
     * 多选一
     * @param  string $question 说明
     * @param  mixed $option  选项数据
     * @param  bool $allowExit  有退出选项 默认 true
     * @return string
     */
    public static function choice($question, $option, $allowExit=true)
    {
        self::write("  <comment>$question</comment>");

        $option    = is_array($option) ? $option : explode(',', $option);
        // no set key
        $isNumeric = isset($option[0]);
        $keys = [];
        $optStr = '';

        foreach ($option as $key => $value) {
            $keys[] = $isNumeric ? ++$key : $key;

            $optStr .= "\n    $key) $value";
        }

        if ($allowExit) {
            $keys[] = 'q';
            $optStr .= "\n    q) quit";
        }

        self::write($optStr . "\n  You choice : ");

        $r = self::readRow();

        if ( !in_array($r, $keys) ) {
            self::write("Warning! option <comment>$r<comment>) don't exists! please entry again! :");

            $r = self::readRow();
        }

        if ($r === 'q' || !in_array($r, $keys) ) {
            exit("\n\n Quit,ByeBye.\n");
        }

        return $r;
    }

    /**
     * 确认, 发出信息要求确认；返回 true | false
     * @param  string $question 发出的信息
     * @param bool $yes
     * @return bool
     */
    public static function confirm($question, $yes = true)
    {
        $question = ucfirst(trim($question));
        $defaultText = $yes ? 'yes' : 'no';

        $message = "<comment>$question</comment>\n    Please confirm (yes|no) [default:<info>$defaultText</info>]: ";
        self::write($message, false);

        $answer = self::readRow();

        return $answer ? !strncasecmp($answer, 'y', 1) : (bool)$yes;
    }

    /**
     * 询问，提出问题；返回 输入的结果
     * @param string      $question   问题
     * @param null|string $default    默认值
     * @param \Closure    $validator  The validate callback. It must return bool.
     * @example This is an example
     *
     * ```
     *  $answer = Interact::ask('Please input your name?', null, function ($answer) {
     *      if ( !preg_match('/\w+/', $answer) ) {
     *          Interact::error('The name must match "/\w+/"');
     *
     *          return false;
     *      }
     *
     *      return true;
     *   });
     * ```
     *
     * @return string
     */
    public static function ask($question, $default = null, \Closure $validator = null)
    {
        return self::question($question, $default, $validator);
    }
    public static function question($question, $default = null, \Closure $validator = null)
    {
        if ( !$question = trim($question) ) {
            self::error('Please provide a question text!', 1);
        }

        $defaultText = null !== $default ? "(default: <info>$default</info>)" : '';
        $answer = self::read( "<comment>" . ucfirst($question) . "</comment>$defaultText " );

        if ( '' === $answer ) {
            if ( null === $default) {
                self::error('A value is required.', false);

                return static::question($question, $default, $validator);
            }

            return $default;
        }

        if ( $validator ) {
            return $validator($answer) ? $answer : static::question($question, $default, $validator);
        }

        return $answer;
    }

    /**
     * 有次数限制的询问,提出问题
     *   若输入了值且验证成功则返回 输入的结果
     *   否则，会连续询问 $allowed 次， 若仍然错误，退出
     * @param string      $question 问题
     * @param null|string $default    默认值
     * @param callable    $validator (默认验证输入是否为空)自定义回调验证输入是否符合要求; 验证成功返回true 否则 可返回错误消息
     * @example This is an example
     *
     * ```
     * // no default value
     * Interact::loopAsk('please entry you age?', null, function($age)
     * {
     *     if ($age<1 || $age>100) {
     *         Interact::error('Allow the input range is 1-100');
     *         return false;
     *     }
     *
     *     return true;
     * } );
     *
     * // has default value
     * Interact::loopAsk('please entry you age?', 89, function($age)
     * {
     *     if ($age<1 || $age>100) {
     *         Interact::error('Allow the input range is 1-100');
     *         return false;
     *     }
     *
     *     return true;
     * } );
     * ```
     *
     * @param int $times Allow input times
     * @return string
     */
    public static function loopAsk($question, $default = null, \Closure $validator = null, $times=3)
    {
        if ( !$question = trim($question) ) {
            self::error('Please provide a question text!', 1);
        }

        $result = false;
        $answer = '';
        $question = ucfirst($question);
        $back = $times = ((int)$times > 6 || $times < 1) ? 3 : (int)$times;
        $defaultText = null !== $default ? "(default: <info>$default</info>)" : '';

        while ($times--) {
            if ( $defaultText ) {
                $answer = self::read("<comment>{$question}</comment>{$defaultText} ");

                if ( '' === $answer ) {
                    $answer = $default;
                    $result = true;

                    break;
                }
            } else {
                $num = $times + 1;
                $answer = self::read("<comment>{$question}</comment>\n(You have a [<bold>$num</bold>] chance to enter!) ");
            }

            // If setting verify callback
            if ($validator && ($result = $validator($answer)) === true ) {
                break;
            }

            // no setting verify callback
            if ( !$validator && $answer !== '') {
                $result = true;

                break;
            }
        }

        if ( !$result ) {

            if ( null !== $default ) {
                return $default;
            }

            self::write("\n  You've entered incorrectly <danger>$back</danger> times in a row. exit!\n", true, 1);
        }

        return $answer;
    }

/////////////////////////////////////////////////////////////////
/// Output Message
/////////////////////////////////////////////////////////////////

    /**
     * @param string $msg   The title message
     * @param int    $width The title section width
     */
    public static function title($msg, $width = 50)
    {
        self::section($msg, $width, '=');
    }

    /**
     * @param string $msg   The section message
     * @param int    $width The section width
     */
    public static function section($msg, $width = 50, $char = '-')
    {
        $msg = ucwords(trim($msg));
        $msgLength = mb_strlen($msg, 'UTF-8');
        $width = is_int($width) && $width > 10 ? $width : 50;

        $indentSpace = str_pad(' ', ceil($width/2) - ceil($msgLength/2), ' ');
        $charStr = str_pad($char, $width, $char);

        self::write("  {$indentSpace}{$msg}   \n  {$charStr}\n");
    }

    /**
     * 多行信息展示
     * @param  mixed $data
     * @param  string $title
     * @return void
     */
    public static function panel(array $data, $title='Info panel')
    {
        $data = is_array($data) ? array_filter($data) : [trim($data)];
        $title = ucwords(trim($title));

        self::write("\n  " . sprintf(self::STAR_LINE,"<bold>$title</bold>"), false);

        foreach ($data as $label => $value) {
            $line = '  * ';

            if (!is_numeric($label)) {
                $line .= "$label: ";
            }

            self::write("$line  <info>$value</info>");
        }

        $star = $title ? substr(self::STAR_LINE, 0, strlen($title)): '';

        self::write('  ' . sprintf(self::STAR_LINE, $star ));
    }

    /**
     * 表格数据信息展示
     * @param  array $data
     * @param  string $title
     * @return void
     */
    public static function table(array $data, $title='Info List', $showBorder = true)
    {
        $rowIndex = 0;
        $head = $table = [];
        $info = [
            'rowCount'  => count($data),
            'columnCount' => 0,     // how many column in the table.
            'columnMaxWidth' => [], // table column max width
            'tableWidth' => 0,      // table width. equals to all max column width's sum.
        ];

        // parse table data
        foreach ($data as $row) {
            // collection all field name
            if ($rowIndex === 0) {
                $head = array_keys($row);
                $info['columnCount'] = count($row);

                foreach ($head as $index => $name) {
                    $info['columnMaxWidth'][$index] = mb_strlen($name, 'UTF-8');
                }
            }

            $colIndex = 0;

            foreach ($row as $value) {
                // collection column max width
                if ( isset($info['columnMaxWidth'][$colIndex]) ) {
                    $colWidth = mb_strlen($value, 'UTF-8');

                    // If current column width gt old column width. override old width.
                    if ($colWidth > $info['columnMaxWidth'][$colIndex]) {
                        $info['columnMaxWidth'][$colIndex] = $colWidth;
                    }
                } else {
                    $info['columnMaxWidth'][$colIndex] = mb_strlen($value, 'UTF-8');
                }

                $colIndex++;
            }

            $rowIndex++;
        }

        $tableWidth = $info['tableWidth'] = array_sum($info['columnMaxWidth']);
        $columnCount = $info['columnCount'];

        // output title
        if ($title) {
            $title = ucwords(trim($title));
            $titleLength = mb_strlen($title, 'UTF-8');
            $indentSpace = str_pad(' ', ceil($tableWidth/2) - ceil($titleLength/2) + ($columnCount*2), ' ');
            self::write("  {$indentSpace}<bold>{$title}</bold>");
        }

        // output table top border
        if ($showBorder) {
            $border = str_pad('-', $tableWidth + ($columnCount*3) + 2, '-');
            self::write('  ' . $border);
        }

        // output table head
        $headStr = '  | ';
        foreach ($head as $index => $name) {
            $colMaxWidth = $info['columnMaxWidth'][$index];
            $name = str_pad($name, $colMaxWidth, ' ');
            $headStr .= " {$name} |";
        }

        self::write($headStr);

        if ($showBorder) {
            self::write('  ' . $border);
        }

        $rowIndex = 0;

        // output table info
        foreach ($data as $row) {
            $colIndex = 0;
            $rowStr = '  | ';

            foreach ($row as $value) {
                $colMaxWidth = $info['columnMaxWidth'][$colIndex];
                $value = str_pad($value, $colMaxWidth, ' ');
                $rowStr .= " <info>{$value}</info> |";
                $colIndex++;
            }

            self::write("{$rowStr}");

            $rowIndex++;
        }

        // output table bottom border
        if ($showBorder) {
            self::write('  ' . $border);
        }

        echo "\n";
        unset($data);
    }

    /**
     * @param mixed         $messages
     * @param string|null   $type
     * @param string        $style
     * @param int|boolean   $quit  If is int, settin it is exit code.
     */
    public static function block($messages, $type = null, $style='default', $quit = false)
    {
        $messages = is_array($messages) ? array_values($messages) : array($messages);

        // add type
        if (null !== $type) {
            $messages[0] = sprintf('[%s] %s', strtoupper($type), $messages[0]);
        }

        $text = implode(PHP_EOL, $messages);
        $color = static::getColor();

        if (is_string($style) && $color->hasStyle($style)) {
            $text = "<{$style}>{$text}</{$style}>";
        }

        // $this->write($text);
        self::write($text, true, $quit);
    }
    public static function primary($messages, $quit = false)
    {
        static::block($messages, 'IMPORTANT', 'primary', $quit);
    }
    public static function success($messages, $quit = false)
    {
        static::block($messages, 'SUCCESS', 'success', $quit);
    }
    public static function info($messages, $quit = false)
    {
        static::block($messages, 'INFO', 'info', $quit);
    }
    public static function warning($messages, $quit = false)
    {
        static::block($messages, 'WARNING', 'warning', $quit);
    }
    public static function danger($messages, $quit = false)
    {
        static::block($messages, 'DANGER', 'danger', $quit);
    }
    public static function error($messages, $quit = false)
    {
        static::block($messages, 'ERROR', 'error', $quit);
    }
    public static function comment($messages, $quit = false)
    {
        static::block($messages, 'COMMENT', 'comment', $quit);
    }

/////////////////////////////////////////////////////////////////
/// Helper Method
/////////////////////////////////////////////////////////////////


    /**
     * @var Color
     */
    private static $color;

    public static function getColor()
    {
        if (!static::$color) {
            static::$color = new Color();
        }

        return static::$color;
    }

    /**
     * 读取输入信息
     * @param  string $text  若不为空，则先输出文本
     * @param  bool   $nl    true 会添加换行符 false 原样输出，不添加换行符
     * @return string
     */
    public static function readRow($message = null, $nl = false)
    {
        return self::read($message, $nl);
    }
    public static function read($message = null, $nl = false)
    {
        self::write($message, $nl);

        return trim(fgets(STDIN));
    }

    /**
     * 输出
     * @param  string      $text
     * @param  bool        $nl    true 会添加换行符 false 原样输出，不添加换行符
     * @param  int|boolean $quit  If is int, settin it is exit code.
     */
    public static function write($text, $nl = true, $quit = false)
    {
        $text = static::getColor()->format($text);

        fwrite(STDOUT, $text . ($nl ? "\n" : null));

        if ( is_int($quit) || true === $quit) {
            $code = true === $quit ? 0 : $quit;
            exit($code);
        }
    }


} // end class
