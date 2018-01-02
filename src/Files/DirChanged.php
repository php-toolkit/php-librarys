<?php
/**
 * Created by PhpStorm.
 * User: Inhere
 * Date: 2017/12/21 0021
 * Time: 20:56
 */

namespace Inhere\Library\Files;

/**
 * Class DirChanged - Check Dir Changed by md5_file()
 * @package Inhere\Server\Components
 */
class DirChanged
{
    /** @var string */
    private $idFile;

    /** @var string */
    private $watchDir;

    /** @var string */
    private $dirMd5;

    /** @var string */
    private $md5s;

    /** @var int */
    private $fileCounter = 0;

    /**
     * 包含的 文件
     * 比 {@see $exlude*} 优先级更高
     * @var array
     */
    private $includeFiles = [
        // 'README.md'
    ];

    /** @var array 包含的文件扩展 */
    private $includeExt = [
        'php'
    ];

    /**
     * 排除的 文件 文件扩展匹配 目录
     * @var array
     */
    private $excludeFiles = [
        '.gitignore',
        'LICENSE',
        'LICENSE.txt'
    ];

    /** @var array 排除的目录 */
    private $excludeDirs = [
        '.git',
        '.idea'
    ];


    /**
     * @param string|null $idFile
     * @return bool
     */
    public function isModified(string $idFile = null)
    {
        return $this->isChanged($idFile);
    }

    /**
     * @param string|null $idFile
     * @return bool
     */
    public function isChanged(string $idFile = null)
    {
        if ($idFile) {
            $this->setIdFile($idFile);
        }

        if (!($old = $this->dirMd5) && (!$old = $this->getMd5ByIdFile())) {
            $this->calcDirMd5();

            return false;
        }

        $this->calcDirMd5();

        return $this->dirMd5 !== $old;
    }

    /**
     * @return bool|string
     */
    public function getMd5ByIdFile()
    {
        if (!$file = $this->idFile) {
            return false;
        }

        if (!is_file($file)) {
            return false;
        }

        return trim(file_get_contents($file));
    }

    /**
     * @param string $watchDir
     * @return string
     */
    public function calcDirMd5(string $watchDir = null)
    {
        $this->setWatchDir($watchDir);
        $this->collectDirMd5($this->watchDir);

        $this->dirMd5 = md5($this->md5s);
        $this->md5s = null;

        if ($this->idFile) {
            file_put_contents($this->idFile, $this->dirMd5);
        }

        return $this->dirMd5;
    }

    /**
     * @param string $watchDir
     */
    private function collectDirMd5(string $watchDir)
    {
        $files = scandir($watchDir, 0);

        foreach ($files as $f) {
            if ($f === '.' || $f === '..') {
                continue;
            }

            $path = $watchDir . '/' . $f;

            //递归目录
            if (is_dir($path)) {
                if (\in_array($f, $this->excludeDirs, true)) {
                    continue;
                }

                $this->collectDirMd5($path);
            }

            //检测文件类型
            $suffix = trim(strrchr($f, '.'), '.');

            if ($suffix && \in_array($suffix, $this->includeExt, true)) {
                $this->md5s .= md5_file($path);
                $this->fileCounter++;
            }
        }
    }

    /**
     * @return string
     */
    public function getIdFile()
    {
        return $this->idFile;
    }

    /**
     * @param string $idFile
     * @return $this
     */
    public function setIdFile(string $idFile)
    {
        $this->idFile = $idFile;

        return $this;
    }

    /**
     * @return string
     */
    public function getWatchDir()
    {
        return $this->watchDir;
    }

    /**
     * @param string $watchDir
     * @return $this
     */
    public function setWatchDir($watchDir)
    {
        if ($watchDir) {
            $this->watchDir = $watchDir;
        }

        return $this;
    }

    /**
     * @return string
     */
    public function getDirMd5()
    {
        return $this->dirMd5;
    }

    /**
     * @return array
     */
    public function getIncludeExt(): array
    {
        return $this->includeExt;
    }

    /**
     * @param array $includeExt
     */
    public function setIncludeExt(array $includeExt)
    {
        $this->includeExt = $includeExt;
    }

    /**
     * @return array
     */
    public function getExcludeFiles(): array
    {
        return $this->excludeFiles;
    }

    /**
     * @param array $excludeFiles
     */
    public function setExcludeFiles(array $excludeFiles)
    {
        $this->excludeFiles = $excludeFiles;
    }

    /**
     * @return array
     */
    public function getExcludeDirs(): array
    {
        return $this->excludeDirs;
    }

    /**
     * @param array $excludeDirs
     */
    public function setExcludeDirs(array $excludeDirs)
    {
        $this->excludeDirs = $excludeDirs;
    }

    /**
     * @return int
     */
    public function getFileCounter(): int
    {
        return $this->fileCounter;
    }

    /**
     * @return array
     */
    public function getIncludeFiles(): array
    {
        return $this->includeFiles;
    }

    /**
     * @param array $includeFiles
     */
    public function setIncludeFiles(array $includeFiles)
    {
        $this->includeFiles = $includeFiles;
    }
}
