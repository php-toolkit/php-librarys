<?php
/**
 * Created by sublime 3.
 * Auth: Inhere
 * Date: 14-9-28
 * Time: 10:35
 */

namespace Inhere\Library;

use Inhere\Library\Traits\PropertyAccessByGetterSetterTrait;
use Inhere\Library\Traits\StdObjectTrait;

/**
 * Class StdBase
 * @package Inhere\Library
 */
abstract class StdObject
{
    use StdObjectTrait;
    use PropertyAccessByGetterSetterTrait;
}
