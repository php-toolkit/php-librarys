<?php
/**
 * Created by PhpStorm.
 * User: Inhere
 * Date: 15-4-1
 * Time: 上午10:08
 * Used:
 * file: Color.php
 */

namespace inhere\tools\console;

use inhere\tools\StdBase;

/**
 * Class Colors
 * @package inhere\tools\console
 * @link https://github.com/ventoviro/windwalker-IO
 */
class Colors extends StdBase
{
    /**
     * Flag to remove color codes from the output
     * @var bool
     */
    public $noColors = false;

    /**
     * Regex to match tags
     * @var string
     */
    protected $tagFilter = '/<([a-z=;]+)>(.*?)<\/\\1>/s';

    /**
     * Regex used for removing color codes
     */
    protected static $stripFilter = '/<[\/]?[a-z=;]+>/';

    /**
     * Array of ColorStyle objects
     * @var array[]
     */
    protected $styles = [];

    /**
     * Known colors
     * @var array
     */
    private static $knownColors = [
        'black'   => 0,
        'red'     => 1,
        'green'   => 2,
        'yellow'  => 3,
        'blue'    => 4,
        'magenta' => 5, // 洋红色 洋红 品红色
        'cyan'    => 6, // 青色 青绿色 蓝绿色
        'white'   => 7
    ];

    /**
     * Known styles
     * @var array
     */
    private static $knownOptions = [
        'bold'       => 1,      // 加粗
        'fuzzy'      => 2,      // 模糊(不是所有的终端仿真器都支持)
        'italic'     => 3,      // 斜体(不是所有的终端仿真器都支持)
        'underscore' => 4,      // 下划线
        'blink'      => 5,      // 闪烁
        'reverse'    => 7,      // 颠倒的 交换背景色与前景色
    ];

    /**
     * Foreground base value
     * @var int
     */
    private static $fgBase = 30;

    /**
     * Background base value
     * @var int
     */
    private static $bgBase = 40;

    /**
     * Constructor
     * @param  string     $fg      前景色(字体颜色)
     * @param  string     $bg      背景色
     * @param  array      $options 其它选项
     * @throws  \InvalidArgumentException
     */
    public function __construct($fg = '', $bg = '', array $options = [])
    {
        if ($fg || $bg || $options) {
            $this->addStyle('base', [
                'fgColor' => $fg,
                'bgColor' => $bg,
                'options' => $options
            ]);
        }

        $this->init();
    }

    /**
     * Class constructor
     */
    public function init()
    {
        $this->addPredefinedStyles();
    }

    /**
     * Adds predefined color styles to the Colors styles
     * default primary success info warning danger
     */
    protected function addPredefinedStyles()
    {
        $this->addStyle(
            'default',
            [
                'options' => ['bold','underscore']
            ]
        )->addStyle(
            'primary',
            [
                'bgColor' => 'blue', 'options' => ['bold']
            ]
        )->addStyle(
            'success',
            [
                'bgColor' => 'green', 'options' => ['bold']
            ]
        )->addStyle(
            'info',
            [
                'bgColor' => 'cyan', 'options' => ['bold']
            ]
        )->addStyle(
            'warning',
            [
                'bgColor' => 'yellow', 'options' => ['bold']
            ]
        )->addStyle(
            'danger',
            [
                'fgColor' => 'white', 'bgColor' => 'red', 'options' => ['bold']
            ]
        )->addStyle(
            'comment',
            [
                'fgColor' => 'yellow', 'options' => ['bold']
            ]
        )->addStyle(
            'question',
            [
                'fgColor' => 'black', 'bgColor' => 'cyan'
            ]
        )->addStyle(
            'error',
            [
                'fgColor' => 'white', 'bgColor' => 'red'
            ]
        );
    }

//////////////////////////////////////////// Text Color handle ////////////////////////////////////////////

    /**
     * Strip color tags from a string.
     * @param $string
     * @return mixed
     */
    public static function stripColors($string)
    {
        return preg_replace(static::$stripFilter, '', $string);
    }

    /**
     * Process a string.
     * @param $string
     * @return mixed
     */
    public function handle($string)
    {
        preg_match_all($this->tagFilter, $string, $matches);

        if (!$matches) {
            return $string;
        }

        foreach ($matches[0] as $i => $m) {
            if (array_key_exists($matches[1][$i], $this->styles)) {
                $string = $this->replaceColors($string, $matches[1][$i], $matches[2][$i], $this->styles[$matches[1][$i]]);
            }
            // Custom format
            elseif (strpos($matches[1][$i], '=')) {
                $string = $this->replaceColors($string, $matches[1][$i], $matches[2][$i], $this->fromString($matches[1][$i]));
            }
        }

        return $string;
    }

