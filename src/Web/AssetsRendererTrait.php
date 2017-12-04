<?php
/**
 * Created by PhpStorm.
 * User: Inhere
 * Date: 2017-08-30
 * Use : ...
 * File: AssetsRendererTrait.php
 */

namespace Inhere\Library\Web;

/**
 * Class AssetsRendererTrait
 * @package Inhere\Library\Web
 */
trait AssetsRendererTrait
{
    /**
     * @var array
     */
    protected $assets = [];

    /********************************************************************************
     * css files
     *******************************************************************************/

    /**
     * @param string|array $cssFile
     * @param string $key
     * @return $this
     */
    public function addTopCssFile($cssFile, string $key = null)
    {
        return $this->addCssFile($cssFile, 'top', $key);
    }

    /**
     * @param string|array $cssFile
     * @param string $key
     * @return $this
     */
    public function addBottomCssFile($cssFile, string $key = null)
    {
        return $this->addCssFile($cssFile, 'bottom', $key);
    }

    /**
     * @param string|array $cssFile
     * @param string $position
     * @param string $key
     * @return $this
     */
    public function addCssFile($cssFile, string $position = 'top', string $key = null)
    {
        if (\is_array($cssFile)) {
            foreach ($cssFile as $k => $code) {
                $this->addCssFile($code, $position, \is_int($k) ? null : $k);
            }

            return $this;
        }

        if ($key) {
            $this->attributes['__cssFiles:' . $position][$key] = $cssFile;
        } else {
            $this->attributes['__cssFiles:' . $position][] = $cssFile;
        }

        return $this;
    }

    /********************************************************************************
     * js files
     *******************************************************************************/

    /**
     * @param string|array $jsFile
     * @param string $key
     * @return $this
     */
    public function addTopJsFile($jsFile, string $key = null)
    {
        return $this->addJsFile($jsFile, 'top', $key);
    }

    /**
     * @param string|array $jsFile
     * @param string $key
     * @return $this
     */
    public function addBottomJsFile($jsFile, string $key = null)
    {
        return $this->addJsFile($jsFile, 'bottom', $key);
    }

    /**
     * @param string|array $jsFile
     * @param string $position
     * @param string $key
     * @return $this
     */
    public function addJsFile($jsFile, string $position = 'bottom', string $key = null)
    {
        if (\is_array($jsFile)) {
            foreach ($jsFile as $k => $code) {
                $this->addJsFile($code, $position, \is_int($k) ? null : $k);
            }

            return $this;
        }

        if ($key) {
            $this->attributes['__jsFiles:' . $position][$key] = $jsFile;
        } else {
            $this->attributes['__jsFiles:' . $position][] = $jsFile;
        }

        return $this;
    }

    /********************************************************************************
     * css codes
     *******************************************************************************/

    /**
     * @param string|array $cssCode
     * @param string $key
     * @return $this
     */
    public function addTopCss($cssCode, string $key = null)
    {
        return $this->addCss($cssCode, 'top', $key);
    }

    /**
     * @param string|array $cssCode
     * @param string $key
     * @return $this
     */
    public function addBottomCss($cssCode, string $key = null)
    {
        return $this->addCss($cssCode, 'bottom', $key);
    }

    /**
     * @param string|array $cssCode
     * @param string $position
     * @param string $key
     * @return $this
     */
    public function addCss($cssCode, string $position = 'top', string $key = null)
    {
        if (\is_array($cssCode)) {
            foreach ($cssCode as $k => $code) {
                $this->addCss($code, $position, \is_int($k) ? null : $k);
            }

            return $this;
        }

        if ($key) {
            $this->attributes['__cssCodes:' . $position][$key] = $cssCode;
        } else {
            $this->attributes['__cssCodes:' . $position][] = $cssCode;
        }

        return $this;
    }

    /********************************************************************************
     * js codes
     *******************************************************************************/

    /**
     * @param string|array $jsCode
     * @param string $key
     * @return $this
     */
    public function addTopJs($jsCode, string $key = null)
    {
        return $this->addJs($jsCode, 'top', $key);
    }

    /**
     * @param string|array $jsCode
     * @param string $key
     * @return $this
     */
    public function addBottomJs($jsCode, string $key = null)
    {
        return $this->addJs($jsCode, 'bottom', $key);
    }

    /**
     * @param string|array $jsCode
     * @param string $position
     * @param string $key
     * @return $this
     */
    public function addJs($jsCode, string $position = 'bottom', string $key = null)
    {
        if (\is_array($jsCode)) {
            foreach ($jsCode as $k => $code) {
                $this->addJs($code, $position, \is_int($k) ? null : $k);
            }

            return $this;
        }

        if ($key) {
            $this->attributes['__jsCodes:' . $position][$key] = $jsCode;
        } else {
            $this->attributes['__jsCodes:' . $position][] = $jsCode;
        }

        return $this;
    }

    /********************************************************************************
     * dump assets
     *******************************************************************************/

    /**
     * @param bool $echo
     * @return null|string
     */
    public function dumpTopAssets($echo = true)
    {
        return $this->dumpAssets('top', $echo);
    }

    public function dumpBottomAssets($echo = true)
    {
        return $this->dumpAssets('bottom', $echo);
    }

    /**
     * dump Assets
     *
     * @param string $position
     * @param boolean $echo
     * @return string|null
     */
    public function dumpAssets($position = 'top', $echo = true)
    {
        $assetHtml = '';

        /** @var array $files css files */
        if ($files = $this->getAttribute('__cssFiles:' . $position)) {
            foreach ($files as $file) {
                $assetHtml .= '<link href="'. $file .'" rel="stylesheet">' . PHP_EOL;
            }
        }

        /** @var array $codes css codes */
        if ($codes = $this->getAttribute('__cssCodes:' . $position)) {
            $assetHtml .= '<style type="text/css">' . PHP_EOL;
            foreach ($codes as $code) {
                $assetHtml .= $code . PHP_EOL;
            }
            $assetHtml .= '</style>' . PHP_EOL;
        }

        /** @var array $files js files */
        if ($files = $this->getAttribute('__jsFiles:' . $position)) {
            foreach ($files as $file) {
                $assetHtml .= '<script src="'. $file .'"></script>' . PHP_EOL;
            }
        }

        /** @var array $codes js codes */
        if ($codes = $this->getAttribute('__jsCodes:' . $position)) {
            $jsCode = '';

            foreach ($codes as $code) {
                $jsCode .= $code . PHP_EOL;
            }

            if ($jsCode) {
                $assetHtml .= "<script type=\"text/javascript\">\n{$jsCode}</script>\n";
            }
        }

        if (!$echo) {
            return $assetHtml;
        }

        // echo it.
        if ($assetHtml) {
            echo '<!-- dumped assets -->' . PHP_EOL . $assetHtml;
        }

        return null;
    }
}
