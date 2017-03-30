<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2016/2/24
 * Time: 15:04
 */

namespace inhere\library\language;

use inhere\library\collections\DataCollector;
use inhere\library\exceptions\InvalidArgumentException;
use inhere\library\exceptions\NotFoundException;
use inhere\library\files\FileSystem;
use inhere\library\helpers\ObjectHelper;
use inhere\library\helpers\StrHelper;
use inhere\library\StdBase;

/**
 * Class LanguageManager
 * @package inhere\library\base
 *
 */
class LanguageManager extends StdBase
{
    /**
     * current use language
     * @var string
     */
    protected $lang = 'en';

    /**
     * fallback language
     * @var string
     */
    protected $fallbackLang = 'en';

    /**
     * @var array ['string' => DataCollector]
     */
    private $data = [];

    /**
     * @var array ['string' => DataCollector]
     */
    private $fallbackData = [];

    /**
     * The default language directory path.
     * @var string
     */
    protected $basePath;

    /**
     * default file name.
     * @var string
     */
    protected $defaultFile = 'default';

    /**
     * the language file type
     * @var string
     */
    protected $fileType = 'yml';

    /**
     * file separator char. when want to get translation form other file.
     * e.g:
     *  $language->tran('app:createPage');
     * will fetch `createPage` value at the file `{$this->path}/{$this->lang}/app.yml`
     * @var string
     */
    protected $fileSeparator = ':';

    /**
     * e.g.
     * [
     *    'user'  => '/xx/yy/zh-cn/user.yml'
     *    'admin' => '/xx/yy/zh-cn/admin.yml'
     * ]
     * @var array
     */
    protected $langFiles = [];

    /**
     * loaded language file list.
     * @var array
     */
    protected $loadedFiles = [];

    const DEFAULT_FILE_KEY = '__default';

    /**
     * @param array $options
     */
    public function __construct(array $options = [])
    {
        ObjectHelper::loadAttrs($this, $options);
    }

    /**
     *
     * how to use language translate ? please see '/doc/language.md'
     *
     * @param string $key
     * @param array $args
     * @param string $default
     * @return string
     */
    public function translate($key, $args = [], $default = '')
    {
        $key = trim($key, $this->fileSeparator . ' ');

        if ( !$key || !is_string($key) ) {
            throw new \InvalidArgumentException('A lack of parameters or type error.');
        }

        // No separator, get value form default language file.
        if ( ($pos = strpos($key, $this->fileSeparator)) === false ) {
            $fileKey = static::DEFAULT_FILE_KEY;

        // Will try to get the value from the other config file
        } else {
            $fileKey = substr($key, 0, $pos);
            $key     = substr($key,$pos+1);
        }

        // translate form current language. if not found, translate form fallback language.
        $value = $this->findTranslationText($fileKey, $key) ?: $this->tranByFallbackLang($fileKey, $key, $default);

        if (!$value) {
            $value = ucfirst(StrHelper::toUnderscoreCase(str_replace(['-','_'],' ', $key), ' '));
        }

        $args = $args ? (array)$args : [];

        // $args is not empty?
        if ( $hasArgs = count($args) ) {
            array_unshift($args, $value);
        }

        return $hasArgs ? call_user_func_array('sprintf', $args) : $value;
    }
    public function tran($key, $args = [], $default = '')
    {
        return $this->translate($key, $args, $default);
    }
    public function tl($key, $args = [], $default = '')
    {
        return $this->translate($key, $args, $default);
    }

    /*********************************************************************************
    * handle current language translate
    *********************************************************************************/

    /**
     * @param string $fileKey
     * @param string $key
     * @param string $default
     * @return mixed
     */
    protected function findTranslationText($fileKey, $key, $default = '')
    {
        // has language data
        if ($collector = $this->getLangFileData($fileKey)) {
            return $collector->get($key, $default);
        }

        return $default;
    }

    /*********************************************************************************
    * fallback language translate handle
    *********************************************************************************/

    /**
     * @param string $fileKey
     * @param string $key
     * @param string $default
     * @return mixed
     */
    protected function tranByFallbackLang($fileKey, $key, $default='')
    {
        if ( $this->lang === $this->fallbackLang ) {
            return $default;
        }

        // check exists
        if ( $collector = $this->getFallbackFileData($fileKey) ) {
            return $collector->get($key, $default);
        }

        return $default;
    }

    /**
     * @param string $fileKey
     * @return DataCollector
     */
    public function getFallbackFileData($fileKey)
    {
        if ( isset($this->fallbackData[$fileKey]) ) {
            return $this->fallbackData[$fileKey];
        }

        // the first time fetch, instantiate it
        if ( $langFile = $this->getLangFile($fileKey)) {
            $file = str_replace("/{$this->lang}/","/{$this->fallbackLang}/", $langFile);

            if ( is_file($file) ) {
                $this->loadedFiles[] = $file;
                $this->fallbackData[$fileKey] = DataCollector::make($file, $this->fileType, $this->fallbackLang.'.'.$fileKey);
            }
        }

        return isset($this->fallbackData[$fileKey]) ? $this->fallbackData[$fileKey] : null;
    }

    /*********************************************************************************
     * helper method
     *********************************************************************************/

