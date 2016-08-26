<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 16/8/25
 * Time: 下午9:53
 */

namespace inhere\librarys\asset;

use inhere\librarys\exceptions\FileSystemException;
use inhere\librarys\exceptions\InvalidArgumentException;
use inhere\librarys\files\Directory;
use inhere\librarys\files\File;
use inhere\librarys\helpers\ObjectHelper;
use inhere\librarys\StdBase;

/**
 * 资源发布 -- 将资源发布到可访问目录(e.g. from vendor to web dir)
 * Class AssetPublisher
 * @package inhere\librarys\asset
 */
class AssetPublisher extends StdBase
{
    /**
     * asset source path
     * @var string
     */
    protected $sourcePath  = '';

    /**
     * will publish path
     * @var string
     */
    protected $publishPath = '';

    /**
     * 包含的可发布的 文件 文件扩展匹配 目录
     * 比 {@see $exlude} 优先级更高
     * @var array
     */
    protected $include = [
        'file' => ['README.md'],
        'ext' => [
            'js','css',
            'ttf','svg', 'eot', 'woff', 'woff2',
            'png', 'jpg', 'jpeg', 'gif', 'ico',
        ],
        'dir' => [], // ['dist'],
    ];

    /**
     * 排除发布的 文件 文件扩展匹配 目录
     * @var array
     */
    protected $exclude = [
        'file' => [ '.gitignore', 'LICENSE', 'LICENSE.txt' ],
        'ext' => [ 'swp', 'json'],
        'dir' => ['.git' , 'src'],
    ];

    /**
     * @var array
     */
    public $publishAssets = [
        'files' => [
            // from => to
            // 'xxx/zzz.js' => 'xxx/zzz-new.js', // real is `$sourcePath+'xxx/zzz.js' => $publishPath+'xxx/zzz-new.js'`
            // 'ccc/yy.js'  // can also only {from}, default {to} = {from}
        ],
        'dirs' => [
            // from => to
        ],
    ];

    /**
     * @var string[]
     */
    protected $publishedAssets = [
        'created' => [], // need new create file.
        'skipped' => [], // target file existing.
    ];

    public function __construct(array $config = [])
    {
        ObjectHelper::loadAttrs($this, $config);
    }

    /**
     * @param mixed $from
     * @param string $to
     * @return $this
     */
    public function add($from, $to = '')
    {
        if ( is_array($from) ) {
            array_walk($from,function($f,$t)
            {
                $this->add($f, $t);
            });

            return $this;
        }

        $to = is_integer($to) || !$to ? $from : $to;
        $fullPath = Directory::isAbsPath($from) || file_exists($from) ?
                    $from :
                    $this->sourcePath . '/' . trim($from, '/\\ ');

        if ( is_file($fullPath) ) {
            $this->publishAssets['files'][$fullPath] = $to;
        } elseif (is_dir($fullPath)) {
            $this->publishAssets['dirs'][$fullPath] = $to;
        } else {
            throw new InvalidArgumentException("The param must be an existing source file or dir path. Current: [$from]");
        }

        return $this;
    }

    /**
     * target path is {@see $publishPath} + $path ( is param of the method `source($path)` )
     * @param bool|false $replace
     * @return $this
     */
    public function publish($replace = false)
    {
        // publish files
        foreach ($this->publishAssets['files'] as $from => $to) {
            $this->publishFile($from, $to, $replace);
        }

        // publish directory
        foreach ($this->publishAssets['dirs'] as $fromDir => $toDir) {
            $this->publishDir($fromDir, $toDir, $replace);
        }

        vd($this);

        return $this;
    }

    /**
     * @param string $from The is full file path
     * @param string $to  The is a relative path
     * @param bool|false $replace
     */
    public function publishFile($from, $to, $replace = false)
    {
        $targetFile = Directory::isAbsPath($to) ? $to : $this->publishPath . '/' . $to;
        //$targetFile = $to . '/' . basename($from);

        if (!file_exists($targetFile) || $replace) {
            if ( !Directory::create(dirname($targetFile), 0775) ) {
                throw new FileSystemException('Create dir path [' . dirname($targetFile). '] failure.');
            }

            File::copy($from, $targetFile);

            $this->publishedAssets['created'][] = $targetFile;
        } else {
            $this->publishedAssets['skipped'][] = $targetFile;
        }
    }

    /**
     * @param $fromDir
     * @param $toDir
     * @param bool|false $replace
     */
    public function publishDir($fromDir, $toDir, $replace = false)
    {
        $files = $this->collectFiles($fromDir, 1);
        $toDir = Directory::isAbsPath($toDir) ? $toDir : $this->publishPath . '/' . $toDir;
        //} else {
            // $toDir = $this->publishPath;
        //}

        // publish files ...
        foreach ($files as $file) {
            $this->publishFile($fromDir . $file, $toDir . $file, $replace);
        }
    }

    /**
     * @param $dir
     * @param bool|false $recursive
     * @param string $basePath
     * @return array
     */
    protected function collectFiles($dir, $recursive = false, $basePath = '/')
    {
        $dir .= '/';
        $list = [];
        $ext   = implode('|',$this->include['ext']);
        $noExt = implode('|',$this->exclude['ext']);

        //glob()寻找与模式匹配的文件路径
        foreach( glob($dir.'*') as $file) {
            $name = basename($file);

            // 匹配文件 如果没有传入$ext 则全部遍历，传入了则按传入的类型来查找
            if ( is_file($file) && (
                    // check include ...
                    ( in_array($name, $this->include['file']) || preg_match("/\.($ext)$/i", $name) ) ||

                    // check exclude ...
                    ( !in_array($name, $this->exclude['file']) && !preg_match("/\.($noExt)$/i", $name) )
                )) {
                $list[] = $basePath .  $name;

                // 是否遍历子目录 并检查子目录是否在可发布列表
            } elseif (
                $recursive && is_dir($file) &&
                ( in_array($name, $this->include['dir']) || !in_array($name, $this->exclude['dir']) )
            ){
                $list = array_merge($list, $this->collectFiles($file, $recursive, $basePath . $name . '/'));
            }
        }

        return $list;
    }

    ////////////////////////////// getter/setter method //////////////////////////////

    /**
     * @return string
     */
    public function getSourcePath()
    {
        return $this->sourcePath;
    }

    /**
     * @param string $sourcePath
     */
    public function setSourcePath($sourcePath)
    {
        if ($sourcePath && is_dir($sourcePath)) {
            $this->sourcePath = $sourcePath;
        } else {
            throw new InvalidArgumentException('The source path must be an existing dir path. ');
        }
    }

    /**
     * @return string
     */
    public function getPublishPath()
    {
        return $this->publishPath;
    }

    /**
     * @param string $publishPath
     */
    public function setPublishPath($publishPath)
    {
        if ( $publishPath ) {
            $this->publishPath = $publishPath;
        }
    }


}