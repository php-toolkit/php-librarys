<?php
/**
 * Created by PhpStorm.
 * User: Inhere
 * Date: 14-4-2
 * Time: 下午2:13
 * 图像处理 todo 有待完善
 */

namespace inhere\tools\files;

use inhere\tools\exceptions\InvalidConfigException;

/**
 * Class Picture
 * @package inhere\tools\files
 */
class Picture
{

    # 是否应用水印
    private $waterOn;
    # 1 图片水印  2 文字水印
    private $waterType = 1;
    # 水印图片
    public $waterImg;
    # 水印的位置
    public $waterPos;
    # 水印的透明度
    public $waterAlpha;
    # 图像的压缩比
    public $waterQuality;
    # 水印文字内容
    public $waterText;
    # 水印文字大小
    public $waterTextSize;
    # 水印文字的颜色
    public $waterTextColor;
    # 水印的文字的字体
    public $waterTextFont;
    # 是否开启缩略图功能
    private $thumbOn;
    # 生成缩略图的方式
    public $thumbType;
    # 缩略图的宽度
    public $thumbWidth;
    # 缩略图的高度
    public $thumbHeight;
    # 生成缩略图文件名后缀
    public $thumbEndFix;
    # 缩略图文件前缀
    public $thumbPreFix;

    public $thumbPath;

    public $thumbConfig = [];
    public $waterConfig = [];

    const WATER_USE_IMAGE = 1; # 1 图片水印
    const WATER_USE_TEXT  = 2; # 2 文字水印

    /**
     * @var array
     */
    protected static $types = ['.jpg', '.jpeg', '.png', '.gif'];

    /**
     * 构造函数
     * @param array $config
     */
    public function __construct(array $config=[])
    {
        //水印参数
        $config = $this->_parseConfig($config);

        $configWater = $config['water'];
        $configThumb = $config['thumb'];

        $this->waterOn          = $configWater['on'];
        $this->waterType        = $configWater['type'];
        $this->waterImg         = $configWater['img'];
        $this->waterPos         = $configWater['pos'];
        $this->waterAlpha       = $configWater['alpha'];
        $this->waterQuality     = $configWater['quality'];
        $this->waterText        = $configWater['text'];
        $this->waterTextColor   = $configWater['text_color'];
        $this->waterTextSize    = $configWater['text_size'];
        $this->waterTextFont    = $configWater['font'];

        if ($this->waterType ===self::WATER_USE_IMAGE && !is_file($this->waterImg)) {
            throw new InvalidConfigException('请配置正确的水印图片资源路径');
        }

        if ($this->waterType === self::WATER_USE_TEXT && !is_file($this->waterTextFont)) {
            throw new InvalidConfigException('请配置正确的水印文字资源路径');
        }

        $this->thumbOn          = $configThumb['open'];
        $this->thumbType        = $configThumb['type'];
        $this->thumbPath        = $configThumb['path'];
        $this->thumbWidth       = $configThumb['width'];
        $this->thumbHeight      = $configThumb['height'];
        $this->thumbPreFix      = $configThumb['prefix'];
        $this->thumbEndFix      = $configThumb['suffix'];
    }
    /**
     * [_parseConfig 解析传入配置]
     * @param  array $config [构造函数传入配置]
     * @return array         [description]
     */
    private function _parseConfig($config)
    {
        //水印参数
        $defaultConfig  = $this->defaultConfig();

        if (isset($config['water'])) {
            $config['water'] = array_merge($defaultConfig['water'], $config['water']);
        }

        if (isset($config['thumb'])) {
            $config['thumb'] = array_merge($defaultConfig['thumb'], $config['thumb']);
        }

        return $config;
    }

