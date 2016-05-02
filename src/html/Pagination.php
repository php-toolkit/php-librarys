<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 14-5-19
 * Time: 上午11:00
 * 新闻、文章处理分页类
 * $curPage    = \Ioc::getRequest()->getParam('page','1');
 * $pageSize   = \Ioc::getRequest()->getParam('pageSize','3');
 * $start      = ($curPage-1)*$pageSize;
 * $arrBill    = $this->model('bill')->limit($start,$pageSize)->getAll();
 * $pageParam  = [
 *     'pageNum'   =>$curPage,
 *     'pageSize'  =>$pageSize,
 *     'count'     =>$total
 * ];
 * $objPage    = new \ulue\libs\client\Pagination($pageParam);
 *
 *
 *
 *
 */
namespace inhere\tools\html;

use inhere\tools\exceptions\InvalidConfigException;

class Pagination
{

    private $pageTotal;        //总的页数
    private $firstNumBtn;    //第一个数字按钮,当前页数减去偏移页数
    private $lastNumBtn;     //最后一个数字按钮
    private $offsetPage;     //偏移页数 floor($this->numberBtn/2)
    private $prevPage;       //上一页
    private $nextPage;       //下一页

    public $total;      // 总的记录数
    public $pageSize;       //每页显示记录数（@example 新闻、文章条数）
    public $numberBtn;  //显示数字按钮个数
    public $page;           //当前页数
    public $pageKey;        //分页的url参数变量,page @example /post/index?page=6
    public $pageUrl;        // 需要分页的页面url地址

    //参数配置
    public $options   = [
        'page'     => '1',  // current page num
        'pageSize' => '10', // pageSize
        'total'    => '',   // total
        'btnNum'   => 5,    // numberBtn
        'ext'      => 'html',// url地址后缀
        'pageKey'  => 'page'
    ];

