<?php
/**
 * Created by PhpStorm.
 * User: Inhere
 * Date: 2016/4/23 0023
 * Time: 10:22
 */

namespace inhere\librarys\console;

/**
 * Class Output
 * @package inhere\librarys\console
 */
class Output
{
    /**
     * 正常输出流
     * Property outStream.
     */
    protected $outputStream = STDOUT;

    /**
     * 错误输出流
     * Property errorStream.
     */
    protected $errorStream = STDERR;

    /**
     * 控制台窗口(字体/背景)颜色添加处理
     * window colors
     * @var Color
     */
    protected $color;

    /**
     * @return Color
     */
    public function getColor()
    {
        if (!$this->color) {
            $this->color = new Color;
        }

        return $this->color;
    }

    /**
     * @param array|string $messages
     * @param string $type
     * @param string|array $style
     */
    public function block($messages, $type = 'INFO', $style='info')
    {
        $messages = is_array($messages) ? array_values($messages) : array($messages);

        // add type
        if (null !== $type) {
            $messages[0] = sprintf('[%s] %s', $type, $messages[0]);
        }

        $text = implode(PHP_EOL, $messages);
        $color = $this->getColor();

        if (is_string($style) && $color->hasStyle($style)) {
            $text = "<{$style}>$text</{$style}>";
        }

        $this->write($text);
    }

    /**
     * @param string $text
     * @param bool $nl
     * @return $this
     */
    public function write($text = '', $nl = true)
    {
        $text = $this->getColor()->format($text);

        fwrite($this->outputStream, $text . ($nl ? "\n" : null));

        return $this;
    }

    /**
     * Write a string to standard error output.
     * @param string $text
     * @param   boolean $nl True (default) to append a new line at the end of the output string.
     * @return $this
     */
    public function err($text = '', $nl = true)
    {
        $text = $this->getColor()->format($text);

        fwrite($this->errorStream, $text . ($nl ? "\n" : null));

        return $this;
    }

    /**
     * @return bool
     */
    public function supportColor()
    {
        return ConsoleHelper::isSupportColor();
    }

    /**
     * getOutStream
     */
    public function getOutputStream()
    {
        return $this->outputStream;
    }

    /**
     * setOutStream
     * @param $outStream
     * @return $this
     */
    public function setOutputStream($outStream)
    {
        $this->outputStream = $outStream;

        return $this;
    }

    /**
     * Method to get property ErrorStream
     */
    public function getErrorStream()
    {
        return $this->errorStream;
    }

    /**
     * Method to set property errorStream
     * @param $errorStream
     * @return $this
     */
    public function setErrorStream($errorStream)
    {
        $this->errorStream = $errorStream;

        return $this;
    }
}
