<?php
/**
 * @author Inhere
 * @version v1.0
 * Use :
 * ContainerManager.php
 * Date : 2014-7-10
 */
namespace inhere\library\di;


abstract class ContainerManager
{

    /**
     * 一组容器的扼要描述，标记不同的容器(这里一组含有一个基础容器和其他的属于这组[$profile]的其它子容器)
     * @var string $profile
     */
    static protected $profile = 'ulue';

    /**
     * $containers 容器列表
     * @var array
     */
    static protected $containers = [
        'ulue' =>    [
                'base'      => null,// 'container name'=> a base Container instance
                'children'  => []
            ]
    ];

    /**
     * 后期绑定服务参数方式 (参数组成的数组。没有key,不能用合并)
     * 1. 用传入的覆盖 (默认)
     * 2. 传入指定了位置的参数来替换掉原有位置的参数
     * [
     *    //pos=>arguments
     *      0 => arg1, // 第一个参数
     *      1 => arg2,
     *      4 => arg3, //第五个参数
     * ]
     * 3. 在后面追加参数
     */
    const OVERLOAD_PARAM = 1;
    const REPLACE_PARAM  = 2;
    const APPEND_PARAM   = 3;

    static public function getContainer($name=null)
    {
        return self::build($name);
    }

    static public function make($name='', $profile='')
    {
        return self::build($name, $profile);
    }

    /**
     * @param string $name
     * @param string $profile
     * @return Container
     */
    static public function build($name='', $profile='')
    {
        $profile = $profile ?: static::$profile;

        // No name, return default's base container.
        if (!$name)
        {
            if (empty(self::$containers[$profile]['base']))
            {
                $container = new Container;

                $container->name = 'ulue.base';

                self::$containers[$profile]['base'] = $container;
            }

            return self::$containers[$profile]['base'];
        }

        // Has name, we return children container.
        if (empty(self::$containers[$profile][$name]) || !(self::$containers[$profile][$name] instanceof Container))
        {
            self::$containers[$profile][$name] = new Container(static::getContainer());

            self::$containers[$profile][$name]->name = $name;
        }

        return self::$containers[$profile][$name];

    }

    /**
     * setProfile
     * @param string $profile
     * @return  void
     */
    public static function setProfile($profile = 'ulue')
    {
        $profile = strtolower(trim($profile));

        if (!isset(static::$containers[$profile]))
        {
            static::$containers[$profile] = array(
                'root'      => null,
                'children'  => []
            );
        }

        static::$profile = $profile;
    }

    /**
     * Method to get property Profile
     * @return  string
     */
    public static function getProfile()
    {
        return static::$profile;
    }

    /**
     * reset
     * @param string $profile
     * @return  void
     */
    public static function reset($profile = null)
    {
        if (!$profile)
        {
            static::$containers = array();

            return;
        }

        static::$containers[$profile] = array();

        return;
    }

    static public function exists($id, $name='')
    {
        $container = self::getContainer($name);

        return $container->exists($id);
    }

    static public function set($id, $service, $name='')
    {
        $container = self::getContainer($name);

        return $container->set($id, $service);
    }

    static public function share($id, $service, $name='')
    {

    }

    /**
     * more @see Container::get()
     * @param $id
     * @param string $name 容器名称
     * @param array $params
     * @param int $bindType
     * @return null|object
     */
    static public function get($id, $name='', array $params=[], $bindType=self::OVERLOAD_PARAM)
    {
        $container = self::getContainer($name);

        return $container->get($id, (array) $params, $bindType);

    }

    /**
     * more @see Container::getNew()
     * @param $id
     * @param string $name
     * @param array $params
     * @param int $bindType
     * @return null|object
     */
    static public function getNew($id, $name='', array $params=[], $bindType=self::OVERLOAD_PARAM)
    {
        $container = self::getContainer($name);

        return $container->make($id, (array) $params, $bindType);
    }

    static public function getShared()
    {

    }


}
