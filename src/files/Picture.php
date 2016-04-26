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
use inhere\tools\exceptions\ExtensionMissException;

/**
 * Class Picture
 * @package inhere\tools\files
 */
class Picture
{
    # 1 图片水印
    const WATER_IMAGE = 1;

    # 2 文字水印
    const WATER_TEXT  = 2;

    // image types
    const IMAGE_BMP  = 'bmp';
    // const IMAGE_EBP  = 'ebp';
    const IMAGE_JPG  = 'jpg';
    const IMAGE_JPEG = 'jpeg';
    const IMAGE_GIF  = 'gif';
    const IMAGE_PNG  = 'png';

    /**
     * 1  图片水印  2 文字水印
     * @var int
     */
    protected $waterType = 2;

    /**
     * 水印图片
     * @var string
     */
    public $waterImg;

    /**
     * 水印的位置 in 1~9
     * @var int
     */
    public $waterPos;

    /**
     * 水印的透明度
     * @var boolean
     */
    public $waterAlpha;

    /**
     * 图像的压缩比
     * @var boolean
     */
    public $waterQuality;

    /**
     * 水印文字内容
     * @var string
     */
    public $waterText = 'THE WATER';

    /**
     * 水印文字大小
     * @var int
     */
    public $waterFontSize = 12;

    /**
     * 水印文字的颜色
     * @var string
     */
    public $waterFontColor;

    /**
     * 水印文字使用的字体文件
     * @var string
     */
    public $waterFontFile = '';


    /**
     * 生成缩略图的方式 in (1~6)
     * @var int
     */
    public $thumbType;

    /**
     * 缩略图的宽度
     * @var int
     */
    public $thumbWidth = 150;

    /**
     * 缩略图的高度
     * @var int
     */
    public $thumbHeight = 100;

    /**
     * 生成缩略图文件名后缀
     * @var string
     */
    public $thumbSuffix = '_thumb';

    /**
     * 缩略图文件前缀
     * @var string
     */
    public $thumbPrefix = 'thumb_';

    /**
     * 缩略图文件保存路径
     * @var string
     */
    public $thumbPath;

    /**
     * 图像水印设置
     * @var array
     */
    protected $waterOptions = [
        'type'            => 1 #1 图片水印  2 文字水印
        ,'fontFile'       => '' # 水印字体文件
        ,'img'            => '' #水印图像
        ,'pos'            => 1 # 水印位置 1-9
        ,'alpha'          => 40 #水印透明度
        ,'quality'        => 80 #水印压缩质量
        ,'text'          => 'THE WATER' #水印文字
        ,'fontColor'     => '#ededed' #水印字体颜色
        ,'fontSize'      => 20 #水印字体大小
    ];

    /**
     * 图片(缩略图)剪裁设置
     * @var array
     */
    protected $thumbOptions = [
        'width'          => 150         # 缩略图宽度
        ,'height'        => 100         # 缩略图高度
        ,'prefix'        => 'thumb_'    # 缩略图文件名前缀
        ,'suffix'        => ''          # 缩略图文件名后缀
            # 生成缩略图方式,
            # 1:固定宽度  高度自增      2:固定高度  宽度自增     3:固定宽度  高度裁切
            # 4:固定高度  宽度裁切      5:缩放最大边 原图不裁切  6:缩略图尺寸不变，自动裁切图片
        ,'type'          => 6
        ,'path'          => ''     # 缩略图存放路径
    ];

    /**
     * @param  array  $waterOptions
     * @param  array  $thumbOptions
     * @return static
     */
    public static function make(array $waterOptions=[], array $thumbOptions=[])
    {
        return new static($waterOptions,$thumbOptions);
    }

