<?php

namespace inhere\librarys\io;

use inhere\librarys\helpers\ObjectHelper;

/**
 * Class Output
 * @package inhere\librarys\io
 */
class Output
{
    const DEFAULT_CHARSET = 'UTF-8';

    /**
     * output charset
     * @var string
     */
    protected $charset = 'UTF-8';

    /**
     * @var array
     */
    private $headers = [];

    /**
     * @var array
     */
    private $bodys = [];

    public function __construct(array $config = [])
    {
        ObjectHelper::loadAttrs($this, $config);
    }

    public function header($name, $value)
    {
        header("$name: $value");
    }

    public function write($content)
    {
        $this->bodys[] = $content;

        return $this;
    }

    /**
     * send response
     * @param  string $content
     * @return mixed
     */
    public function send($content = '')
    {
        if ($content) {
            $this->write($content);
        }

        header($_SERVER['SERVER_PROTOCOL'] . ' 200 OK');
        header('Content-type: text/html; charset=' . $this->charset);

        $content = implode('', $this->bodys);

        if ( !$content ) {
            throw new \RuntimeException('No content to display.');
        }

//        \App::logger()->debug('Send text content.');

        echo $content;

        $this->bodys = [];

        return true;
    }

    /**
     * output json response
     * @param  array  $data
     * @return mixed
     */
    public function json(array $data)
    {
        header($_SERVER['SERVER_PROTOCOL'] . ' 200 OK');
        header('Content-type: application/json; charset='.$this->charset);

        echo json_encode($data);

        return true;
    }

    public function formatJson($data, $code = 0, $msg = '')
    {
        // if `$data` is integer, queals to `formatJson(int $code, string $msg )`
        if ( is_numeric($data) ) {
            $jsonData = [
                'code'     => (int)$data,
                'msg'      => $code,
                'data'     => [],
            ];
        } else {
            $jsonData = [
                'code'     => (int)$code,
                'msg'      => $msg ?: 'successful!',
                'data'     => (array)$data,
            ];
        }

        return $this->json($jsonData);
    }

    /**
     * @param int $status
     * @param string $msg
     * @param array $data
     * @return mixed
     */
    public function formatJson2($status = 200, $msg = '', $data = [])
    {
        $jsonData = [
            'status'  => (int)$status,
            'message' => $msg ?: 'successful!',
            'data'    => $data,
        ];

        return $this->json($jsonData);
    }
}
