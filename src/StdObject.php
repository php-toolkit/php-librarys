<?php
/**
 * Created by sublime 3.
 * Auth: Inhere
 * Date: 14-9-28
 * Time: 10:35
 */

namespace inhere\library;

use inhere\library\traits\StdObjectTrait;
use inhere\library\traits\PropertyAccessByGetterSetterTrait;

/**
 * Class StdBase
 * @package inhere\library
 */
abstract class StdObject
{
    use StdObjectTrait;
    use PropertyAccessByGetterSetterTrait;
}
