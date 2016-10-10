<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 16/9/1
 * Time: 下午4:51
 */

namespace inhere\librarys\auth;

/**
 * Class AccessChecker
 * @package inhere\librarys\auth
 */
class AccessChecker implements CheckAccessInterface
{
    /**
     * @param $userId
     * @param $permission
     * @param array $params
     * @return bool
     */
    public function checkAccess($userId, $permission, $params = [])
    {
        return true;
    }
}