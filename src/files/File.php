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

use inhere\exceptions\FileNotFoundException;
use inhere\exceptions\FileReadException;
use inhere\exceptions\IOException;
use inhere\library\helpers\StringHelper;
use inhere\exceptions\FileSystemException;

/**
 * Class File
 * @package inhere\library\files
 */
class File extends FileSystem
{
    /**
     * 获得文件名称
     * @param string $file
     * @param bool $clearExt 是否去掉文件名中的后缀，仅保留名字
     * @return string
     */
    public static function getName($file, $clearExt = false): string
    {
        $filename = basename(trim($file));

        return $clearExt ? strstr($filename, '.', true) : $filename;
    }

    /**
     * 获得文件扩展名、后缀名
     * @param $filename
     * @param bool $clearPoint 是否带点
     * @return string
     */
    public static function getSuffix($filename, $clearPoint = false): string
    {
        $suffix = strrchr($filename, '.');

        return (bool)$clearPoint ? trim($suffix, '.') : $suffix;
    }

    /**
     * 获得文件扩展名、后缀名
     * @param $path
     * @param bool $clearPoint 是否带点
     * @return string
     */
    public static function getExtension($path, $clearPoint = false): string
    {
        $ext = pathinfo($path, PATHINFO_EXTENSION);

        return $clearPoint ? $ext : '.' . $ext;
    }

    /**
     * @param $filename
     * @param bool $check
     * @return array
     * @throws \inhere\exceptions\NotFoundException
     * @throws \InvalidArgumentException
     */
    public static function info($filename, $check = true): array
    {
        $check && self::check($filename);

        return [
            'name' => basename($filename), //文件名
            'type' => filetype($filename), //类型
            'size' => (filesize($filename) / 1000) . ' Kb', //大小
            'is_write' => is_writable($filename) ? 'true' : 'false', //可写
            'is_read' => is_readable($filename) ? 'true' : 'false',//可读
            'update_time' => filectime($filename), //修改时间
            'last_visit_time' => fileatime($filename), //文件的上次访问时间
        ];
    }

    /**
     * @param $filename
     * @return array
     */
    public static function getStat($filename): array
    {
        return stat($filename);
    }

    /**
     * [save description]
     * @param  mixed $data string array(仅一维数组) 或者是 stream  资源
     * @param  string $filename [description], LOCK_EX
     * @return bool
     */
    public static function save($filename, $data): bool
    {
        return file_put_contents($filename, $data) !== false;
    }

    /**
     * @param $content
     * @param $path
     * @throws \inhere\exceptions\IOException
     */
    public static function write($content, $path): void
    {
        $handler = static::openHandler($path);

        static::writeToFile($handler, $content);

        @fclose($handler);
    }

    /**
     * @param $path
     * @return resource
     * @throws IOException
     */
    public static function openHandler($path)
    {
        if (($handler = @fopen($path, 'wb')) === false) {
            throw new IOException('The file "' . $path . '" could not be opened for writing. Check if PHP has enough permissions.');
        }

        return $handler;
    }

    /**
     * Attempts to write $content to the file specified by $handler. $path is used for printing exceptions.
     *
     * @param resource $handler The resource to write to.
     * @param string $content The content to write.
     * @param string $path The path to the file (for exception printing only).
     * @throws IOException
     */
    public static function writeToFile($handler, $content, $path = ''): void
    {
        if (($result = @fwrite($handler, $content)) === false || ($result < strlen($content))) {
            throw new IOException('The file "' . $path . '" could not be written to. Check your disk space and file permissions.');
        }
    }

    /**
     * ********************** 创建多级目录和多个文件 **********************
     * 结合上两个函数
     * @param $fileData - 数组：要创建的多个文件名组成,含文件的完整路径
     * @param $append - 是否以追加的方式写入数据 默认false
     * @param $mode =0777 - 权限，默认0775
     *  eg: $fileData = array(
     *      'file_name'   => 'content',
     *      'case.html'   => 'content' ,
     *  );
     **/
    public static function createAndWrite(array $fileData = [], $append = false, $mode = 0664): void
    {
        foreach ($fileData as $file => $content) {
            //检查目录是否存在，不存在就先创建（多级）目录
            Directory::create(dirname($file), $mode);

            //$fileName = basename($file); //文件名

            //检查文件是否存在
            if (!is_file($file)) {
                file_put_contents($file, $content, LOCK_EX);
                @chmod($file, $mode);
            } elseif ($append) {
                file_put_contents($file, $content, FILE_APPEND | LOCK_EX);
                @chmod($file, $mode);
            }
        }
    }

