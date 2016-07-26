<?php
/**
 * Created by PhpStorm.
 * User: Inhere
 * Date: 2015/2/27
 * Use : (请求来源)客户端信息
 * File: Client.php
 */

namespace inhere\librarys\env;


/**
 * 客户端信息(e.g. 浏览器)
 * Class Client
 * @package inhere\librarys\env
 *
 * @property string uri
 * @property string method
 * @property string conn
 * @property string accept
 * @property string acceptEncoding
 * @property string acceptLang
 * @property string userAgent
 *
 * @property bool   isPc
 * @property bool   isMobile
 * @property string os
 * @property string browser
 * @property bool   isUnix
 * @property bool   isLinux
 * @property bool   isWin
 * @property bool   isMac
 * @property bool   isAndroid
 * @property string addr
 * @property string ip
 * @property int    port
 *
 * @property array  accepts
 * @property array  encodings
 * @property array  langs
 */
class Client extends AbstractEnv
{
    /**
     * @inherit
     * @var array
     */
    static public $config = [

        // $_SERVER['REQUEST_URI']
        'uri'      => 'REQUEST_URI',

        // $_SERVER['REQUEST_METHOD']
        'method'      => 'REQUEST_METHOD',

        /**
         * 客户端(与服务端)连接
         * $_SERVER['HTTP_CONNECTION'] = close
         * @var string
         */
        'conn'      => 'HTTP_CONNECTION',
    ];

    // 是移动端
    // protected $isMobile = false;
    // protected $platform;

    public function init()
    {
        $this->sets([
            'isPc'      => false,
            'isMobile'  => false,

            'os'        => 'Unknown',
            'browser'   => 'Unknown',

            'isUnix'    => false,
            'isLinux'   => false,
            'isWin'     => false,
            'isMac'     => false,
            'isAndroid' => false,

            'ip'        => 0,
            'addr'      => Server::value('REMOTE_ADDR'),
            'port'      => Server::value('REMOTE_PORT'),
        ]);

        $this->getHeaders();

        // Parse the HTTP_ACCEPT.
        if ( $accept = $this->getHeader('accept') ) {
            $this->set('accepts', $this->_handleInfo($accept) );
        }

        // Parse the accepted encodings.
        if ($acceptEncoding = $this->getHeader('acceptEncoding') ) {
            $this->set('encodings', $this->_handleInfo($acceptEncoding) );
        }

        if ($acceptLang = $this->get('acceptLang')) {
            $this->set('langs', $this->_handleInfo($acceptLang) );
        }

        // $this->data['isSslConnect'] = $this->isSSLConnection();
        $this->set('ip', $this->getIp() );

        $this->_userAgentCheck();
    }

    protected function _handleInfo($info)
    {
        return array_map(function($val)
        {
            return trim(strpos($val,';') ? strchr($val,';', true) : $val);
        },
        (array) explode(',', $info)
        );
    }

    /**
     * 响应头信息
     * @var array
     *
     * @example [
     *
     *  可接受资源类型 $_SERVER['HTTP_ACCEPT']
     *  'accept'    => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp;q=0.8',
     *
     *  客户端可接受的资源（压缩）编码  $_SERVER['HTTP_ACCEPT_ENCODING'];
     * 'acceptEncoding'  => 'gzip, deflate, sdch',
     *
     *  客户端默认接受的语言  $_SERVER['HTTP_ACCEPT_LANGUAGE'];
     *  'acceptLang'      => 'zh-CN,zh;q=0.8',
     *
     *  用户代理 (通常是浏览器) $_SERVER['HTTP_USER_AGENT']
     * 'userAgent' => 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.2171.99 Safari/537.36',
     *
     * ]
     */
    private $_headers = null;

    /**
     * @return array|false|null
     */
    public function getHeaders()
    {
        if ($this->_headers === null) {
            if (function_exists('getallheaders')) {
                $this->_headers = getallheaders();
            } elseif (function_exists('http_get_request_headers')) {
                $this->_headers = http_get_request_headers();
            } else {
                foreach ($_SERVER as $name => $value) {
                    if ( $name = $this->_nameConver($name)) {
                        $this->_headers[$name] = $value;
                    }
                }
            }
        }

        return $this->_headers;
    }

    /**
     * @param array $headers
     * @return $this
     */
    public function setHeaders(array $headers)
    {
        $this->_headers = $headers;

        return $this;
    }

    /**
     * @param $name
     * @param null $default
     * @return null
     */
    public function getHeader($name, $default = null)
    {
        return isset($this->_headers[$name]) ? $this->_headers[$name] : $default;
    }

    // HTTP_X_TOKEN => xToken
    // HTTP_USER_AGENT => userAgent
    protected function _nameConver($string)
    {
        // if ( !strpos($string, '_') ) {
        //     return strtolower($string);
        // }

        if ( strpos($string,'HTTP_')!==false ) {
            $string    = substr($string, strlen('HTTP_'));
        } else {
            return false;
        }

        $arr_char  = explode('_', strtolower($string));
        $newString = array_shift($arr_char);

        foreach($arr_char as $val){
            $newString .= ucfirst($val);
        }

        return $newString;
    }

