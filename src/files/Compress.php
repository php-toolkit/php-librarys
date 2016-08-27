<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 16/8/27
 * Time: 下午3:21
 */

namespace inhere\librarys\files;

use inhere\librarys\exceptions\NotFoundException;
use inhere\librarys\helpers\ObjectHelper;
use inhere\librarys\StdBase;
use ZipArchive;

/**
 * dir compress | file uncompressed
 * Class Compress
 * @package inhere\librarys\files
 */
class Compress extends FileFinder
{
    /**
     * dir path, wait compress ...
     * @var string
     */
    protected $sourcePath;

    /**
     * the compressed file
     * @var string
     */
    protected $compressedFile = '';

    const TYPE_ZIP = 'zip';

    protected $type = 'zip';

    /**
     * 包含的可发布的 文件 文件扩展匹配 目录
     * 比 {@see $exlude} 优先级更高
     * @var array
     */
    protected $include = [
        'file' => [],
        'ext' => [],
        'dir' => [], // ['dist'],
    ];

    /**
     * 排除发布的 文件 文件扩展匹配 目录
     * @var array
     */
    protected $exclude = [
        'file' => [],
        'ext' => [],
        'dir' => [],
    ];

//    public function __construct(array $config = [])
//    {}

    public function reset()
    {
        $this->compressedFile = '';

        return parent::reset();
    }

    public function compress($sourcePath, $compressedFile, $type = self::TYPE_ZIP)
    {

    }

    public function uncompress()
    {

    }

    public static function getTypes()
    {
        return [

        ];
    }
}