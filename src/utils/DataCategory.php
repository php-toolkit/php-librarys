<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/7/26
 * Time: 下午10:17
 */

namespace inhere\library\utils;

/**
 * Class DataCategory
 * @package inhere\library\utils
 * 调用：
 *  $dc = new DataCategory;
 *  $re = $dc->getDataArray();
 *  $re = $dc->getDataArray(array('chName','levelId'));
 *  $re = $dc->getCategoryTree('0',array('chName','levelId'));
 *  $re = $dc->getCategoryTree('5',array('chName','levelId'));
 */
class DataCategory
{
    /**
     * 主键列名
     * @var string
     */
    public $pkColumn = 'id';

    /**
     * 父类列名
     * @var string
     */
    public $pidColumn = 'pid';

    public $childKeyName = 'children';

    public $data = [];

    public $modelClass;

    public function __construct($modelClass = null)
    {
        $this->modelClass = trim($modelClass);
    }

    private function checkModel()
    {
        $className = $this->modelClass;

        if (class_exists($className)) {
            return call_user_func(array($className, 'model'));
        }

        throw new \RuntimeException($className . ' Table model does not exist!');

    }

    public function getData()
    {
        if (!$this->data) {
            $class_name = $this->modelClass;

            if (class_exists($class_name)) {
                $this->data = call_user_func(array($class_name, 'findAll'));
            } else {
                trigger_error($class_name . ' 表模型不存在！', E_USER_ERROR);
            }
        }

        return $this->data;
    }

    /**
     * getDataArray  初始数据转换成的数组
     * @param  array $need | 'all' [需要获取哪些字段值]
     * 1. 'all' 全部字段
     * 2. array('chName','enName') --> 获取数组仅含有 chName enName 字段
     * @return array
     */
    public function getDataArray(array $need = [])
    {
        $arrData = $this->getData();

        if ($need) {
            $arrList = [];

            foreach ($arrData as $item) {
                $needArr = array();

                foreach ($need as $val) {
                    if (isset($item[$val])) {
                        $needArr[$val] = $item[$val];
                    } else {
                        trigger_error('参数错误！' . $this->modelClass . '不存在字段：' . $val, E_USER_ERROR);
                    }
                }

                $arrList[] = $needArr;
            }

            return $arrList;
        }

        return $arrData;
    }

    /**
     * [getCategoryArr 指定层级父id(parentId)，获取其下面的子级初始数据数组]
     * @param  string $rootId [ 当等于0时，等同于 调用 getDataArray() ]
     * @param  array $need @see getDataArray()
     * @param string $setKey
     * @return array
     */
    public function getCategoryArr($rootId = '0', array $need = [], $setKey = 'id')
    {
        $allDataArr = $this->getDataArray();

        if ($rootId === '0' || $rootId === '') {
            return $allDataArr;
        }

        $arr_result = $needArr = array();
        $id = $this->pkColumn;
        $parentId = $this->pidColumn;

        # 递归获取子级
        $allNeedArr = $this->arrTree($allDataArr, $rootId);

        foreach ($allNeedArr as $value) {
            if (isset($value[$this->childKeyName])) {
                /** @var array $items */
                $items = $value[$this->childKeyName];

                foreach ($items as $key => $item) {
                    foreach ($need as $val) {
                        if (isset($item[$val])) {
                            $needArr[$val] = $item[$val];
                        } else {
                            throw new \RuntimeException('parameter error!' . $this->modelClass . 'There is no field in the table: ' . $val);
                        }
                    }

                    if ($setKey === '') {
                        $arr_result[] = $needArr;
                    } else {
                        $arr_result[$item[$setKey]] = $needArr;
                    }
                }
            }
        }

        return $arr_result;
    }

    /**
     * getCategory  递归获取树形图
     * @param  string|int $rootId [开始层父级id 默认 0，顶级]
     * @param  array|string $need 需要获取哪些字段值，id pid默认含有
     * 1. [] 全部字段
     * 2. array('chName','enName') --> 获取含有 id pid chName enName 字段
     * @param  string $setKey 需要什么作为 树形数组的 键名，默认：主键id值为键，留空为自增的数字. e.g. id | enName
     * @return array
     */
    public function getCategoryTree($rootId = 0, array $need = [], $setKey = 'id')
    {
        if (!$rootId) {
            $rootId = 0;
        }

        if ($need) {
            $need = array_merge([$this->pkColumn, $this->pidColumn], $need);
        }

        $arrList = $this->getDataArray($need);

        return $this->arrTree($arrList, $rootId, $setKey);
    }

    /**
     * arrTree 树形数组递归
     * @param array $tree 需要处理的原始数组
     * @param  integer $rootId 开始层父级id 默认 0，顶级
     * @param  string $setKey 需要什么作为 树形数组的 键名，默认：主键id值为键，留空为自增的数字. e.g. id | enName
     * @return array
     */
    public function arrTree(array $tree, $rootId = 0, $setKey = 'id')
    {
        $result = [];
        $id = $this->pkColumn;
        $parentId = $this->pidColumn;
        $childKey = $this->childKeyName;

        foreach ($tree as $leaf) {
            if ($leaf[$parentId] === $rootId) {
                foreach ($tree as $subLeaf) {
                    if ($subLeaf[$parentId] === $leaf[$id]) {
                        $leaf[$childKey] = $this->arrTree($tree, $leaf[$id], $setKey);
                        break;
                    }
                }

                if ($setKey) {
                    $result[$leaf[$setKey]] = $leaf;
                } else {
                    $result[] = $leaf;
                }
            }
        }

        return $result;
    }
}
