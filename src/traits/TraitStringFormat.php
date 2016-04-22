<?php
/**
 * Created by PhpStorm.
 * User: Inhere
 * Date: 2015/1/30
 * Use : ...
 * File: TraitStringFormat.php
 */

namespace inhere\tools\traits;


trait TraitStringFormat
{
    /**
     * 驼峰式 <=> 下划线式
     * @param  [type]  $string [description]
     * @param  bool $toCamelCase
     * true : 驼峰式 => 下划线式
     * false : 驼峰式 <= 下划线式
     * @return mixed|string
     */
    static public function nameChange($string, $toCamelCase=true)
    {
        $string         = trim($string);

        #默认 ：下划线式 =>驼峰式
        if ((bool)$toCamelCase) {

            if (strpos($string,'_')===false) {
                return $string;
            }

            $arr_char  = explode('_',strtolower($string));
            $newString = array_shift($arr_char);

            foreach($arr_char as $val){
                $newString .= ucfirst($val);
            }

            return $newString;
        }

        #驼峰式 => 下划线式
        return strtolower(preg_replace('/((?<=[a-z])(?=[A-Z]))/', '_', $string));
    }

    /**
     * [format description]
     * @param $string
     * @param array $replaceParams 用于 str_replace('search','replace',$string )
     * @example
     *   $replaceParams = [
     *        'xx',  //'search'
     *        'yy', //'replace'
     *   ]
     *   $replaceParams = [
     *        ['xx','xx2'],  //'search'
     *        ['yy','yy2'],  //'replace'
     *   ]
     *
     * @param array $pregParams  用于 preg_replace('pattern','replace',$string)
     *
     * @example
     * $pregParams = [
     *     'xx',  //'pattern'
     *     'yy',  //'replace'
     * ]
     * * $pregParams = [
     *     ['xx','xx2'],  //'pattern'
     *     ['yy','yy2'],  //'replace'
     * ]
     *
     *
     * @return string [type]                [description]
     */
    static public function format($string,array $replaceParams=[],array $pregParams=[])
    {
        if ( !is_string($string) || empty($string) || (empty($replaceParams) && empty($pregParams)) )
        {
            return $string;
        }

        if ( !empty($replaceParams) && count($replaceParams)==2 )
        {
            list($search,$replace) = $replaceParams;
            $string = str_replace($search,$replace,$string);
        }

        if ( !empty($pregParams) && count($pregParams)==2 )
        {
            list($pattern,$replace) = $pregParams;
            $string = preg_replace($pattern,$replace,$string);
        }

        return trim($string);
    }

    /**
     * 格式化，用空格分隔各个词组
     * @param  string $keyword 字符串
     * @return string 格式化后的字符串
     */
    static public function wordFormat($keyword)
    {
        # 将全角角逗号换为空格
        $keyword = str_replace("，",",",$keyword);
        # 将半角角逗号换为空格
        $keyword = str_replace(",",' ',$keyword);
        # 去掉头尾空格
        $keyword = trim($keyword);
        # 去掉两个空格以上的
        $keyword = preg_replace('/\s(?=\s)/', '', $keyword);
        # 将非空格替换为一个空格
        $keyword = preg_replace('/[\n\r\t]/', ' ', $keyword);
        return $keyword;
    }

    static public function clearChar($string, $del=null, $type='both')
    {
        return self::trim($string,$del,$type);
    }

    /**
     * 循环清除首字符（左边）
     * @param $string '&*,test'
     * @param array $del ['&','*',',']
     * @return string 'test'
     */
    static public function leftTrim($string, array $del=[])
    {
        return self::trim($string, $del, 'left');
    }

    /**
     * 循环清除尾字符（右侧）
     * @param $string 'test*&?'
     * @param array $del ['*','?','&']
     * @return string 'test'
     */
    static public function rightTrim($string, array $del=[])
    {
        return self::trim($string, $del, 'right');
    }

