<?php
/**
 * Created by PhpStorm.
 * User: Inhere
 * Date: 14-4-2
 * Time: 下午2:13
 * 图像处理
 * USE:
 *  $img = "/www/uploads/test.jpg"
 *  $pic = Picture::make([], [
 *       'path'   => dirname($img) . '/thumbs',
 *       'width'  => 120,
 *       'height' => 60,
 *       'suffix' => '_120x60',
 *  ])->thumbnail($img);
 *
 *  if ($pic->hasError()) {
 *      return $res->write($pic->getError());
 *  }
 *
 *  $data = $pic->getResult();
 *
 */

namespace inhere\librarys\files;

use inhere\librarys\exceptions\InvalidArgumentException;
use inhere\librarys\exceptions\InvalidConfigException;
use inhere\librarys\exceptions\ExtensionMissException;
use inhere\librarys\exceptions\FileSystemException;

/**
 * Class Picture
 * @package inhere\librarys\files
 */
class Picture
{
    # 1 图片水印
    const WATER_IMAGE = 1;

    # 2 文字水印
    const WATER_TEXT  = 2;

    // image types
    const IMAGE_BMP  = 'bmp';
    const IMAGE_JPG  = 'jpg';
    const IMAGE_JPEG = 'jpeg';
    const IMAGE_GIF  = 'gif';
    const IMAGE_PNG  = 'png';

    /**
     * 图像水印设置
     * @var array
     */
    protected $waterOptions = [
        // 1 图片水印  2 文字水印
        'type'           => 1,
        // 水印字体文件
        'fontFile'       => '',
        // 水印图像
        'img'            => '',
        // 图像输出路径
        'path'          => '',
        // 水印位置 1-9
        'pos'            => 1 ,
        // 水印透明度 transparency
        'alpha'          => 40,
        // 水印压缩质量
        'quality'        => 80,
        // 水印文字
        'text'          => 'THE WATER',
        // 水印字体颜色
        'fontColor'     => 'ededed',
        // 水印字体大小
        'fontSize'      => 20
    ];

    /**
     * 图片(缩略图)剪裁设置
     * @var array
     */
    protected $thumbOptions = [
        // 缩略图宽度
        'width'          => 150,
        // 缩略图高度
        'height'        => 100,
        // 缩略图文件名前缀 thumb_
        'prefix'        => '',
        // 缩略图文件名后缀
        'suffix'        => '',
        // 生成缩略图方式,
        // 1:固定宽度，高度自增    2:固定高度，宽度自增     3:固定宽度，高度裁切
        // 4:固定高度，宽度裁切    5:缩放最大边，原图不裁切  6:按缩略图尺寸自动裁切图片
        'type'          => 6,
        // 缩略图存放路径
        'path'          => ''
    ];

    private $_error = null;

    /**
     * 正在操作的文件记录
     * @var string
     */
    public $working = [
        'raw' => '', // 正在操作的原文件
        'out' => '', // 正在操作的输出文件
    ];

    private $_result = [];

    /*********************************************************************************
     * build
     *********************************************************************************/

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
     * @param  array $waterOptions
     * @param  array $thumbOptions
     * @throws ExtensionMissException
     */
    public function __construct(array $waterOptions=[], array $thumbOptions=[])
    {
        if ( !extension_loaded('gd') ) {
            throw new ExtensionMissException('This tool required extension [gd].');
        }

        $this->waterOptions['fontFile'] = dirname(__DIR__) . '/resources/fonts/Montserrat-Bold.ttf';

        $this->setWaterOptions($waterOptions)->setThumbOptions($thumbOptions);

        $this->init();
    }

    protected function init() {}

    /*********************************************************************************
    * add watermark
    *********************************************************************************/

