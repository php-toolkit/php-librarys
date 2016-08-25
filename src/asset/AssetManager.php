<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 16/8/25
 * Time: 下午9:44
 */

namespace inhere\librarys\asset;

use inhere\librarys\exceptions\InvalidArgumentException;
use inhere\librarys\helpers\ObjectHelper;
use inhere\librarys\helpers\UrlHelper;
use inhere\librarys\html\Html;
use inhere\librarys\StdBase;

/**
 * 前端资源加载管理
 * Class AssetManager
 * @package inhere\librarys\asset
 */
class AssetManager extends StdBase
{
    /**
     * css or js file link tag
     * @var array
     */
    protected $assets  = []; //static

    /**
     * css or js code
     * @var array
     */
    protected $codes   = []; //

    // todo unused ...
    protected  $locked  = [
        ''
    ];

    /**
     * 资源基础URL
     * 添加资源时，不以 '/' 开始的资源，自动在前面加上 $baseUrl
     * @var [type]
     */
    protected $baseUrl = '';

    /**
     * $baseUrl 对应的物理路径
     * 用于资源是否存在验证
     * @example
     *     $baseUrl: '/static/'
     *     $basePath: 'D:/www/web/static/'
     * @var string
     */
    protected $basePath = '';

    protected $mergeCssCode = false;

    protected $mergeJsCode  = false;

    public $cdn = [
        'jquery'       => 'http://libs.useso.com/js/jquery/2.1.0/jquery.min.js'
    ];

    public $local = [
        'jquery' => ''
    ];

    /**
     * 给加载的资源添加标记id
     * @var string
     */
    protected $id;

    /**
     * 给资源管理类输出的资源添加标记
     * @var boolean
     */
    public $markSource  = true;
    public $markAttr  = [
        'data-source' => 'app-shell-load',
    ];

    const POS_HEAD      = 'head';       # 在解析视图数据时放到</head>之前的位置
    const POS_BODY      = 'body';       # 在解析视图数据时放到<body>之后的位置
    const POS_END       = 'end';        # 在解析视图数据时放到</body>之前的位置

    protected $headNode = '</head>';
    protected $bodyNode = '<body>';
    protected $endNode  = '</body>';

    const ASSET_JS_FILE  = 'js-file';
    const ASSET_JS       = 'js';
    const ASSET_CSS_FILE = 'css-file';
    const ASSET_CSS      = 'css';

    public function __construct(array $config = [])
    {
        ObjectHelper::loadAttrs($this, $config);
    }

//////////////////////////////////// register ////////////////////////////////////

    /**
     * register asset
     * e.g.
     *
     * $manager->loadAsset('home-page',[
     *  'css' => [
     *      ],
     *  'js' => [
     *      ],
     * ], [
     *      ''
     * ]);
     *
     * @param string $name define zhe asset name
     * @param array $assets
     * @param array $options
     * @return $this
     */
    public function loadAsset($name, $assets, $options = [])
    {
        return $this;
    }

    /**
     * [addJsFile 注册添加 javascript 文件, 引入相关标签]
     * @param $asset
     * @param string $position
     * @return mixed
     */
    public function addJsFile($asset, $position=self::POS_END)
    {
        return $this->_loggingAsset($asset, self::ASSET_JS_FILE , $position);
    }

    /**
     * [addCssFile 注册添加 css style 文件, 引入相关标签]
     * @param $asset
     * @param string $position
     * @return mixed
     */
    public function addCssFile($asset, $position=self::POS_HEAD)
    {
        return $this->_loggingAsset($asset, self::ASSET_CSS_FILE, $position);
    }

    /**
     * [addJs 注册添加 javascript 内容到视图数据]
     * @param $scriptCode
     * @param string $position
     * @internal param $ [type] $scriptCode
     * @internal param $ [type] $position
     * @return mixed
     */
    public function addJs($scriptCode, $position=self::POS_END)
    {
        return $this->_loggingAsset($scriptCode, self::ASSET_JS, $position);
    }

