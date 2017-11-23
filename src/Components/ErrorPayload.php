<?php
/**
 * Created by PhpStorm.
 * User: Inhere
 * Date: 2017/11/23 0023
 * Time: 22:41
 * @from https://github.com/phalcon/phalcon-devtools/blob/master/scripts/Phalcon/Error/AppError.php
 */

namespace Inhere\Library\Components;

/**
 * Class ErrorPayload
 * @package Inhere\Library\Components
 *
 * @method int type()
 * @method string message()
 * @method string file()
 * @method string line()
 * @method \Exception|null exception()
 * @method bool isException()
 * @method bool isError()
 *
 */
class ErrorPayload
{

    /**
     * @var array
     */
    protected $attributes = [];

    /**
     *  constructor.
     *
     * @param array $options
     */
    public function __construct(array $options = [])
    {
        $defaults = [
            'type' => -1,
            'message' => 'No error message',
            'file' => '',
            'line' => '',
            'exception' => null,
            'isException' => false,
            'isError' => false,
        ];

        $this->attributes = array_merge($defaults, $options);
    }

    /**
     * Magic method to retrieve the attributes.
     *
     * @param string $method
     * @param array $args
     *
     * @return mixed|null
     */
    public function __call($method, $args)
    {
        return $this->attributes[$method] ?? null;
    }
}
