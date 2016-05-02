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
    static public $buildedTag;

	//Independent lables
    static public $aloneTags = [
            'area','br','base','col','frame','hr','img','input','link','mate' ,'param'
        ];

    // closing tag | Ditags
    static public $ditags = [];

    static public $eleAttr = [
            'id',
            'class',
            'style',
            'type',
            'href',
            'src',
        ];

    /**
     * @param $name
     * @return bool
     */
    static public function isAloneTag($name)
    {
        $aloneTags = [ 'area','br','base','col','frame','hr','img','input','link','mate' ,'param' ];

        return in_array(trim($name), $aloneTags);
    }

    /**
     * style tag
     * @param  string $content
     * @return string
     */
    static public function style($content,  array $attrs=[])
    {
        $attrs = array_merge( array('type' =>"text/css"), $attrs );

        return self::tag('style', PHP_EOL . trim($content) . PHP_EOL,$attrs);
    }

    /**
     * link tag
     * @param $href
     * @param array $attrs
     * @return string
     */
    static public function link($href,  array $attrs=[])
    {
        $attrs = array_merge(
            [
                'type' => "text/css",
                'rel'  => 'stylesheet',
                'href'  => $href,
            ],
            $attrs );

        return self::tag('link',null,$attrs);
    }

    static public function cssLink($href,  array $attrs=[])
    {
        return self::link($href, $attrs);
    }

    /**
     * javascript tag
     * @param  string $content
     * @param array $attrs
     * @return string
     */
    static public function scriptCode($content=null, array $attrs=[])
    {
        $attrs = array_merge( array('type' => 'text/javascript'), $attrs );

        return self::tag('script',  PHP_EOL . trim($content) . PHP_EOL,$attrs);
    }

    /**
     * javascript tag
     * @param  string $src
     * @param array $attrs
     * @return string
     */
    static public function script($src, array $attrs=[])
    {
        $attrs = array_merge(
            [
                'type' => 'text/javascript',
                'src' => $src
            ],
            $attrs );

        return self::tag('script',null,$attrs);
    }

    static public function siteIcon($url)
    {
        return '<link rel="icon" href="' . $url . '" type="image/x-icon"/>'."\n".
        	 '<link rel="shortcut icon" href="' . $url . '" type="image/x-icon"/>';
    }

    static public function a($content, $url, array $attrs=[])
    {
        $url 	= empty($url) ? 'javascript:void();' : $url;

        $aLink 	= self::tag('a', $content, array_merge(['href'=>$url], $attrs) );

        return $aLink;
    }

    /**
     * @param $src
     * @param array $attrs
     * @return string
     * @internal param string $alt
     */
    static public function img($src, array $attrs=[])
    {
        $newAttrs = array_merge( array('src'=>$src), $attrs);

        return self::tag('img',null,$newAttrs);
    }

    /**
     * @param string $content
     * @param array $attrs
     * @return string
     * @internal param string $type
     */
    static public function button($content,  array $attrs=[])
    {
        $attrs = array_merge(['type'=>'button'], $attrs);

        $button = self::tag('button',$content, $attrs);

        return $button;
    }

    /**
     * @param string $type
     * @param array $attrs
     * @return string
     * @internal param string $content
     */
    static public function input($type='text', array $attrs=[])
    {
        $attrs = array_merge(['type'=>$type], $attrs);

        $input = self::tag('input',null, $attrs );

        return $input;
    }
//////////////////////////////////////// form tag ////////////////////////////////////////

    static public function startForm($action='', $method = 'post', array $attrs=[])
    {
        $attrs = array_merge( [
                'action' => $action,
                'method' => $method,
            ], $attrs );

        return self::startTag('form', $attrs);
    }

    static public function endForm()
    {
        return self::endTag('form');
    }


//////////////////////////////////////// create tag ////////////////////////////////////////

    //Independent element
    /**
     * @param $name
     * @param string $content
     * @param array $attrs
     * @return string
     */
    static public function tag($name, $content='', array $attrs=[])
    {
        $name = strtolower(trim($name));

        if ( !$name ) {
            return '';
        }

        if ( isset($attrs['content']) ) {
            $content = $attrs['content'];
            unset($attrs['content']);

        } elseif ( isset($attrs['text']) ) {
            $content = $attrs['text'];

            unset($attrs['text']);
        }

        $eleString = self::startTag($name, $attrs) . $content;
        $eleString .= self::isAloneTag($name) ? "\n" : self::endTag($name);

        return $eleString;
    }

    /**
     * 开始标签
     * @param  string $name
     * @param array $attrs
     * @return string
     */
    static public function startTag($name, array $attrs=[])
    {
        return sprintf("\n<%s %s>", strtolower(trim($name)), self::_buildAttr($attrs));
    }

    /**
     * @param $name
     * @return string
     */
    static public function closeTag($name)
    {
        return self::endTag($name);
    }

    /**
     * 结束标签
     * @param  string$name
     * @return string
     */
    static public function endTag($name)
    {
        return "</".strtolower(trim($name)).">\n";
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
                $attrs[] = self::_buildAttr($name,$val);
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
    static public function encode($text, $charset= 'utf-8')
    {
        return htmlspecialchars($text,ENT_QUOTES, 'utf-8');
    }

    /**
     * This is the opposite of {@link encode()}.
     * @param string $text data to be decoded
     * @return string the decoded data
     * @see http://www.php.net/manual/en/function.htmlspecialchars-decode.php
     */
    static public function decode($text)
    {
        return htmlspecialchars_decode($text,ENT_QUOTES);
    }
    /**
     * @form yii1
     * @param array $data data to be encoded
     * @return array the encoded data
     * @see http://www.php.net/manual/en/function.htmlspecialchars.php
     */
    static public function encodeArray($data, $charset= 'utf-8')
    {
        $d       =[];

        foreach($data as $key=>$value) {
            if (is_string($key)) {
                $key = htmlspecialchars($key,ENT_QUOTES,$charset);
            }

            if (is_string($value)) {
                $value = htmlspecialchars($value,ENT_QUOTES,$charset);
            } elseif (is_array($value)) {
                $value = self::encodeArray($value);
            }

            $d[$key] = $value;
        }

        return $d;
    }
}