    /**
     * @param  array  $waterOptions
     * @param  array  $thumbOptions
     */
    public function __construct(array $waterOptions=[], array $thumbOptions=[])
    {
        if ( !extension_loaded('gd') ) {
            throw new ExtensionMissException('This tool required extension [gd].');
        }

        $this->waterOptions['fontFile'] = dirname(__DIR__) . '/resources/fonts/Montserrat-Bold.ttf';

        $this->setWaterOptions($waterOptions)->setCuttingOptions($thumbOptions);

        $this->init();
    }

    /*********************************************************************************
    * parse config
    *********************************************************************************/

    /**
     * [_parseConfig 解析传入配置]
     * @param  array $config [构造函数传入配置]
     * @return array
     */
    protected function init()
    {
        $this->waterType        = $this->waterOptions['type'];
        $this->waterImg         = $this->waterOptions['img'];

        $this->waterText        = $this->waterOptions['text'];
        $this->waterOptions['fontColor']   = $this->waterOptions['fontColor'];
        $this->waterOptions['fontSize']    = $this->waterOptions['fontSize'];
        $this->waterOptions['fontFile']    = $this->waterOptions['fontFile'];

        $this->waterPos         = $this->waterOptions['pos'];
        $this->waterAlpha       = $this->waterOptions['alpha'];
        $this->waterQuality     = $this->waterOptions['quality'];

        $this->thumbType        = $this->thumbOptions['type'];
        $this->thumbPath        = $this->thumbOptions['path'];
        $this->thumbWidth       = $this->thumbOptions['width'];
        $this->thumbHeight      = $this->thumbOptions['height'];
        $this->thumbPrefix      = $this->thumbOptions['prefix'];
        $this->thumbSuffix      = $this->thumbOptions['suffix'];
    }

    /*********************************************************************************
    * add watermark
    *********************************************************************************/

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
    public function watermark($img, $outImg = '', $pos = '', $waterImg = '', $alpha = '', $text = '')
    {
        $imgType   = pathinfo($img, PATHINFO_EXTENSION);

        //验证原图像
        if ( !$this->_checkImage($img, $imgType) ) {
            return false;
        }

        //判断另存图像
        $outImg     = $outImg   ? : $img;
        //水印位置
        $pos        = $pos      ? : $this->waterPos;

        //水印透明度
        $alpha      = $alpha    ? : $this->waterAlpha;

        $imgInfo    = getimagesize($img);
        $imgWidth   = $imgInfo[0];
        $imgHeight  = $imgInfo[1];

        //验证水印图像
        $waterImg   = $waterImg ? : $this->waterImg;
        $waterImgType  = pathinfo($waterImg, PATHINFO_EXTENSION);

        //获得水印信息
        if ( $waterImgOn = $this->_checkImage($waterImg, $waterImgType) ) {
            $waterImgType = $this->_getImageType($imgType);
            $waterInfo    = getimagesize($waterImg);
            $waterWidth   = $waterInfo[0];
            $waterHeight  = $waterInfo[1];
            $wImg = call_user_func("imagecreatefrom{$waterImgType}", $waterImg);
        } else {
            //水印文字
            $text       = $text ?: $this->waterText;

            if ( !is_file($this->waterOptions['fontFile']) ) {
                throw new InvalidConfigException('请配置正确的水印文字资源路径');
            }

            if (!$text || strlen($this->waterOptions['fontColor']) !== 7) {
                return false;
            }

            $textInfo       = imagettfbbox($this->waterOptions['fontSize'], 0, $this->waterOptions['fontFile'], $text);
            $waterWidth     = $textInfo [2] - $textInfo [6];
            $waterHeight    = $textInfo [3] - $textInfo [7];
        }

        //建立原图资源
        if ($imgHeight < $waterHeight || $imgWidth < $waterWidth) {
            return false;
        }

        $resImg = call_user_func("imagecreatefrom{$imgType}", $img);

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

        if ($waterImgOn && isset($resImg) && isset($wImg)) {
            $waterInfo = getimagesize($waterImg);

            if ($waterInfo[2] === 3) {
                imagecopy($resImg, $wImg, $x, $y, 0, 0, $waterWidth, $waterHeight);
            } else {
                imagecopymerge($resImg, $wImg, $x, $y, 0, 0, $waterWidth, $waterHeight, $alpha);
            }
        } else {
            $r       = hexdec(substr($this->waterOptions['fontColor'], 1, 2));
            $g       = hexdec(substr($this->waterOptions['fontColor'], 3, 2));
            $b       = hexdec(substr($this->waterOptions['fontColor'], 5, 2));
            $color   = imagecolorallocate($resImg, $r, $g, $b);
            $charset = 'UTF-8';

            imagettftext(
                $resImg, $this->waterOptions['fontSize'], 0, $x, $y,
                $color, $this->waterOptions['fontFile'], iconv($charset, 'utf-8', $text)
            );
        }

        if ( $imgType === IMAGE_JPEG ) {
            imagejpeg($resImg, $outImg, $this->waterQuality);
        } else {
            call_user_func("image{$imgType}", $resThumb, $outFile);
        }

        if (isset($resImg)) {
            imagedestroy($resImg);
        }

        if (isset($resThumb)) {
            imagedestroy($resThumb);
        }

        return true;
    }

