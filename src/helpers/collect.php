<?php
    function createComponent($config)
    {
        if (is_string($config))
        {
            $type=$config;
            $config=array();
        }
        else if (isset($config['class']))
        {
            $type=$config['class'];
            unset($config['class']);
        }
        else
            throw new CException(Yii::t('yii','Object configuration must be an array containing a "class" element.'));

        if (!class_exists($type,false))
            $type=Yii::import($type,true);

        if (($n=func_num_args())>1)
        {
            $args=func_get_args();
            if ($n===2)
                $object=new $type($args[1]);
            else if ($n===3)
                $object=new $type($args[1],$args[2]);
            else if ($n===4)
                $object=new $type($args[1],$args[2],$args[3]);
            else
            {
                unset($args[0]);
                $class=new ReflectionClass($type);
                // Note: ReflectionClass::newInstanceArgs() is available for PHP 5.1.3+
                // $object=$class->newInstanceArgs($args);
                $object=call_user_func_array(array($class,'newInstance'),$args);
            }
        }
        else
            $object=new $type;

        foreach($config as $key=>$value)
            $object->$key=$value;

        return $object;
    }
#判断内容的字符编码
function chkCode($string){
 $code = array('UTF-8','GBK','GB18030','GB2312');
 foreach($code as $c){
  if ( $string === iconv('UTF-8', $c, iconv($c, 'UTF-8', $string))){
   return $c;
  }
 }
 return NULL;
}
/**
 * url_encode form urlencode(),但是 : / ? & = 几个符号不会被转码为 %3A %2F %3F %26 %3D
 * @return [type] [description]
 */
function url_encode1($url)
{
    $url        = trim($url);
    if (empty($url)) { return $url; }
    $encodeUrl = urlencode($url);
    $search    = array('%3A','%2F','%3F','%26','%3D','%23', "%40");
    $replace   = array(':','/','?','&','=','#', "@");
    $encodeUrl = str_replace( $search, $replace, $encodeUrl);
    return $encodeUrl;
}

function url_encode($url="")
{
    $url    = rawurlencode(mb_convert_encoding($url, 'utf-8'));
    // $url    = rawurlencode($url);
    $a      = array("%3A", "%2F", "%40");
    $b      = array(":", "/", "@");
    $url    = str_replace($a, $b, $url);
    return $url;
}
$url="ftp://ud03:password@www.nowamagic.net/中文/中文.rar";
$url1 =  url_encode($url);
$url2 =  urldecode($url);
//ftp://ud03:password@www.nowamagic.net/%D6%D0%CE%C4/%D6%D0%CE%C4.rar
echo $url1.PHP_EOL.$url2;
########################################
# 去掉两个空格以上的
        $keyword = preg_replace('/\s(?=\s)/', '', $keyword);
# 将非空格替换为一个空格
        $keyword = preg_replace('/[\n\r\t]/', ' ', $keyword);



//定义一个用来序列化对象的函数

function my_serialize( $obj )
{
   return base64_encode(gzcompress(serialize($obj)));
}

//反序列化
function my_unserialize($txt)
{
   return unserialize(gzuncompress(base64_decode($txt)));
}

// 加密数据并写到cookie里
$cookie_data = $this -> encrypt("nowamagic", $data);

$cookie = array(
    'name'   => '$data',
    'value'  => $cookie_data,
    'expire' => $user_expire,
    'domain' => '',
    'path'   => '/',
    'prefix' => ''
);
$this->input->set_cookie($cookie);

// 加密
function encrypt($key, $plain_text) {
    $plain_text = trim($plain_text);
    $iv     = substr(md5($key), 0,mcrypt_get_iv_size (MCRYPT_CAST_256,MCRYPT_MODE_CFB));
    $c_t    = mcrypt_cfb (MCRYPT_CAST_256, $key, $plain_text, MCRYPT_ENCRYPT, $iv);
    return trim(chop(base64_encode($c_t)));
}
if ( isset($_COOKIE['data']) )
{
    //用cookie给session赋值
    $_SESSION['data'] = decrypt("nowamagic", $_COOKIE['data']);
}

function decrypt($key, $c_t) {
    $c_t    = trim(chop(base64_decode($c_t)));
    $iv     = substr(md5($key), 0,mcrypt_get_iv_size (MCRYPT_CAST_256,MCRYPT_MODE_CFB));
    $p_t    = mcrypt_cfb (MCRYPT_CAST_256, $key, $c_t, MCRYPT_DECRYPT, $iv);
    return trim(chop($p_t));
}

function getExtension($path)
    {
        return pathinfo($path,PATHINFO_EXTENSION);
    }




$con = file_get_contents("http://www.jbxue.com/news/jb-1.html");
$pattern="/<[img|IMG].*?src=[\'|\"](.*?(?:[\.gif|\.jpg|\.png]))[\'|\"].*?[\/]?>/";
preg_match_all($pattern,$con,$match);
print_r($match);
?>
输出结果：
Array
(
    [0] => Array
        (
            [0] => <img src="http://www.jbxue.com/usr/themes/dddefault/images/logo.png" alt="脚本学堂" />
            [1] => <img style="display: block; margin-left: auto; margin-right: auto;" title="脚本学堂上线了" src="http://www.jbxue.com/usr/uploads/2012/09/531656480.jpg" alt="脚本学堂上线了2" />
            [2] => <img style="display: block; margin-left: auto; margin-right: auto;" src="http://www.jbxue.com/usr/uploads/2012/09/2647136297.jpg" alt="875EA1C00E50B4542797E24FA6E7E1F2.jpg" />
        )
    [1] => Array
        (
            [0] => http://www.jbxue.com/usr/themes/dddefault/images/logo.png
            [1] => http://www.jbxue.com/usr/uploads/2012/09/531656480.jpg
            [2] => http://www.jbxue.com/usr/uploads/2012/09/2647136297.jpg
        )
)