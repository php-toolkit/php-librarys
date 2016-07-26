<?php
/**
 * Created by PhpStorm.
 * User: Inhere
 * Date: 15-4-1
 * Time: 上午10:08
 * Used: create html Element
 * file: Element.php
 */

namespace inhere\librarys\html;

use inhere\librarys\StdBase;
use inhere\librarys\exceptions\InvalidConfigException;

/*

$form = new Element('form');

$form->addAttr('id','test-id');
$form->setAttr('class','test dfdf kkkkk')->appendAttr('class','hhhhh');

$input = $form->addChild('input',null, [ 'value'=>'test-id' , 'name' => 'username', 'type' => 'text'];
$div1 = $form->addChild('div','test-id',['id'=>'div-1']);
$div2->setContent('set content');
$div2 = $form->addChild('div','test-iddf',['id'=>'div-2']);
$div2->addContent('add content');

var_dump((string)$form);

*/
class Element extends StdBase
{
    /**
     * tag name
     * @var string
     */
    protected $name = 'div';

    /**
     * tag attribute
     * e.g. [
     *   'id'    => 'div-1'
     *   'class' => 'class-1 class-2 class-3'
     * ]
     * @var array
     */
    protected $attrs = [];

    /**
     * tag content
     * @var string
     */
    protected $content=null;

    /**
     * current tag's parent element
     * @var null|self
     */
    protected $parent = null;

    /**
     * current tag's child element
     * @var self[]
     */
    protected $childs = [];

    /**
     * 如果当前元素有内容的话，添加子元素位置规则
     * before  -- 添加在内容之前
     * after  -- 添加在内容之后
     * replace -- 替换覆盖掉内容
     */
    protected $addChildPosition = 'replace';

    const BEFORE_TEXT    = 'before';
    const AFTER_TEXT     = 'after';
    const REPLACE_TEXT   = 'replace';

    public function __construct($name=null, $content=null, array $attrs=[])
    {
        $this->name    = $name;
        $this->content = $content;
        $this->attrs   = $attrs;
    }

///////////////////////////////////////// generate element /////////////////////////////////////////

    /**
     * generate element sting
     */
    public function getString()
    {
        if ( !$name = strtolower(trim($this->name)) ) {
            throw new InvalidConfigException('请设置标签元素的名称！');
        }

        $attrString = $this->getAttrs(true);
        $content    = $this->_handleChildAndContent();

        $eleString  = sprintf("\n<{$name}%s>%s", $attrString,$content);
        $eleString  .= $this->isAloneTag($name) ? "\n": "</{$name}>\n";

        // has parent
        if ($parent = $this->parent) {

            if ( $this->isAloneTag($parent->name) ) {
                throw new InvalidConfigException('不能设置单标签元素 '.$parent->name.'为父元素！');
            }

            $parent->setContent($eleString);
            $eleString = $parent->getString();
        }

        unset($name, $attrString, $content, $parent);
        return $eleString;
    }

    public function __toString()
    {
        return $this->getString();
    }

    /**
     * @return null|string
     */
    protected function _handleChildAndContent()
    {
        if ( !($childs = $this->childs) ) {
            return $this->content;
        }

        $content = $this->content;

        // 替换 直接占有 内容的位置
        if ( isset($childs[self::REPLACE_TEXT]) ) {
            $string = '';
            foreach ($childs[self::REPLACE_TEXT] as $child) {
                $string .= rtrim( (string)$child );
            }

            $content = $string . "\n";
// vd($content,-4);
        }

        if ( isset($childs[self::BEFORE_TEXT]) ) {
            $string = '';
            foreach ($childs[self::BEFORE_TEXT] as $child) {
                $string .= rtrim( (string)$child );
            }

            $content = $string . $content;
        }

        if ( isset($childs[self::AFTER_TEXT]) ) {
            $string = '';
            foreach ($childs[self::AFTER_TEXT] as $child) {
                $string .= rtrim( (string)$child );
            }

            $content .= $string . "\n";
        }

        return $content;
    }

