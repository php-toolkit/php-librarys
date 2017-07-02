<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/7/2
 * Time: 下午1:50
 */

namespace inhere\library\process\pool;

/**
 * Class ResourcePool3
 * - 需要继承它，在自己的子类实现资源的创建和销毁
 *
 * @package inhere\library\process\pool
 */
abstract class ResourcePool3 extends BasePool implements ResourceInterface
{
    protected function init()
    {
        parent::init();

        // 预准备资源
        $this->prepare($this->getInitSize());
    }

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
            $this->getPool()->push($this->create());
        }

        return $size;
    }

    /**
     * release pool
     */
    public function clear()
    {
        while ($obj = $this->getPool()->pop()) {
            $this->destroy($obj);
        }

        parent::clear();
    }

    abstract public function create();

    abstract public function destroy($obj);
}
