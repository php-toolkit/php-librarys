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
 * - 允许设定加载位置
 * - 自动注入到HTML中指定位置
 *
 * Class AssetManager
 * @package inhere\librarys\asset
 */
class AssetManager extends StdBase
{
    /**
     * asset bag list
     * @var array
     *
     * e.g:
     *
     * $bags  = [
     *  self::POS_BODY => [
     *
     *  ],
     *  self::POS_END => [
     *      ''
     *  ],
     * ]
     */
    protected $bags  = []; //static

    /**
     * asset file list
     * @var array
     */
    protected $files = [];

    /**
     * asset code list
     * css or js code
     * @var array
     */
    protected $codes   = []; //

    /**
     * @var bool
     */
    protected $mergeCssCode = true;

    /**
     * @var bool
     */
    protected $mergeJsCode  = true;

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

    public $cdn = [
        'jquery'    => 'http://libs.useso.com/js/jquery/2.1.0/jquery.min.js'
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

    const POS_HEAD      = 1;       # 在解析视图数据时放到</head>之前的位置
    const POS_BODY      = 2;       # 在解析视图数据时放到<body>之后的位置
    const POS_END       = 3;       # 在解析视图数据时放到</body>之前的位置

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
     * register named asset
     *
     * e.g.
     *
     * $manager->loadAsset('homePage',[
     *   'css' => [
     *          'xx/zz.css',
     *      ],
     *   'js' => [
     *          'xx/zz.css',
     *      ],
     *   'depends'    => [''] // 当前资源的依赖. 依赖资源会在当前资源之前加载
     *   'cssOptions' => [],
     *   'jsOptions'  => [],
     * ]);
     *
     * @param array|AssetBag $name define zhe asset name. 当前注册资源添加命名标记
     * @param array $config 要添加的资源以及相关选项
     * @return $this
     */
    public function loadAsset($name, array $config = [])
    {
        if ($name instanceof AssetBag) {
            $this->bags[$name->getName()] = $name;
        } else {
            $asset = new AssetBag($config);
            $asset->setName($name);

            $this->bags[$asset->getName()] = $asset;
        }

        return $this;
    }

    /**
     * [addJsFile 注册添加 javascript 文件
     * @param string|array $asset
     * @param array $options
     * @param null|string $key
     * @return mixed
     */
    public function addJsFile($asset, array $options = [], $key = null)
    {
        if ( !isset($options['position']) ) {
            $options['position'] = self::POS_END;
        }

        return $this->loggingFileAsset($asset, self::ASSET_JS_FILE , $options, $key);
    }

    /**
     * [addCssFile 注册添加 css style 文件
     * @param string|array $asset
     * @param array $options
     * @param null|string $key
     * @return mixed
     */
    public function addCssFile($asset, array $options = [], $key = null)
    {
        if ( !isset($options['position']) ) {
            $options['position'] = self::POS_HEAD;
        }

        return $this->loggingFileAsset($asset, self::ASSET_CSS_FILE, $options, $key);
    }

    /**
     * [loggingAsset description]
     * @param  mixed $assets
     * @param  string $type
     * @param array $options
     * @param null|string $key
     * @return AssetManager
     */
    protected function loggingFileAsset($assets, $type=self::ASSET_CSS_FILE, array $options = [], $key = null)
    {
        if (!$assets) {
            throw new InvalidArgumentException('The 1th param [$assets] is can\'t empty.');
        }

        $pos = trim($options['position']);
        $this->checkTypeAndPosition($type, $pos);

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
     * [addJs 注册添加 javascript 内容到视图数据]
     * @param string $scriptCode
     * @param array $options
     * @return mixed
     */
    public function addJs($scriptCode, array $options = [])
    {
        if ( !isset($options['position']) ) {
            $options['position'] = self::POS_END;
        }

        return $this->loggingCodeAsset($scriptCode, self::ASSET_JS, $options);
    }

    /**
     * [addCss 注册添加 css style code内容到视图数据]
     * @param $styleCode
     * @param array $options
     * @return mixed
     */
    public function addCss($styleCode, array $options = [])
    {
        if ( !isset($options['position']) ) {
            $options['position'] = self::POS_HEAD;
        }

        return $this->loggingCodeAsset($styleCode, self::ASSET_CSS, $options);
    }


    /**
     * @param $assets
     * @param string $type
     * @param array $options
     * @return $this
     */
    protected function loggingCodeAsset($assets, $type=self::ASSET_CSS_FILE, array $options = [])
    {
        return $this;
    }

    protected function checkTypeAndPosition($type, $pos)
    {
        $positions = $this->getPositions();
        $types     = $this->getAssetTypes();

        if ( !in_array($pos, $positions ) ) {
            throw new InvalidArgumentException('资源注册位置允许设置 ['.implode(', ', $positions).'] 中的一个。');
        }

        if ( !in_array($type, $types ) ) {
            throw new InvalidArgumentException('资源类型可选 ['.implode(', ', $types).'] 中的一个。');
        }
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
                return Html::css($asset, $attrs);
                break;
            case self::ASSET_CSS:
                return Html::style($asset, $attrs);
                break;
        }

        return '';
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

////////////////////////////// inject Assets to HTML //////////////////////////////

    /**
     * 自动注入资源到指定的位置
     *
     * - 在渲染好html后,输出html字符之前调用此方法
     * `$html = $manager->injectAssets($html);`
     *
     * @param string $html html document string
     * @return mixed|string [type]
     */
    public function injectAssets($html)
    {
        $html = trim($html);

        if ( !($assets = $this->assets) ) {
            return $html;
        }

        if (!empty($assets[self::POS_BODY])) {

            $assetBody  = $this->bodyNode . implode('', $assets[self::POS_BODY]);
            $bodyNode   = str_replace('/', '\/', $this->bodyNode);
            $html       = preg_replace( "/$bodyNode/i", $assetBody, $html, 1 , $count);

            // 没找到节点，注入失败时，直接加入开始位置
            if ($count==0) {
                $html = $assetBody.$html;
            }
        }

        if (!empty($assets[self::POS_HEAD])) {
            $assetHead= implode('', $assets[self::POS_HEAD]) . $this->headNode;
            $headNode = str_replace('/', '\/', $this->headNode);
            $html     = preg_replace( "/$headNode/i", $assetHead, $html, 1 , $count);

            if ($count==0) {
                $html = $assetHead.$html;
            }
        }

        if (!empty($assets[self::POS_END])) {

            $assetEnd   = implode('', $assets[self::POS_END]) . $this->endNode;
            $endNode    = str_replace('/', '\/', $this->endNode);
            $html       = preg_replace( "/$endNode/i", $assetEnd, $html, 1 , $count);

            // 没找到节点，注入失败时，直接加入末尾位置
            if ($count==0) {
                $html = $html.$assetEnd;
            }
        }

        unset($bodyNode,$headNode,$endNode,$assetHead,$assetBody,$assetEnd,$count);

        return $html;
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