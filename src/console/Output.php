<?php
/**
 * Created by PhpStorm.
 * User: Inhere
 * Date: 2016/4/23 0023
 * Time: 10:22
 */

namespace inhere\librarys\console;


use inhere\librarys\StdBase;

/**
 * Class Output
 * @package inhere\librarys\console
 */
class Output extends StdBase
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
     * @var Colors
     */
    protected $colors;

    /**
     * make Colors
     * @param  string     $fg      前景色(字体颜色)
     * @param  string     $bg      背景色
     * @param  array      $options 其它选项
     * @return Colors
     */
    public function makeColors($fg = '', $bg = '', array $options=[])
    {
        $this->colors = new Colors($fg, $bg, $options);

        return $this;
    }

    /**
     * @param Colors $colors
     * @return Colors
     */
    public function setColors(Colors $colors)
    {
        $this->colors = $colors;

        return $this;
    }

    /**
     * @return Colors
     */
    public function getColors()
    {
        if (!$this->colors) {
            $this->colors = new Colors;
        }

        return $this->colors;
    }

    /**
     * @param $messages
     * @param string $type
     * @param string|array $style
     */
    public function block($messages, $type = 'INFO', $style='question')
    {
        $messages = is_array($messages) ? array_values($messages) : array($messages);

        // add type
        if (null !== $type) {
            $messages[0] = sprintf('[%s] %s', $type, $messages[0]);
        }

        $colors = $this->getColors();

        if (is_string($style) && !$colors->hasStyle($style)) {
            $style = '';
        } elseif ( is_array($style) ) {
            $colors->addStyle($style[0], $style[1]);
        }

        $text = implode(PHP_EOL, $messages);
        $text = $this->createStyleTag("[$type]: ".$text, $style);

        $this->out($text);
    }

    /**
     * add style tag for $text
     * all enable color style @see Colors::$styles
     * @param  string     $text
     * @param  string     $colorStyle
     * @return string
     */
    protected function createStyleTag($text, $colorStyle)
    {
        return "<{$colorStyle}>". $text ."</{$colorStyle}>";
    }

    /**
     * use color render text
     * @author inhere
     * @date   2015-10-05
     * @param  string     $text
     * @return string
     */
    public function renderColor($text)
    {
        // at windows CMD , don't handle ...
        if ( !$this->hasColorSupport()) {
            return $text;
        }

        return $this->getColors()->handle($text);
    }

    public function out($text = '', $nl = true)
    {
        $text = $this->getColors()->handle($text);

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
        $text = $this->renderColor($text);

        fwrite($this->errorStream, $text . ($nl ? "\n" : null));

        return $this;
    }

    /**
     * Returns true if STDOUT supports colorization.
     * This code has been copied and adapted from
     * \Symfony\Component\Console\Output\OutputStream.
     * @return boolean
     */
    public function hasColorSupport()
    {
        if (DIRECTORY_SEPARATOR === '\\') {
            return false !== getenv('ANSICON') || 'ON' === getenv('ConEmuANSI');
        }

        if (!defined('STDOUT')) {
            return false;
        }

        return $this->isInteractive(STDOUT);
    }

    /**
     * Returns if the file descriptor is an interactive terminal or not.
     * @param  int|resource $fileDescriptor
     * @return boolean
     */
    public function isInteractive($fileDescriptor)
    {
        return function_exists('posix_isatty') && @posix_isatty($fileDescriptor);
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
