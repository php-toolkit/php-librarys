<?php
/**
 * Created by sublime 3.
 * Auth: Inhere
 * Date: 15-1-14
 * Time: 10:35
 * AbstractFileSystem.php.php
 * Uesd: 主要功能是 hi
 */

namespace inhere\tools\fileSystem;

use inhere\tools\helpers\StrHelper;
//use ulue\core\utils\StaticInvoker;

abstract class AbstractFileSystem //extends StaticInvoker
{
    /**
     * 转换为标准的路径结构
     * @param  [type] $dirName [description]
     * @return mixed|string [type]          [description]
     */
    static public function pathFormat($dirName)
    {
        $dirName = str_ireplace('\\','/', trim($dirName));
        return substr($dirName,-1) == '/' ? $dirName: $dirName.'/';
    }

    /**
     * [checkFileExists 检查文件是否存在 和 判断后缀名是否正确
     * @param  string | array $files 要检查的文件(文件列表)
     * @param  string $ext 是否检查后缀
     * @throws \InvalidArgumentException
     * @throws \NotFoundException
     * @return array|string [type]        [description]
     */
    static public function exists($files, $ext=null)
    {
        $ext    = $ext ? trim($ext,'. ') : false;
        $files  = StrHelper::toArray($files);

        foreach ($files as $file) {
            $file = trim($file);

            if (!file_exists($file)) {
                throw new \NotFoundException("文件 {$file} 不存在！");
            }

            // if ( $ext && strrchr($file,'.') != '.'.$ext ) {
            if ( $ext && preg_match("/\.($ext)$/i",$file) )
            {
                throw new \InvalidArgumentException("{$file} 不是 {$ext} 文件！");
            }
        }

        return $files;
    }

    static public function chmodr($path, $filemode)
    {
        if (!is_dir($path))
            return @chmod($path, $filemode);
        $dh = opendir($path);
        while (($file = readdir($dh)) !== false)
        {
            if ($file != '.' && $file != '..')
            {
                $fullpath = $path.'/'.$file;
                if (is_link($fullpath))
                    return false;
                elseif (!is_dir($fullpath) && !@chmod($fullpath, $filemode))
                    return false;
                elseif (!self::chmodr($fullpath, $filemode))
                    return false;
            }
        }
        closedir($dh);
        if (@chmod($path, $filemode))
            return true;
        else
            return false;
    }

    /**
     * 文件或目录权限检查函数
     *
     * @from web
     * @access public
     * @param  string  $file_path   文件路径
     * @param  bool    $rename_prv  是否在检查修改权限时检查执行rename()函数的权限
     * @return int  返回值的取值范围为{0 <= x <= 15}，每个值表示的含义可由四位二进制数组合推出。
     *                  返回值在二进制计数法中，四位由高到低分别代表
     *                  可执行rename()函数权限 |可对文件追加内容权限 |可写入文件权限|可读取文件权限。
     */
    static public function file_mode_info($file_path)
    {
        /* 如果不存在，则不可读、不可写、不可改 */
        if (!file_exists($file_path)) {
            return false;
        }

        $mark = 0;

        if (strtoupper(substr(PHP_OS, 0, 3)) == 'WIN') {

            /* 测试文件 */
            $test_file = $file_path . '/cf_test.txt';

            /* 如果是目录 */
            if (is_dir($file_path)) {

                /* 检查目录是否可读 */
                $dir = @opendir($file_path);

                //如果目录打开失败，直接返回目录不可修改、不可写、不可读
                if ($dir === false) {
                    return $mark;
                }

                //目录可读 001，目录不可读 000
                if (@readdir($dir) !== false) {
                    $mark ^= 1;
                }

                @closedir($dir);

                /* 检查目录是否可写 */
                $fp = @fopen($test_file, 'wb');

                //如果目录中的文件创建失败，返回不可写。
                if ($fp === false) {
                    return $mark;
                }

                //目录可写可读 011，目录可写不可读 010
                if (@fwrite($fp, 'directory access testing.') !== false) {
                    $mark ^= 2;
                }

                @fclose($fp);
                @unlink($test_file);

                /* 检查目录是否可修改 */
                $fp = @fopen($test_file, 'ab+');

                if ($fp === false) {
                    return $mark;
                }

                if (@fwrite($fp, "modify test.\r\n") !== false) {
                    $mark ^= 4;
                }

                @fclose($fp);

                /* 检查目录下是否有执行rename()函数的权限 */
                if (@rename($test_file, $test_file) !== false) {
                    $mark ^= 8;
                }

                @unlink($test_file);

            /* 如果是文件 */
            } elseif (is_file($file_path)) {
                /* 以读方式打开 */
                $fp = @fopen($file_path, 'rb');
                if ($fp) {
                    $mark ^= 1; //可读 001
                }

                @fclose($fp);

                /* 试着修改文件 */
                $fp = @fopen($file_path, 'ab+');

                if ($fp && @fwrite($fp, '') !== false) {
                    $mark ^= 6; //可修改可写可读 111，不可修改可写可读011...
                }

                @fclose($fp);

                /* 检查目录下是否有执行rename()函数的权限 */
                if (@rename($test_file, $test_file) !== false) {
                    $mark ^= 8;
                }
            }

        } else {

            if (@is_readable($file_path)) {
                $mark ^= 1;
            }

            if (@is_writable($file_path)) {
                $mark ^= 14;
            }
        }

        return $mark;
    }
}