    /*********************************************************************************
    * Image cutting processing
    *********************************************************************************/

    public function thumb($img, $outFile = '', $path = '', $thumbWidth = '', $thumbHeight = '', $thumbType = '')
    {
        return $this->thumbnail($img, $outFile, $path, $thumbWidth, $thumbHeight, $thumbType);
    }

    /**
     * 图片裁切处理(制作缩略图)
     * @param string $img         操作的图片文件路径(原图)
     * @param string $outFile     另存文件名
     * @param string $path        文件存放路径
     * @param string $thumbWidth  缩略图宽度
     * @param string $thumbHeight 缩略图高度
     * @param string $thumbType   裁切图片的方式
     * @return bool|string
     */
    public function thumbnail($img, $outFile = '', $path = '', $thumbWidth = '', $thumbHeight = '', $thumbType = '')
    {
        $imgType   = pathinfo($img, PATHINFO_EXTENSION);

        if (!$this->_checkImage($img, $imgType)) {
            return false;
        }

        //基础配置
        $thumbType   = $thumbType   ? : $this->thumbOptions['type'];
        $thumbWidth  = $thumbWidth  ? : $this->thumbOptions['width'];
        $thumbHeight = $thumbHeight ? : $this->thumbOptions['height'];
        $path        = $path        ? : $this->thumbOptions['path'];

        //获得图像信息
        $imgInfo   = getimagesize($img);
        $imgWidth  = $imgInfo[0];
        $imgHeight = $imgInfo[1];
        $imgType   = $this->_getImageType($imgType);

        //获得相关尺寸
        $thumbSize = $this->calcThumbSize($imgWidth, $imgHeight, $thumbWidth, $thumbHeight, $thumbType);

        //原始图像资源
        // imagecreatefromgif() imagecreatefrompng() imagecreatefromjpeg() imagecreatefromwbmp()
        $resImg   = call_user_func("imagecreatefrom{$imgType}" , $img);

        //缩略图的资源
        if ($imgType === static::IMAGE_GIF) {
            $resThumb  = imagecreate($thumbSize[0], $thumbSize[1]);
            $color      = imagecolorallocate($resThumb, 255, 0, 0);
        } else {
            $resThumb  = imagecreatetruecolor($thumbSize[0], $thumbSize[1]);
            imagealphablending($resThumb, false); //关闭混色
            imagesavealpha($resThumb, true); //储存透明通道
        }

        //绘制缩略图X
        if (function_exists('imagecopyresampled')) {
            imagecopyresampled($resThumb, $resImg, 0, 0, 0, 0, $thumbSize[0], $thumbSize[1], $thumbSize[2], $thumbSize[3]);
        } else {
            imagecopyresized($resThumb, $resImg, 0, 0, 0, 0, $thumbSize[0], $thumbSize[1], $thumbSize[2], $thumbSize[3]);
        }

        //处理透明色
        if ($imgType === static::IMAGE_GIF) {
            imagecolortransparent($resThumb, $color);
        }

        //配置输出文件名
        $imgInfo   = pathinfo($img);
        $outFile   = $outFile ? : $this->thumbOptions['prefix'] . $imgInfo['filename'] . $this->thumbOptions['suffix'] . '.' . $imgInfo['extension'];
        $uploadDir = $path ? : dirname($img);
        $outFile = $uploadDir . DIRECTORY_SEPARATOR . $outFile;

        Directory::create($uploadDir);

        // imagepng(), imagegif(), imagejpeg(), imagewbmp()
        call_user_func("image{$imgType}", $resThumb, $outFile);

        if (isset($resImg)) {
            imagedestroy($resImg);
        }

        if (isset($resThumb)) {
            imagedestroy($resThumb);
        }

        return $outFile;
    }

