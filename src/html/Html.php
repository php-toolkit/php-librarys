<?php
/**
 * Created by sublime 3.
 * Auth: Inhere
 * Date: 14-6-28
 * Time: 10:35
 * Uesd: 主要功能是 html 标签元素创建
 */
namespace inhere\tools\html;


use inhere\tools\helpers\ArrHelper;

class Html
{
    public static $buildedTag;

	// Independent lables
    public static $aloneTags = [
        'area','br','base','col','frame','hr','img','input','link','mate' ,'param'
    ];

    // closing tag | Ditags
    public static $ditags = [];

    public static $eleAttr = [ 'id', 'class', 'style', 'type', 'href', 'src', ];

    /**
     * @param $name
     * @return bool
     */
    public static function isAloneTag($name)
    {
        return in_array(trim($name), static::$aloneTags);
    }

    /**
     * style tag
     * @param  string $content
     * @return string
     */
    public static function style($content,  array $attrs=[])
    {
        $attrs = array_merge( ['type' =>"text/css"], $attrs );

        return static::tag('style', PHP_EOL . trim($content) . PHP_EOL,$attrs);
    }

    /**
     * link tag
     * @param $href
     * @param array $attrs
     * @return string
     */
    public static function link($href,  array $attrs=[])
    {
        $attrs = array_merge(
            [
                'type' => "text/css",
                'rel'  => 'stylesheet',
                'href' => $href,
            ],
            $attrs );

        return static::tag('link',null,$attrs);
    }

    public static function cssLink($href,  array $attrs=[])
    {
        return static::link($href, $attrs);
    }

    /**
     * javascript tag
     * @param  string $content
     * @param array $attrs
     * @return string
     */
    public static function scriptCode($content=null, array $attrs=[])
    {
        $attrs = array_merge( array('type' => 'text/javascript'), $attrs );

        return static::tag('script',  PHP_EOL . trim($content) . PHP_EOL,$attrs);
    }

    /**
     * javascript tag
     * @param  string $src
     * @param array $attrs
     * @return string
     */
    public static function script($src, array $attrs=[])
    {
        $attrs = array_merge(
            [
                'type' => 'text/javascript',
                'src' => $src
            ],
            $attrs );

        return static::tag('script',null,$attrs);
    }

    public static function siteIcon($url)
    {
        return <<<EOF
    <link rel="icon" href="$url" type="image/x-icon"/>
    <link rel="shortcut icon" href="$url" type="image/x-icon"/>
EOF;
    }

    public static function a($content, $url, array $attrs=[])
    {
        $url   = $url ? : 'javascript:void(0);';

        return static::tag('a', $content, array_merge([
            'href' => $url,
            'title'=> $content,
        ], $attrs) );
    }

    /**
     * @param $src
     * @param array $attrs
     * @return string
     * @internal param string $alt
     */
    public static function img($src, array $attrs=[])
    {
        $newAttrs = array_merge(['src'=>$src], $attrs);

        return static::tag('img',null,$newAttrs);
    }

    /**
     * @param string $content
     * @param array $attrs
     * @return string
     * @internal param string $type
     */
    public static function button($content,  array $attrs=[])
    {
        $attrs = array_merge(['type'=>'button'], $attrs);

        $button = static::tag('button',$content, $attrs);

        return $button;
    }

    /**
     * @param string $type
     * @param array $attrs
     * @return string
     * @internal param string $content
     */
    public static function input($type='text', array $attrs=[])
    {
        $attrs = array_merge(['type'=>$type], $attrs);

        $input = static::tag('input',null, $attrs);

        return $input;
    }

//////////////////////////////////////// form tag ////////////////////////////////////////

    public static function startForm($action='', $method = 'post', array $attrs=[])
    {
        $attrs = array_merge( [
                'action' => $action,
                'method' => $method,
            ], $attrs );

        return static::startTag('form', $attrs);
    }

    public static function endForm()
    {
        return static::endTag('form');
    }

//////////////////////////////////////// create tag ////////////////////////////////////////

    //Independent element
    /**
     * @param $name
     * @param string $content
     * @param array $attrs
     * @return string
     */
    public static function tag($name, $content='', array $attrs=[])
    {
        if ( !$name = strtolower(trim($name)) ) {
            return '';
        }

        if ( isset($attrs['content']) ) {
            $content = $attrs['content'];
            unset($attrs['content']);

        } elseif ( isset($attrs['text']) ) {
            $content = $attrs['text'];

            unset($attrs['text']);
        }

        $eleString = static::startTag($name, $attrs) . $content;
        $eleString .= static::isAloneTag($name) ? "\n" : static::endTag($name);

        return $eleString;
    }

    /**
     * 开始标签
     * @param  string $name
     * @param array $attrs
     * @return string
     */
    public static function startTag($name, array $attrs=[])
    {
        return sprintf("\n<%s %s>", strtolower(trim($name)), static::_buildAttr($attrs));
    }

    /**
     * @param $name
     * @return string
     */
    public static function closeTag($name)
    {
        return static::endTag($name);
    }

    /**
     * 结束标签
     * @param  string$name
     * @return string
     */
    public static function endTag($name)
    {
        return '</'.strtolower(trim($name)).">\n";
    }

    /**
     * 属性添加
     * @param string $attr
     * @param string $value
     * @return string
     */
    static protected function _buildAttr($attr, $value='')
    {
        if ( is_string($attr) ) {

            if (is_bool($value)) {
                $value = $value ? 'true' : 'false';
            }

            return "{$attr}=\"{$value}\"";
        }

        if (is_array($attr)) {
            $attrs = [];

            foreach ($attr as $name => $val) {
                $attrs[] = static::_buildAttr($name,$val);
            }

            return implode(' ', $attrs);
        }

        return '';
    }

//////////////////////////////////////// other ////////////////////////////////////////

    /**
     * Encodes special characters into HTML entities.
     * @param string $text data to be encoded
     * @return string the encoded data
     * @see http://www.php.net/manual/en/function.htmlspecialchars.php
     */
    public static function encode($text, $charset= 'utf-8')
    {
        return htmlspecialchars($text,ENT_QUOTES, 'utf-8');
    }

    /**
     * This is the opposite of {@link encode()}.
     * @param string $text data to be decoded
     * @return string the decoded data
     * @see http://www.php.net/manual/en/function.htmlspecialchars-decode.php
     */
    public static function decode($text)
    {
        return htmlspecialchars_decode($text,ENT_QUOTES);
    }
    /**
     * @form yii1
     * @param array $data data to be encoded
     * @return array the encoded data
     * @see http://www.php.net/manual/en/function.htmlspecialchars.php
     */
    public static function encodeArray($data, $charset= 'utf-8')
    {
        $d = [];

        foreach($data as $key=>$value) {
            if (is_string($key)) {
                $key = htmlspecialchars($key,ENT_QUOTES,$charset);
            }

            if (is_string($value)) {
                $value = htmlspecialchars($value,ENT_QUOTES,$charset);
            } elseif (is_array($value)) {
                $value = static::encodeArray($value);
            }

            $d[$key] = $value;
        }

        return $d;
    }
}