    /**
     * [defaultConfig 默认配置]
     * @return array
     */
    protected function defaultConfig()
    {
        return [
            ###########| IMG WATER 图像水印 #############
            'water'   => [
                'type'            => 1 #1 图片水印  2 文字水印
                ,'font'           => dirname(__DIR__) . '/resources/fonts/Montserrat-Bold.ttf' #水印字体
                ,'img'            => '' #水印图像
                ,'pos'            => 1 #水印位置 1-9
                ,'alpha'          => 40 #水印透明度
                ,'quality'        => 80 #水印压缩质量
                ,'text'           => 'YZONE.NET' #水印文字
                ,'text_color'     => '#ededed' #水印文字颜色
                ,'text_size'      => 20 #水印文字大小
            ]
            ###########| IMG THUMB 图片缩略图 ############
            ,'thumb'      => [
                'width'          => 150         #缩略图宽度
                ,'height'        => 100         #缩略图高度
                ,'prefix'        => ''          #缩略图前缀
                ,'suffix'        => '_thumb'    #缩略图后缀
                    #生成缩略图方式,
                    #1:固定宽度  高度自增      2:固定高度  宽度自增    3:固定宽度  高度裁切
                    #4:固定高度  宽度裁切      5:缩放最大边 原图不裁切  6:缩略图尺寸不变，自动裁切图片
                ,'type'          => 6
                ,'path'          => ''     #缩略图存放路径
            ]
        ];
    }

    /**
     * 环境验证
     * @param string $img            图像路径
     * @return bool
     */
    private function check($img)
    {
        $imgType    = strtolower(strrchr($img, '.'));

        return extension_loaded('gd') && file_exists($img) && in_array($imgType, static::$types);
    }

    /**
     *
     * 获得缩略图的尺寸信息
     * @param string  $imgWidth         原图宽度
     * @param string  $imgHeight        原图高度
     * @param string  $thumbWidth       缩略图宽度
     * @param string  $thumbHeight      缩略图的高度
     * @param string  $thumbType        处理方式
     * @return array
     */
    private function thumbSize($imgWidth, $imgHeight, $thumbWidth, $thumbHeight, $thumbType)
    {
        //初始化缩略图尺寸
        $w = $thumbWidth;
        $h = $thumbHeight;
        //初始化原图尺寸
        $oldThumbWidth  = $imgWidth;
        $oldThumbHeight = $imgHeight;

        if ($imgWidth <= $thumbWidth && $imgHeight <= $thumbHeight) {
            $w = $imgWidth;
            $h = $imgHeight;
        } else {
            switch ($thumbType) {
                case 1 :
                    //固定宽度  高度自增
                    $h = $thumbWidth / $imgWidth * $imgHeight;
                    break;
                case 2 :
                    //固定高度  宽度自增
                    $w = $thumbHeight / $imgHeight * $imgWidth;
                    break;
                case 3 :
                    //固定宽度  高度裁切
                    $oldThumbHeight = $imgWidth / $thumbWidth * $thumbHeight;
                    break;
                case 4 :
                    //固定高度  宽度裁切
                    $oldThumbWidth = $imgHeight / $thumbHeight * $thumbWidth;
                    break;
                case 5 :
                    //缩放最大边 原图不裁切
                    if (($imgWidth / $thumbWidth) > ($imgHeight / $thumbHeight)) {
                        $h = $thumbWidth / $imgWidth * $imgHeight;
                    } else if (($imgWidth / $thumbWidth) < ($imgHeight / $thumbHeight)) {
                        $w = $thumbHeight / $imgHeight * $imgWidth;
                    } else {
                        $w = $thumbWidth;
                        $h = $thumbHeight;
                    }
                    break;
                default:
                    //缩略图尺寸不变，自动裁切图片
                    if (($imgHeight / $thumbHeight) < ($imgWidth / $thumbWidth)) {
                        $oldThumbWidth = $imgHeight / $thumbHeight * $thumbWidth;
                    } else if (($imgHeight / $thumbHeight) > ($imgWidth / $thumbWidth)) {
                        $oldThumbHeight = $imgWidth / $thumbWidth * $thumbHeight;
                    }
            }
        }
        $arr [0] = $w;
        $arr [1] = $h;
        $arr [2] = $oldThumbWidth;
        $arr [3] = $oldThumbHeight;

        return $arr;
    }

