<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017-10-23
 * Time: 11:41
 */

namespace Inhere\Library\Traits;

use Inhere\Library\Helpers\Php;

/**
 * Trait PathResolver
 * @package Inhere\Library\Traits
 */
trait PathResolverTrait
{
    /**
     * @var callable
     */
    protected $pathResolver;

    /**
     * @param string $path
     * @return string
     */
    public function resolverPath($path)
    {
        if (!$this->pathResolver) {
            return $path;
        }

        return Php::call($this->pathResolver, $path);
    }

    /**
     * @return callable
     */
    public function getPathResolver(): callable
    {
        return $this->pathResolver;
    }

    /**
     * @param callable $pathResolver
     */
    public function setPathResolver(callable $pathResolver)
    {
        $this->pathResolver = $pathResolver;
    }
}