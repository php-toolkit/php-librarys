<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/6/24
 * Time: 上午9:31
 */

namespace inhere\library\process\pool;

/**
 * Class ResourcePool - 资源池
 * - 通过设置两个闭包来实现资源的创建和销毁
 *
 * ```php
 * $rpl = new ResourcePool([
 *  'maxSize' => 50,
 * ]);
 *
 * $rpl->setResourceCreator(function () {
 *  return new \Db(...);
 * );
 *
 * $rpl->setResourceDestroyer(function ($db) {
 *   $db->close();
 * );
 *
 * // use
 * $db = $rpl->get();
 *
 * $rows = $db->query('select * from table limit 10');
 *
 * $rpl->put($db);
 * ```
 *
 * @package inhere\library\process
 */
class ResourcePool extends BasePool
{
    /**
     * 资源创建者
     * @var \Closure
     */
    private $creator;

    /**
     * 资源销毁/释放者
     * @var \Closure
     */
    private $destroyer;

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

        $cb = $this->creator;

        for ($i = 0; $i < $size; $i++) {
            $this->incrementCreatedNumber();
            $this->getPool()->push($cb());
        }

        return $size;
    }

    /**
     * release pool
     */
    public function clear()
    {
        if ($cb = $this->destroyer) {
            while ($obj = $this->getPool()->pop()) {
                $cb($obj);
            }
        }

        parent::clear();
    }

    /**
     * @return \Closure
     */
    public function getCreator()
    {
        return $this->creator;
    }

    /**
     * @param \Closure $creator
     * @return $this
     */
    public function setCreator(\Closure $creator)
    {
        $this->creator = $creator;

        // 预准备资源
        $this->prepare($this->getInitSize());

        return $this;
    }

    /**
     * @return \Closure
     */
    public function getDestroyer()
    {
        return $this->destroyer;
    }

    /**
     * @param \Closure $destroyer
     * @return $this
     */
    public function setDestroyer(\Closure $destroyer)
    {
        $this->destroyer = $destroyer;

        return $this;
    }
}
