<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 16/8/25
 * Time: 下午9:53
 */

namespace inhere\library\asset;

use inhere\library\exceptions\FileSystemException;
use inhere\library\exceptions\InvalidArgumentException;
use inhere\library\files\Directory;
use inhere\library\files\File;
use inhere\library\files\FileFinder;
use inhere\library\helpers\ArrHelper;
use inhere\library\helpers\ObjectHelper;
use inhere\library\StdBase;

/**
 * 资源发布 -- 将资源发布到可访问目录(e.g. from vendor to web dir)
 * Class AssetPublisher
 * @package inhere\library\asset
 */
class AssetPublisher extends StdBase
{
    /**
     * asset source base path
     * @var string
     */
    protected $sourcePath = '';

    /**
     * asset publish base path
     * @var string
     */
    protected $publishPath = '';

    /**
     * @var array[]
     */
    protected $publishAssets = [
        'files' => [
            // from => to
            // e.g.
            //  # real is `$sourcePath+'xxx/zzz.js' => $publishPath+'xxx/zzz-new.js'`
            // 'xxx/zzz.js' => 'xxx/zzz-new.js',
            //  # can also only {from}, default {to} = {from}
            // 'ccc/yy.js'
        ],
        'dirs' => [
            // from => to
            // 'zzz/ddd' => 'aaa/bbb'
        ],
    ];

    /**
     * @var array
     */
    protected $publishedAssets = [
        'created' => [], // need new create file.
        'skipped' => [], // target file existing.
    ];

    /**
     * @var FileFinder
     */
    protected $finder;

    public function __construct(array $config = [])
    {
        $include = ArrHelper::remove('include', $config, $this->defaultOptions()['include']);
        $exclude = ArrHelper::remove('exclude', $config, $this->defaultOptions()['exclude']);

        $this->finder = new FileFinder([
            'include' => $include,
            'exclude' => $exclude,
        ]);

        ObjectHelper::loadAttrs($this, $config);
    }

    public function defaultOptions()
    {
        return [
            /**
             * 包含的可发布的 文件 文件扩展匹配 目录
             * 比 {@see $exlude} 优先级更高
             * @var array
             */
            'include' => [
                'file' => ['README.md'],
                'ext' => [
                    'js', 'css',
                    'ttf', 'svg', 'eot', 'woff', 'woff2',
                    'png', 'jpg', 'jpeg', 'gif', 'ico',
                ],
                'dir' => [], // ['dist'],
            ],

            /**
             * 排除发布的 文件 文件扩展匹配 目录
             * @var array
             */
            'exclude' => [
                'file' => ['.gitignore', 'LICENSE', 'LICENSE.txt'],
                'ext' => ['swp', 'json'],
                'dir' => ['.git', 'src'],
            ]
        ];
    }

    /**
     * @param mixed $from
     * @param string $to
     * @return $this
     * @throws InvalidArgumentException
     */
    public function add($from, $to = '')
    {
        if (!$from) {
            return $this;
        }

        if (is_array($from)) {
            array_walk($from, function ($f, $t) {
                $this->add($f, $t);
            });

            return $this;
        }

        $to = !$to ? $from : $to;
        $fullPath = Directory::isAbsPath($from) || file_exists($from) ?
            $from :
            $this->sourcePath . '/' . trim($from, '/\\ ');

        if (is_file($fullPath)) {
            $this->publishAssets['files'][$fullPath] = $to;
        } elseif (is_dir($fullPath)) {
            $this->publishAssets['dirs'][$fullPath] = $to;
        } else {
            throw new InvalidArgumentException("The param must be an existing source file or dir path. Input: [$from]");
        }

        return $this;
    }

    /**
     * target path is {@see $publishPath} + $path ( is param of the method `source($path)` )
     * @param bool|false $override
     * @return $this
     */
    public function publish($override = false)
    {
        // publish files
        foreach ($this->publishAssets['files'] as $from => $to) {
            $this->publishFile($from, $to, $override);
        }

        // publish directory
        foreach ($this->publishAssets['dirs'] as $fromDir => $toDir) {
            $this->publishDir($fromDir, $toDir, $override);
        }

        // no define asset to publish, will publish source-path to publish-path
        if (!$this->hasAssetToPublish()) {
            $this->publishDir($this->sourcePath, $this->publishPath, $override);
        }

        return $this;
    }

    /**
     * @param string $from The is full file path
     * @param string $to The is a relative path
     * @param bool|false $override
     * @throws FileSystemException
     */
    public function publishFile($from, $to, $override = false)
    {
        $targetFile = Directory::isAbsPath($to) ? $to : $this->publishPath . '/' . $to;
        //$targetFile = $to . '/' . basename($from);

        if (!file_exists($targetFile) || $override) {
            if (!Directory::create(dirname($targetFile), 0775)) {
                throw new FileSystemException('Create dir path [' . dirname($targetFile) . '] failure.');
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
     * @param bool|false $override
     */
    public function publishDir($fromDir, $toDir, $override = false)
    {
        $files = $this->finder->findAll(1, $fromDir)->getFiles();
        $toDir = Directory::isAbsPath($toDir) ? $toDir : $this->publishPath . '/' . $toDir;

        // publish files ...
        foreach ($files as $file) {
            $this->publishFile($fromDir . '/' . $file, $toDir . '/' . $file, $override);
        }
    }

    public function hasAssetToPublish()
    {
        return 0 < count($this->publishAssets['files']) || 0 < count($this->publishAssets['dirs']);
    }

    ////////////////////////////// getter/setter method //////////////////////////////

    /**
     * @return FileFinder
     */
    public function getFinder()
    {
        if (!$this->finder) {
            $this->finder = new FileFinder([
                'include' => $this->defaultOptions()['include'],
                'exclude' => $this->defaultOptions()['exclude'],
            ]);
        }

        return $this->finder;
    }

    /**
     * @param FileFinder $finder
     */
    public function setFinder(FileFinder $finder)
    {
        $this->finder = $finder;
    }

    /**
     * @return string
     */
    public function getSourcePath()
    {
        return $this->sourcePath;
    }

    /**
     * @param string $sourcePath
     * @throws InvalidArgumentException
     */
    public function setSourcePath($sourcePath)
    {
        if ($sourcePath && is_dir($sourcePath)) {
            $this->sourcePath = $sourcePath;
        } else {
            throw new InvalidArgumentException('The source path must be an existing dir path. Input: ' . $sourcePath);
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
        if ($publishPath) {
            $this->publishPath = $publishPath;
        }
    }

    /**
     * @return array
     */
    public function getPublishedAssets()
    {
        return $this->publishedAssets;
    }

    /**
     * @return array
     */
    public function getPublishAssets()
    {
        return $this->publishAssets;
    }
}
