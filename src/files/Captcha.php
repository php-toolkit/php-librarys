<?php
/**
 * Created by JetBrains PhpStorm.
 * User: simon
 * Date: 14-3-16
 * Time: 下午1:34
 * name: 生成图片验证码,Captcha-验证码 .
 * use :    1. $captcha = new Captcha(....); $captcha->show()
 *          2. Captcha::make(...)->show()
 */
namespace inhere\tools\files;

use inhere\tools\exceptions\NotFoundException;

/**
 * Class Captcha
 * @package inhere\tools\files
 */
class Captcha
{
    private $img;               // 资源
    public $width;              // 画布宽
    public $height;             // 画布高
    public $bgColor;            // 背景色
    public $font;               // 字体

    public $pixelNum;           // 干扰点数量
    public $lineNum =0;            // 干扰线条数量
    public $aecNum  =0;             // 干扰弧线数量
    public $fontNum;            // 干扰字体数量

    public $fontSize;           // 产生验证码字体大小
    public $charNum;            // 产生验证码字符个数
    public $codeStr;            // 产生验证码字符串, 验证码的随机种子
    public $captcha;            // 产生的验证码

    public $config = [];            // 配置

    /**
     * @param array $config
     * @return static
     */
    public static function make($config=[])
    {
        return new static($config);
    }

    /**
     * 构造函数
     * 配置优先级 __construct > $this->config() > $fileConfig > $this->defaultConfig()
     * @param array $config
     */
    public function __construct(array $config=[])
    {
        $defaultConfig  = $this->defaultConfig();

        if ($config) {
            $config = array_merge( $defaultConfig, $config);
        } else {
            $config = $this->defaultConfig();
        }

        $this->config = $config;
        $this->font   = $config['font'];

        if (!is_file($this->font)) {
            throw new NotFoundException('验证码字体文件不存在');
        }

        $this->codeStr = $config['str'];

        $this->fontSize = isset($config['font_size'])   ? $config['font_size']  : $defaultConfig['font_size'];
        $this->charNum  = isset($config['length'])      ? $config['length']     : $defaultConfig['length'];
        $this->width    = isset($config['width'])       ? $config['width']      : $defaultConfig['width'];
        $this->height   = isset($config['height'])      ? $config['height']     : $defaultConfig['height'];
        $this->bgColor  = isset($config['bg_color'])    ? $config['bg_color']   : $defaultConfig['bg_color'];
        $this->pixelNum = isset($config['pixel_num'])   ? $config['pixel_num']  : $defaultConfig['pixel_num'];
        $this->fontNum  = isset($config['font_num'])    ? $config['font_num']   : $defaultConfig['font_num'];
    }

    public function defaultConfig()
    {
       return [
            'font'         => dirname(__DIR__) . '/resources/fonts/Montserrat-Bold.ttf'#字体文件
           ,'rand_str'     => '23456789abcdefghigkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ'
           ,'width'        => '120'
           ,'height'       => '45'
           ,'bg_color'     => '#eeeeee'
           ,'length'       => '4'
           ,'font_color'   => ''
           ,'font_size'    => '24'   #验证码字体大小
           ,'pixel_num'    => '10'   #干扰点数量
           ,'font_num'     => '50'    #干扰字符数量
           ,'session_key'  => 'app_captcha' #存入SESSION的键值，
       ];
    }

    // 画干扰点-可选 imagesetpixel($this->img,x坐标,y坐标,颜色)
    /**
     * @return $this
     */
    private function drawPixel()
    {
        for($i=1; $i<=$this->pixelNum;$i++)
        {
            //$pixelColor = imagecolorallocate( $this->img,rand(100,240), mt_rand(100,240), mt_rand(100,
            //240) );//点颜色
            //imagesetpixel($this->img,rand(0,$this->width),rand(0,$this->height),$pixelColor);
            $char ='.';
            $pixelColor = imagecolorallocate($this->img, mt_rand(140,200),mt_rand(140,200),mt_rand(140,200));
            imagefttext(
                $this->img, 8 , mt_rand(-30,30), mt_rand(6, $this->width), mt_rand(6,$this->height - 5),
                $pixelColor,    $this->font,  $char
            );
        }

        return $this;
    }

