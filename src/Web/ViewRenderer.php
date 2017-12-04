<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017-08-30
 * Time: 17:31
 */

namespace Inhere\Library\Web;

use Inhere\Library\Files\File;

/**
 * Class ViewRenderer
 *  Render PHP view scripts into a PSR-7 Response object
 * @package Inhere\Library\Web
 */
class ViewRenderer
{
    use AssetsRendererTrait;

    /**
     * 视图存放基础路径
     * @var string
     */
    protected $viewsPath;

    /** @var null|string 默认布局文件 */
    protected $layout;

    /** @var array Attributes for the view */
    protected $attributes;

    /** @var string Default view suffix. */
    protected $suffix = 'php';

    /** @var array Allowed suffix list */
    protected $suffixes = ['php','tpl','phtml','html'];

    /**
     * in layout file '...<body>{_CONTENT_}</body>...'
     * @var string
     */
    protected $placeholder = '{_CONTENT_}';

    /**
     * constructor.
     * @param string $viewsPath
     * @param string $layout
     * @param array $attributes
     */
    public function __construct($viewsPath = null, $layout = null, array $attributes = [])
    {
        $this->layout = $layout;
        $this->attributes = $attributes;

        $this->setViewsPath($this->viewsPath);
    }

    /********************************************************************************
     * render methods
     *******************************************************************************/

    /**
     * Render a view, if layout file is setting, will use it.
     * throws RuntimeException if view file does not exist
     * @param string $view
     * @param array $data extract data to view, cannot contain view as a key
     * @param string|null|false $layout Override default layout file.
     *  False - will disable use layout file
     * @return string
     * @throws \Throwable
     */
    public function render($view, array $data = [], $layout = null)
    {
        $output = $this->fetch($view, $data);

        // False - will disable use layout file
        if ($layout === false) {
            return $output;
        }

        return $this->renderContent($output, $data, $layout);
    }

    /**
     * @param $view
     * @param array $data
     * @return string
     * @throws \Throwable
     */
    public function renderPartial($view, array $data = [])
    {
        return $this->fetch($view, $data);
    }

    /**
     * @param string $content
     * @param array $data
     * @param string|null $layout override default layout file
     * @return string
     * @throws \Throwable
     */
    public function renderBody($content, array $data = [], $layout = null)
    {
        return $this->renderContent($content, $data, $layout);
    }

    /**
     * @param string $content
     * @param array $data
     * @param string|null $layout override default layout file
     * @return string
     * @throws \Throwable
     */
    public function renderContent($content, array $data = [], $layout = null)
    {
        // render layout
        if ($layout = $layout ?: $this->layout) {
            $mark = $this->placeholder;
            $main = $this->fetch($layout, $data);
            $content = preg_replace("/$mark/", $content, $main, 1);
        }

        return $content;
    }

    /**
     * @param $view
     * @param array $data
     * @param bool $outputIt
     * @return string|null
     * @throws \Throwable
     */
    public function include($view, array $data = [], $outputIt = true)
    {
        if ($outputIt) {
            echo $this->fetch($view, $data);
            return null;
        }

        return $this->fetch($view, $data);
    }

    /**
     * Renders a view and returns the result as a string
     * throws RuntimeException if $viewsPath . $view does not exist
     * @param string $view
     * @param array $data
     * @return mixed
     * @throws \Throwable
     */
    public function fetch($view, array $data = [])
    {
        $file = $this->getViewFile($view);

        if (!is_file($file)) {
            throw new \RuntimeException("cannot render '$view' because the view file does not exist. File: $file");
        }

        /*
        foreach ($data as $k=>$val) {
            if (in_array($k, array_keys($this->attributes))) {
                throw new \InvalidArgumentException("Duplicate key found in data and renderer attributes. " . $k);
            }
        }
        */
        $data = array_merge($this->attributes, $data);

        try {
            ob_start();
            $this->protectedIncludeScope($file, $data);
            $output = ob_get_clean();
        } catch (\Throwable $e) { // PHP 7+
            ob_end_clean();
            throw $e;
        }

        return $output;
    }

    /********************************************************************************
     * helper methods
     *******************************************************************************/

    /**
     * @param $view
     * @return string
     */
    public function getViewFile($view)
    {
        $view = $this->getRealView($view);

        return File::isAbsPath($view) ? $view : $this->viewsPath . $view;
    }

    /**
     * @param string $file
     * @param array $data
     */
    protected function protectedIncludeScope($file, array $data)
    {
        extract($data, EXTR_OVERWRITE);
        include $file;
    }

    /**
     * @param string $default
     * @return string
     */
    public function getPageTitle(string $default = null)
    {
        return $this->attributes['__pageTitle'] ?? $default;
    }

    /**
     * @param string $title
     * @return $this
     */
    public function setPageTitle(string $title)
    {
        $this->attributes['__pageTitle'] = $title;

        return $this;
    }

    /********************************************************************************
     * getter/setter methods
     *******************************************************************************/

    /**
     * Get the attributes for the renderer
     * @return array
     */
    public function getAttributes()
    {
        return $this->attributes;
    }

    /**
     * Set the attributes for the renderer
     * @param array $attributes
     * @return $this
     */
    public function setAttributes(array $attributes)
    {
        $this->attributes = $attributes;

        return $this;
    }

    /**
     * Set an attribute
     * @param $key
     * @param $value
     * @return $this
     */
    public function setAttribute($key, $value)
    {
        $this->attributes[$key] = $value;

        return $this;
    }

    /**
     * Add an attribute
     * @param $key
     * @param $value
     * @return $this
     */
    public function addAttribute($key, $value)
    {
        if (!isset($this->attributes[$key])) {
            $this->attributes[$key] = $value;
        }

        return $this;
    }

    /**
     * Retrieve an attribute
     * @param string $key
     * @param mixed $default
     * @return array|mixed
     */
    public function getAttribute($key, $default = null)
    {
        if (!isset($this->attributes[$key])) {
            return $default;
        }

        return $this->attributes[$key];
    }

    /**
     * Get the view path
     * @return string
     */
    public function getViewsPath()
    {
        return $this->viewsPath;
    }

    /**
     * Set the view path
     * @param string $viewsPath
     * @return $this
     */
    public function setViewsPath($viewsPath)
    {
        if ($viewsPath) {
            $this->viewsPath = rtrim($viewsPath, '/\\') . '/';
        }

        return $this;
    }

    /**
     * Get the layout file
     * @return string
     */
    public function getLayout()
    {
        return $this->layout;
    }

    /**
     * Set the layout file
     * @param string $layout
     * @return $this
     */
    public function setLayout($layout)
    {
        $this->layout = rtrim($layout, '/\\');

        return $this;
    }

    /**
     * @return string
     */
    public function getPlaceholder(): string
    {
        return $this->placeholder;
    }

    /**
     * @param string $placeholder
     */
    public function setPlaceholder(string $placeholder)
    {
        $this->placeholder = $placeholder;
    }

    /**
     * @param string $view
     * @return string
     */
    protected function getRealView($view)
    {
        $sfx = File::getSuffix($view, true);
        $ext = $this->suffix;

        if ($sfx === $ext || \in_array($sfx, $this->suffixes, true)) {
            return $view;
        }

        return $view . '.' . $ext;
    }

    /**
     * @return string
     */
    public function getSuffix(): string
    {
        return $this->suffix;
    }

    /**
     * @param string $suffix
     */
    public function setSuffix(string $suffix)
    {
        $this->suffix = $suffix;
    }
}