    /**
     * [addCss 注册添加 css style code内容到视图数据]
     * @param $styleCode
     * @param string $position
     * @return mixed
     */
    public function addCss($styleCode, $position=self::POS_HEAD)
    {
        return $this->_loggingAsset($styleCode, self::ASSET_CSS, $position);
    }

    /**
     * [_loggingAsset description]
     * @param  mixed     $assets
     * @param  string     $type
     * @param  string     $pos
     * @return self
     */
    protected function _loggingAsset($assets, $type=self::ASSET_CSS_FILE, $pos=self::POS_HEAD)
    {
        if (!$assets) {
            throw new InvalidArgumentException('The 1th param [$assets] is can\'t empty.');
        }

        $pos       = trim($pos);
        $positions = $this->getPositions();
        $types     = $this->getAssetTypes();

        if ( !in_array($pos, $positions ) ) {
            throw new InvalidArgumentException('资源注册位置允许设置 ['.implode(', ', $positions).'] 中的一个。');
        }

        if ( !in_array($type, $types ) ) {
            throw new InvalidArgumentException('资源类型可选 ['.implode(', ', $types).'] 中的一个。');
        }

        $assets = (array) $assets;

        foreach ($assets as $name => $asset) {
            $asset = trim($asset);

            // check asset url
            if ( in_array($type, [self::ASSET_JS_FILE, self::ASSET_CSS_FILE]) ) {
                $asset = AssetLoad::buildUrl( $asset, $this->getBaseUrl(), $this->basePath, false);
            }

            $tag = $this->buildTag($asset, $type);

            if (!is_numeric($name)) {
                // if ( $this->lockedCheck($name, $pos) ) {
                //     # code...
                // }

                $this->assets[$pos][$name] = $tag;
            } else {
                $this->assets[$pos][] = $tag;
            }
        }

        return $this;
    }

    /**
     * create html tag
     * @param  string $asset
     * @param  string $type
     * @return string
     */
    public function buildTag($asset, $type )
    {
        $attrs = $this->markSource ? $this->markAttr : [];

        switch ($type) {
            case self::ASSET_JS_FILE:
                return Html::script($asset, $attrs);
                break;
            case self::ASSET_JS:
                return Html::scriptCode($asset, $attrs);
                break;
            case self::ASSET_CSS_FILE:
                return Html::link($asset, $attrs);
                break;
            case self::ASSET_CSS:
                return Html::style($asset, $attrs);
                break;
        }

        return '';
    }

    /**
     * 给当前注册资源添加标记， 可用于唯一性资源检查 避免重复注册，
     * e.g.
     * 未添加标记，不论是否已加载，都会往后面追加
     * $this->assets['position'][] => 'xx/jquery.js'
     * 给jquery资源标记名称‘jquery’
     * $this->assets['position']['jquery'] => 'xx/jquery.js'
     * @param $link
     */
    public function lockedCheck($link)
    {
        foreach ($this->locked as $rule) {
            if (preg_match($rule, $link)) {
                //Trigger::error('xxxxxx');
            }
        }
    }

    public function addLock()
    {
        # code...
    }

    /**
     * check asset has exists
     * @author inhere
     * @date   2015-08-02
     * @param  string     $name
     * @param  null|string     $pos
     * @return bool
     */
    public function exists($name, $pos = null)
    {
        if (!$pos) {
            foreach ( $this->getAssetTypes() as $type ) {
                $assets = $this->getAssetsByPos($type);

                if ( isset($assets[$name]) ) {
                    return true;
                }
            }
        } else {
            $assets = $this->getAssetsByPos($pos);

            return isset($assets[$name]);
        }

        return false;
    }

    public function getPositions()
    {
        return [
            self::POS_HEAD,
            self::POS_BODY,
            self::POS_END
        ];
    }

    public function getAssetTypes()
    {
        return [
            self::ASSET_CSS_FILE,
            self::ASSET_CSS,
            self::ASSET_JS_FILE,
            self::ASSET_JS
        ];
    }

////////////////////////////// inject Assets to data //////////////////////////////

