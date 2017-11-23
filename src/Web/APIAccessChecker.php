<?php
/**
 * Created by PhpStorm.
 * User: Inhere
 * Date: 2017/11/23 0023
 * Time: 22:39
 */

namespace Inhere\Library\Web;

/**
 * Class APIAccessChecker
 * @package Inhere\Library\Web
 */
class APIAccessChecker
{
    /** @var string  */
    private $hostname;

    /**
     * The allowed IP address.
     * @var array
     */
    protected $allowedIps;

    /**
     * The allowed host names.
     * @var array
     */
    protected $allowedHosts;

    /**
     * APIAccessChecker constructor.
     * @param array $ips
     * @param null|array $hosts
     */
    public function __construct($ips, $hosts = null)
    {
        $this->hostname = gethostname();
        $this->allowedIps = (array)$ips;
        $this->allowedHosts = (array)$hosts;
    }

    /**
     * @param string $clientIp client Ip
     * @return bool
     */
    public function isAllowedAccess($clientIp)
    {
        if ($this->checkHostname()) {
            return true;
        }

        // no limit
        if (!$this->allowedIps) {
            return true;
        }

        if (!$clientIp) {
            return false;
        }

        // check
        if ($this->checkIp($clientIp)) {
            return true;
        }

        return false;
    }

    /**
     * Check if IP address for securing area matches the given
     *
     * @param  string $clientIp
     * @return bool
     */
    private function checkIp($clientIp)
    {
        // local env
        if ($clientIp === '127.0.0.1' || $clientIp === '::1') {
            return true;
        }

        foreach ($this->allowedIps as $allowedIp) {
            if (0 === strpos($clientIp, $allowedIp)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return bool
     */
    public function checkHostname()
    {
        $host = $this->hostname;

        foreach ($this->allowedHosts as $allowedHost) {
            if (0 === strpos($host, $allowedHost)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array
     */
    public function getAllowedIps()
    {
        return $this->allowedIps;
    }

    /**
     * @param array $allowedIps
     */
    public function setAllowedIps($allowedIps)
    {
        $this->allowedIps = (array)$allowedIps;
    }

    /**
     * @return array
     */
    public function getAllowedHosts()
    {
        return $this->allowedHosts;
    }

    /**
     * @param array $allowedHosts
     */
    public function setAllowedHosts($allowedHosts)
    {
        $this->allowedHosts = (array)$allowedHosts;
    }
}
