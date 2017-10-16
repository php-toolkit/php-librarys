<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/10/17
 * Time: 上午12:01
 */

namespace Inhere\Library\Interfaces;

/**
 * Interface PipelineInterface
 * @package Inhere\Library\Interfaces
 */
interface PipelineInterface
{
    /**
     * Adds stage to the pipelene
     *
     * @param callable $stage
     * @return $this
     */
    public function add(callable $stage);

    /**
     * Runs pipeline with initial value
     *
     * @param mixed $payload
     * @return mixed
     */
    public function run($payload);

    /**
     * Makes pipeline callable. Does same as {@see run()}
     *
     * @param mixed $payload
     * @return mixed
     */
    public function __invoke($payload);
}