    /**
     * 自动注入资源到指定的位置
     * @param string $data html document string
     * @return mixed|string [type]
     */
    public function autoInject($data)
    {
        $data = trim($data);

        if ( !($assets = $this->assets) ) {
            return $data;
        }

        if (!empty($assets[self::POS_BODY])) {

            $assetBody  = $this->bodyNode . implode('', $assets[self::POS_BODY]);
            $bodyNode   = str_replace('/', '\/', $this->bodyNode);
            $data       = preg_replace( "/$bodyNode/i", $assetBody, $data, 1 , $count);

            // 没找到节点，注入失败时，直接加入开始位置
            if ($count==0) {
                $data = $assetBody.$data;
            }
        }

        if (!empty($assets[self::POS_HEAD])) {
            $assetHead= implode('', $assets[self::POS_HEAD]) . $this->headNode;
            $headNode = str_replace('/', '\/', $this->headNode);
            $data     = preg_replace( "/$headNode/i", $assetHead, $data, 1 , $count);

            if ($count==0) {
                $data = $assetHead.$data;
            }
        }

        if (!empty($assets[self::POS_END])) {

            $assetEnd   = implode('', $assets[self::POS_END]) . $this->endNode;
            $endNode    = str_replace('/', '\/', $this->endNode);
            $data       = preg_replace( "/$endNode/i", $assetEnd, $data, 1 , $count);

            // 没找到节点，注入失败时，直接加入末尾位置
            if ($count==0) {
                $data = $data.$assetEnd;
            }
        }

        unset($bodyNode,$headNode,$endNode,$assetHead,$assetBody,$assetEnd,$count);

        return $data;
    }

////////////////////////////// The output asset link //////////////////////////////

    /**
     * 手动输出指定位置的资源
     */
    public function dumpHeadAssets()
    {
        $this->dump(self::POS_HEAD);
    }

    public function dumpBodyAssets()
    {
        $this->dump(self::POS_BODY);
    }

    public function dumpFootAssets()
    {
        $this->dump(self::POS_END);
    }

    public function dumpEndAssets()
    {
        $this->dumpFootAssets();
    }

    /**
     * @param string $pos
     */
    public function dump($pos = self::POS_HEAD)
    {
        $assets = $this->getAssetsByPos($pos);

        foreach ((array)$assets as $link) {
            echo $link;
        }
    }

/////////////////////////////////////////// class attr ///////////////////////////////////////////

    /**
     * Gets the value of assets.
     * @return mixed
     */
    public function getAssets()
    {
        return $this->assets;
    }

    /**
     * @param string $pos
     * @return array
     */
    public function getAssetsByPos($pos = self::POS_HEAD)
    {
        if (isset( $this->assets[$pos])) {
            return $this->assets[$pos];
        }

        return [];
    }

    /**
     * Sets the 资源基础URL
     * @param string $baseUrl
     * @return $this
     */
    public function setBaseUrl($baseUrl)
    {
        if ($baseUrl) {
            if ( UrlHelper::isUrl($baseUrl) ) {
                $this->baseUrl = rtrim($baseUrl, '/') .'/';
            } else {
                $this->baseUrl = rtrim($baseUrl, '/') .'/';
            }
        }

        return $this;
    }

    /**
     * Gets the 资源基础URL
     * @return string
     */
    public function getBaseUrl()
    {
        return $this->baseUrl;
    }

    /**
     * Gets the $baseUrl 对应的物理路径
     *
     * @return string
     */
    public function getBasePath()
    {
        return $this->basePath;
    }

    /**
     * Sets the $baseUrl 对应的物理路径用于资源是否存在验证
     * @example
     * $baseUrl: '/static/'
     * $basePath: 'D:/www/web/static/'.
     *
     * @param string $basePath the base path
     *
     * @return self
     */
    protected function setBasePath($basePath)
    {
        if (is_dir($basePath)) {
            $this->basePath = $basePath;
        }

        return $this;
    }
}// end class AssetManager