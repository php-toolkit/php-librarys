<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 16/8/28
 * Time: 上午2:14
 */

namespace inhere\librarys\files\compress;

/**
 * Class PharCompressor
 * @package inhere\librarys\files\compress
 */
class PharCompressor extends AbstractCompressor
{

    /**
     * @return bool
     */
    public function isSupported()
    {
        // TODO: Implement isSupported() method.
    }

    /**
     * @param string $sourcePath
     * @param string $archiveFile
     * @param bool $override
     * @return bool
     */
    public function encode($sourcePath, $archiveFile, $override = true)
    {
        // TODO: Implement encode() method.
    }

    /**
     * @param string $archiveFile
     * @param string $extractTo
     * @param bool $override
     * @return bool
     */
    public function decode($archiveFile, $extractTo = '', $override = true)
    {
        // TODO: Implement decode() method.
    }

    /**
     * @return mixed
     */
    public function getDriver()
    {
        // TODO: Implement getDriver() method.
    }
}