<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 16/8/25
 * Time: 下午9:43
 */

namespace inhere\library\asset;

use inhere\library\exceptions\FileNotFoundException;
use inhere\library\exceptions\FileSystemException;
use inhere\library\exceptions\InvalidArgumentException;
use inhere\library\exceptions\InvalidOptionException;
use inhere\library\exceptions\NotFoundException;
use inhere\library\files\Directory;
use inhere\library\files\File;
use inhere\library\helpers\ObjectHelper;
use inhere\library\helpers\UrlHelper;
use inhere\library\html\Html;
use inhere\library\StdBase;
use MatthiasMullie\Minify;

/**
 * Class AssetLoad
 * @package inhere\library\asset
 *
 * usage (in template file):
 *
 * <?php
 *
 * echo AssetLoad::css([
 *  'xx/zz.css',
 * ])->dump();
 *
 */
class AssetLoad extends StdBase
{
    /**
     * 网站域名url地址
     * @example http:://www.xxx.com
     * @var string
     */
    public $hostUrl = '';

    /**
     * 资源文件的基础url
     * @var string
     */
    public $baseUrl  = '';

    /**
     * 资源文件的基础路径
     *  baseUrl 对应的 真实目录路径
     * @var string
     */
    public $basePath = '';

    /**
     * check asset file exists.
     * require property {@see $basePath} is not empty.
     * @var bool
     */
    protected $checkFileExists = true;

    /**
     * 输出的 asset html tag, url 是否使用带域名的完整url；
     *  true 是
     *  false 否
     * @var bool
     */
    protected $useFullUrl = false;

    /**
     * is a callable.
     * can return handled path by the callable.
     * @var null|\Closure
     */
    protected $resolvePath;

    const TYPE_JS  = 'js';
    const TYPE_CSS = 'css';

    /**
     * @var string
     */
    protected $assetType = self::TYPE_CSS;

    /**
     * @var bool
     */
    private $compressed = false;

    /**
     * @var array
     */
    protected $assets = [];

    /**
     * @var array
     */
    protected $compressedAssets = [];

    /**
     * @var array
     */
    public $compressOptions = [
        'alwaysGen' => false,     // always new generate file. default(`false`) if compressed file is exists, will skip it.

        'mergeFile'     => false, // if true, will merge all file to one file.
        'mergeFilePath' => '',    // merged file output path. depend option mergeFile is true.

        'outPath'   => '',       // default output to old file path.
        'webPath'   => '',       // web access path.
    ];

    private function __construct(array $config = [])
    {
        ObjectHelper::loadAttrs($this, $config);
    }

    /**
     * always return new instance.
     * @param array $config
     * @return AssetLoad
     */
    public static function make(array $config = [])
    {
        return new static($config);
    }

    /**
     * 加载 css 文件
     * @param  string|array $asset
     * @param  array $options
     * @return self
     */
    public static function css($asset, array $options=[])
    {
        return static::make($options)->handleLoad($asset, self::TYPE_CSS);
    }

    /**
     * 加载 js文件
     * @param $asset
     * @param array $options
     * @return $this|AssetLoad
     */
    public static function js($asset, array $options =[])
    {
        return static::make($options)->handleLoad($asset, self::TYPE_JS);
    }

    /**
     *
     * @param string|array $assets 要加载的资源
     *
     * 1. $assets = 'file path' 加载一个文件
     * 2. $assets = 'a,b,c' 加载 a,b,c 多个文件
     * 3. 直接使用数组配置
     *    $assets = [
     *        'file1',
     *        'file2',
     *        'file3',
     *        'file4',
     *         ...
     *   ]
     *
     * @param string $assetType 资源文件类型 css js
     * @return $this
     * @throws InvalidArgumentException
     */
    protected function handleLoad($assets, $assetType = self::TYPE_CSS)
    {
        $this->setAssetType($assetType);

        // reset
        $this->assets = $this->compressedAssets = [];
        $this->compressed = false;

        // is string
        if ( $assets && is_string($assets) ) {
            // 是否是以逗号连接的多个文件名
            $assets = (strpos($assets, ',') && false === strpos($assets, 'http')) ? explode(',', $assets) : [$assets];
        }

        if ( !is_array($assets) ) {
            throw new InvalidArgumentException('The param $asset type only allow string or array.');
        }

        /** @var array $assets */
        foreach ($assets as $path) {
            $this->assets[] = trim($path);
        }

        unset($asset, $options);

        return $this;
    }

