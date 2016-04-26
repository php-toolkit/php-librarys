<?php
/**
 * Created by PhpStorm.
 * User: Inhere
 * Date: 14-4-3
 * Time: 下午11:47
 * 文件上传
 */

namespace inhere\tools\files;

/**
 * Class Uploader
 * @package inhere\tools\files
 */
class Uploader
{
    /**
     * $_FILES
     * @var array
     */
    private $_data = [];

    /**
     * 错误信息
     * @var string
     */
    private $error;

    /**
     * @var Picture
     */
    private $picture;

    /**
     * 上传成功的 文件信息
     * @var array
     */
    private $uploadedFiles = [];

    /**
     * @var array
     */
    private $result = [];

    /**
     * @var array
     */
    public $config = [

        // 保存文件路径
        'path' => '',

        'basepath' => '',

        // 允许的文件类型 e.g. ['jpg', 'png']
        'ext' => [],

        // 文件上传大小 最大值
        'maxSize' => 0,

        // 水印是否开始
        'waterOn' => false,

        // 缩略图是否开启
        'thumbOn' => false,
    ];

    /**
     * 水印配置 当 $config['waterOn'] == true 有效
     * @var array
     */
    public $waterConfig = [];

    /**
     * 缩略图配置 当 $config['thumbOn'] == true 有效
     * 只需要 宽 高 类型
     * @var array
     */
    public $thumbConfig = [];

    protected static $imageTypes = [ 'jpg','jpeg','gif','bmp','png'];

    /**
     * @param array $config
     * @param array $waterConfig
     * @param array $thumbConfig
     * @return static
     */
    public static function make(array $config = [], array $waterConfig = [], array $thumbConfig = [])
    {
        return new static($config, $waterConfig, $thumbConfig);
    }

    /**
     * @param array $config
     * @param array $waterConfig 水印配置
     * @param array $thumbConfig 缩略图配置
     */
    public function __construct(array $config = [], array $waterConfig = [], array $thumbConfig = [])
    {
        $this->_data = &$_FILES;
        $this->config = array_merge($this->config, $config);
        $this->waterConfig = $waterConfig;
        $this->thumbConfig = $thumbConfig;
    }

    /**
     * @param string $name key of the $_FILES
     * @param string $targetFile save to the path
     * @return $this
     */
    public function uploadOne($name, $targetPath)
    {
        if (!$name || !isset($this->_data[$name])) {
            $this->error = "name [$name] don't exists of the _FILES";
            return $this;
        }

        !$targetPath && ($targetPath = $this->config['path']);
        $result = $this->_uploadHandle($this->_data[$name], $targetPath);

        $this->result = $result;

        return $this;
    }

    /**
     * 文件上传
     * 返回文件信息多维数组|没有文件则返回FALSE(可用于判断)
     * @param array $keys 取指定的key对应的文件
     * @param string $targetPath
     * @return array|bool
     */
    public function uploadMulti(array $keys = [], $targetPath = '')
    {
        if (!$this->_data) {
            return false;
        }

        !$targetPath && ($targetPath = $this->config['path']);

        foreach($this->_data as $key => $file){
            if ($keys && !in_array($key, $keys)) {
                continue;
            }

            $this->result[$key] = $this->_uploadHandle($file, $targetPath);
        }

        return $this;
    }

    protected function _uploadHandle($sourceFile, $targetPath)
    {
        $filename = $sourceFile['name'];

        if ( is_array($filename) ) {
            $result = [];
            $file = $this->_decodeData([
                $filename,
                $sourceFile['type'],
                $sourceFile['tmp_name'],
                $sourceFile['error'],
                $sourceFile['size']
            ]);

            foreach ($file as $key => $value) {
                $result[$key] = $this->_uploadHandle($value, $targetPath);
            }
        } else {
            $file = $sourceFile;
            //文件信息
            $file['ext'] = pathinfo($file['name'], PATHINFO_EXTENSION);

            //没有文件 || 文件不合法，跳过
            if ( !$this->_checkFile($file) ){
                return [];
            }

            $result = $this->moveTo($file, $targetPath);

            //判断文件是图片，
            if ( in_array($file['ext'], static::$imageTypes) && getimagesize($result['targetFile']) ) {
                //缩略图处理
                if ($this->config['thumbOn']) {
                    $this->makeThumb($result['targetFile'], $result['targetName'], false);
                }

                //加水印
                if ($this->config['waterOn']) {
                    $this->watermark($result['targetFile'], $result['targetName'], false);
                }
            }
        }

        return $result;
    }

    /**
     * Method to decode a data array.
     * @param   array  $data  The data array to decode.
     * @return  array
     */
    protected function _decodeData(array $data)
    {
        $result = [];

        if (is_array($data[0])) {
            foreach ($data[0] as $k => $v) {
                $result[$k] = $this->_decodeData([
                    $data[0][$k], $data[1][$k], $data[2][$k], $data[3][$k], $data[4][$k]
                ]);
            }

            return $result;
        }

        return [
            'name' => $data[0],
            'type' => $data[1],
            'tmp_name' => $data[2],
            'error' => $data[3],
            'size' => $data[4]
        ];
    }

