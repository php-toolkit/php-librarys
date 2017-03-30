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

use inhere\library\exceptions\FileNotFoundException;
use inhere\library\exceptions\FileReadException;
use inhere\library\exceptions\IOException;
use inhere\library\helpers\StrHelper;
use inhere\library\exceptions\FileSystemException;

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
    public static function getName($file, $clearExt=false)
    {
        $filename = basename( trim($file) );

        return $clearExt ? strstr($filename,'.', true) : $filename;
    }

    /**
     * 获得文件扩展名、后缀名,带点 .jpg
     * @param $filename
     * @param bool $clearPoint
     * @return string
     */
    public static function getSuffix($filename, $clearPoint=false)
    {
        $suffix = strrchr($filename,'.');

        return (bool)$clearPoint ? trim($suffix,'.') : $suffix;
    }

    /**
     * 获得文件扩展名、后缀名,没有带点 jpg
     * @param $path
     * @param bool $clearPoint
     * @return string
     */
    public static function getExtension($path, $clearPoint=false)
    {
        $ext = pathinfo($path,PATHINFO_EXTENSION);

        return $clearPoint ? $ext : '.' . $ext;
    }

    public static function getInfo($filename, $check=true)
    {
        $check && self::exists($filename);

        return [
            'name'            => basename($filename), //文件名
            'type'            => filetype($filename), //类型
            'size'            => ( filesize($filename)/1000 ).' Kb', //大小
            'is_write'        => is_writable($filename) ? 'true' : 'false', //可写
            'is_read'         => is_readable($filename) ? 'true' : 'false',//可读
            'update_time'     => filectime($filename), //修改时间
            'last_visit_time' => fileatime($filename), //文件的上次访问时间
        ];
    }

    /**
     * @param $filename
     * @return array
     */
    public static function getStat($filename)
    {
         return stat($filename);
    }

    /**
     * [save description]
     * @param  mixed $data string array(仅一维数组) 或者是 stream  资源
     * @param  string $filename [description], LOCK_EX
     * @return bool
     */
    public static function save($filename, $data )
    {
        return file_put_contents($filename, $data)!==false;
    }

    /**
     * @param $content
     * @param $path
     */
    public static function write($content, $path)
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
            throw new IOException('The file "'.$path.'" could not be opened for writing. Check if PHP has enough permissions.');
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
    public static function writeToFile($handler, $content, $path = '')
    {
        if (($result = @fwrite($handler, $content)) === false || ($result < strlen($content))) {
            throw new IOException('The file "'.$path.'" could not be written to. Check your disk space and file permissions.');
        }
    }

    /**
     * ********************** 创建多级目录和多个文件 **********************
     * 结合上两个函数
     * @param $fileData - 数组：要创建的多个文件名组成,含文件的完整路径
     * @param $append   - 是否以追加的方式写入数据 默认false
     * @param $mode=0777 - 权限，默认0775
     *  eg: $fileData = array(
     *      'file_name'   => 'content',
     *      'case.html'   => 'content' ,
     *  );
     **/
    public static function createAndWrite(array $fileData = [],$append=false,$mode=0664)
    {
        foreach($fileData as $file=>$content) {
            //检查目录是否存在，不存在就先创建（多级）目录
            Directory::create(dirname($file),$mode);

            //$fileName = basename($file); //文件名

            //检查文件是否存在
            if ( !is_file($file) ) {
                file_put_contents($file,$content,LOCK_EX);
                @chmod($file,$mode);
            } elseif ($append) {
                file_put_contents($file,$content,FILE_APPEND | LOCK_EX);
                @chmod($file,$mode);
            }
        }
    }

    /**
     * @param string $file a file path or url path
     * @param bool|false $useIncludePath
     * @param null $streamContext
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

        if (in_array(ini_get('allow_url_fopen'), ['On', 'on', '1']) || !preg_match('/^https?:\/\//', $file)) {
            if ( !file_exists($file) ) {
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
     */
    public static function move($file, $target)
    {
        Directory::mkdir(dirname($target));

        if ( static::copy($file, $target) ) {
            unlink($file);
        }
    }

    /**
     * @param $filename
     * @return bool
     */
    public static function delete($filename)
    {
        return self::exists($filename) && unlink($filename);
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

    public static function combine($inFile, $outFile)
    {
        self::exists($inFile);

        $data = '';
        if (is_array($inFile)) {
            foreach ($inFile as $value) {
                if (is_file($value)) {
                    $data .= trim( file_get_contents($value) );
                } else {
                    throw new FileNotFoundException('文件'.$value.'不存在！！');
                }
            }
        }

        /*if (is_string($inFile) && is_file($value)) {
            $data .= trim( file_get_contents($inFile) );
        } else {
            Trigger::error('文件'.$value.'不存在！！');
        }*/

        $preg_arr = array(
                '/\/\*.*?\*\/\s*/is'        // 去掉所有多行注释/* .... */
                ,'/\/\/.*?[\r\n]/is'        // 去掉所有单行注释//....
                ,'/(?!\w)\s*?(?!\w)/is'     // 去掉空白行
                );

        $data     = preg_replace($preg_arr,'',$data);
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
     * @param  boolean $deleteSpace [description]
     * @return void [type]               [description]
     */
    public static function margePhp($fileArr,$outFile,$deleteSpace=true)
    {
        $savePath = dirname($outFile);

        if ( !is_dir($savePath) ) {
            Directory::create($savePath);
        }

        if ( !is_array($fileArr) ) {
            $fileArr =array($fileArr);
        }

        $data = '';

        foreach( $fileArr as $v )
        {
            #删除注释、空白
            if ( $deleteSpace )
            {
                $data .= StrHelper::deleteStripSpace($v);
            }#不删除注释、空白
            else {
                $o_data = file_get_contents($v);
                $o_data = substr($o_data,0,5) === "<?php" ? substr($o_data,5) : $o_data ;
                $data  .= substr($o_data,-2) === "?>" ? substr($o_data,0,-2) : $o_data ;
            }
        }

        $data = '<?php ' .$data. '?>';
        file_put_contents($outFile, $data);
    }
}
