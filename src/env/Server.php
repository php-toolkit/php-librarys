<?php
/**
 * Created by PhpStorm.
 * User: Inhere
 * Date: 2015/2/27
 * Use : ...
 * File: Server.php
 */

namespace inhere\tools\env;

use ulue\libs\helpers\PhpHelper;

/**
 * 服务端信息 Server
 */
class Server extends AbstractEnv
{
    /**
     * @inherit
     * @var array
     */
    static public $config = [

        // $_SERVER['REQUEST_SCHEME'] 架构
        // 'scheme' => 'REQUEST_SCHEME',

        // $_SERVER['PATH']
        'path'     => 'PATH',

        // $_SERVER['SERVER_PROTOCOL'] 协议 e.g. HTTP/1.1
        'protocol' => 'SERVER_PROTOCOL',

        // $_SERVER['SERVER_SIGNATURE'] 签名
        'signature' => 'SERVER_SIGNATURE',

        // $_SERVER['SERVER_ADDR']
        'addr'     => 'SERVER_ADDR',

        // $_SERVER['SERVER_PORT']
        'port'     => 'SERVER_PORT',

        // $_SERVER['SERVER_NAME']
        'name'     => 'SERVER_NAME',

        // $_SERVER['SERVER_SOFTWARE']
        'software' => 'SERVER_SOFTWARE',

        // $_SERVER['DOCUMENT_ROOT']
        'documentRoot'     => 'DOCUMENT_ROOT',

        // $_SERVER['SCRIPT_FILENAME']
        'scriptFilename' => 'SCRIPT_FILENAME',

        // $_SERVER['SCRIPT_NAME']
        'scriptName'     => 'SCRIPT_NAME',

        // $_SERVER['PHP_SELF']
        'phpSelf'     => 'PHP_SELF',
    ];

    public function init()
    {
        // $this->data['uri']       = rtrim($this->data['uri'],'?& ');
        // $this->data['rootUrl']     = $this->scheme . ':/'.'/' . $this->name;
        // $this->data['referer']  = !empty($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : $this->rootUrl;
        // $this->data['entryUrl']    = $this->rootUrl . $this->script;
        // $this->data['currentUrl']  = $this->isUrl($this->uri) ? $this->uri : $this->rootUrl . $this->uri;

        $this->setData([
                'workDir' => getcwd(),
                'entry'   => $this->getEntry(),
                'root'    => $this->getRoot(),

                'isCli'   => PhpHelper::isCli(),
                'isWeb'   => PhpHelper::isWeb(),

                 // operate system
                'uname'   => PHP_OS,
                'os'      => strtoupper(substr($this->get('uname'), 0, 3)),
                'isWin'   => ($this->get('os') == 'WIN'),
                'isLinux' => ($this->get('os') == 'LIN'),
                'isUnix'  => $this->isUnix(),
            ]);

    }

    /**
     * getEntry
     * @param   bool $full
     * @return  string
     */
    public function getEntry($full = true)
    {
        $key = $full ? 'scriptFilename' : 'scriptName';

        $wDir = $this->get('workDir');
        $file = $this->get($key);

        if (strpos($file, $wDir) === 0) {
            $file = substr($file, strlen($wDir));
        }

        $file = trim($file, '.' . DIRECTORY_SEPARATOR);

        if ($full && $this->get('isCli') ) {
            $file = $wDir . DIRECTORY_SEPARATOR . $file;
        }

        return $file;
    }

    /**
     * @param  boolean $full
     * @return string
     */
    public function getRoot($full = true)
    {
        return dirname($this->getEntry($full));
    }

    /**
     * isUnix
     * @see  https://gist.github.com/asika32764/90e49a82c124858c9e1a
     * @return  bool
     */
    public function isUnix()
    {
        $unames = array('CYG', 'DAR', 'FRE', 'HP-', 'IRI', 'LIN', 'NET', 'OPE', 'SUN', 'UNI');

        return in_array($this->get('os'), $unames);
    }

    /**
     * check url string
     * @param  string  $str
     * @return boolean
     */
    public function isUrl($str)
    {
        $rule = '/^(http|https|ftp):\/\/([A-Z0-9][A-Z0-9_-]*(?:\.[A-Z0-9][A-Z0-9_-]*)+):?(\d+)?\/?/i';

        return preg_match($rule,trim($str))===1;
    }
}// end class Server