    /**
     * @param $name
     * @return bool
     */
    public function isAloneTag($name)
    {
        return Html::isAloneTag($name);
    }


///////////////////////////////////////// parent element /////////////////////////////////////////

    /**
     * @param null $name
     * @param null $content
     * @param array $attrs
     * @return Element
     */
    public function setParent($name=null, $content=null, array $attrs=[])
    {
        if ($name instanceof self) {
            $parent = $name;
        } else {
            $parent = new self($name, $content, $attrs);
        }

        return ($this->parent = $parent);
    }

    /**
     * @return null|Element
     */
    public function getParent()
    {
        return $this->parent;
    }

    public function removeParent()
    {
        $this->parent = null;
    }

///////////////////////////////////////// child element /////////////////////////////////////////

    /**
     * @param null $name
     * @param null $content
     * @param array $attrs
     * @return \ulue\libs\front\Element $child self
     */
    public function addChild($name=null, $content=null, array $attrs=[])
    {
        if ($name instanceof self) {
            $child = $name;
        } else {
            $child = new self($name, $content, $attrs);
        }

        $this->childs[$this->addChildPosition][] = $child;

        return $child;
    }

    /**
     * @param $childs self[]
     * @return $this
     */
    public function setChilds(array $childs)
    {
        $this->childs = [];

        return $this->addChilds($childs);
    }

    /**
     * @param $childs self[]
     * @return $this
     */
    public function addChilds(array $childs)
    {
        foreach ($childs as $child) {

            if ($child instanceof self) {
                $this->childs[$this->addChildPosition][] = $child;
            }
        }

        return $this;
    }

    /**
     * 如果当前元素有内容的话，添加子元素规则
     * before  -- 添加在内容之前
     * after  -- 添加在内容之后
     * replace -- 替换覆盖掉内容
     * @param $value
     * @return $this
     */
    public function setChildPosition($value)
    {
        if (in_array($value, ['before','after','replace'])) {
            $this->addChildPosition = $value;
        }

        return $this;
    }

    /**
     * @return string
     */
    public function getChildPosition()
    {
        return $this->addChildPosition;
    }

///////////////////////////////////////// property /////////////////////////////////////////

    /**
     * @param $value
     * @return $this
     */
    public function setName($value)
    {
        $this->name = $value;

        return $this;
    }

