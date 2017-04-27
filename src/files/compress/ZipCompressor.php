<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 16/8/27
 * Time: 下午6:23
 */

namespace inhere\library\files\compress;

use inhere\library\exceptions\FileSystemException;
use inhere\library\exceptions\NotFoundException;
use inhere\library\files\FileSystem;
use ZipArchive;

/**
 * Class ZipCompressor
 * @package inhere\library\files\compress
 */
class ZipCompressor extends AbstractCompressor
{
    protected $suffix = 'zip';

    public function isSupported()
    {
        return class_exists('ZipArchive');
    }

    /**
     * @param string $sourcePath a dir will compress
     * @param string $archiveFile zip file save path
     * @param bool $override
     * @return bool
     * @throws FileSystemException
     * @throws NotFoundException
     * @throws \inhere\library\exceptions\IOException
     * @throws \inhere\library\exceptions\InvalidArgumentException
     */
    public function encode($sourcePath, $archiveFile, $override = true)
    {
        if (!class_exists('ZipArchive')) {
            throw new NotFoundException('The method is require class ZipArchive (by zip extension)');
        }

        // 是一些指定文件
        if (is_array($sourcePath)) {
            $files = new \ArrayObject();
        } else {
            $files = $this->finder->findAll(true, $sourcePath)->getFiles();
        }

        // no file
        if (!$files->count()) {
            return false;
        }

        $archiveFile = FileSystem::isAbsPath($archiveFile) ? $archiveFile : dirname($sourcePath) . '/' . $archiveFile;

        FileSystem::mkdir(dirname($archiveFile));

        try {
            $za = $this->getDriver();

            if (true !== $za->open($archiveFile, $override ? ZipArchive::OVERWRITE : ZipArchive::CREATE)) {
                return false;
            }

            $za->setArchiveComment('compressed at ' . date('Y-m-d H:i:s'));

            foreach ($files as $file) {
                $file = FileSystem::isAbsPath($file) ? $file : $this->sourcePath . '/' . $file;
                $za->addFile($file);
//            $za->addFile($this->sourcePath . $path, $path);
            }

            return $za->close();
        } catch (\Exception $e) {
            throw new FileSystemException(
                "Compress directory [$sourcePath] to archive file [$archiveFile] failure!! MSG:" . $e->getMessage()
            );
        }
    }

    /**
     * @param string $archiveFile
     * @param string $extractTo
     * @param bool $override
     * @return bool
     * @throws FileSystemException
     */
    public function decode($archiveFile, $extractTo = '', $override = true)
    {
        $za = $this->getDriver();
        $res = $za->open($archiveFile);

        if ($res !== true) {
            throw new FileSystemException('Open the zip file [' . $archiveFile . '] failure!!');
        }

        $za->extractTo($extractTo ?: dirname($archiveFile));

        return $za->close();
    }

    public function getDriver()
    {
        if (!$this->driver || !($this->driver instanceof ZipArchive)) {
            $this->driver = new ZipArchive();
        }

        return $this->driver;
    }

}