    //文本配置,自定义时 6 个元素都必须配置完整
    // cn
    public $text = [
        'first'                   => '首页',
        'prev'                    => '上一页',
        'prevs'                   => '« 上%s页', // ...
        'nexts'                   => '下%s页 »', // ...
        'next'                    => '下一页',
        'last'                    => '末页',
        'countStr'                => ' 共有%s条记录 ',
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
    //     'countStr'                   => 'A total of %s records',    //  共有 %s 条记录
    //     'jumpTo'                  => 'Jump to'                   // 跳转到
    // ];
    public $elements = [
        'box'               => [
              'tag'             => 'ul', //'div>ul',
              'class'           => '',
        ],
        'list'              => [
              'tag'             => 'li',
              'currentClass'    => 'active',
        ],
        'link'              => [
              'tag'             => 'a',
              // 'currentClass'    => 'active',
        ],
    ];


    public static function make(array $options=[], array $text=[], $urlMode=2)
    {
        return new static($options, $text, $urlMode);
    }

    /**
     * @param array $options
     * @param int $urlMode  url模式 1 ex:/post/index/p/6 | 2 ex:c=post&a=index&p=6
     * @param array $text
     */
    public function __construct(array $options=[], array $text=[], $urlMode=2)
    {

        $this->urlMode     = $urlMode !='' ? $urlMode : 1;
        $this->handleOptions($options);

        // 处理中间数字分页按钮的逻辑
        $this->handleLogic();
    }

    protected function handleOptions($options)
    {
        if ( !isset($options['total']) ) {
            throw new InvalidConfigException('请传入必要的参数 total ');
        }

        $this->options = array_merge($this->options,$options);

        foreach ($this->options as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }

        //文本配置
        if ( $text ) {
            $this->text  = array_merge( $this->text, $text);
        }

        return $this;
    }

    // 处理当前页面URL
    private function getCurrentPageUrl()
    {
        $url = trim($_SERVER['REQUEST_URI'],'/&?');

        if (strpos($url,'?')!==false) {
            // 页面URL有无 ? (问号)
            $this->hasMark = true;
            $this->urlMode = 2;
        }

        $var = $this->options['var'];

        // $requestUrl = _ACTION_; todo 暂不处理添加 扩展名
        if ($this->urlMode == 1 && strpos($url,'&')===false) {
            $this->pageKey       = "/{$var}/";
            $url = preg_replace("/($var\/\d+)[\/]?/i", '', $url);
            $url = rtrim($url,'/');
        } else {
            $url = preg_replace("/($var=\d+)[\&]?/i", '', $url);

            if ($this->hasMark == true) {
                $this->pageKey  = substr($url, -1)=='?' ? "?{$var}=" : "&{$var}=";
            } else {
                $this->pageKey  = "?{$var}=";
            }

            $url = StrHelper::trimStr($url,['&','?'],'r');
        }

        $this->pageUrl = $url;  //$url str_replace('search', replace, $url);

    }

    public function getUrl($pageNum='')
    {
        return $this->pageUrl.$this->pageKey.$pageNum;
    }
////////////////////////////////////////// handler paging ///////////////////////////////////////

    //处理中间数字分页按钮的逻辑
    protected function handleLogic()
    {

        // 计算
        $this->pageTotal   = ceil($this->total/$this->pageSize);
        $this->offsetPage  = floor($this->numberBtn/2);//偏移页数
        $this->prevPage    = $this->page - 1; // 上一页
        $this->nextPage    = $this->page + 1; // 下一页
        $this->firstNumBtn = $this->page - $this->offsetPage; //第一个数字按钮,当前页数减去偏移页数;
        $this->lastNumBtn  = $this->page + $this->offsetPage; //最后一个数字按钮

        //当第一个数字按钮小于1时；
        if ($this->firstNumBtn < 1) {
            $this->firstNumBtn  = 1;
            $this->lastNumBtn   = $this->numberBtn;
        }

        //当最后一个数字按钮大于总页数时；通常情况下
        if ($this->lastNumBtn > $this->pageTotal) {
            $this->lastNumBtn   = $this->pageTotal;
            $this->firstNumBtn  = $this->pageTotal - $this->numberBtn+1;
        }

        //当总页数小于翻页的数字按钮个数时；
        if ($this->numberBtn > $this->pageTotal) {
            $this->lastNumBtn   = $this->pageTotal;
            $this->firstNumBtn  = 1;
        }
    }

    //得到回到第一页字符串
    public function getFirstPage()
    {

        // return '<a href="'.$this->getUrl().'" class="number" title="1">'.$this->text['first'].'</a>';
        $first = Html::a($this->text['first'],$this->getUrl(1));

        return $this->hasListElement($first);
    }

    //得到上一页,当前页数大于1时，出现 ‘上一页’ 按钮
    public function getPrevPage()
    {
        if ($this->page > 1) {
            $prev = Html::a($this->text['prev'],$this->getUrl($this->prevPage),['title'=>$this->text['prev']]);

            $prev = $this->hasListElement($prev);
        }

        return isset($prev) ? $prev : '';
    }

    //向上跳转 $this->numberBtn 的页数
    public function getPrevAcrossPages()
    {
        $prevAcrossPages = $this->page - $this->numberBtn;

        if ($prevAcrossPages > 0) {
            $text  = sprintf($this->text['prevs'],$this->numberBtn);
            // return '<a href="'.$this->getUrl($prevAcrossPages).'" class="number" title="'.$text.'">'.$text.'</a>';
            $prevs = Html::a($text,$this->getUrl($prevAcrossPages),['title'=>$text]);
            $prevs = $this->hasListElement($prevs);
        }

        return isset($prevs) ? $prevs: '';
    }

    //得到数字按钮分页字符串
    public function getNumBtnPage($numBtnPage='')
    {
        for($i=$this->firstNumBtn;$i<=$this->lastNumBtn;$i++)
        {

            if ($i==$this->page)
            {
                //给当前页按钮设置额外样式
                // $numBtnPage .='<a href="'.$this->getUrl($i).'" class="number current" title="'.$i.'">'.$i.'</a>';
                $numBtnPage = Html::a($i,'javascript:;',['title'=>$i]);
                $numBtnPage = $this->hasListElement($numBtnPage,['class'=>$this->elements['list']['currentClass']]);
            } else
            {
                // $numBtnPage .='<a href="'.$this->getUrl($i).'" class="number" title="'.$i.'">'.$i.'</a>';
                $numBtnPage = Html::a($i,$this->getUrl($i),['title'=>$i]);
                $numBtnPage = $this->hasListElement($numBtnPage);
            }
        }

      return $numBtnPage;
    }
    //向下跳转 $this->numberBtn 的页数
    public function getNextAcrossPages()
    {
        $nextAcrossPages = $this->page + $this->numberBtn;

        if ($nextAcrossPages < $this->pageTotal)
        {
          $text  = sprintf($this->text['nexts'],$this->numberBtn);
          $nexts = Html::a($text,$this->getUrl($nextAcrossPages),['title'=>$text]);
          $nexts = $this->hasListElement($nexts);
        }

        return isset($nexts) ? $nexts : '';
    }

    //得到下一页,当前页数小于总页数时，出现 ‘下一页’ 按钮
    public function getNextPage()
    {
        if ($this->page < $this->pageTotal)
        {
          $next = Html::a($this->text['next'],$this->getUrl($this->nextPage),['title'=>$this->text['next']]);
          $next = $this->hasListElement($next);
        }

        return isset($next) ? $next : '';

    }

    //得到跳到尾页字符串
    public function getLastPage()
    {
      $last = Html::a($this->text['last'],$this->getUrl($this->pageTotal),['title'=>$this->text['last']]);

      return $this->hasListElement($last);
    }

////////////////////////////////////////// Other ///////////////////////////////////////

    //得到总记录数或总记录字符串
    public function getAllRecord($type='string')
    {
        if ($type =='string')
        {
            return sprintf( $this->text['countStr'],$this->total );
        } else
        {// count
            return $this->total;
        }
    }

    //输入页数跳转
    public function getJumpPage()
    {
        if ($this->pageTotal <= 1)
        {
            return '';
        }

        $jumpPage = ' <input type="button" class="button jump-page-btn" value="'.$this->text['jumpTo'].'"
            onclick="javascript:var jumpPN = document.getElementById(\'jumpPageNum\');
            if (jumpPN.value<='.$this->pageTotal.'){
                    location.href=\''.$this->getUrl().'\'+jumpPN.value;
                }">';
        $jumpPage .= ' <input type="text" class="page-num-input" id="jumpPageNum"
            style="width: 35px;" value="'.$this->page.'" onkeydown = "javascript:
                if (event.keyCode==13 && this.value<='.$this->pageTotal.'){
                    location.href=\''.$this->getUrl().'\'+this.value;
                }"> ';

        return $jumpPage;

    }

    //select  下拉选择框跳转
    public function getSelectPage()
    {
        if ($this->pageTotal <= 1)
        {
            return '';
        }

        $select = ' <select class="page-num-input" style="max-height: 400px"
        onchange="javascript:location.href=\''.$this->getUrl().'\'+this.value;">';

        for($i=1;$i<=$this->pageTotal;$i++)
        {
            $select .=($this->page==$i)?
                '<option value="'.$i.'" selected="selected"">'.$i.'</option>' :
                '<option value="'.$i.'" >'.$i.'</option>';
        }
        $select .= '</select> ';

        return $select;

    }

////////////////////////////////////////// get paging string ///////////////////////////////////////

    //得到组装后的分页字符串
    public function getPageStr($type='full')
    {
        switch(trim($type))
        {
            case '1'://自定义组装 1
                $pageStr = $this->getFirstPage().$this->getPrevAcrossPages().$this->getPrevPage().
                    $this->getNumBtnPage().$this->getNextPage().$this->getNextAcrossPages().
                    $this->getLastPage().$this->getAllRecord('string');
                break;
            case 'mini'://最简单
                $pageStr = $this->getPrevPage().$this->getNextPage();
                break;
            case 'normal'://常用
                $pageStr = $this->getPrevAcrossPages().$this->getPrevPage().
                    $this->getNumBtnPage().$this->getNextPage().$this->getNextAcrossPages().$this->getAllRecord('string');
                break;
            case 'normal+select'://常用+select
                $pageStr = $this->getPrevAcrossPages().$this->getPrevPage().$this->getNumBtnPage().$this->getNextPage()
                    .$this->getNextAcrossPages().$this->getSelectPage().$this->getAllRecord('string');
                break;
            case 'normal+input'://常用+input
                $pageStr = $this->getPrevAcrossPages().$this->getPrevPage().$this->getNumBtnPage().$this->getNextPage()
                    .$this->getNextAcrossPages().$this->getJumpPage().$this->getAllRecord('string');
                break;
            default://完整的组装
                $pageStr = $this->getFirstPage().$this->getPrevAcrossPages().$this->getPrevPage().
                    $this->getNumBtnPage().$this->getNextPage().$this->getNextAcrossPages().
                    $this->getLastPage().$this->getAllRecord('string').$this->getSelectPage().$this->getJumpPage();
                break;
        }

        if ($this->elements['box']['tag'])
        {
            $box      = $this->elements['box'];
            $tag      = $box['tag'];
            $class    = isset($box['class']) ? $box['class'] : '';
            $pageStr  = Html::tag($tag,$pageStr,['class'=>$class]);
        }

        // md($this,$pageStr,$_SERVER);
        return $pageStr;

    }

    // 添加 a 的外部元素 li
    private function hasListElement($ele,$attrs=[])
    {
      if ($this->elements['list']['tag'] !='')
      {
        $listEle = $this->elements['list']['tag'];

        return Html::tag($listEle,$ele,$attrs);
      }

      return $ele;
    }
}//++++ end class Mypage +++++