    /**
     * 图片裁切处理
     * @param string| $img   操作的图片文件路径(原图)
     * @param string|  $outFile 另存文件名
     * @param string|  $path 文件存放路径
     * @param string|  $thumbWidth 缩略图宽度
     * @param string| $thumbHeight 缩略图高度
     * @param string| $thumbType 裁切图片的方式
     * @return bool|string
     */
    public function thumb($img, $outFile = '', $path = '', $thumbWidth = '', $thumbHeight = '', $thumbType = '')
    {
        if (!$this->check($img)) {
            return false;
        }

        //基础配置
        $thumbType   = $thumbType   ? : $this->thumbType;
        $thumbWidth  = $thumbWidth  ? : $this->thumbWidth;
        $thumbHeight = $thumbHeight ? : $this->thumbHeight;
        $path        = $path        ? :  $this->thumbPath;

        //获得图像信息
        $imgInfo        = getimagesize($img);
        $imgWidth       = $imgInfo [0];
        $imgHeight      = $imgInfo [1];
        $imgType        = image_type_to_extension($imgInfo [2]);

        //获得相关尺寸
        $thumb_size = $this->thumbSize($imgWidth, $imgHeight, $thumbWidth, $thumbHeight, $thumbType);

        //原始图像资源
        $func       = 'imagecreatefrom' . substr($imgType, 1);
        $resImg     = $func($img);

        //缩略图的资源
        if ($imgType === '.gif') {
            $res_thumb  = imagecreate($thumb_size [0], $thumb_size [1]);
            $color      = imagecolorallocate($res_thumb, 255, 0, 0);
        } else {
            $res_thumb  = imagecreatetruecolor($thumb_size [0], $thumb_size [1]);
            imagealphablending($res_thumb, false); //关闭混色
            imagesavealpha($res_thumb, true); //储存透明通道
        }

        //绘制缩略图X
        if (function_exists('imagecopyresampled')) {
            imagecopyresampled($res_thumb, $resImg, 0, 0, 0, 0, $thumb_size [0], $thumb_size [1], $thumb_size [2], $thumb_size [3]);
        } else {
            imagecopyresized($res_thumb, $resImg, 0, 0, 0, 0, $thumb_size [0], $thumb_size [1], $thumb_size [2], $thumb_size [3]);
        }

        //处理透明色
        if ($imgType === '.gif') {
            /** @var $color string */
            imagecolortransparent($res_thumb, $color);
        }

        //配置输出文件名
        $imgInfo        = pathinfo($img);
        $outFile        = $outFile ? : $this->thumbPreFix . $imgInfo['filename'] . $this->thumbEndFix . '.' . $imgInfo['extension'];
        $upload_dir     = $path ? : dirname($img);

        Directory::create($upload_dir);

        $outFile        = $upload_dir . '/' . $outFile;
        $func           = 'image' . substr($imgType, 1);
        $func($res_thumb, $outFile);

        if (isset($resImg)) {
            imagedestroy($resImg);
        }

        if (isset($res_thumb)) {
            imagedestroy($res_thumb);
        }

        return $outFile;
    }

