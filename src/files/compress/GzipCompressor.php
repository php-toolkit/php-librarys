<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 16/8/28
 * Time: 上午1:45
 */

namespace inhere\library\files\compress;

use inhere\library\exceptions\FileNotFoundException;
use inhere\library\exceptions\FileSystemException;
use inhere\library\exceptions\NotFoundException;
use inhere\library\files\FileSystem;
use Phar;
use PharData;

/**
 * Class GzipCompressor
 * @package inhere\library\files\compress
 */
class GzipCompressor extends AbstractCompressor
{
    protected $suffix = 'tar.gz';

    public function isSupported()
    {
        return extension_loaded('zlib') && 0 === ini_get('phar.readonly');
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
            $files = new \ArrayObject($sourcePath);
        } else {
            $files = $this->finder->findAll(true, $sourcePath)->getFiles();
        }

        // no file
        if (!$files->count()) {
            return false;
        }

        $archiveFile = FileSystem::isAbsPath($archiveFile) ? $archiveFile : dirname($sourcePath) . '/' . $archiveFile;
        $archiveFileDir = dirname($archiveFile);

        FileSystem::mkdir($archiveFileDir);

        try {
            $pd = $this->driver = new PharData($archiveFileDir . '/temp-archive.tar');

            // ADD FILES TO archive.tar FILE
            foreach ($files as $file) {
                $file = FileSystem::isAbsPath($file) ? $file : $this->sourcePath . '/' . $file;
                $pd->addFile($file);
//                $pd->addFile($this->sourcePath . $path, $path);
            }

            // COMPRESS archive.tar FILE. COMPRESSED FILE WILL BE archive.tar.gz
            $pd->compress(Phar::GZ);

            // NOTE THAT BOTH FILES WILL EXISTS. SO IF YOU WANT YOU CAN UNLINK archive.tar
            return unlink('archive.tar');
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
     * @throws FileNotFoundException
     */
    public function decode($archiveFile, $extractTo = '', $override = true)
    {
        $za = $this->getDriver();
        $res = $za->open($archiveFile);

        if ($res !== true) {
            throw new FileNotFoundException('Open the zip file [' . $archiveFile . '] failure!!');
        }

        $za->extractTo($extractTo ?: dirname($archiveFile));

        return $za->close();
    }

    public function getDriver()
    {
        return $this->driver;
    }
}