    /**
     * 水印处理
     * @param string $img 操作的图像
     * @param string $outPath 另存的图像
     * @param string $pos 水印位置
     * @param string $waterImg 水印图片
     * @param string $alpha 透明度
     * @param string $text 文字水印内容
     * @return Picture
     * @throws InvalidConfigException
     */
    public function watermark($img, $outPath = '', $pos = '', $waterImg = '', $alpha = '', $text = '')
    {
        // 验证原图像 和 是否已有错误
        if ( false === $this->_checkImage($img) || $this->hasError()) {
            return $this;
        }

        $imgInfo  = pathinfo($img);
        $imgType  = $this->_handleImageType($imgInfo['extension']);
        $outPath  = $outPath ?: ( $this->waterOptions['path'] ? : dirname($img) );
        $pos      = $pos    ?: $this->waterOptions['pos'];
        $alpha    = $alpha  ?: $this->waterOptions['alpha'];
        $waterImg = $waterImg ?: $this->waterOptions['img'];

        list($imgWidth, $imgHeight) = getimagesize($img);

        if ( $waterImg ) {

            // 验证水印图像
            if ( false === $this->_checkImage($waterImg) ) {
                return $this;
            }

            $waterImgType = $this->_handleImageType( pathinfo($waterImg, PATHINFO_EXTENSION) );
            list($waterWidth, $waterHeight) = getimagesize($waterImg);

            if ($imgHeight < $waterHeight || $imgWidth < $waterWidth) {
                $this->_error = 'The image is too small.';

                return $this;
            }

            // create water image resource
            $resWaterImg = call_user_func("imagecreatefrom{$waterImgType}", $waterImg);
        } else {
            //水印文字
            $text = $text ?: $this->waterOptions['text'];

            if ( !is_file($this->waterOptions['fontFile']) ) {
                throw new InvalidConfigException('请配置正确的水印文字资源路径');
            }

            if (!$text || strlen($this->waterOptions['fontColor']) !== 6) {
                throw new InvalidConfigException('The watermark font color length must equal to 6.');
            }

            $textInfo    = imagettfbbox($this->waterOptions['fontSize'], 0, $this->waterOptions['fontFile'], $text);
            $waterWidth  = $textInfo[2] - $textInfo[6];
            $waterHeight = $textInfo[3] - $textInfo[7];
        }

        // create image resource 建立原图资源
        $resImg = call_user_func("imagecreatefrom{$imgType}", $img);

        //水印位置处理
        list($x, $y) = $this->_calcWaterCoords($pos, $imgWidth, $waterWidth, $imgHeight, $waterHeight);

        if ($waterImg && isset($waterImgType) && isset($resWaterImg)) {

            // is png image. 'IMAGETYPE_PNG' === 3
            if ($waterImgType === self::IMAGE_PNG) {
                imagecopy($resImg     , $resWaterImg, $x, $y, 0, 0, $waterWidth, $waterHeight);
            } else {
                imagecopymerge($resImg, $resWaterImg, $x, $y, 0, 0, $waterWidth, $waterHeight, $alpha);
            }
        } else {
            $r       = hexdec(substr($this->waterOptions['fontColor'], 0, 2));
            $g       = hexdec(substr($this->waterOptions['fontColor'], 2, 2));
            $b       = hexdec(substr($this->waterOptions['fontColor'], 4, 2));
            $color   = imagecolorallocate($resImg, $r, $g, $b);
            $charset = 'UTF-8';

            imagettftext(
                $resImg, $this->waterOptions['fontSize'], 0, $x, $y,
                $color, $this->waterOptions['fontFile'], iconv($charset, 'utf-8', $text)
            );
        }

        if ( ! Directory::create($outPath) ) {
            $this->_error = 'Failed to create the output directory path!. OUT-PATH: ' . $outPath;
            return $this;
        }

        $outFile = $outPath . '/' . $imgInfo['basename'];

        if ( $imgType === self::IMAGE_JPEG ) {
            imagejpeg($resImg, $outFile, $this->waterOptions['quality']);
        } elseif ( $imgType === self::IMAGE_PNG ) {
            imagepng($resImg, $outFile, ceil($this->waterOptions['quality']/10));
        } else {
            call_user_func("image{$imgType}", $resImg, $outFile);
        }

        if (isset($resImg)) {
            imagedestroy($resImg);
        }

        if (isset($resThumb)) {
            imagedestroy($resThumb);
        }

        $this->working = [
            'raw' => $img,
            'out' => $outFile,
        ];

        $this->_result['workWater']['rawFile'] = $img;
        $this->_result['workWater']['outFile'] = $outFile;

        return $this;
    }

    /*********************************************************************************
     * Image cutting processing
     ********************************************************************************/

    /**
     * @param $img
     * @param string $outFile
     * @param string $path
     * @param string $thumbWidth
     * @param string $thumbHeight
     * @param string $thumbType
     * @return static
     */
    public function thumb($img, $outFile = '', $path = '', $thumbWidth = '', $thumbHeight = '', $thumbType = '')
    {
        return $this->thumbnail($img, $outFile, $path, $thumbWidth, $thumbHeight, $thumbType);
    }

