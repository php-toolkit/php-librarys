<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 16/8/25
 * Time: 下午9:53
 */

namespace inhere\librarys\asset;

use inhere\librarys\exceptions\InvalidArgumentException;
use inhere\librarys\StdBase;

/**
 * 资源发布 -- 将资源发布到可访问目录(e.g. from vendor to web dir)
 * Class AssetPublisher
 * @package inhere\librarys\asset
 */
class AssetPublisher extends StdBase
{
    /**
     * asset source path
     * @var string
     */
    protected $sourcePath  = '';

    /**
     * will publish path
     * @var string
     */
    protected $publishPath = '';

    /**
     * @var array
     */
    protected $include = [
        'file' => ['README.md'],
        'ext' => [
            'js','css',
            'ttf','svg', 'eot', 'woff', 'woff2',
            'png', 'jpg', 'jpeg', 'gif', 'ico',
        ],
        'dir' => [], // ['dist'],
    ];

    /**
     * @var array
     */
    protected $exclude = [
        'file' => '.gitignore',
        // 'ext' => ['swp', 'json'],
        'dir' => ['.git' , 'src'],
    ];

    public $publishAssets = [];

    /**
     * @var array
     */
    public $regex = [
        'cssJs' => '/.\.(css|js)$/i',
        'css' => '/.\.css$/i',
        'js' => '/.\.js$/i',
        'min' => '/.[-.]min\.(css|js)$/i',

        'font' => '/.\.(ttf|svg|eot|woff|woff2)$/i',
        'img' => '/.\.(png|jpg|jpeg|gif|ico)$/i',
    ];

    /**
     * @param mixed $path
     * @return $this
     */
    public function source($path)
    {
        $fullPath = $path[0] === '/' || file_exists($path) ?
                $path :
                $this->sourcePath . '/' . trim($path, '/\\ ');

        if ( is_file($fullPath) ) {
            $this->publishAssets[$path] = $fullPath;
        } elseif (is_dir($fullPath)) {
            $this->publishAssets[$path] = $this->collectFiles($fullPath);
        } else {
            throw new InvalidArgumentException('the param must be is a file or dir path.');
        }

        return $this;
    }

    /**
     * target path is {@see $publishPath} + $path ( is param of the method `source($path)` )
     * @param bool|false $replace
     */
    public function publish($replace = false)
    {

    }

    /**
     * @param string $dir change publish dir
     *
     * if $dir is relation path
     *      target path is  {@see $publishPath} + $dir
     * if $dir is absolute path
     *      target path is $dir
     * @param bool|false $replace
     */
    public function publishTo($dir, $replace = false)
    {

    }

    /**
     * @param $path
     * @return array
     */
    protected function collectFiles($path)
    {

    }

}