    /**
     * 存储文件
     * @param array $file 上传文件信息数组
     * @param $targetPath
     * @return array
     */
    private function moveTo($file, $targetPath = '')
    {
        $nowName    = time().'_'.mt_rand(1000,9999).'.'.$file['ext'];
        $filePath   = ($targetPath ? : $this->config['path']) . DIRECTORY_SEPARATOR . $nowName;

        $dir = dirname($filePath);

        if ( !$this->_makeDir($dir) ) {
            $this->error = "目录创建失败或者不可写.[$dir]";
            return false;
        }

        if ( !move_uploaded_file($file['tmp_name'],$filePath) ){
            $this->error = '移动上传文件失败！';
            return false;
        }

        $file['targetFile'] = $filePath;
        $file['targetName'] = $nowName;
        $file['targetUrl'] = $nowName;

        return $file;
    }

    public function watermark($filePath, $targetFile, &$result)
    {
        $waterImage = dirname($targetFile). '/thumb/' .basename($targetFile);
        $this->getPicture()->watermark($filePath, $waterImage);

        $result['waterImage'] = $waterImage;

        return $this;
    }

    public function makeThumb($filePath, $targetFile, &$result)
    {
        $path = dirname($targetFile). '/thumb';
        $name = basename($targetFile);

        $this->getPicture()->thumb($filePath, $name, $path);

        $result['thumbImage'] = $path . '/' . $name;

        return $this;
    }

    /**
     * @return Picture
     */
    public function getPicture()
    {
        if (!$this->picture) {
            $this->picture = new Picture([
                'water' => $this->waterConfig,
                'thumb' => $this->thumbConfig,
            ]);
        }

        return $this->picture;
    }

    /**
     * 目录创建 目录验证
     * @param $path
     * @return bool
     */
    protected function _makeDir($path)
    {
        return $path && (is_dir($path) || mkdir($path, 0664, true)) && is_writable($path);
    }

    /**
     * 验证上传文件,是否有文件、上传类型、文件大小
     * @param $file
     * @return bool
     */
    private function _checkFile($file)
    {
        // check system error
        if ( (int)$file['error'] !== 0 ) {
            switch($file['error']){
                case UPLOAD_ERR_INI_SIZE:   # 1
                    $this->error = '超过php.ini 配置文件指定大小！';
                    break;
                case UPLOAD_ERR_FORM_SIZE:  # 2
                    $this->error = '上传文件超过Html表单指定大小！';
                    break;
                case UPLOAD_ERR_NO_FILE:    # 3
                    $this->error = '没有上传任何文件！';
                    break;
                case UPLOAD_ERR_PARTIAL:    # 4
                    $this->error = '文件只上传了一部分！';
                    break;
                case UPLOAD_ERR_NO_TMP_DIR: # 5
                    $this->error = '没有文件上传的临时目录！';
                    break;
                case UPLOAD_ERR_CANT_WRITE: # 6
                    $this->error = '不能写入临时上传文件！';
                    break;
            }

            return false;
        }

        $maxSize = $this->config['maxSize'] > 0 ? $this->config['maxSize'] : 0;
        $extList = $this->config['ext'];
        $fileExt     = strtolower($file['ext']);

        if ($extList && !in_array($fileExt, $extList) ){
            $this->error = '不允许的上传文件类型！';
        } elseif ( $maxSize && $file['size'] > $maxSize ){
            $this->error = '上传文件超出允许大小！';
        } elseif ( !is_uploaded_file($file['tmp_name'])){
            $this->error = '非法文件！';
        }

        return $this->error === null;
    }

    /**
     * @return bool
     */
    public function hasError()
    {
        return $this->error !== null;
    }

    /**
     * 返回上传时发生的错误原因
     * @return string
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * @return array
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * @param array $config
     */
    public function setConfig(array $config)
    {
        $this->config = array_merge($this->config, $config);

        return $this;
    }

    /**
     * @return array
     */
    public function getWaterConfig()
    {
        return $this->waterConfig;
    }

    /**
     * @param array $waterConfig
     */
    public function setWaterConfig(array $waterConfig)
    {
        $this->waterConfig = $waterConfig;

        return $this;
    }

    /**
     * @return array
     */
    public function getThumbConfig()
    {
        return $this->thumbConfig;
    }

    /**
     * @param array $thumbConfig
     */
    public function setThumbConfig(array $thumbConfig)
    {
        $this->thumbConfig = $thumbConfig;

        return $this;
    }

    /**
     * @return array
     */
    public function getUploadedFiles()
    {
        return $this->uploadedFiles;
    }

    /**
     * @param null|string $key
     * @return array|string
     */
    public function getResult($key = null)
    {
        return $key && isset($this->result[$key]) ? $this->result[$key] : $this->result;
    }
}