    /**
     * 图片裁切处理(制作缩略图)
     * @param string $img         操作的图片文件路径(原图)
     * @param string $outPath     文件存放路径
     * @param string $outFilename 另存文件名
     * @param string $thumbWidth  缩略图宽度
     * @param string $thumbHeight 缩略图高度
     * @param string $thumbType   裁切图片的方式
     * @return static
     */
    public function thumbnail($img, $outPath = '', $outFilename = '', $thumbWidth = '', $thumbHeight = '', $thumbType = '')
    {
        if (!$this->_checkImage($img) || $this->hasError()) {
            return $this;
        }

        $imgInfo   = pathinfo($img);
        $rawImgType   = $imgInfo['extension'];

        //基础配置
        $thumbType   = $thumbType   ? : $this->thumbOptions['type'];
        $thumbWidth  = $thumbWidth  ? : $this->thumbOptions['width'];
        $thumbHeight = $thumbHeight ? : $this->thumbOptions['height'];
        $outPath     = $outPath     ? : ( $this->thumbOptions['path'] ? : dirname($img) );

        //获得图像信息
        list($imgWidth, $imgHeight) = getimagesize($img);
        $imgType   = $this->_handleImageType($rawImgType);

        //获得相关尺寸
        $thumbSize = $this->_calcThumbSize($imgWidth, $imgHeight, $thumbWidth, $thumbHeight, $thumbType);

        //原始图像资源
        // imagecreatefromgif() imagecreatefrompng() imagecreatefromjpeg() imagecreatefromwbmp()
        $resImg   = call_user_func("imagecreatefrom{$imgType}" , $img);

        //缩略图的资源
        if ($imgType === static::IMAGE_GIF) {
            $resThumb  = imagecreate($thumbSize[0], $thumbSize[1]);
            $color     = imagecolorallocate($resThumb, 255, 0, 0);
            imagecolortransparent($resThumb, $color); //处理透明色
        } else {
            $resThumb  = imagecreatetruecolor($thumbSize[0], $thumbSize[1]);
            imagealphablending($resThumb, false); //关闭混色
            imagesavealpha($resThumb, true);      //储存透明通道
        }

        // 绘制缩略图X
        if (function_exists('imagecopyresampled')) {
            imagecopyresampled($resThumb, $resImg, 0, 0, 0, 0, $thumbSize[0], $thumbSize[1], $thumbSize[2], $thumbSize[3]);
        } else {
            imagecopyresized($resThumb  , $resImg, 0, 0, 0, 0, $thumbSize[0], $thumbSize[1], $thumbSize[2], $thumbSize[3]);
        }

        //配置输出文件名
        $outFilename   = $outFilename ?: $this->thumbOptions['prefix'] . $imgInfo['filename'] . $this->thumbOptions['suffix'] . '.' . $rawImgType;
        $outFile = $outPath . DIRECTORY_SEPARATOR . $outFilename;

        if ( ! Directory::create($outPath) ) {
            $this->_error = 'Failed to create the output directory path!. OUT-PATH: ' . $outPath;
            return $this;
        }

        // generate image to dst file. imagepng(), imagegif(), imagejpeg(), imagewbmp()
        call_user_func("image{$imgType}", $resThumb, $outFile);

        if (isset($resImg)) {
            imagedestroy($resImg);
        }

        if (isset($resThumb)) {
            imagedestroy($resThumb);
        }

        $this->working = [
            'raw' => $img,
            'out' => $outFile,
        ];

        $this->_result['workThumb']['rawFile'] = $img;
        $this->_result['workThumb']['outFile'] = $outFile;

        return $this;
    }

    /**
     * 显示 image file 到浏览器
     * @param string $img 图片文件
     * @return bool
     * @throws FileSystemException
     * @throws InvalidArgumentException
     */
    public static function show($img)
    {
        if ( !is_file($img) || !is_readable($img)) {
            throw new FileSystemException('image file don\'t exists or file is not readable!');
        }

        $type = pathinfo($img, PATHINFO_EXTENSION);
        $type = $type === self::IMAGE_JPG ? self::IMAGE_JPEG : $type;

        if ( !static::isSupportedType($type) ) {
            throw new InvalidArgumentException("image type [$type] is not supported!", 1);
        }

        /** @var resource $resImg */
        $resImg = call_user_func("imagecreatefrom{$type}" , $img);

        // 保持png图片的透明度
        if ( $type === self::IMAGE_PNG ) {
            // 设置标记以在保存 PNG 图像时保存完整的 alpha 通道信息。
            imagesavealpha($resImg, true);
        }

        header('Cache-Control: max-age=1, s-maxage=1, no-cache, must-revalidate');
        header('Content-type: image/'.$type.';charset=utf8'); // 生成图片格式 png jpeg 。。。

        ob_clean();
        //生成图片,在浏览器中进行显示-格式 $type ，与上面的header声明对应
        // e.g. imagepng($resImg);
        $success   = call_user_func("image{$type}" , $resImg);

        // 已经显示图片后，可销毁，释放内存（可选）
        imagedestroy($resImg);

        return $success;
    }

