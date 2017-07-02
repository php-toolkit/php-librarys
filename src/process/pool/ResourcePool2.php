<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/7/2
 * Time: 下午1:50
 */

namespace inhere\library\process\pool;

/**
 * Class ResourcePool2
 * - 通过设置的资源工厂类实现资源的创建和销毁
 *
 * @package inhere\library\process\pool
 */
class ResourcePool2 extends BasePool
{
    /**
     * @var ResourceInterface
     */
    private $factory;

    /**
     * (创建)准备资源
     * @param int $size
     * @return int
     */
    public function prepare($size)
    {
        if ($size <= 0) {
            return 0;
        }

        for ($i = 0; $i < $size; $i++) {
            $this->incrementCreatedNumber();
            $this->getPool()->push($this->factory->create());
        }

        return $size;
    }

    /**
     * release pool
     */
    public function clear()
    {
        while ($obj = $this->getPool()->pop()) {
            $this->factory->destroy($obj);
        }

        parent::clear();
    }

    /**
     * @return ResourceInterface
     */
    public function getFactory()
    {
        return $this->factory;
    }

    /**
     * @param ResourceInterface $factory
     * @return $this
     */
    public function setFactory(ResourceInterface $factory)
    {
        $this->factory = $factory;

        // 预准备资源
        $this->prepare($this->getInitSize());

        return $this;
    }
}