    /**
     * @param $filename
     * @param string $lang
     * @return string
     */
    protected function buildLangFilePath($filename, $lang = '')
    {
        $path = ($lang ?: $this->lang) . DIRECTORY_SEPARATOR . trim($filename);

        return $this->basePath . DIRECTORY_SEPARATOR . $path;
    }

    /*********************************************************************************
     * language file handle
     *********************************************************************************/

    /**
     * @param string $fileKey
     * @return string|null
     */
    public function getLangFile($fileKey)
    {
        if ( static::DEFAULT_FILE_KEY === $fileKey && !$this->hasLangFile($fileKey)) {
            $this->langFiles[$fileKey] = $this->buildLangFilePath($this->defaultFile.'.'.$this->fileType);
        }

        return isset($this->langFiles[$fileKey]) ? $this->langFiles[$fileKey] : null;
    }

    /**
     * @param string $fileKey
     * @return bool
     */
    public function hasLangFile($fileKey)
    {
        return isset($this->langFiles[$fileKey]);
    }

    /**
     * @param $file
     * @param string $fileKey
     * @throws InvalidArgumentException
     * @throws NotFoundException
     */
    public function addLangFile($file, $fileKey = '')
    {
        if ( !FileSystem::isAbsPath($file) ) {
            $file = $this->buildLangFilePath($file);
        }

        if ( !is_file($file) ) {
            throw new NotFoundException("The language file don't exists. FILE: $file");
        }

        $fileKey = $fileKey ?: basename($file, '.' . $this->fileType);

        if (!preg_match('/^[a-z][\w-]+$/i', $fileKey)) {
            throw new InvalidArgumentException("language file key [$fileKey] naming format error!!");
        }

        if ( $this->hasLangFile($fileKey) ) {
            throw new InvalidArgumentException("language file key [$fileKey] have been exists, don't allow override!!");
        }

        $this->langFiles[trim($fileKey)] = $file;
    }

    /**
     * @param string $fileKey
     * @return DataCollector
     * @throws NotFoundException
     */
    public function getLangFileData($fileKey)
    {
        if ( isset($this->data[$fileKey]) ) {
            return $this->data[$fileKey];
        }

        // at first, load language data, create data collector.
        if ($file = $this->getLangFile($fileKey)) {

            if ( !is_file($file) ) {
                throw new NotFoundException("The language file don't exists. FILE: $file");
            }

            $this->data[$fileKey] = DataCollector::make($file, $this->fileType, $this->lang.'.'.$fileKey);
            $this->loadedFiles[] = $file;

            return $this->data[$fileKey];
        }

        return null;
    }

    /**
     * @return DataCollector
     */
    public function getDefaultFileData()
    {
        return $this->getLangFileData(static::DEFAULT_FILE_KEY);
    }

    /*********************************************************************************
     * getter/setter
     *********************************************************************************/

    /**
     * Allow quick access default file translate by `$lang->key`,
     * is equals to `$lang->tl('key')`.
     * @param string $name
     * @return mixed|string
     */
    public function __get($name)
    {
        return $this->translate($name);
    }

    /**
     * Allow quick access default file translate by `$lang->key()`,
     * is equals to `$lang->tl('key')`.
     * @param string $name
     * @param array $args
     * @return mixed|string
     */
    public function __call($name, $args)
    {
        return $this->translate($name);
    }

    /**
     * @return string
     */
    public function getLang()
    {
        return $this->lang;
    }

    /**
     * @param string $lang
     */
    public function setLang($lang)
    {
        $this->lang = trim($lang);
    }

    /**
     * @return string
     */
    public function getBasePath()
    {
        return $this->basePath;
    }

    /**
     * @param string|array $path
     */
    public function setBasePath($path)
    {
        if ($path && is_dir($path)) {
            $this->basePath = $path;
        }
    }

    /**
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @return array
     */
    public function getLangFiles()
    {
        return $this->langFiles;
    }

    /**
     * @param array $langFiles
     */
    public function setLangFiles(array $langFiles)
    {
        foreach ($langFiles as $fileKey => $file) {
            $this->addLangFile($file, is_numeric($fileKey) ? '' : $fileKey);
        }
    }

    /**
     * @param bool $full
     * @return string
     */
    public function getDefaultFile($full= false)
    {
        return $full ? $this->getLangFile(static::DEFAULT_FILE_KEY) : $this->defaultFile;
    }

    /**
     * @return string
     */
    public function getFallbackLang()
    {
        return $this->fallbackLang;
    }

    /**
     * @param string $fallbackLang
     */
    public function setFallbackLang($fallbackLang)
    {
        $this->fallbackLang = $fallbackLang;
    }

    /**
     * @return array
     */
    public function getFallbackData()
    {
        return $this->fallbackData;
    }

    /**
     * @return string
     */
    public function getFileType()
    {
        return $this->fileType;
    }

    /**
     * @param string $fileType
     */
    public function setFileType($fileType)
    {
        if ( in_array($fileType, DataCollector::getFormats()) ) {
            $this->fileType = $fileType;
        }
    }

    /**
     * @return string
     */
    public function getFileSeparator()
    {
        return $this->fileSeparator;
    }

    /**
     * @param string $fileSeparator
     */
    public function setFileSeparator($fileSeparator)
    {
        $this->fileSeparator = $fileSeparator;
    }

    /**
     * @return array
     */
    public function getLoadedFiles()
    {
        return $this->loadedFiles;
    }
}