    /**
     * 画干扰直线-可选
     * @return $this
     */
    private function drawLine()
    {
        for($i=1;$i<=$this->lineNum;$i++)
        {
            $lineColor = imagecolorallocate($this->img, mt_rand(150,250), mt_rand(150,250), mt_rand(150,250) );
            //($this->img,起点坐标x.y，终点坐标x.y，颜色)
            imageline(
                $this->img,           mt_rand(0,$this->width),  mt_rand(0,$this->height),
                mt_rand(0,$this->width), mt_rand(0,$this->height), $lineColor
            );
        }

        return $this;
    }

    /**
     * 画干扰弧线-可选
     * @return $this
     */
    private function drawAec()
    {
        for($i=1;$i<=$this->aecNum;$i++)
        {
            $arcColor = imagecolorallocate($this->img, mt_rand(150,250), mt_rand(150,250), mt_rand(150,250));
            imagearc(
                $this->img,  mt_rand(0,$this->width), mt_rand(0,$this->height), mt_rand(0,100),
                mt_rand(0,100), mt_rand(-90,90),     mt_rand(70,360),          $arcColor
            );
        }

        return $this;
    }

    // 产生随机字符,验证码,并写入图像
    private function drawChar()
    {
        $x          = ($this->width - 10) / $this->charNum;
        $captchaStr = '';//保存产生的字符串

        for($i=0;$i<$this->charNum;$i++)
        {
            $char = $this->codeStr[ mt_rand( 0,strlen($this->codeStr)-1) ];
            $captchaStr .=$char;
            $fontColor = imagecolorallocate($this->img, mt_rand(80,200), mt_rand(80,200), mt_rand(80,200));
            imagefttext(
                $this->img, $this->fontSize ,  mt_rand(-30,30), $i*$x + mt_rand(6, 10),
                mt_rand($this->height / 1.3,   $this->height - 5),   $fontColor,
                $this->font,  $char
            );
        }

        $this->captcha = strtolower($captchaStr);

        //把纯的验证码字符串放置到SESSION中进行保存，便于后面进行验证对比
        $_SESSION[$this->config['session_key']] = md5( $this->captcha );

        //设置cookie到前端浏览器，可用于前端验证
        setcookie($this->config['session_key'], md5( $this->captcha ));
    }

    /**
     * 返回验证码
     */
    public function getCaptcha()
    {
        return $this->captcha;
    }

    //填充干扰字符-可选
    private function drawChars()
    {
       for($i=0;$i<$this->fontNum;$i++)
       {
            $char      = $this->codeStr[ mt_rand( 0,strlen($this->codeStr)-1) ];
            $fontColor = imagecolorallocate($this->img, mt_rand(180,240), mt_rand(180,240), mt_rand(180,240));
            imagefttext(
                $this->img, mt_rand(4,8) , mt_rand(-30,40), mt_rand(8,$this->width-10),
                mt_rand(10,$this->height-10), $fontColor, $this->font, $char
            );
       }
    }

    // 生成图像资源，Captcha-验证码
    public function create()
    {
        if (!$this->checkGd() ) {
            return false;
        }

        //建立背景画布,图像资源
        $this->img = imagecreatetruecolor($this->width,$this->height);
        //给画布填充矩形的背景色rgb(230, 255, 230);
        $bgColor = $this->bgColor;
        //$bgColor=imagecolorallocate($this->img,230, 255, 230); //背景色

        //背景色
        $bgColor=imagecolorallocate(
             $this->img,                    hexdec(substr($bgColor, 1,2)),
             hexdec(substr($bgColor, 3,2)), hexdec(substr($bgColor, 5,2))
        );

        imagefilledrectangle($this->img,0,0,$this->width,$this->height,$bgColor);
        //给资源画上边框-可选 rgb(153, 153, 255)
        $borderColor = imagecolorallocate($this->img, 153, 153, 255); // 0-255

        imagerectangle($this->img, 0, 0, $this->width-1, $this->height-1,$borderColor);

        $this->drawLine();
        $this->drawChar();
        $this->drawPixel();
        $this->drawChars();

        return $this;
    }

    //显示
    public function show()
    {
        $this->create();

        header('Cache-Control: max-age=1, s-maxage=1, no-cache, must-revalidate');
        header('Content-type: image/png;charset=utf8');//生成图片格式png jpeg 。。。

        ob_clean();
        //生成图片,在浏览器中进行显示-格式png，与上面的header声明对应
        imagepng($this->img);
        // 已经显示图片后，可销毁，释放内存（可选）
        imagedestroy($this->img);
    }

    private function checkGd()
    {
        return extension_loaded('gd') && function_exists('imagepng');
    }
}
