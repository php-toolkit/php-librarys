<?php
/**
 * Created by sublime 3.
 * Auth: Inhere
 * Date: 14-10-1
 * Time: 10:35
 * Uesd: 主要功能是 安全加密
 *  uniqid(string $prefix  = "" , bool $more_entropy  = false );
 *  prefix为空，则返回的字符串长度为13。more_entropy 为 TRUE ，则返回的字符串长度为23。
 *  crypt(str,salt);
 * //mcrypt支持的加密算法列表
 * $cipher_list = mcrypt_list_algorithms();
 * //mcrypt支持的加密模式列表
 * $mode_list = mcrypt_list_modes();
 */

namespace inhere\library\helpers;

/**
 * Class OpenSSL
 * @package inhere\library\helpers
 */
class OpenSSL
{
    private $_secureKey, $_iv;
    protected $clearKey;
    private $_mcryptCipher = MCRYPT_RIJNDAEL_128;
    private $_mcryptMode = MCRYPT_MODE_ECB;

    static public $cipherList = [];
    static public $methodsList = [];

    /**
     * @param string $clearKey 明文 key]
     */
    public function __construct($clearKey)
    {
        if (!extension_loaded('openssl')) {
            throw new \RuntimeException('the php extension: openssl is required!');
        }

        $this->clearKey = $clearKey;
    }

    /**
     * [enc description]
     * @param $string
     * @param $clearKey
     * @return string
     */
    public function encode($string, $clearKey)
    {
        // openssl_encrypt
    }

    /**
     * @param $encrypted
     * @param $clearKey
     * @return string
     */
    public function decode($encrypted, $clearKey)
    {
        // openssl_decrypt
    }

    /**
     * 处理特殊字符
     * @param $string
     * @return mixed|string
     */
    public static function safeB64encode($string)
    {
        $data = base64_encode($string);
        $data = str_replace(array('+', '/', '='), array('-', '_', ''), $data);

        return $data;
    }

    /**
     * 解析特殊字符
     * @param $string
     * @return string
     */
    public static function safeB64decode($string)
    {
        $data = str_replace(array('-', '_'), array('+', '/'), $string);
        $mod4 = strlen($data) % 4;

        if ($mod4) {
            $data .= substr('====', $mod4);
        }

        return base64_decode($data);
    }

    /**
     * [setMode 设置算法使用模式]
     * @param string $mode
     * @return $this
     */
    public function setMode($mode)
    {
        $mode = trim($mode);
        $methodsList = $this->getMethods();

        if (in_array($mode, $methodsList, true)) {
            $this->_mcryptMode = $mode;
        }

        return $this;
    }

    public function getMode()
    {
        return $this->_mcryptMode;
    }

    /**
     * @link http://php.net/manual/zh/function.openssl-get-cipher-methods.php
     * @return array
     */
    public function getMethods()
    {
        if (!self::$methodsList) {
            self::$methodsList = openssl_get_cipher_methods();
        }

        return self::$methodsList;
    }


}
