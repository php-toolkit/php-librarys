<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017-10-19
 * Time: 9:12
 */

namespace Inhere\Library\Web;

/**
 * Trait ViewRendererTrait
 * @package Inhere\Library\Web
 */
trait ViewRendererTrait
{
    /**
     * getRenderer
     * @return ViewRenderer
     */
    abstract public function getRenderer();

    /**
     * @param string $view
     * @return string
     */
    protected function resolveView(string $view)
    {
        return $view;
    }

    /*********************************************************************************
     * view method
     *********************************************************************************/

    /**
     * @param string $view
     * @param array $data
     * @param null|string $layout
     * @return string
     */
    public function render(string $view, array $data = [], $layout = null)
    {
        return $this->getRenderer()->render($this->resolveView($view), $data, $layout);
    }

    /**
     * @param string $view
     * @param array $data
     * @return string
     */
    public function renderPartial($view, array $data = [])
    {
        return $this->getRenderer()->fetch($this->resolveView($view), $data);
    }

    /**
     * @param string $string
     * @param array $data
     * @return string
     */
    public function renderContent($string, array $data = [])
    {
        return $this->getRenderer()->renderContent($string, $data);
    }

}