<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 16/8/27
 * Time: 下午3:46
 */

namespace Inhere\Library\Files;

use ArrayObject;
use Inhere\Exceptions\InvalidArgumentException;
use Inhere\Library\StdObject;

/**
 * Class FileFinder
 * @package Inhere\Library\Files
 * @deprecated Please use SimpleFinder::class
 *  $finder = new FileFinder([
 *       'sourcePath'  => '/var/xxx/vendor/bower/jquery'),
 *   ]);
 *  $result = $finder->findAll(1)->getFiles();
 *  // Further filtering the result set
 *  // $result = $finder->findAll(1)->filterResult(function($file){
 *      if (false !== strpos($file, 'jqu') ) {
 *          return true;
 *      }
 *      return false;
 * });
 *  var_dump($result);
 * Can also, yan can custom find-filter. like:
 * // more see $finder::doFileFilter()
 * $finder->setFileFilter(function($name, $finder){
 *  // some logic ..
 * });
 * // more see $finder::doDirFilter()
 * $finder->setDirFilter(function($name, $finder){
 *  // some logic ..
 * });
 */
class FileFinder extends StdObject
{
    /**
     * dir path
     * @var string
     */
    protected $sourcePath;

    /**
     * 包含的 文件 文件扩展匹配 目录
     * 比 {@see $exlude} 优先级更高
     * @var array
     */
    protected $include = [
//        'file' => ['README.md'], // file name
//        'ext' => [
        // 'js','css',
        // 'ttf','svg', 'eot', 'woff', 'woff2',
        // 'png', 'jpg', 'jpeg', 'gif', 'ico',
//        ],
//        'dir' => [], // ['dir'],
    ];

    /**
     * 排除的 文件 文件扩展匹配 目录
     * @var array
     */
    protected $exclude = [
//        'file' => ['.gitignore', 'LICENSE', 'LICENSE.txt'],
//        'ext' => ['swp', 'json'],
//        'dir' => ['.git', 'src'],
    ];

    /**
     * @var string
     */
    protected $pathPrefix;

    /**
     * @var callable
     */
    protected $fileFilter;

    /**
     * @var callable
     */
    protected $dirFilter;

    /**
     * @var array|ArrayObject
     */
    protected $files;

    /**
     * @var string
     */
    private $_relatedFile;

    /**
     * @param array $config
     * @return static
     */
    public static function make(array $config = [])
    {
        return new static($config);
    }

    /**
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        // reset settings
        $this->reset();

        parent::__construct($config);

        // formatting
        $this->include['file'] = (array)$this->include['file'];
        $this->include['ext'] = (array)$this->include['ext'];

        $this->exclude['file'] = (array)$this->exclude['file'];
        $this->exclude['ext'] = (array)$this->exclude['ext'];
    }

    public function reset()
    {
        $this->sourcePath = '';
        $this->files = [];
        $this->include = $this->exclude = [
            'file' => [],
            'ext' => [],
            'dir' => [],
        ];

        return $this;
    }

    /**
     * @param bool $recursive
     * @param string $path
     * @param string $pathPrefix
     * @return FileFinder
     * @throws InvalidArgumentException
     */
    public function find($recursive = true, $path = '', $pathPrefix = '')
    {
        return $this->findAll($recursive, $path, $pathPrefix);
    }

    /**
     * @param bool|false $recursive
     * @param string $path
     * @param string $pathPrefix
     * @return $this
     * @throws InvalidArgumentException
     */
    public function findAll($recursive = true, $path = '', $pathPrefix = '')
    {
        $path = $path ?: $this->sourcePath;
        $pathPrefix = $pathPrefix ?: $this->pathPrefix;

        if (!$path || !is_dir($path)) {
            throw new InvalidArgumentException('The path must be an existing dir path. Current: ' . $this->sourcePath);
        }

        // have been find
        if ($this->_relatedFile === $path && ($this->files instanceof ArrayObject)) {
            return $this;
        }

        $files = $this->findFiles($path, $recursive, $pathPrefix);
        $this->files = $files ? new ArrayObject($files) : [];
        $this->_relatedFile = $path;

        return $this;
    }

    /**
     * @param $files
     * @param bool $reset
     * @return $this
     */
    public function saveResultTo(&$files, $reset = false)
    {
        $files = $this->files;

        if ($reset) {
            $this->reset();
        }

        return $this;
    }