    /*********************************************************************************
    * helper method
    *********************************************************************************/

    /**
     * 验证
     * @param string $img  图像路径
     * @return bool
     */
    private function _checkImage($img)
    {
        if ( !file_exists($img) ) {
            $this->_error = 'Image file dom\'t exists! file: ' . $img;
        } elseif ( !($type = pathinfo($img, PATHINFO_EXTENSION)) || !in_array($type ,static::getImageTypes()) ) {
            $this->_error = "Image type [$type] is not supported.";
        }

        return !$this->hasError();
    }

    /**
     * @param $type
     * @return string
     */
    private function _handleImageType($type)
    {
        if ( $type === self::IMAGE_JPG ) {
            return self::IMAGE_JPEG;
        }

        return $type;
    }

    protected function _calcWaterCoords($pos, $imgWidth, $waterWidth, $imgHeight, $waterHeight)
    {
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

        return [$x, $y];
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
    private function _calcThumbSize($imgWidth, $imgHeight, $thumbWidth, $thumbHeight, $thumbType)
    {
        //初始化缩略图尺寸
        $w = $thumbWidth;
        $h = $thumbHeight;

        //初始化原图尺寸
        $oldThumbWidth  = $imgWidth;
        $oldThumbHeight = $imgHeight;

        // 原图比需要的缩略图还小
        if ($imgWidth <= $thumbWidth && $imgHeight <= $thumbHeight) {
            return [$imgWidth, $imgHeight, $oldThumbWidth, $oldThumbHeight];
        }

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

        return [$w, $h, $oldThumbWidth, $oldThumbHeight];
    }

    /*********************************************************************************
    * getter/setter
    *********************************************************************************/

    public static function getImageTypes()
    {
        return [
//            self::IMAGE_BMP,
            self::IMAGE_JPEG,
            self::IMAGE_JPG,
            self::IMAGE_GIF,
            self::IMAGE_PNG,
        ];
    }

    /**
     * @param string $type e.g. jpg
     * @return bool
     */
    public static function isSupportedType($type)
    {
        return in_array($type, static::getImageTypes());
    }

    /**
     * @param  string $name
     * @param  string $type water|cutting
     * @return string
     */
    public function getOption($name, $type = 'water')
    {
        return $type === 'water' ? $this->getWaterOption($name) : $this->getThumbOption($name);
    }

    /**
     * set waterOptions
     * @param array $options
     * @return $this
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
     * @return $this
     */
    public function setThumbOptions(array $options)
    {
        $this->thumbOptions = array_merge($this->thumbOptions, $options);

        return $this;
    }

    public function getThumbOptions()
    {
        return $this->thumbOptions;
    }

    /**
     * getThumbOption
     * @param  string $name
     * @param  string|null $default
     * @return string
     */
    public function getThumbOption($name, $default = null)
    {
        return array_key_exists($name, $this->thumbOptions) ? $this->thumbOptions[$name] : $default;
    }

    /**
     * @return bool
     */
    public function hasError()
    {
        return $this->_error !== null;
    }

    /**
     * @return string
     */
    public function getError()
    {
        return $this->_error;
    }

    public function getResult()
    {
        return $this->_result;
    }

    /*********************************************************************************
    * other
    *********************************************************************************/

    /**
     * @param $pngImg
     * @param string $outPath
     */
    public function png2gif($pngImg, $outPath = '')
    {
        // Load the PNG
        $png = imagecreatefrompng($pngImg);

        $info = pathinfo($pngImg);
        $outPath = $outPath ?: $info['dirname'];
        $filename = $info['filename'];

        // Save the image as a GIF
        imagegif($png, "{$outPath}/{$filename}.gif");

        // Free from memory
        imagedestroy($png);
    }
}