    /**
     * @param $value
     * @return mixed
     */
    public function getName($value)
    {
        return $this->name = $value;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setContent($value)
    {
        $this->content = $value;

        return $this;
    }

    /**
     * @param $value
     * @param string $position
     * @return $this
     */
    public function addContent($value, $position='after')
    {
        if ($position == 'after') {
            $this->content .= $value;
        } else {
            $this->content = $value.$this->content;
        }

        return $this;
    }

    /**
     * @return mixed
     */
    public function getContent()
    {
        if ( $content = $this->getAttr('content') ?: $this->getAttr('text') ) {
            unset($this->attrs['content'],$this->attrs['text']);

            $this->content = $content;
        }

        return $this->content;
    }
///////////////////////////////////////// element attr /////////////////////////////////////////

    /**
     * @param array $value
     * @return $this
     */
    public function setAttrs(array $value)
    {
        $this->attrs = $value;

        return $this;
    }

    /**
     * @param bool $toString
     * @return array|string
     */
    public function getAttrs($toString=false)
    {
        if ( $content = $this->getAttr('content') ?: $this->getAttr('text') ) {
            unset($this->attrs['content'],$this->attrs['text']);

            $this->content = $content;
        }

        if ((bool)$toString) {
            $attrString = '';

            foreach ($this->attrs as $name => $value) {
                $attrString .= " $name=\"$value\"";
            }

            return $attrString;
        }

        return $this->attrs;
    }


    /**
     * @param array $attrs
     * @return $this
     */
    public function addAttrs(array $attrs)
    {
        foreach ($attrs as $name => $val) {
            $this->addAttr($name, $val);
        }

        return $this;
    }

    /**
     * @param $name
     * @return bool
     */
    public function existsAttr($name)
    {
        return isset($this->attrs[trim($name)]);
    }

    /**
     * @param $name
     * @return null
     */
    public function getAttr($name)
    {
        $name  = trim($name);

        return isset($this->attrs[$name]) ? $this->attrs[$name] : null;
    }

    /**
     * @param $name
     * @param $value
     * @return $this
     */
    public function setAttr($name, $value)
    {
        $this->attrs[trim($name)] = trim($value);

        return $this;
    }

    /**
     * 属性添加
     * @param string $name
     * @param string $value
     * @return $this
     */
    public function addAttr($name, $value)
    {
        $name  = trim($name);
        $value = trim($value);

        if ( $value && !$this->existsAttr($name)) {
            $this->attrs[$name] = $value;
        }

        return $this;
    }

    /**
     * 在已有的属性值上追加值
     * e.g. ['class'=>'class-1 class-2']
     *  appendAttr('class', 'class-3') --> ['class'=>'class-1 class-2 class-3']
     * @param string $name
     * @param string $value
     * @param string $separator 追加的值与原有的值之间的分隔符 e.g. 两个class之间的空格
     * @return $this
     */
    public function appendAttr($name, $value, $separator='')
    {
        if ($this->existsAttr($name)) {

            if ($name == 'class') {
                $separator=' ';
            }

            $this->attrs[$name] .= $separator . trim($value);

        } else {
            $this->attrs[trim($name)] = trim($value);
        }

        return $this;
    }

    /**
     * @param $value
     * @return Element
     */
    public function setClass( $value )
    {
        return $this->setAttr('class', $value );
    }

    /**
     * @param $value
     * @return Element
     */
    public function addClass( $value )
    {
        return $this->appendAttr('class', $value, ' ');
    }

    /**
     * @param $name
     * @param null $value
     * @return Element
     */
    public function addStyle( $name, $value=null )
    {
        if ($value) {
            $value = $name.':'.$value;
        }

        return $this->appendAttr('style', $value);
    }

    /**
     * todo unused
     * 属性合并，多用于 class style $arr1 ，$arr2
     *  $arr1 = array(
     *       'class'  =>"navbar-form navbar-left",
     *       'action' =>'index.php/user/add',
     *       'method' =>'post'
     *       );
     *  $arr1 = array(
     *       'class'  =>"navbar-fixed",
     *       'action' =>'index.php/user/edit',
     *       'method' =>'get'
     *       );
     *   --->
     *   returns: array(
     *       'class'  =>"navbar-form navbar-left navbar-fixed",
     *       'action' =>'index.php/user/edit',
     *       'method' =>'get'
     *       );
     * @param  array $old [原属性组]
     * @param mixed $new [传入的新增属性组]
     * @param  array $attrs [需要合并的属性]
     * @return string
     */
    public function attrMerge($old, $new, array $attrs=[])
    {
        if ( !$old && !$new ) {
            return [];
        } else if (!$new) {
            return $old;
        }

        $default = ['class','style'];

        if ($attrs) {
            array_map(function($value) use(&$default) {
                if ( !in_array($value, $default) ) {
                   $default[] = $value;
                }
            }, $attrs);
        }

        $attrs  = $default;
        $merges = [];

        // 交集, 都含有的属性
        $intersectAttrs  = array_keys(array_intersect_key($old,$new));

        foreach ($attrs as $attr) {

            if ( in_array($attr, $intersectAttrs) )
            {
                $merges[$attr] = $old[$attr].' '.$new[$attr];
            }
        }

        return array_merge($old, $new, $merges);
    }


}// end class Element