    /**
     * Further filtering the find result set
     * @param callable $filter
     * e.g:
     * $filter = function($file) {
     *      if (false !== strpos($file, 'jqu') ) {
     *          return true;
     *      }
     *      return false;
     * };
     * @return array
     */
    public function filterResult(callable $filter)
    {
        $result = [];

        foreach ($this->files as $file) {
            if ($filter($file)) {
                $result[] = $file;
            }
        }

        return $result;
    }

    ////////////////////////////// find file/dir handle //////////////////////////////

    /**
     * @param $dir
     * @param bool|false $recursive
     * @param string $pathPrefix
     * @param array $list
     * @return array
     */
    protected function findFiles($dir, $recursive = false, $pathPrefix = '', array &$list = [])
    {
        $dir = rtrim($dir, '/') . '/';
        $pathPrefix = $pathPrefix ? $pathPrefix . '/' : '';

        //glob()寻找与模式匹配的文件路径
        foreach (glob($dir . '*') as $file) {
            $name = basename($file);

            // 匹配文件
            if (is_file($file) && $this->doFilterFile($name)) {
                $list[] = $pathPrefix . $name;

                // 是否遍历子目录 并检查子目录是否在查找列表
            } elseif ($recursive && is_dir($file) && $this->doFilterDir($name)) {
                $this->findFiles($file, $recursive, $pathPrefix . $name, $list);
            }
        }

        return $list;
    }

    /**
     * 文件过滤 -- 过滤掉不需要的文件.
     * 也可自定义过滤回调,来实现个性化过滤
     * @param $name
     * @return bool|mixed
     */
    protected function doFilterFile($name /*, $file*/)
    {
        // check include ...
        if (\in_array($name, $this->include['file'], true)) {
            return true;
        }

        // check exclude file ...
        if (\in_array($name, $this->exclude['file'], true)) {
            return false;
        }

        $ext = implode('|', $this->getInclude('ext'));

        // check include ext ...
        if ($ext && preg_match("/\.($ext)$/i", $name)) {
            // have been set custom file Filter
            if ($fileFilter = $this->fileFilter) {
                return $fileFilter($name);
            }

            return true;
        }

        $noExt = implode('|', $this->getExclude('ext'));

        // check exclude ext ...
        if ($noExt && preg_match("/\.($noExt)$/i", $name)) {
            return false;
        }

        // have been set custom file Filter
        if ($fileFilter = $this->fileFilter) {
            return $fileFilter($name);
        }

        return true;
    }

    /**
     * 文件夹过滤 -- 过滤掉不需要的文件夹. 也可添加自定义过滤回调,来实现个性化过滤
     * @param $name
     * @return bool
     */
    protected function doFilterDir($name /*, $dir*/)
    {
        if (\in_array($name, $this->include['dir'], true)) {
            // have bee set custom dir Filter
            if ($dirFilter = $this->dirFilter) {
                return $dirFilter($name);
            }

            return true;
        }

        if (\in_array($name, $this->exclude['dir'], true)) {
            return false;
        }

        // have bee set custom dir Filter
        if ($dirFilter = $this->dirFilter) {
            return $dirFilter($name);
        }

        // use default filter handle
        return true;
    }

    ////////////////////////////// helper method //////////////////////////////

    /**
     * @param mixed $data
     * @param string $type
     * @return $this
     */
    public function include($data, $type = 'dir')
    {
        switch ($type) {
            case 'dir':
                $this->includeDir($data);
                break;
            case 'file':
                $this->includeFile($data);
                break;
            case 'ext':
                $this->includeExt($data);
                break;
            default:
                break;
        }

        return $this;
    }

    /**
     * @param string|array $file
     * @return self
     */
    public function includeFile($file)
    {
        if (\is_string($file)) {
            $file = [$file];
        }

        if (\is_array($file)) {
            foreach ($file as $name) {
                if (\in_array($name, $this->include['file'], true)) {
                    continue;
                }

                $this->include['file'][] = trim($name);
            }
        }

        return $this;
    }

    /**
     * @param string|array $ext
     * @return self
     */
    public function includeExt($ext)
    {
        if (\is_string($ext)) {
            $ext = [$ext];
        }

        if (\is_array($ext)) {
            foreach ($ext as $name) {
                if (\in_array($name, $this->include['ext'], true)) {
                    continue;
                }

                $this->include['ext'][] = trim($name, '.');
            }
        }

        return $this;
    }

    /**
     * @param string|array $dir
     * @return self
     */
    public function includeDir($dir)
    {
        if (\is_string($dir)) {
            $dir = [$dir];
        }

        if (\is_array($dir)) {
            foreach ($dir as $name) {
                if (\in_array($name, $this->include['dir'], true)) {
                    continue;
                }

                $this->include['dir'][] = trim($name);
            }
        }

        return $this;
    }