    /**
     * Determine if we are using a secure (SSL) connection.
     * @return  boolean  True if using SSL, false if not.
     */
   /* public function isSSLConnection()
    {
        return (!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) != 'off');
    }*/

    /**
     * user-Agent 信息分析
     * @return bool
     */
    protected function _userAgentCheck()
    {
        if (empty($_SERVER['HTTP_USER_AGENT'])) {
            return true;
        }

        $agent = strtolower($_SERVER['HTTP_USER_AGENT']);

        /* @link http://detectmobilebrowser.com/mobile */
        if(preg_match('/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|mobile.+firefox|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows ce|xda|xiino/i',$agent)
            || preg_match('/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i',substr($agent,0,4) ) ) {
            $this->set('isMobile', true);
        } else {
            $this->set('isPc', true);
        }

        //// system check
//        $isLinux = $isMac = $isAndroid = false;

        if (preg_match('/win/i', $agent)) {

            $this->set('isWin', true);

            if (preg_match('/nt 6.0/i', $agent)) {
                $os = 'Windows Vista';
            } else if (preg_match('/nt 6.2/i', $agent)) {
                $os = 'Windows 8';
            } else if (preg_match('/nt 10/i', $agent)) {
                $os = 'Windows 10';
            } else if (preg_match('/nt 6.1/i', $agent)) {
                $os = 'Windows 7';
            } else if (preg_match('/nt 5.1/i', $agent)) {
                $os = 'Windows XP';
            } else if (preg_match('/nt 5/i', $agent)) {
                $os = 'Windows 2000';
            } else {
                $os = 'Windows other';
            }

        } elseif (strpos('linux', $agent)) {

            if (strpos('android', $agent)) {
                $os = 'Android';
                $this->set('isAndroid', true);
            } else {
                $os = 'Linux';
                $this->set('isLinux', true);
            }

        } elseif (strpos('android', $agent)) {
            $os   = 'Android';
            $this->set('isAndroid', true);
        } elseif ( strpos($agent,"iphone") ) {
            $os   = 'Ios';
            $this->set('isIos', true);
        } elseif (strpos('ubuntu', $agent)) {
            $os   = 'Ubuntu';
            $this->set('isLinux', true);
        } elseif (strpos('mac', $agent)) {
            $os   = 'Mac OS X';
            $this->set('isMac', true);
        } elseif (strpos('unix', $agent)) {
            $os   = 'Unix';
            $this->set('isUnix', true);
        } elseif (strpos('bsd', $agent)) {
            $os = 'BSD';
        } elseif (strpos('symbian', $agent)) {
            $os = 'SymbianOS';
        } else {
            $os = 'Unknown';
        }

        $this->set('os', $os);

        //// browser check
        $browser = 'Unknown';

        // myie
        if(strpos($agent, 'myie')){
            $browser = 'Myie';

        // Netscape
        } else if(strpos($agent, 'netscape')) {
            $browser = 'Netscape';

        // Opera
        } else if(strpos($agent, 'opera')){
            $browser = 'Opera';

        // netcaptor
        } else if(strpos($agent, 'netcaptor')) {
            $browser = 'NetCaptor';

        // TencentTraveler
        } else if(strpos($agent, 'tencenttraveler')) {
            $browser = 'TencentTraveler';

        // Firefox
        } else if(strpos($agent, 'firefox')) {
            $browser = 'Firefox';

        // ie
        } else if(strpos($agent, 'msie')) {
            $browser = 'IE';

        // chrome
        } else if(strpos($agent, 'chrome')) {
            $browser = 'Chrome';
        }

        $this->set('browser', $browser);

        return true;
    }

    /**
     * get client Ip
     * @from web
     * @return string
     */
    public function getIP()
    {
        $ip = '';

        if ( $_SERVER['REMOTE_ADDR'] ) {
           $ip = $_SERVER['REMOTE_ADDR'];
        } elseif (getenv('HTTP_CLIENT_IP')) {
            $ip = getenv('HTTP_CLIENT_IP');
        } elseif (getenv('HTTP_X_FORWARDED_FOR')) {
            $ip = getenv('HTTP_X_FORWARDED_FOR');
        } elseif (getenv('HTTP_X_FORWARDED')) {
            $ip = getenv('HTTP_X_FORWARDED');
        } elseif (getenv('HTTP_FORWARDED_FOR')) {
            $ip = getenv('HTTP_FORWARDED_FOR');
        } elseif (getenv('HTTP_FORWARDED')) {
            $ip = getenv('HTTP_FORWARDED');
        }

        return $ip;
    }

    public function getBrowsers()
    {
        return get_browser($this->get('userAgent'),true);
    }

}// end class Client