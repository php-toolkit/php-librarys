<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 16/8/26
 * Time: 上午11:11
 */

namespace inhere\library\asset;

use inhere\library\helpers\ObjectHelper;

/**
 * 命名资源 -- 保存了指定名称的一组资源 (reference Yii2 AssetBundle)
 *
 * AssetManager::loadAsset($name, $assets)
 *
 * Class AssetBag
 * @package inhere\library\asset
 */
class AssetBag
{
    /**
     * asset map name
     * @var string
     */
    protected $name;

    /**
     * @var string the directory that contains the source asset files for this asset bundle.
     * A source asset file is a file that is part of your source code repository of your Web application.
     *
     * You must set this property if the directory containing the source asset files is not Web accessible.
     * By setting this property, [[AssetManager]] will publish the source asset files
     * to a Web-accessible directory automatically when the asset bundle is registered on a page.
     *
     * If you do not set this property, it means the source asset files are located under [[basePath]].
     *
     * You can use either a directory or an alias of the directory.
     * @see $publishOptions
     */
    public $sourcePath;

    /**
     * @var string the Web-accessible directory that contains the asset files in this bundle.
     *
     * If [[sourcePath]] is set, this property will be *overwritten* by [[AssetManager]]
     * when it publishes the asset files from [[sourcePath]].
     *
     * You can use either a directory or an alias of the directory.
     */
    public $basePath;

    /**
     * @var string the base URL for the relative asset files listed in [[js]] and [[css]].
     *
     * If [[sourcePath]] is set, this property will be *overwritten* by [[AssetManager]]
     * when it publishes the asset files from [[sourcePath]].
     *
     * You can use either a URL or an alias of the URL.
     */
    public $baseUrl;

    /**
     * @var array
     */
    public $css = [];

    /**
     * @var array
     */
    public $js = [];

    /**
     * @var array
     */
    public $depends = [];

    /**
     * @var array the options that will be passed to [[View::registerJsFile()]]
     * when registering the JS files in this bundle.
     */
    public $jsOptions = [];

    /**
     * @var array the options that will be passed to [[View::registerCssFile()]]
     * when registering the CSS files in this bundle.
     */
    public $cssOptions = [];

    /**
     * @var array the options to be passed to [[AssetManager::publish()]] when the asset bundle
     * is being published. This property is used only when [[sourcePath]] is set.
     */
    public $publishOptions = [];

    public function __construct(array $config = [])
    {
        ObjectHelper::loadAttrs($this, $config);
    }

    /**
     * @return string
     */
    public function getName()
    {
        if (!$this->name) {
            $this->name = static::class;
        }

        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = trim($name);
    }


}