    /**
     * 水印处理
     * @param string $img         操作的图像
     * @param string| $outImg 另存的图像
     * @param string| $pos 水印位置
     * @param string| $waterImg 水印图片
     * @param string| $alpha 透明度
     * @param string| $text 文字水印内容
     * @return bool
     */
    public function water($img, $outImg = '', $pos = '', $waterImg = '', $alpha = '', $text = '')
    {
        //验证原图像
        if (!$this->check($img) || !$this->waterOn) {
            return false;
        }

        //验证水印图像
        $waterImg   = $waterImg ? : $this->waterImg;
        $waterImgOn = $this->check($waterImg) ? 1 : 0;

        //判断另存图像
        $outImg     = $outImg   ? : $img;
        //水印位置
        $pos        = $pos      ? : $this->waterPos;
        //水印文字
        $text       = $text     ? : $this->waterText;
        //水印透明度
        $alpha      = $alpha    ? : $this->waterAlpha;

        $imgInfo    = getimagesize($img);
        $imgWidth   = $imgInfo [0];
        $imgHeight  = $imgInfo [1];

        //获得水印信息
        if ($waterImgOn) {
            $waterInfo      = getimagesize($waterImg);
            $waterWidth     = $waterInfo [0];
            $waterHeight    = $waterInfo [1];

            switch ($waterInfo [2]) {
                case 1 :
                    $w_img = imagecreatefromgif($waterImg);
                    break;
                case 2 :
                    $w_img = imagecreatefromjpeg($waterImg);
                    break;
                case 3 :
                    $w_img = imagecreatefrompng($waterImg);
                    break;
            }
        } else {
            if (!$text || strlen($this->waterTextColor) !== 7) {
                return false;
            }

            $textInfo       = imagettfbbox($this->waterTextSize, 0, $this->waterTextFont, $text);
            $waterWidth     = $textInfo [2] - $textInfo [6];
            $waterHeight    = $textInfo [3] - $textInfo [7];
        }

        //建立原图资源
        if ($imgHeight < $waterHeight || $imgWidth < $waterWidth) {
            return false;
        }

        $resImg = '';
        switch ($imgInfo [2]) {
            case 1 :
                $resImg = imagecreatefromgif ($img);
                break;
            case 2 :
                $resImg = imagecreatefromjpeg($img);
                break;
            case 3 :
                $resImg = imagecreatefrompng($img);
                break;
        }

        //水印位置处理方法
        switch ($pos) {
            case 1 :
                $x = $y = 25;
                break;
            case 2 :
                $x = ($imgWidth - $waterWidth) / 2;
                $y = 25;
                break;
            case 3 :
                $x = $imgWidth - $waterWidth;
                $y = 25;
                break;
            case 4 :
                $x = 25;
                $y = ($imgHeight - $waterHeight) / 2;
                break;
            case 5 :
                $x = ($imgWidth - $waterWidth) / 2;
                $y = ($imgHeight - $waterHeight) / 2;
                break;
            case 6 :
                $x = $imgWidth - $waterWidth;
                $y = ($imgHeight - $waterHeight) / 2;
                break;
            case 7 :
                $x = 25;
                $y = $imgHeight - $waterHeight;
                break;
            case 8 :
                $x = ($imgWidth - $waterWidth) / 2;
                $y = $imgHeight - $waterHeight;
                break;
            case 9 :
                $x = $imgWidth - $waterWidth - 10;
                $y = $imgHeight - $waterHeight;
                break;
            default :
                $x = mt_rand(25, $imgWidth - $waterWidth);
                $y = mt_rand(25, $imgHeight - $waterHeight);
        }

        if ($waterImgOn && isset($resImg) && isset($w_img)) {
            $waterInfo = getimagesize($waterImg);

            if ($waterInfo[2] === 3) {
                imagecopy($resImg, $w_img, $x, $y, 0, 0, $waterWidth, $waterHeight);
            } else {
                imagecopymerge($resImg, $w_img, $x, $y, 0, 0, $waterWidth, $waterHeight, $alpha);
            }
        } else {
            $r          = hexdec(substr($this->waterTextColor, 1, 2));
            $g          = hexdec(substr($this->waterTextColor, 3, 2));
            $b          = hexdec(substr($this->waterTextColor, 5, 2));
            $color      = imagecolorallocate($resImg, $r, $g, $b);
            $charset    = 'UTF-8';

            imagettftext($resImg, $this->waterTextSize, 0, $x, $y, $color, $this->waterTextFont, iconv($charset, 'utf-8', $text));
        }

        switch ($imgInfo [2]) {
            case 1 :
                imagegif ($resImg, $outImg);
                break;
            case 2 :
                imagejpeg($resImg, $outImg, $this->waterQuality);
                break;
            case 3 :
                imagepng($resImg, $outImg);
                break;
        }

        if (isset($resImg)) {
            imagedestroy($resImg);
        }

        if (isset($res_thumb)) {
            imagedestroy($res_thumb);
        }

        return true;
    }

}