    /**
     * Replace color tags in a string.
     * @param string $text
     * @param   string $tag The matched tag.
     * @param   string $match The match.
     * @param   array $styles The color style to apply.
     * @return mixed
     */
    protected function replaceColors($text, $tag, $match, array $styles)
    {
        $style   = $this->styleToString($styles);
        $replace = $this->noColors ? $match : "\033[{$style}m{$match}\033[0m";

        return str_replace('<' . $tag . '>' . $match . '</' . $tag . '>', $replace, $text);
    }

///////////////////////////////////////// Attr Color Style /////////////////////////////////////////

    public function setStyles(array $styles)
    {
        $this->styles = $styles;

        return $this;
    }

    public function getStyleList()
    {
        return array_keys($this->styles);
    }
    public function getStyleNames()
    {
        return array_keys($this->styles);
    }

    public function getStyles()
    {
        return $this->styles;
    }

    /**
     * Add a style.
     * @param $name
     * @param array $styleOptions
     * @return $this
     */
    public function addStyle($name, array $styleOptions=[])
    {
        $this->styles[$name] = $this->handleStyle($styleOptions);

        return $this;
    }

    /**
     * @param $name
     * @return null|string
     */
    public function getStyle($name)
    {
        if (!isset($this->styles[$name])) {
            return null;
        }

        return $this->styles[$name];
    }

    public function existsStyle($name)
    {
        return $this->hasStyle($name);
    }
    public function hasStyle($name)
    {
        return isset($this->styles[$name]);
    }

//////////////////////////////////////////// Color Style handle ////////////////////////////////////////////

    /**
     * handle color Style
     * @param  array      $styleOptions 样式设置信息
     *   [
     *       'fgColor' => 'white',
     *       'bgColor' => 'black',
     *       'options' => ['bold', 'underscore']
     *   ]
     * @return array
     */
    public function handleStyle(array $styleOptions = [])
    {
        $style = [
            'fgColor' => 'white',
            'bgColor' => 'black',
            'options' => []
        ];

        $styleOptions = array_merge($style, $styleOptions);
        list($fg, $bg, $options) = array_values($styleOptions);

        if ($fg) {
            if (false === array_key_exists($fg, static::$knownColors)) {
                throw new \InvalidArgumentException(
                    sprintf('Invalid foreground color "%1$s" [%2$s]',
                        $fg, implode(', ', $this->getKnownColors())
                    )
                );
            }

            $style['fgColor'] = static::$fgBase + static::$knownColors[$fg];
        }

        if ($bg) {
            if (false === array_key_exists($bg, static::$knownColors)) {
                throw new \InvalidArgumentException(
                    sprintf('Invalid background color "%1$s" [%2$s]',
                        $bg, implode(', ', $this->getKnownColors())
                    )
                );
            }

            $style['bgColor'] = static::$bgBase + static::$knownColors[$bg];
        }

        foreach ($options as $option) {
            if (false === array_key_exists($option, static::$knownOptions)) {
                throw new \InvalidArgumentException(
                    sprintf('Invalid option "%1$s" [%2$s]',
                        $option,
                        implode(', ', $this->getKnownOptions())
                    )
                );
            }

            $style['options'][] = $option;
        }

        return $style;
    }

    /**
     * Create a color style from a parameter string.
     * @param $string
     * @return string
     */
    public function fromString($string)
    {
        $fg = '';
        $bg = '';
        $options = [];
        $parts = explode(';', $string);

        foreach ($parts as $part) {
            $subParts = explode('=', $part);

            if (count($subParts) < 2) {
                continue;
            }

            switch ($subParts[0]) {
                case 'fg':
                    $fg = $subParts[1];
                    break;

                case 'bg':
                    $bg = $subParts[1];
                    break;

                case 'options':
                    $options = explode(',', $subParts[1]);
                    break;

                default:
                    throw new \RuntimeException('Invalid option');
                    break;
            }
        }

        return $this->styleToString( $this->handleStyle([
            'fgColor' => $fg,
            'bgColor' => $bg,
            'options' => $options
        ]) );
    }

    /**
     * Get the translated color code.
     * @param array $style
     * @return string
     */
    public function styleToString(array $style)
    {
        $values = [];

        isset($style['fgColor']) && $values[] = $style['fgColor'];
        isset($style['bgColor']) && $values[] = $style['bgColor'];

        foreach ($style['options'] as $option) {
            $values[] = static::$knownOptions[$option];
        }

        return implode(';', $values);
    }

    /**
     * Get the known colors.
     * @param bool $onlyName
     * @return array
     */
    public function getKnownColors($onlyName=true)
    {
        return (bool)$onlyName ? array_keys(static::$knownColors) : static::$knownColors;
    }

    /**
     * Get the known options.
     * @param bool $onlyName
     * @return array
     */
    public function getKnownOptions($onlyName=true)
    {
        return (bool)$onlyName ? array_keys(static::$knownOptions) : static::$knownOptions;
    }

    /**
     * Method to get property NoColors
     */
    public function getNoColors()
    {
        return $this->noColors;
    }

    /**
     * Method to set property noColors
     * @param $noColors
     * @return $this
     */
    public function setNoColors($noColors)
    {
        $this->noColors = $noColors;

        return $this;
    }
}