    /*********************************************************************************
    * helper method
    *********************************************************************************/

    /**
     * 环境验证
     * @param string $img  图像路径
     * @param string $imgType  type of img e.g. jpg
     * @return bool
     */
    private function _checkImage($img, $imgType='')
    {
        $imgType = $imgType ?: pathinfo($img, PATHINFO_EXTENSION);

        return file_exists($img) && in_array($imgType, static::$getImageTypes());
    }

    private function _getImageType($imgType)
    {
        if ( $type === IMAGE_JPG ) {
            return IMAGE_JPEG;
        } elseif ( $type === IMAGE_BMP ) {
            return 'wbmp';
        }

        return $type;
    }

    /**
     *
     * 计算获得缩略图的尺寸信息
     * @param string  $imgWidth         原图宽度
     * @param string  $imgHeight        原图高度
     * @param string  $thumbWidth       缩略图宽度
     * @param string  $thumbHeight      缩略图的高度
     * @param string  $thumbType        处理方式
     * @return array
     */
    private function calcThumbSize($imgWidth, $imgHeight, $thumbWidth, $thumbHeight, $thumbType)
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

        return [
            $w,
            $h,
            $oldThumbWidth,
            $oldThumbHeight,
        ];
    }

    /*********************************************************************************
    * getter/setter
    *********************************************************************************/

    public static function getImageTypes()
    {
        return [
            self::IMAGE_BMP,
            self::IMAGE_JPEG,
            self::IMAGE_JPG,
            self::IMAGE_GIF,
            self::IMAGE_PNG,
        ];
    }

    /**
     * @param  string $name
     * @param  string $type water|cutting
     * @return string
     */
    public function getOption($name, $type = 'water')
    {
        return $type === 'water' ? $this->getWaterOption($name) : $this->getCuttingOption($name);
    }

    /**
     * set waterOptions
     * @param array $options
     */
    public function setWaterOptions(array $options)
    {
        $this->waterOptions = array_merge($this->waterOptions, $options);

        return $this;
    }

    public function getWaterOptions()
    {
        return $this->waterOptions;
    }

    /**
     * getWaterOption
     * @param  string $name
     * @param  string|null $default
     * @return string
     */
    public function getWaterOption($name, $default = null)
    {
        return array_key_exists($name, $this->waterOptions) ? $this->waterOptions[$name] : $default;
    }

    /**
     * set thumbOptions
     * @param array $options
     */
    public function setCuttingOptions(array $options)
    {
        $this->thumbOptions = array_merge($this->thumbOptions, $options);

        return $this;
    }

    public function getCuttingOptions()
    {
        return $this->thumbOptions;
    }

    /**
     * getCuttingOption
     * @param  string $name
     * @param  string|null $default
     * @return string
     */
    public function getCuttingOption($name, $default = null)
    {
        return array_key_exists($name, $this->thumbOptions) ? $this->thumbOptions[$name] : $default;
    }
}
