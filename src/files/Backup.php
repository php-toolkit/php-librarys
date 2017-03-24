<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017-03-24
 * Time: 10:33
 */

namespace inhere\librarys\files;

/**
 * Class Backup
 * @package inhere\librarys\files
 *
 * ```
 * $bk = new Backup;
 *
 * ```
 *
 */
class Backup
{
//    protected $srcPath = '';

    /**
     * 备份保存目录
     * @var string
     */
    protected $distPath = '';

    /**
     * @var array
     */
    protected $options = [
        'max_version' => 5, // 最多保留最近 5 个
        // 备份文件名
        // 可用标志
        // {name} 文件或目录名
        // {date} 当前日期 'YmdHis'
        // {time} 当前日期时间戳
        'filename' => '{name}-{date}',
        'compress' => 'zip', // 压缩格式 zip gzip phar tgz, 为空 直接拷贝目录备份
    ];

    protected $finder;

    /**
     * Backup constructor.
     * @param $srcPath
     * @param $distPath
     * @param $options
     */
    public function __construct($srcPath, $distPath, $options = [])
    {
        $this->distPath = $distPath;

        $options = array_merge([
            'sourcePath' => $srcPath,
            'include' => [],
            'exclude' => [],
        ], $options);

        $this->finder = new FileFinder($options);
    }

    public function dirs()
    {

    }

    public function files()
    {

    }

    public function pack()
    {

    }

    public function clearExpire($name)
    {

    }
}