<?php
/**
 * Created by PhpStorm.
 * User: Inhere
 * Date: 14-4-3
 * Time: 下午11:47
 * 文件上传
 */

namespace inhere\tools\files;

class Uploador
{
    //文件类型
    private $ext=array();
    //文件上传大小
    public $size;
    //文件保存目录
    public $path;
    //文件上传表单
    //public $form;//$field
    //错误信息
    public $error;
    //是否开启缩略图处理
    public $thumbOn;
    //缩略图处理
    public $thumb = array();
    //水印处理
    public $waterMarkOn;
    //上传成功的 文件信息
    public $uploadedFile = array();
    public $config = array();

    /**
     * @param string $path 保存路径
     * @param null $ext 可用扩展名
     * @param string $size 大小
     * @param string $waterMarkOn 水印是否开始
     * @param int|string $thumbOn 缩略图是否开启
     * @param array $thumb 缩略图配置
     */
    function __construct(
        $path='',        $ext='',    $size='',
        $waterMarkOn='', $thumbOn=1, $thumb=array()
    ) {
        $config         = $this->config = Ulue::$app->get("upload");
        $this->path     = empty($path) ? $config['path'] : $path; //上传路径
        $this->ext      = empty($ext) && !is_array($ext) ? array_keys($config['ext_size']) : $ext; //上传类型
        $ext            = array();
        foreach ($this->ext as $v) {
            $ext[]          = strtolower($v);
        }
        $this->ext      = $ext;
        //array_change_key_case — 返回 字符串键名(除去数字型的) 全为小写或大写的数组 1 大写 0 小写
        $this->size     = $size ? $size : array_change_key_case($config['ext_size'], 0);
        $this->waterMarkOn = empty($waterMarkOn) ? $config['water_on'] : $waterMarkOn;
        $this->thumbOn  = empty($thumbOn) ? $config['thumb_on'] : $thumbOn;
        $this->thumb    = $thumb;//传参数，只需要 宽 高 类型
    }

    public static function make(
        $path='' ,       $ext='' ,  $size='',
        $waterMarkOn='', $thumbOn=1, $thumb=array()
    ) {
        return new static($path,$ext, $size, $waterMarkOn, $thumbOn,$thumb);
    }

    /**
     * 文件上传
     * 返回文件信息多维数组|没有文件则返回FALSE(可用于判断)
     */
    public function upload()
    {
        if ( !$this->checkDir($this->path) ){
            $this->error = $this->path."目录创建失败或者不可写";
            return false;
        }

        $files = $this->format();

        //验证文件
        foreach($files as $v){
            $info           = pathinfo( $v['name']);//文件信息
            !isset($info['extension']) || $v['ext'] = $info['extension'];//获得文件扩展名
            $v['filename']  = $info['filename'];//文件名

            if ( !$this->checkFile($v) ){
                continue;//没有文件|文件不合法，跳过
            }

            $uploadFile     = $this->save($v);

            if ( $uploadFile ){
                $this->uploadedFile[] =$uploadFile;//$this->uploadedFile[] 多维数组，处理多文件上传
            }
        }

        return $this->uploadedFile;
    }