    /**
     * @return string
     */
    public function dump()
    {
        $assets  = $this->compressedAssets ?: $this->assets;
        $tags    = [];
        $hostUrl = $this->useFullUrl ? $this->getHostUrl() : '';

        // create asset html tag
        foreach ($assets as $url) {
            $url = self::buildUrl(trim($url), $this->baseUrl, $this->getBasePath(), $hostUrl);

            $tags[] = $this->assetType === self::TYPE_CSS ? Html::css($url) : Html::script($url);;
        }

        return implode("\n", $tags);
    }

//////////////////////////////////// compress assets ////////////////////////////////////

    /**
     * @param array $options
     * @return $this
     * @throws FileNotFoundException
     * @throws FileSystemException
     * @throws InvalidOptionException
     * @throws NotFoundException
     */
    public function compress(array $options = [])
    {
        if ($this->compressed || !$this->assets) {
            return $this;
        }

        $this->compressOptions = array_merge($this->compressOptions, $options);

        if ( !class_exists('MatthiasMullie\Minify\JS') ) {
            throw new NotFoundException('Class [MatthiasMullie\Minify\JS] not found. compress is require package "matthiasmullie/minify"');
        }

        $webPath = trim($this->compressOptions['webPath']);

        // compress add merged
        if ( $this->compressOptions['mergeFile'] ) {
            if (!$this->compressOptions['mergeFilePath']) {
                throw new InvalidOptionException(sprintf(
                    'If want to merge asset files, please set [mergeFilePath]. e.g like "/var/xx/zz/web/app.min.%s"',
                    $this->assetType
                ));
            }

            $saveFile = $this->compressOptions['mergeFilePath'];
            $this->compressedAssets[] = $this->compressAndMerge($this->assets, $saveFile, $webPath);

        // only compress
        } else {
            foreach ($this->assets as $url) {
                $this->compressedAssets[] = $this->compressAndSave($url, '', $webPath);
            }
        }

        $this->compressed = true;

        return $this;
    }

    /**
     * @param array $assets
     * @param string $saveFile
     * @param string $webPath
     * @return string
     * @throws FileNotFoundException
     * @throws FileSystemException
     */
    public function compressAndMerge(array $assets, $saveFile, $webPath = '')
    {
        $saveDir = dirname($saveFile);
        $oldKey = '';

        // create path.
        if ( !Directory::create($saveDir) ) {
            throw new FileSystemException("Create dir path [$saveDir] failure!!");
        }

        // check target file exists
        if ( file_exists($saveFile) ) {
            $oldKey = md5(file_get_contents($saveFile));
        }

        if ($this->assetType === self::TYPE_CSS) {
            $minifier = new Minify\CSS();
        } else {
            $minifier = new Minify\JS();
        }

        $basePath = $this->getBasePath();

        foreach ($assets as $url) {

            // is full url, have http ...
            if ( !UrlHelper::isRelative($url) ) {
                $this->compressedAssets[] = $url;

                continue;
            }

            $sourceFile = $basePath . '/' . $url;

            if (!is_file($sourceFile)) {
                throw new FileNotFoundException("File [$sourceFile] don't exists! please check it.");
            }

            $minifier->add($sourceFile);
        }

        // check file content has changed.
        if ($oldKey) {
            $newContent = $minifier->minify();
            $newKey = md5($newContent);

            if ($newKey !== $oldKey) {
                File::write($newContent, $saveFile);
            }

        } else {
            $minifier->minify($saveFile);
        }

        return $this->baseUrl . str_replace($webPath ?: $basePath, '', $saveFile);
    }

