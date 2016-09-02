<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 16/9/2
 * Time: 上午11:49
 */

namespace inhere\librarys\utils;

use inhere\librarys\collections\FixedData;

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
class JsonMessage extends FixedData
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

}