    /**
     * 存储文件
     * @param array $file 上传文件数组
     * @return bool
     */
    private function save($file)
    {
        $is_img     = 0;
        $nowName    = time().'_'.rand(1,9999).'.'.$file['ext'];
        $filePath   = $this->path.'/'.$nowName;

        //Farr($file);
        //判断文件是图片，
        if ( in_array($file['ext'],array('jpg','jpeg','gif','bmp','png')) && getimagesize($file['tmp_name']) ){
            $filePath   = $this->config['img_path'].'/'.time().'.'.$file['ext'];

            if ( !$this->checkDir( $this->config['img_path'] ) ) {
                $this->error = $this->config['img_path']."目录创建失败或者不可写";
                return false;
            }

            $is_img     = 1;
        }

        if ( !move_uploaded_file($file['tmp_name'],$filePath) ){
            $this->error='上传文件失败！';
            return false;
        }

        //普通文件到此就返回了
        if ( !$is_img ){
            $filePath = ltrim(str_replace(WEB_PATH, '', $filePath), '/');//去掉绝对路径中的APP_PATH，便于保存
            return array_merge(array('path'=>$filePath), $file);
        }

        //对上传图片进行处理
        $img = new GImage();

        //缩略图处理
        if ( $this->thumbOn ){
            $args = array();

            if ( is_array($this->thumb) ){
                array_unshift($args,$filePath,'');
                array_merge($args,$this->thumb);
            } else {
                array_unshift($args,$filePath);
            }

            // 回调 $img->thumb()方法，并传入 参数 array $args;
            $thumbFile= call_user_func_array(array($img,'thumb'),$args);
        }

        //加水印
        if ( $this->waterMarkOn ){
            $img->water($filePath);
        }

        $filePath = ltrim(str_replace(SITE_PATH, '', $filePath), '/');

        if ($this->thumbOn) {
            $thumbFile = ltrim(str_replace(SITE_PATH, '', $thumbFile), '/');
            $arr = array("path" => $filePath, "thumb" => $thumbFile);
        } else {
            $arr = array("path" => $filePath);
        }

        return array_merge($arr, $file);
    }

    /**
     * 目录创建 目录验证
     * @param $path
     * @return bool
     */
    private function checkDir($path)
    {
        return (is_dir($path) || mkdir($path, 0664, true)) && is_writable($path) ? true: false;
    }

    /**
     * 将上传文件整理为标准数组,二维转换为一位数组
     */
    private function format()
    {
        $files  = $_FILES;

        if ( !isset($files) ){
            $this->error='没有任何文件上传';
            return false;
        }

        $info   = array();
        $n      = 0;

        foreach( $files as $name => $v){

            if ( is_array($v['name'])){
                $count                  = count($v['name']);

                for( $i=0;$i<$count;$i++){
                    foreach($v as $m =>$f){
                        $info[$n][$m]       = $f[$i];
                    }
                    $info[$n]['fieldName']  = $name;//添加一个单元，保存字段名
                    $n++;
                }

            } else {
                $info[$n]               = $v;
                $info[$n]['fieldName']  = $name;//字段名
                $n++;
            }
        }

        return $info;
    }

    /**
     * 验证上传文件,是否有文件、上传类型、文件大小
     * @param $file
     * @return bool
     */
    private function checkFile($file)
    {
        if ( $file['error'] != 0){
            $this->error($file['error']);//错误类型
            return false;
        }

        $ext_size = empty($this->size) ? $this->config['ext_size']:$this->size;
        $ext      = strtolower($file['ext']);

        if ( !in_array($ext, $this->ext) ){
            $this->error = '非法上传类型！';

            return false;
        }
        if ( !empty($ext_size) && $file['size']>$ext_size ){
            $this->error = '上传文件太大！';

            return false;
        }
        if ( !is_uploaded_file($file['tmp_name'])){
            $this->error = '非法文件！';

            return false;
        }

        return true;
    }

    /**
     * 获得错误类型
     * @param $type
     */
    private function error($type)
    {
        switch($type){
            case UPLOAD_ERR_INI_SIZE:   # 1
                $this->error = "超过php.ini 配置文件指定大小！";
                break;
            case UPLOAD_ERR_FORM_SIZE:  # 2
                $this->error = "上传文件超过Html表单指定大小！";
                break;
            case UPLOAD_ERR_NO_FILE:    # 3
                $this->error = "没有上传任何文件！";
                break;
            case UPLOAD_ERR_PARTIAL:    # 4
                $this->error = "文件只上传了一部分！";
                break;
            case UPLOAD_ERR_NO_TMP_DIR: # 5
                $this->error = "没有文件上传的临时目录！";
                break;
            case UPLOAD_ERR_CANT_WRITE: # 6
                $this->error = "不能写入临时上传文件！";
                break;
        }
    }
    /**
     * 返回上传时发生的错误原因
     * @return string
     */
    public function getError()
    {
        return $this->error;
    }

}