    /**
     * @param string $url
     * @param string $saveFile If is empty, will save to old file dir.
     * @param string $webPath
     * @return string
     * @throws FileNotFoundException
     * @throws FileSystemException
     */
    public function compressAndSave($url, $saveFile = '', $webPath = '')
    {
        // is full url, have http ...
        if ( !UrlHelper::isRelative($url) ) {
            return $url;
        }

        $oldKey = null;
        $basePath = $this->getBasePath();
        $sourceFile = $basePath . '/' . $url;

        if (!is_file($sourceFile)) {
            throw new FileNotFoundException("File [$sourceFile] don't exists!!");
        }

        // maybe need create directory.
        if ( $saveFile ) {
            $saveDir = dirname($saveFile);

            // create path.
            if ( !Directory::create($saveDir) ) {
                throw new FileSystemException("Create dir path [$saveDir] failure!!");
            }
        } else {
            $saveFile = substr($sourceFile, 0, - strlen($this->assetType)) . 'min.' .$this->assetType;
        }

        // check target file exists
        if ( file_exists($saveFile) ) {
            $oldKey = md5(file_get_contents($saveFile));
        }

        if ($this->assetType === self::TYPE_CSS) {
            $minifier = new Minify\CSS($sourceFile);
        } else {
            $minifier = new Minify\JS($sourceFile);
        }

        // check file content has changed.
        if ($oldKey) {
            $newContent = $minifier->minify();
            $newKey = md5($newContent);

            if ($newKey !== $oldKey) {
                File::write($newContent, $saveFile);
            }

        } else {
            $minifier->minify($saveFile);
        }

        return $this->baseUrl . str_replace($webPath ?: $basePath, '', $saveFile);
    }

//////////////////////////////////// helper method ////////////////////////////////////

    //资源路径检查，返回可用的 url
    // $useFullUrl 返回 url path 是: true 绝对  | false 相对
    public static function buildUrl($path, $baseUrl = '', $basePath = '', $hostUrl='')
    {
        $path = str_replace( '\\','/', trim($path) );

        //是完整的url路径
        if ( UrlHelper::isUrl( $path ) ) {
            return $path;

        }

        // 是url绝对路径
        if ($path{0} === '/') {
            return $hostUrl ? $hostUrl . $path :  $path;
        }

        // 相对路径
        $baseUrl = $baseUrl ? rtrim($baseUrl, '/') . '/' : '/';

        if ( $basePath ) {
            $basePath = rtrim($basePath, '/') . '/';

            if (!file_exists($basePath . $path)) {
                throw new \RuntimeException('资源 ['. $basePath . $path .'] 不存在 !!');
            }
        }

        return ($hostUrl ? $hostUrl . '/' : $baseUrl) . $path;
    }

    /**
     * check file has been compressed.
     * @param $file
     * @return bool
     */
    public static function isMinFile($file)
    {
        $regex = '/.[-.]min\.(css|js)$/i';

        return preg_match($regex, $file) === 1;
    }

    /**
     * @return string
     */
    public function getHostUrl()
    {
        if (!$this->hostUrl) {
            $this->hostUrl = $_SERVER['REQUEST_SCHEME'] . '/'.'/'.$_SERVER['HTTP_HOST'];
        }

        return $this->hostUrl;
    }

//////////////////////////////////// getter / setter method ////////////////////////////////////

    /**
     * @return string
     */
    public function getAssetType()
    {
        return $this->assetType;
    }

    /**
     * @param string $assetType
     * @throws InvalidArgumentException
     */
    public function setAssetType($assetType)
    {
        $types = [self::TYPE_CSS, self::TYPE_JS];

        if (!in_array($assetType, $types, true)) {
            throw new InvalidArgumentException('param must be is in array('. implode(',', $types) .')');
        }

        $this->assetType = $assetType;
    }

    /**
     * @return boolean
     */
    public function isCheckFileExists()
    {
        return $this->checkFileExists;
    }

    /**
     * @param boolean $value
     */
    public function setCheckFileExists($value)
    {
        $this->checkFileExists = (bool)$value;
    }

    /**
     * get base path
     *
     * maybe path use alias. (e.g. like '@app' --> '/xx/yy/app')
     * you can define
     *
     * $this->resolvePath = function($path) {
     *   if ( $path{0} ==='@' ) {
     *       // some handle logic ... ...
     *       // $path = App::resolvePath($basePath);
     *   }
     *   return $path;
     * }
     *
     * @return string
     */
    public function getBasePath()
    {
        //
        if ($this->resolvePath && $this->resolvePath instanceof \Closure) {
            $pathHandler = $this->resolvePath;
            $this->basePath = $pathHandler($this->basePath);
        }

        return $this->basePath;
    }

    /**
     * @return \Closure|null
     */
    public function getResolvePath()
    {
        return $this->resolvePath;
    }

    /**
     * @param \Closure|null $resolvePath
     */
    public function setResolvePath(\Closure $resolvePath=null)
    {
        $this->resolvePath = $resolvePath;
    }

    /**
     * @param boolean $useFullUrl
     */
    public function setUseFullUrl($useFullUrl)
    {
        $this->useFullUrl = (bool)$useFullUrl;
    }
}
