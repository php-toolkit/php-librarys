<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2016/5/3
 * Time: 13:11
 */

namespace inhere\tools\html;

use inhere\tools\exceptions\InvalidConfigException;

/**
 * Class PagingBase
 * @package inhere\tools\html
 */
class PagingBase
{
    /**
     * 总的页数
     * @var int
     */
    protected $pageTotal = 0;      //

    /**
     * 第一个数字按钮,当前页数减去偏移页数
     * @var int
     */
    protected $firstNumBtn = 0;

    /**
     * 最后一个数字按钮
     * @var int
     */
    protected $lastNumBtn = 0;

    /**
     * 偏移页数 floor($this->btnNum/2)
     * @var int
     */
    protected $offsetPage;

    /**
     * 上一页
     * @var int
     */
    protected $prevPage;

    /**
     * 下一页
     * @var int
     */
    protected $nextPage;

    /**
     * html string
     * @var array
     */
    protected $html = [];

    /**
     * 参数配置
     * @var array
     */
    public $options   = [
        'page'     => 1,  // 当前页码
        'pageSize' => 10, // 每页显示记录数
        'total'    => 0,   // 总的记录数
        'btnNum'   => 5,    // 显示数字按钮个数
        'ext'      => 'html',// url地址后缀
        'pageKey'  => 'page', // 分页的url参数变量,page @example /post/index?page=6
        'pageUrl'  => '',  // 需要分页的页面url地址
    ];

    /**
     * 文本配置,自定义时 6 个元素尽量配置完整
     * @var array
     */
    public $text = [
        'first'                   => '首页',
        'prev'                    => '上一页',
        'prevs'                   => '« 上%s页', // ...
        'nexts'                   => '下%s页 »', // ...
        'next'                    => '下一页',
        'last'                    => '末页',
        'totalStr'                => ' 共有%s条记录 ',
        'jumpTo'                  => '跳转到'
    ];
    // en
    // public $text = [
    //     'first'                   => 'First',                    // 首页
    //     'prev'                    => 'Prev',                     // 上一页
    //     'prevs'                   => '« ... ',                    // 上 %s 页
    //     'nexts'                   => ' ... »',                    // 下 %s 页'
    //     'next'                    => 'Next',                     // 下一页
    //     'last'                    => 'End',                      // 末页
    //     'totalStr'                   => 'A total of %s records',    //  共有 %s 条记录
    //     'jumpTo'                  => 'Jump to'                   // 跳转到
    // ];



    public static function make(array $options=[], array $text=[])
    {
        return new static($options, $text);
    }

    /**
     * @param array $options
     * @param array $text
     */
    public function __construct(array $options=[], array $text=[])
    {
        $this->handleOptions($options, $text);

        // 处理中间数字分页按钮的逻辑
        $this->handleLogic();
    }

    protected function handleOptions($options, $text)
    {
        if ( !isset($options['total']) ) {
            throw new InvalidConfigException('请传入必要的参数 total ');
        }

        $this->options = array_merge($this->options,$options);

        // foreach ($this->options as $key => $value) {
        //     if (property_exists($this, $key)) {
        //         $this->$key = $value;
        //     }
        // }

        //文本配置
        if ( $text ) {
            $this->text  = array_merge($this->text, $text);
        }

        return $this;
    }

    // 处理当前页面URL
    private function getCurrentPageUrl()
    {
        $url = trim($_SERVER['REQUEST_URI'],'/&?');
        $pos = strpos($url,'?');

        // 页面URL有无 ? (问号)
        $hasMark = $pos !== false;

        $var = $this->getOption('pageKey');

        // todo 暂不处理添加 扩展名
        if (strpos($url,'&')===false) {
            $this->pageKey       = "/{$var}/";
            $url = preg_replace("/($var\/\d+)[\/]?/i", '', $url);
            $url = rtrim($url,'/');
        } else {
            $url = preg_replace("/($var=\d+)[\&]?/i", '', $url);

            if ($hasMark == true) {
                $this->pageKey  = substr($url, -1)=='?' ? "?{$var}=" : "&{$var}=";
            } else {
                $this->pageKey  = "?{$var}=";
            }

            $url = trim($url,'&?');
        }

        $this->options['pageUrl'] = $url;  //$url str_replace('search', replace, $url);

    }

    public function getUrl($pageNum='')
    {
        return $this->options['pageUrl'].$this->options['pageKey'].$pageNum;
    }

    /*********************************************************************************
     * handle logic
     *********************************************************************************/

    //处理中间数字分页按钮的逻辑
    protected function handleLogic()
    {
        $page = $this->getOption('page', 1);
        $btnNum = $this->getOption('btnNum', 5);

        // 计算
        $this->pageTotal   = ceil($this->getOption('total')/$this->getOption('pageSize'));
        $this->offsetPage  = floor($btnNum/2);//偏移页数
        $this->prevPage    = $page - 1; // 上一页
        $this->nextPage    = $page + 1; // 下一页
        $this->firstNumBtn = $page - $this->offsetPage; //第一个数字按钮,当前页数减去偏移页数;
        $this->lastNumBtn  = $page + $this->offsetPage; //最后一个数字按钮

        //当第一个数字按钮小于1时；
        if ($this->firstNumBtn < 1) {
            $this->firstNumBtn = 1;
            $this->lastNumBtn  = $btnNum;
        }

        //当最后一个数字按钮大于总页数时；通常情况下
        if ($this->lastNumBtn > $this->pageTotal) {
            $this->lastNumBtn  = $this->pageTotal;
            $this->firstNumBtn = $this->pageTotal - $btnNum + 1;
        }

        //当总页数小于翻页的数字按钮个数时；
        if ($btnNum > $this->pageTotal) {
            $this->lastNumBtn  = $this->pageTotal;
            $this->firstNumBtn = 1;
        }
    }


    /*********************************************************************************
     * getter/setter
     *********************************************************************************/

    /**
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * @param $key
     * @param mixed $default
     * @return mixed
     */
    public function getOption($key, $default=null)
    {
        return isset($this->options[$key]) ? $this->options[$key] : $default;
    }

    /**
     * @return string
     */
    public function toString()
    {
        return implode(' ', $this->html);
    }

    /**
     * @return array
     */
    public function getHtml()
    {
        return $this->html;
    }

    /**
     * @return array
     */
    public function getText()
    {
        return $this->text;
    }

    /**
     * @return array
     */
    public function getData()
    {
        return [
            'pageTotal'   => $this->pageTotal,
            'offsetPage'  => $this->offsetPage,
            'prevPage'    => $this->prevPage,
            'nextPage'    => $this->nextPage,
            'firstNumBtn' => $this->firstNumBtn,
            'lastNumBtn'  => $this->lastNumBtn,
        ];
    }

    /**
     * @param $key
     * @param mixed $default
     * @return mixed
     */
    public function get($key, $default=null)
    {
        return property_exists($this, $key) ? $this->$key : $default;

        // return isset($this->data[$key]) ? $this->data[$key] : $default;
    }

}