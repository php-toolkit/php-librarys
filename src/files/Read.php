<?php
/**
 * Created by sublime 3.
 * Auth: Inhere
 * Date: 15-1-14
 * Name: File.php
 * Time: 10:35
 * Uesd: 主要功能是 文件相关信息获取
 */

namespace inhere\library\files;

use app\extensions\exceptions\FileSystemException;

/**
 * Class Read
 * @package inhere\library\files
 */
abstract class Read extends File
{
    /**
     * @param string $ini 要解析的 ini 文件名 或 字符串内容。
     * @param bool $process_sections 如果将 process_sections 参数设为 TRUE ，将得到一个多维数组，
     *       包括了配置文件中每一节的名称和设置。process_sections 的默认值是 FALSE 。
     * @param int $scanner_mode Can either be INI_SCANNER_NORMAL  (default) or INI_SCANNER_RAW .
     *           If INI_SCANNER_RAW  is supplied, then option values will not be parsed.
     * @example simple.ini
     * ; This is a sample configuration file
     * ; Comments start with ';', as in php.ini[first_section]
     * one = 1
     * five = 5
     * animal = BIRD[second_section]
     * path = "/usr/local/bin"
     * URL = "http://www.example.com/~username"[third_section]
     * phpversion[] = "5.0"
     * phpversion[] = "5.1"
     * phpversion[] = "5.2"
     * phpversion[] = "5.3"
     *
     * 全大写的 BIRD -- 如果已定义了常量BIRD，则会被解析为对应的值
     * phpversion[] -- 会解析成数组
     * 如果 $process_sections = true, 则会以 [first_section] [second_section].. 标记 分割放置到以对应标记名为键名的数组内
     * @return array|bool
     */
    public static function ini($ini, $process_sections = false, $scanner_mode = INI_SCANNER_NORMAL)
    {
        $ini = trim($ini);

        if (is_file($ini) && self::getSuffix($ini, true) === 'ini') {
            return parse_ini_file($ini, (bool)$process_sections, (int)$scanner_mode);
        }

        if ($ini && is_string($ini)) {
            return parse_ini_string($ini, (bool)$process_sections, (int)$scanner_mode);
        }

        return false;
    }

    /**
     * @param $file
     * @param bool|true $toArray
     * @return mixed
     */
    public static function json($file, $toArray = true)
    {
        $content = self::getContents($file);

        $content = preg_replace('/\/\/.*?[\r\n]/is', '', trim($content));

        return (bool)$toArray ? json_decode($content, true) : $content;
    }

    /**
     * @param $file
     * @param bool|true $filter
     * @return array|string
     */
    public static function allLine($file, $filter = true)
    {
        $contents = self::getContents($file);

        if (!$contents) {
            return [];
        }

        $array = implode(PHP_EOL, $contents);

        return (bool)$filter ? array_filter($array) : $array;
    }

    /**
     * [getLines 获取文件一定范围内的内容]
     * @param  string $fileName 含完整路径的文件
     * @param  integer $startLine [开始行数 默认第1行]
     * @param  integer $endLine [结束行数 默认第50行]
     * @param  string $method [打开文件方式]
     * @throws FileSystemException
     * @return array  返回内容
     */
    public static function lines($fileName, $startLine = 1, $endLine = 50, $method = 'rb')
    {
        $content = array();

        // 判断php版本（因为要用到SplFileObject，PHP>=5.1.0）
        if (class_exists('SplFileObject', false)) {
            $count = $endLine - $startLine;

            try {
                $obj_file = new \SplFileObject($fileName, $method);
                $obj_file->seek($startLine - 1); // 转到第N行, seek方法参数从0开始计数

                for ($i = 0; $i <= $count; ++$i) {
                    $content[] = $obj_file->current(); // current()获取当前行内容
                    $obj_file->next(); // 下一行
                }
            } catch (\Exception $e) {
                throw new FileSystemException("读取文件--{$fileName} 发生错误！");
            }

        } else { //PHP<5.1
            $openFile = fopen($fileName, $method);

            if (!$openFile) {
                throw new FileSystemException('error:can not read file--' . $fileName);
            }

            # 移动指针 跳过前$startLine行
            for ($i = 1; $i < $startLine; ++$i) {
                fgets($openFile);
            }

            # 读取文件行内容
            for (; $i <= $endLine; ++$i) {
                $content[] = fgets($openFile);
            }

            fclose($openFile);
        }

        return $content;
    }

    /**
     * symmetry  得到当前行对称上下几($lineNum)行的内容
     * @param string $fileName 含完整路径的文件
     * @param  integer $current [当前行数]
     * @param  integer $lineNum [获取行数] = $lineNum*2+1
     * @throws FileSystemException
     * @return array
     */
    public static function symmetry($fileName, $current = 1, $lineNum = 3)
    {
        $startLine = $current - $lineNum;
        $endLine = $current + $lineNum;

        if ((int)$current < ($lineNum + 1)) {
            $startLine = 1;
            $endLine = 9;
        }

        return self::lines($fileName, $startLine, $endLine);
    }

    /**
     * 得到基准行数上5行下3行的内容， lines up and down
     * @param $fileName
     * @param string $current 基准行数
     * @return array
     */
    public static function getLines5u3d($fileName, $current = '1')
    {
        $startLine = 1;
        $endLine = 9;

        if ((int)$current < 6) {
            $startLine = 1;
            $endLine = 9;
        }

        return self::lines($fileName, $startLine, $endLine);
    }
}