    /**
     * 去除多个首尾特殊字符
     * 删除字符串前后的 空白 不需要的字符(两边都删除、删除左边、删除右边)
     * @param $string
     * @param array $chars
     * @param string $type
     * @return string
     */
    static public function trim($string, array $chars=[], $type='')
    {
        if (!$chars || !is_array($chars))
        {
            $chars = [ '/', '?', '&', '.', ',' ];
        }

        $string = trim($string);

        if (!$string)
        {
            return '';
        }

        // 首字符
        $ltrim = function($string) use ($chars)
        {
            while (in_array(substr($string,0,1), $chars) )
            {
                $string = substr($string,1);
            }

            return $string;
        };

        if ($type=='l' || $type=='left')
        {
            return $ltrim($string);
        }

        // 尾字符
        $rtrim = function ($string) use($chars)
        {
            while ( in_array(substr($string,-1), $chars) )
            {
                $string = substr($string,0,-1);
            }

            return $string;
        };

        if ($type=='r' || $type=='right')
        {
            return $rtrim($string);
        }

        // 都清理
        return $rtrim( $ltrim($string) );
    }

    //clearChar 别名函数
    static public function delCharSpace($string,$del='',$type='both')
    {
        return self::clearChar($string,$del,$type);
    }

    //缩进格式化内容，去空白/注释 已不会影响到 HEREDOC 标记
    static public function deleteStripSpace($fileName,$type=0)
    {
        $data = trim( file_get_contents($fileName) );
        // substr($data,5) 从第五位开始截取到末尾
        $data = substr($data,0,5) == "<?php" ? substr($data,5) : $data ;
        $data = substr($data,-2) == "?>" ? substr($data,0,-2) : $data ;

        switch ((int)$type) {
            //去掉所有注释 换行空白保留
            case 1:
                $preg_arr = array(
                    '/\/\*.*?\*\/\s*/is'    // 去掉所有多行注释/* .... */
                    ,'/\/\/.*?[\r\n]/is'    // 去掉所有单行注释//....
                    ,'/\#.*?[\r\n]/is'      // 去掉所有单行注释 #....
                );
                return preg_replace($preg_arr,'',$data);
                break;
            default:
                $preg_arr = array(
                    '/\/\*.*?\*\/\s*/is'    // 去掉所有多行注释 /* .... */
                    ,'/\/\/.*?[\r\n]/is'    // 去掉所有单行注释 //....
                    ,'/\#.*?[\r\n]/is'      // 去掉所有单行注释 #....
                    ,'/(?!\w)\s*?(?!\w)/is' //去掉空白行
                );
                $data = preg_replace($preg_arr,'',$data);
                //保留 HEREDOC 标记
                return preg_replace(
                    array('/<<<EOF/is','/EOF;/is'),
                    array("<<<EOF".PHP_EOL,"EOF;".PHP_EOL),
                    $data
                );
                break;
        }
    }//todo 已修正影响到 HEREDOC 标记

    /**
     * 去空格，去除注释包括单行及多行注释 不会影响到HEREDOC
     * $data    用于操作的数据内容
     * @param $content
     * @param string $headDoc
     * @return string
     */
    static public function phpFormat($content, $headDoc='EOF')
    {
        $str = ""; //合并后的字符串
        $data = token_get_all($content);
        $end = false; //没结束如$v = "php"中的等号;

        for ($i = 0, $count = count($data); $i < $count; $i++) {
            if (is_string($data[$i])) {
                $end = false;
                $str.=$data[$i];
            } else {

                switch ($data[$i][0]) {//检测类型
                    case T_COMMENT:   //忽略单行多行注释
                    case T_DOC_COMMENT:
                        break;
                    case T_WHITESPACE: //去除空格
                        if (!$end) {
                            $end = true;
                            $str.=" ";
                        }
                        break;
                    case T_START_HEREDOC://定界符开始
                        // $str.="<<<EOF".PHP_EOL;
                        $str.="<<<$headDoc".PHP_EOL;
                        break;
                    case T_END_HEREDOC://定界符结束
                        $str.="$headDoc;".PHP_EOL;

                        //类似str;分号前换行情况
                        for ($m = $i + 1; $m < $count; $m++) {
                            if (is_string($data[$m]) && $data[$m] == ';') {
                                $i = $m;
                                break;
                            }
                            if ($data[$m] == T_CLOSE_TAG) {
                                break;
                            }
                        }
                        break;

                    default:
                        $end = false;
                        $str.=$data[$i][1];
                }
            }
        }

        return $str;
    }//todo 来源于 hdphp

}// end class TraitStringFormat