<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 14-5-19
 * Time: 上午11:00
 * 新闻、文章处理分页类
 * $curPage    = isset($_GET['page']) ? $_GET['page'] : 1;
 * $pageSize   = 10;
 * $params  = [
 *     'page'      => $curPage,
 *     'pageSize'  => $pageSize,
 *     'total'     => $total
 * ];
 * $paging    = new \inhere\tools\html\Paging($params);
 *
 * $pagingString = $paging->useStyle()->toString();
 *
 *
 */
namespace inhere\tools\html;

/**
 * Class Paging
 * @package inhere\tools\html
 */
class Paging extends PagingBase
{
    /**
     * @var array
     */
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


    /*********************************************************************************
    * build html tag string
    *********************************************************************************/

    /**
     * 得到回到第一页字符串
     * @return $this
     */
    public function firstPage()
    {
        // return '<a href="'.$this->getUrl().'" class="number" title="1">'.$this->text['first'].'</a>';
        $first = Html::a($this->text['first'],$this->getUrl(1));

        $this->html['first'] = $this->hasListElement($first);

        return $this;
    }

    /**
     * 得到上一页,当前页数大于1时，出现 ‘上一页’ 按钮
     * @return $this
     */
    public function prevPage()
    {
        $page = (int)$this->getOption('page', 1);

        if ($page > 1) {
            $prev = Html::a($this->text['prev'],$this->getUrl($this->prevPage));

            $prev = $this->hasListElement($prev);
        }

        $this->html['prev'] = isset($prev) ? $prev : '';

        return $this;
    }

    /**
     * 向上跳转 $this->btnNum 的页数
     * @return $this
     */
    public function prevAcrossPages()
    {
        $prevAcrossPages = $this->options['page'] - $this->options['btnNum'];

        if ($prevAcrossPages > 0) {
            $text  = sprintf($this->text['prevs'], $this->options['btnNum']);
            // return '<a href="'.$this->getUrl($prevAcrossPages).'" class="number" title="'.$text.'">'.$text.'</a>';
            $prevs = Html::a($text,$this->getUrl($prevAcrossPages),['title'=>$text]);
            $prevs = $this->hasListElement($prevs);
        }

        $this->html['prevs'] = isset($prevs) ? $prevs: '';

        return $this;
    }

    /**
     * 得到数字按钮分页字符串
     * @return $this
     */
    public function numberPageBtn()
    {
        $numBtnPage='';
        $page = (int)$this->getOption('page', 1);

        for($i=$this->firstNumBtn; $i<=$this->lastNumBtn;$i++) {
            if ($i === $page) {
                //给当前页按钮设置额外样式
                // $numBtnPage .='<a href="'.$this->getUrl($i).'" class="number current" title="'.$i.'">'.$i.'</a>';
                $a = Html::a($i,'',['title'=>$i]);
                $numBtnPage .= $this->hasListElement($a,['class'=>$this->elements['list']['currentClass']]);
            } else {
                // $numBtnPage .='<a href="'.$this->getUrl($i).'" class="number" title="'.$i.'">'.$i.'</a>';
                $a = Html::a($i,$this->getUrl($i),['title'=>$i]);
                $numBtnPage .= $this->hasListElement($a);
            }
        }

        $this->html['numbers'] = $numBtnPage;

        return $this;
    }

    /**
     * 向下跳转 $this->btnNum 的页数
     * @return $this
     */
    public function nextAcrossPages()
    {
        $nextAcrossPages = $this->options['page'] + $this->options['btnNum'];

        if ($nextAcrossPages < $this->pageTotal) {
            $text  = sprintf($this->text['nexts'],$this->options['btnNum']);
            $nexts = Html::a($text,$this->getUrl($nextAcrossPages),['title'=>$text]);
            $nexts = $this->hasListElement($nexts);
        }

        $this->html['nexts'] = isset($nexts) ? $nexts : '';

        return $this;
    }

    /**
     * 得到下一页,当前页数小于总页数时，出现 ‘下一页’ 按钮
     * @return $this
     */
    public function nextPage()
    {
        if ($this->options['page'] < $this->pageTotal) {
            $next = Html::a($this->text['next'], $this->getUrl($this->nextPage));
            $next = $this->hasListElement($next);
        }

        $this->html['next'] = isset($next) ? $next : '';

        return $this;
    }

