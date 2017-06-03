<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 16/8/27
 * Time: 下午3:46
 */

namespace inhere\library\files;

use ArrayObject;
use inhere\exceptions\InvalidArgumentException;
use inhere\library\helpers\ObjectHelper;
use inhere\library\StdBase;

/**
 * Class FileFinder
 * @package inhere\library\files
 *
 *  $finder = new FileFinder([
 *       'sourcePath'  => '/var/xxx/vendor/bower/jquery'),
 *   ]);
 *
 *  $result = $finder->findAll(1)->getFiles();
 *  // Further filtering the result set
 *  // $result = $finder->findAll(1)->filterResult(function($file){
 *      if (false !== strpos($file, 'jqu') ) {
 *          return true;
 *      }
 *
 *      return false;
 * });
 *  var_dump($result);
 *
 * Can also, yan can custom find-filter. like:
 *
 * // more see $finder::doFileFilter()
 * $finder->setFileFilter(function($name, $finder){
 *  // some logic ..
 * });
 *
 * // more see $finder::doDirFilter()
 * $finder->setDirFilter(function($name, $finder){
 *  // some logic ..
 * });
 *
 *
 */
class FileFinder extends StdBase
{
    /**
     * dir path
     * @var string
     */
    protected $sourcePath;

    /**
     * 包含的可发布的 文件 文件扩展匹配 目录
     * 比 {@see $exlude} 优先级更高
     * @var array
     */
    protected $include = [
        'file' => ['README.md'],
        'ext' => [
            // 'js','css',
            // 'ttf','svg', 'eot', 'woff', 'woff2',
            // 'png', 'jpg', 'jpeg', 'gif', 'ico',
        ],
        'dir' => [], // ['dir'],
    ];

    /**
     * 排除发布的 文件 文件扩展匹配 目录
     * @var array
     */
    protected $exclude = [
        'file' => ['.gitignore', 'LICENSE', 'LICENSE.txt'],
        'ext' => ['swp', 'json'],
        'dir' => ['.git', 'src'],
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
     */
    public function __construct(array $config = [])
    {
        /**
         * reset settings
         */
        $this->reset();

        ObjectHelper::loadAttrs($this, $config);
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
     * @param bool|false $recursive
     * @param string $path
     * @param string $pathPrefix
     * @return $this
     * @throws InvalidArgumentException
     */
    public function findAll($recursive = false, $path = '', $pathPrefix = '')
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
     *
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
     * @return array
     */
    protected function findFiles($dir, $recursive = false, $pathPrefix = '')
    {
        $dir .= '/';
        $list = [];
        $pathPrefix = $pathPrefix ? $pathPrefix . '/' : '';

        //glob()寻找与模式匹配的文件路径
        foreach (glob($dir . '*') as $file) {
            $name = basename($file);

            // 匹配文件
            if (is_file($file) && $this->doFilterFile($name)) {
                $list[] = $pathPrefix . $name;

                // 是否遍历子目录 并检查子目录是否在查找列表
            } elseif ($recursive && is_dir($file) && $this->doFilterDir($name)) {
                $list = array_merge($list, $this->findFiles($file, $recursive, $pathPrefix . $name));
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
        // have bee set custom file Filter
        if (($fileFilter = $this->fileFilter) && is_callable($fileFilter)) {
            return call_user_func($fileFilter, $name, $this);
        }

        // use default filter handle
        $ext = implode('|', $this->getInclude('ext'));
        $noExt = implode('|', $this->getExclude('ext'));

        if ($ext || $this->include['file']) {
            // check include ...
            return in_array($name, $this->include['file']) || preg_match("/\.($ext)$/i", $name);
        }

        // check exclude ...
        return !in_array($name, $this->exclude['file']) && !preg_match("/\.($noExt)$/i", $name);
    }

    /**
     * 文件夹过滤 -- 过滤掉不需要的文件夹.
     * 也可自定义过滤回调,来实现个性化过滤
     * @param $name
     * @return bool|mixed
     */
    protected function doFilterDir($name /*, $dir*/)
    {
        // have bee set custom dir Filter
        if (($fileFilter = $this->fileFilter) && is_callable($fileFilter)) {
            return $fileFilter($name, $this);
        }

        // use default filter handle
        return in_array($name, $this->include['dir']) || !in_array($name, $this->exclude['dir']);
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
     * @throws InvalidArgumentException
     */
    public function setSourcePath($sourcePath)
    {
        if ($sourcePath) {
            if (!is_dir($sourcePath)) {
                throw new InvalidArgumentException('The source path must be an existing dir path. Input: ' . $sourcePath);
            }

            $this->sourcePath = $sourcePath;
        }
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
     */
    public function setFileFilter(callable $fileFilter)
    {
        $this->fileFilter = $fileFilter;
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
     */
    public function setDirFilter(callable $dirFilter)
    {
        $this->dirFilter = $dirFilter;
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
        if (!$key || !is_string($key)) {
            return $this->include;
        }

        return isset($this->include[$key]) ? $this->include[$key] : [];
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
        if (!$key || !is_string($key)) {
            return $this->exclude;
        }

        return isset($this->exclude[$key]) ? $this->exclude[$key] : [];
    }

    /**
     * @param array $exclude
     */
    public function setExclude(array $exclude)
    {
        $this->exclude = array_merge($this->exclude, $exclude);
    }
}
