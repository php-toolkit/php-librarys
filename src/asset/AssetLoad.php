<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 16/8/25
 * Time: 下午9:43
 */

namespace inhere\librarys\asset;

use inhere\librarys\helpers\ObjectHelper;
use inhere\librarys\helpers\UrlHelper;
use inhere\librarys\html\Html;
use inhere\librarys\StdBase;

/**
 * Class AssetLoad
 * @package inhere\librarys\asset
 */
class AssetLoad extends StdBase
{
    const TYPE_JS = 'js';
    const TYPE_CSS = 'css';

    /**
     * 网站域名url地址
     * @example http:://www.xxx.com
     * @var string
     */
    public $hostUrl = '';

    /**
     * @var string
     */
    public $baseUrl  = '/';

    /**
     * @var string
     */
    protected $basePath = '';

    /**
     * check asset file exists.
     * require property {@see $basePath} is not empty.
     * @var bool
     */
    protected $checkFileExists = true;

    /**
     * is a callable.
     * can return handled path by the callable.
     * @var null|\Closure
     */
    protected $resolvePath;

    private function __construct(array $config = [])
    {
        ObjectHelper::loadAttrs($this, $config);
    }

    /**
     * @param array $config
     * @param bool|false $new
     * @return AssetLoad
     */
    public static function make(array $config = [], $new = false)
    {
        static $instance = null;

        if (!$instance || $new) {
            $instance = new self($config);
        }

        return $instance;
    }

    /**
     * 加载 css 文件
     * @param  string|array $asset
     * @param  array $options
     * @return void
     */
    public static function css($asset, array $options=[])
    {
        static::make()->_handleLoad($asset, $options);
    }
    public static function loadCss($asset, array $options=[])
    {
        self::css($asset, $options);
    }

    /*
     * 加载 js文件
     */
    public static function js($asset, array $options=[])
    {
        if (!isset($options['resType'])) {
            $options['resType'] = self::TYPE_JS;
        }

        static::make()->_handleLoad($asset, $options);
    }
    public static function loadJs($asset, array $options=[])
    {
        self::js($asset, $options);
    }


    /**
     *
     * @param string|array $asset 要加载的资源
     * 1. $asset = 'file path' 加载一个文件
     * 2. $asset = 'a,b,c' 加载 a,b,c 多个文件
     * 3. 直接使用数组配置
     *    $asset = [
     *        'file1',
     *        'file2',
     *        'file3',
     *        'file4',
     *         ...
     *   ]
     *
     * @param array $options 默认加载 css
     *   $options = [
     *       'useFullUrl' => 输出的 asset url 是否使用带域名的完整url； true 是  | false 否
     *       'resType'    => 资源文件类型 css js,
     *       'baseUrl'    => base url 资源文件 基准url
     *       'basePath'   => baseUrl 对应的 真实目录,
     *   ]
     **/
    protected function _handleLoad($asset, array $options=[])
    {
        $options = array_merge([
            'useFullUrl' => false,              // url path 是: true 绝对  | false 相对
            'resType'    => self::TYPE_CSS,     // 资源文件类型 css js
            'baseUrl'    => $this->baseUrl,     // 资源文件 基准url
            'basePath'   => $this->getBasePath(), // 资源文件 基准url 对应的 真实目录
        ], $options );

        $resType    = $options['resType'];
        $baseUrl    = $options['baseUrl'];
        $basePath   = $this->checkFileExists ? $options['basePath'] : '';
        $hostUrl    = (bool)$options['useFullUrl'] ? $this->getHostUrl() : '';

        if (is_array($asset)) {
            foreach ($asset as $file) {
                $url = self::buildUrl($file, $baseUrl, $basePath, $hostUrl);

                echo $resType == self::TYPE_CSS ? Html::link($url) : Html::script($url);
            }

        } elseif ( $asset && is_scalar($asset) ) {

            //是否是以逗号连接的多个文件名
            if (strpos($asset, ',') && !strstr($asset, 'http')) {
                $files    = explode(',', $asset);

                foreach ($files as $file) {
                    $url = self::buildUrl($file, $baseUrl, $basePath, $hostUrl);

                    echo $resType == self::TYPE_CSS ? Html::link($url) : Html::script($url);
                }

            } else {
                $url = self::buildUrl( $asset, $baseUrl, $basePath, $hostUrl);

                echo $resType == self::TYPE_CSS ? Html::link($url) : Html::script($url);
            }
        }

        unset($asset, $options, $basePath, $baseUrl);
    }


    //资源路径检查，返回可用的 url path,末尾都已经添上了 '/'
    // $useFullUrl 返回 url path 是: true 绝对  | false 相对
    public static function buildUrl($path, $baseUrl = '/', $basePath = '', $hostUrl='')
    {
        $path = str_replace( '\\','/',trim($path,'/\\ ') );

        //是完整的url路径
        if ( UrlHelper::isUrl( $path ) ) {
            return $path;

        // 是url绝对路径
        } else if ( $path{0} == '/' ) {
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
     * @return string
     */
    public function getHostUrl()
    {
        if (!$this->hostUrl) {
            $this->hostUrl = $_SERVER['REQUEST_SCHEME'] . '/'.'/'.$_SERVER['HTTP_HOST'];
        }

        return $this->hostUrl;
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
     * @param string $basePath
     */
    public function setBasePath($basePath)
    {
        $this->basePath = $basePath;
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
}