    /**
     * 得到跳到尾页字符串
     * @return $this
     */
    public function lastPage()
    {
        $last = Html::a($this->text['last'],$this->getUrl($this->pageTotal),['title'=>$this->text['last']]);

        $this->html['last'] = $this->hasListElement($last);

        return $this;
    }

    /*********************************************************************************
     * extra build html tag string
     *********************************************************************************/

    /**
     * 得到总记录数或总记录字符串
     * @return $this
     */
    public function totalStr()
    {
        $this->html['total'] = sprintf( $this->text['totalStr'], $this->options['total'] );

        return $this;
    }

    /**
     * 输入页数跳转
     * @return $this|string
     */
    public function jumpPage()
    {
        if ($this->pageTotal <= 1) {
            return '';
        }

        $jumpPage = ' <input type="button" class="button jump-page-btn" value="'.$this->text['jumpTo'].'"
            onclick="javascript:var jumpPN = document.getElementById(\'jumpPageNum\');
            if (jumpPN.value<='.$this->pageTotal.'){
                    location.href=\''.$this->getUrl().'\'+jumpPN.value;
                }">';
        $jumpPage .= ' <input type="text" class="page-num-input" id="jumpPageNum"
            style="width: 35px;" value="'.$this->options['page'].'" onkeydown = "javascript:
                if (event.keyCode==13 && this.value<='.$this->pageTotal.'){
                    location.href=\''.$this->getUrl().'\'+this.value;
                }"> ';

        $this->html['jump'] = $jumpPage;

        return $this;
    }

    /**
     * select  下拉选择框跳转
     * @return $this|string
     */
    public function selectPage()
    {
        if ($this->pageTotal <= 1) {
            return '';
        }

        $page = (int)$this->getOption('page', 1);
        $select = ' <select class="page-num-input" style="max-height: 400px"
        onchange="javascript:location.href=\''.$this->getUrl().'\'+this.value;">';

        for($i=1;$i<=$this->pageTotal;$i++) {
            $select .=($page==$i)?
                '<option value="'.$i.'" selected="selected"">'.$i.'</option>' :
                '<option value="'.$i.'" >'.$i.'</option>';
        }

        $select .= '</select> ';

        $this->html['select'] = $select;

        return $this;
    }

////////////////////////////////////////// get paging string ///////////////////////////////////////

    //得到组装后的分页字符串
    public function useStyle($type='full')
    {
        switch(trim($type)) {
            case 'mini'://最简单
                $this->prevPage()->nextPage();
                break;
            case 'simple'://simple
                $this->firstPage()->numberPageBtn()->lastPage()->totalStr();
                break;
            case 'normal'://常用
                $this->prevAcrossPages()->prevPage()
                     ->numberPageBtn()->nextPage()->nextAcrossPages()->totalStr();
                break;
            case 'normal_select'://常用+select
                $this->prevAcrossPages()->prevPage()->numberPageBtn()->nextPage()
                     ->nextAcrossPages()->selectPage()->totalStr();
                break;
            case 'normal_jump'://常用+input jump
                $this->prevAcrossPages()->prevPage()->numberPageBtn()->nextPage()
                     ->nextAcrossPages()->jumpPage()->totalStr();
                break;
            default://完整的组装
                $this->firstPage()->prevAcrossPages()->prevPage()
                     ->numberPageBtn()->nextPage()->nextAcrossPages()
                     ->lastPage()->totalStr()->selectPage()->jumpPage();
                break;
        }

        return $this;

    }

    // 添加 a 的外部元素 li
    private function hasListElement($ele,$attrs=[])
    {
        if ($this->elements['list']['tag'] !='') {
            $listEle = $this->elements['list']['tag'];

            return Html::tag($listEle,$ele,$attrs);
        }

        return $ele;
    }

    /**
     * @return string
     */
    public function toString()
    {
        $string = parent::toString();

        if ($this->elements['box']['tag']) {
            $box      = $this->elements['box'];
            $string  = Html::tag($box['tag'], $string, [
                'class'=>isset($box['class']) ? $box['class'] : ''
            ]);
        }

        return $string;
    }

}// end class