    /**
     * @return self
     */
    public function ignoreVCS()
    {
        return $this->excludeDir(['.git', '.svn'])->excludeFile('.gitignore');
    }

    /**
     * @param mixed $data
     * @param string $type
     * @return $this
     */
    public function exclude($data, $type = 'dir')
    {
        switch ($type) {
            case 'dir':
                $this->excludeDir($data);
                break;
            case 'file':
                $this->excludeFile($data);
                break;
            case 'ext':
                $this->excludeExt($data);
                break;
            default:
                break;
        }

        return $this;
    }

    /**
     * @param string|array $file
     * @return self
     */
    public function notName($file)
    {
        return $this->excludeFile($file);
    }

    /**
     * @param string|array $file
     * @return self
     */
    public function notFile($file)
    {
        return $this->excludeFile($file);
    }

    /**
     * @param string|array $file
     * @return self
     */
    public function excludeFile($file)
    {
        if (\is_string($file)) {
            $file = [$file];
        }

        if (\is_array($file)) {
            foreach ($file as $name) {
                if (\in_array($name, $this->exclude['file'], true)) {
                    continue;
                }

                $this->exclude['file'][] = trim($name);
            }
        }

        return $this;
    }

    /**
     * @param string|array $ext
     * @return self
     */
    public function excludeExt($ext)
    {
        if (\is_string($ext)) {
            $ext = [$ext];
        }

        if (\is_array($ext)) {
            foreach ($ext as $name) {
                if (\in_array($name, $this->exclude['ext'], true)) {
                    continue;
                }

                $this->exclude['ext'][] = trim($name);
            }
        }

        return $this;
    }

    /**
     * @param string|array $dir
     * @return self
     */
    public function excludeDir($dir)
    {
        if (\is_string($dir)) {
            $dir = [$dir];
        }

        if (\is_array($dir)) {
            foreach ($dir as $name) {
                if (\in_array($name, $this->exclude['dir'], true)) {
                    continue;
                }

                $this->exclude['dir'][] = trim($name);
            }
        }

        return $this;
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
     * @param $sourcePath
     * @return self
     * @throws InvalidArgumentException
     */
    public function inDir($sourcePath)
    {
        return $this->setSourcePath($sourcePath);
    }

    /**
     * @param string $sourcePath
     * @return $this
     * @throws InvalidArgumentException
     */
    public function setSourcePath($sourcePath)
    {
        if ($sourcePath) {
            if (!is_dir($sourcePath)) {
                throw new InvalidArgumentException('The source path must be an existing dir path. Input: ' . $sourcePath);
            }

            $this->sourcePath = realpath($sourcePath);
        }

        return $this;
    }

    /**
     * @return callable
     */
    public function getFileFilter()
    {
        return $this->fileFilter;
    }

    /**
     * @param callable $fileFilter
     * @return $this
     */
    public function setFileFilter(callable $fileFilter)
    {
        if (\is_string($fileFilter) || method_exists($fileFilter, '__invoke')) {
            $this->fileFilter = $fileFilter;
        }

        return $this;
    }

    /**
     * @return callable
     */
    public function getDirFilter()
    {
        return $this->dirFilter;
    }

    /**
     * @param callable $dirFilter
     * @return $this
     */
    public function setDirFilter(callable $dirFilter)
    {
        if (\is_string($dirFilter) || method_exists($dirFilter, '__invoke')) {
            $this->dirFilter = $dirFilter;
        }

        return $this;
    }

    /**
     * @return array|ArrayObject
     */
    public function getFiles()
    {
        return $this->files;
    }

    /**
     * @param string $key
     * @return array
     */
    public function getInclude($key = '')
    {
        if (!$key || !\is_string($key)) {
            return $this->include;
        }

        return $this->include[$key] ?? [];
    }

    /**
     * @param array $include
     */
    public function setInclude(array $include)
    {
        $this->include = array_merge($this->include, $include);
    }

    /**
     * @param string $key
     * @return array
     */
    public function getExclude($key = '')
    {
        if (!$key || !\is_string($key)) {
            return $this->exclude;
        }

        return $this->exclude[$key] ?? [];
    }

    /**
     * @param array $exclude
     */
    public function setExclude(array $exclude)
    {
        $this->exclude = array_merge($this->exclude, $exclude);
    }
}
