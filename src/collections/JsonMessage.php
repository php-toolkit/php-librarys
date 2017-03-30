<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 16/9/2
 * Time: 上午11:49
 */

namespace inhere\library\collections;

use inhere\exceptions\PropertyException;

/**
 * Class JsonMessage
 * @package slimExt\helpers
 *
 * $mg = JsonMessage::create(['msg' => 'success', 'code' => 23]);
 * $mg->data = [ 'key' => 'test'];
 *
 * echo json_encode($mg);
 *
 * response to client:
 *
 * {
 *  "code":23,
 *  "msg":"success",
 *  "data": {
 *      "key":"value"
 *  }
 * }
 *
 */
class JsonMessage extends ActiveData
{
    /**
     * @var int
     */
    public $code = 0;

    /**
     * @var string
     */
    public $msg = 'success';

    /**
     * @var array
     */
    public $data = [];

    /**
     * @return bool
     */
    public function isSuccess()
    {
        return (int)$this->code === 0;
    }

    /**
     * @return bool
     */
    public function isFailure()
    {
        return (int)$this->code !== 0;
    }

    /**
     * @param $code
     * @return $this
     */
    public function code($code)
    {
        $this->code = (int)$code;

        return $this;
    }

    /**
     * @param $msg
     * @return $this
     */
    public function msg($msg)
    {
        $this->msg = $msg;

        return $this;
    }

    /**
     * @param array $data
     * @return $this
     */
    public function data(array $data)
    {
        $this->data = $data;

        return $this;
    }

    public function all($toArray=true)
    {
        // add a new alert message
        return [
            'code'  => (int)$this->code,
            'msg'   => $this->msg,
            'data'  => (array)$this->data
        ];
    }

    public function toArray()
    {
        return $this->all();
    }

    public function __set($name, $value)
    {
        $this->data[$name] = $value;
    }

    // disable unset property
    public function offsetUnset($offset)
    {
        //$this->$offset = null;
    }

    public function __get($name)
    {
        if ( isset($this->data[$name]) ) {
            return $this->data[$name];
        }

        throw new PropertyException(sprintf('获取不存在的属性 %s ！',$name));
    }
}