    /**
     * @param string $file a file path or url path
     * @param bool|false $useIncludePath
     * @param null|resource $streamContext
     * @param int $curlTimeout
     * @return bool|mixed|string
     * @throws FileNotFoundException
     * @throws FileReadException
     */
    public static function getContents($file, $useIncludePath = false, $streamContext = null, $curlTimeout = 5)
    {
        if (null === $streamContext && preg_match('/^https?:\/\//', $file)) {
            $streamContext = @stream_context_create(array('http' => array('timeout' => $curlTimeout)));
        }

        if (
            in_array(ini_get('allow_url_fopen'), ['On', 'on', '1'], true) ||
            !preg_match('/^https?:\/\//', $file)
        ) {
            if (!file_exists($file)) {
                throw new FileNotFoundException("File [{$file}] don't exists!");
            }

            if (!is_readable($file)) {
                throw new FileReadException("File [{$file}] is not readable！");
            }

            return @file_get_contents($file, $useIncludePath, $streamContext);
        }

        // fetch remote content by url
        if (function_exists('curl_init')) {
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($curl, CURLOPT_URL, $file);
            curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 5);
            curl_setopt($curl, CURLOPT_TIMEOUT, $curlTimeout);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);

            if (null !== $streamContext) {
                $opts = stream_context_get_options($streamContext);

                if (isset($opts['http']['method']) && strtolower($opts['http']['method']) === 'post') {
                    curl_setopt($curl, CURLOPT_POST, true);

                    if (isset($opts['http']['content'])) {
                        parse_str($opts['http']['content'], $post_data);
                        curl_setopt($curl, CURLOPT_POSTFIELDS, $post_data);
                    }
                }
            }

            $content = curl_exec($curl);
            curl_close($curl);

            return $content;
        }

        return false;
    }

    /**
     * @param $file
     * @param $target
     * @throws \inhere\exceptions\IOException
     * @throws \inhere\exceptions\FileSystemException
     */
    public static function move($file, $target): void
    {
        Directory::mkdir(dirname($target));

        if (static::copy($file, $target)) {
            unlink($file);
        }
    }

    /**
     * @param $filename
     * @return bool
     * @throws \inhere\exceptions\NotFoundException
     * @throws \InvalidArgumentException
     */
    public static function delete($filename): bool
    {
        return self::check($filename) && unlink($filename);
    }

    /**
     * @param $source
     * @param $destination
     * @param null $streamContext
     * @return bool|int
     * @throws FileSystemException
     */
    public static function copy($source, $destination, $streamContext = null)
    {
        if (null === $streamContext && !preg_match('/^https?:\/\//', $source)) {
            if (!is_file($source)) {
                throw new FileSystemException("Source file don't exists. File: $source");
            }

            return copy($source, $destination);
        }

        return @file_put_contents($destination, self::getContents($source, false, $streamContext));
    }

    /**
     * @param $inFile
     * @param $outFile
     * @return mixed
     * @throws \inhere\exceptions\NotFoundException
     * @throws \InvalidArgumentException
     * @throws FileNotFoundException
     */
    public static function combine($inFile, $outFile)
    {
        self::check($inFile);

        $data = '';
        if (is_array($inFile)) {
            foreach ($inFile as $value) {
                if (is_file($value)) {
                    $data .= trim(file_get_contents($value));
                } else {
                    throw new FileNotFoundException('File: ' . $value . ' not exists!');
                }
            }
        }

        /*if (is_string($inFile) && is_file($value)) {
            $data .= trim( file_get_contents($inFile) );
        } else {
            Trigger::error('文件'.$value.'不存在！！');
        }*/

        $preg_arr = [
            '/\/\*.*?\*\/\s*/is',        // 去掉所有多行注释/* .... */
            '/\/\/.*?[\r\n]/is',        // 去掉所有单行注释//....
            '/(?!\w)\s*?(?!\w)/is'     // 去掉空白行
        ];

        $data = preg_replace($preg_arr, '', $data);
//        $outFile  = $outDir . Data::getRandStr(8) . '.' . $fileType;

        $fileData = array(
            $outFile => $data
        );

        self::createAndWrite($fileData);

        return $outFile;
    }

    /**
     * 合并编译多个文件
     * @param $fileArr
     * @param $outFile
     * @param  boolean $deleteSpace
     */
    public static function margePhp($fileArr, $outFile, $deleteSpace = true): void
    {
        $savePath = dirname($outFile);

        if (!is_dir($savePath)) {
            Directory::create($savePath);
        }

        if (!is_array($fileArr)) {
            $fileArr = array($fileArr);
        }

        $data = '';

        foreach ($fileArr as $v) {
            #删除注释、空白
            if ($deleteSpace) {
                $data .= StringHelper::deleteStripSpace($v);
            }#不删除注释、空白
            else {
                $o_data = file_get_contents($v);
                $o_data = substr($o_data, 0, 5) === '<?php' ? substr($o_data, 5) : $o_data;
                $data .= substr($o_data, -2) === '?>' ? substr($o_data, 0, -2) : $o_data;
            }
        }

        $data = '<?php ' . $data . '?>';
        file_put_contents($outFile, $data);
    }
}
