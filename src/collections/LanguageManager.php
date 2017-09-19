<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2016/2/24
 * Time: 15:04
 */

namespace inhere\library\collections;

use inhere\exceptions\InvalidArgumentException;
use inhere\exceptions\NotFoundException;
use inhere\library\files\FileSystem;
use inhere\library\helpers\Str;
use inhere\library\StdObject;
use Traversable;

/**
 * Class LanguageManager
 * @package inhere\library\base
 */
class LanguageManager extends StdObject implements \ArrayAccess, \Countable, \IteratorAggregate
{
    /**
     * current use language
     * @var string
     */
    private $lang = 'en';

    /**
     * fallback language
     * @var string
     */
    private $fallbackLang = 'en';

    /**
     * @var Collection[]
     * [ 'fileKey' => Collection ]
     */
    private $data = [];

    /**
     * @var Collection[]
     * [ 'fileKey' => Collection ]
     */
    private $fallbackData = [];

    /**
     * The base path language directory.
     * @var string
     */
    private $basePath;

    /**
     * default file name.
     * @var string
     */
    private $defaultFile = 'default';

    /**
     * the language file type. more see Collection::FORMAT_*
     * @var string
     */
    private $fileType = 'yml';

    /**
     * file separator char. when want to get translation form other file.
     * e.g:
     *  $language->tran('app:createPage');
     * will fetch `createPage` value at the file `{$this->path}/{$this->lang}/app.yml`
     * @var string
     */
    private $fileSeparator = ':';

    /**
     * e.g.
     * [
     *    'user'  => '/xx/yy/zh-cn/user.yml'
     *    'admin' => '/xx/yy/zh-cn/admin.yml'
     * ]
     * @var array
     */
    private $langFiles = [];

    /**
     * loaded language file list.
     * @var array
     */
    private $loadedFiles = [];

    /**
     * whether ignore not exists lang file when addLangFile()
     * @var bool
     */
    private $ignoreError = false;

    const DEFAULT_FILE_KEY = '__default';

    /**
     * how to use language translate ? please see '/doc/language.md'
     * @param string|bool $key
     * @param array $args
     * @param string $default
     * @return string|array
     * @throws \inhere\exceptions\NotFoundException
     * @throws \InvalidArgumentException
     */
    public function translate($key, array $args = [], $default = '')
    {
        if (!$key || !is_string($key)) {
            throw new \InvalidArgumentException('A lack of parameters or type error.');
        }

        list($fileKey, $key) = $this->splitFileKey($key);

        // translate form current language. if not found, translate form fallback language.
        if (($value = $this->findTranslationText($fileKey, $key)) === null) {
            $value = $this->transByFallbackLang($fileKey, $key, $default);
        }

        // no translate text
        if ($value === '' || $value === null) {
            return ucfirst(Str::toSnakeCase(str_replace(['-', '_'], ' ', $key), ' '));
        }

        // $args is not empty
        if ($args) {
            array_unshift($args, $value);

            return sprintf(...$args);
        }

        return $value;
    }

    /**
     * {@inheritdoc}
     * @see self::translate()
     */
    public function trans($key, array $args = [], $default = '')
    {
        return $this->translate($key, $args, $default);
    }

    /**
     * {@inheritdoc}
     * @see self::translate()
     */
    public function tl($key, array $args = [], $default = '')
    {
        return $this->translate($key, $args, $default);
    }

    /*********************************************************************************
     * handle current language translate
     *********************************************************************************/

    /**
     * @param string $key
     * @param mixed $value
     * @return mixed
     */
    public function set($key, $value)
    {
        list($fileKey, $key) = $this->splitFileKey($key);

        // no language data, init data(If have lang file, but no activation)
        if (!$this->getLangFileData($fileKey)) {
            $this->data[$fileKey] = Collection::make()->setName($this->lang . '.' . $fileKey);
        }

        return $this->data[$fileKey]->set($key, $value);
    }

    /**
     * @param string $key
     * @return mixed
     */
    public function has($key)
    {
        if (!$key || !is_string($key)) {
            throw new \InvalidArgumentException('A lack of parameters or type error.');
        }

        list($fileKey, $key) = $this->splitFileKey($key);

        return $this->findTranslationText($fileKey, $key) === null;
    }

    /**
     * @param string $fileKey
     * @param string $key
     * @param string $default
     * @return mixed
     * @throws \inhere\exceptions\NotFoundException
     */
    protected function findTranslationText($fileKey, $key, $default = null)
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
    protected function transByFallbackLang($fileKey, $key, $default = null)
    {
        if ($this->lang === $this->fallbackLang) {
            return $default;
        }

        // check exists
        if ($collector = $this->getFallbackFileData($fileKey)) {
            return $collector->get($key, $default);
        }

        return $default;
    }

    /**
     * @param string $fileKey
     * @return Collection
     */
    public function getFallbackFileData($fileKey)
    {
        if (isset($this->fallbackData[$fileKey])) {
            return $this->fallbackData[$fileKey];
        }

        // the first time fetch, instantiate it
        if ($langFile = $this->getLangFile($fileKey)) {
            $file = str_replace("/{$this->lang}/", "/{$this->fallbackLang}/", $langFile);

            if (is_file($file)) {
                $this->loadedFiles[] = $file;
                $this->fallbackData[$fileKey] = Collection::make($file, $this->fileType, $this->fallbackLang . '.' . $fileKey);
            }
        }

        return $this->fallbackData[$fileKey] ?? null;
    }

    /**
     * @param string $fileKey
     * @return bool
     */
    public function hasFallbackFileData($fileKey)
    {
        return isset($this->fallbackData[$fileKey]);
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


    /**
     * @param string|bool $key
     * @return mixed
     */
    private function splitFileKey($key)
    {
        $key = trim($key, $this->fileSeparator . ' ');

        // No separator, get value form default language file.
        if (($pos = strpos($key, $this->fileSeparator)) === false) {
            $fileKey = static::DEFAULT_FILE_KEY;

            // Will try to get the value from the other config file
        } else {
            $fileKey = substr($key, 0, $pos);
            $key = substr($key, $pos + 1);
        }

        return [$fileKey, $key];
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
        if (static::DEFAULT_FILE_KEY === $fileKey && !$this->hasLangFile($fileKey)) {
            $this->langFiles[$fileKey] = $this->buildLangFilePath($this->defaultFile . '.' . $this->fileType);
        }

        return $this->langFiles[$fileKey] ?? null;
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
     * @param string $file
     * @param string $fileKey
     * @return bool
     * @throws InvalidArgumentException
     * @throws NotFoundException
     */
    public function addLangFile($file, $fileKey = null)
    {
        if (!FileSystem::isAbsPath($file)) {
            $file = $this->buildLangFilePath($file);
        }

        if (!is_file($file)) {
            if ($this->ignoreError) {
                return false;
            }

            throw new NotFoundException("The language file don't exists. FILE: $file");
        }

        $fileKey = $fileKey ?: basename($file, '.' . $this->fileType);

        if (!preg_match('/^[a-z][\w-]+$/i', $fileKey)) {
            throw new InvalidArgumentException("language file key [$fileKey] naming format error!!");
        }

        if ($this->hasLangFile($fileKey)) {
            if ($this->ignoreError) {
                return false;
            }

            throw new InvalidArgumentException("language file key [$fileKey] have been exists, don't allow override!!");
        }

        $this->langFiles[$fileKey] = $file;

        return true;
    }

    /**
     * @param string $fileKey
     * @return Collection
     * @throws NotFoundException
     */
    public function getLangFileData($fileKey)
    {
        if (isset($this->data[$fileKey])) {
            return $this->data[$fileKey];
        }

        // at first, load language data, create data collector.
        if ($file = $this->getLangFile($fileKey)) {
            if (!is_file($file)) {
                throw new NotFoundException("The language file don't exists. FILE: $file");
            }

            $this->data[$fileKey] = Collection::make($file, $this->fileType, $this->lang . '.' . $fileKey);
            $this->loadedFiles[] = $file;

            return $this->data[$fileKey];
        }

        return null;
    }

    /**
     * @param string $fileKey
     * @return Collection
     */
    public function getCollection($fileKey)
    {
        return $this->getLangFileData($fileKey);
    }

    /**
     * @return Collection
     * @throws \inhere\exceptions\NotFoundException
     */
    public function getDefaultFileData()
    {
        return $this->getLangFileData(static::DEFAULT_FILE_KEY);
    }

    /**
     * @param $fileKey
     * @return bool
     */
    public function hasLangFileData($fileKey)
    {
        return isset($this->data[$fileKey]);
    }

    /*********************************************************************************
     * getter/setter
     *********************************************************************************/

    /**
     * Allow quick access default file translate by `$lang->key`,
     * is equals to `$lang->tl('key')`.
     * @param string $name
     * @return mixed|string
     * @throws \InvalidArgumentException
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
     * @throws \InvalidArgumentException
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
     * @throws \inhere\exceptions\InvalidArgumentException
     * @throws \inhere\exceptions\NotFoundException
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
    public function getDefaultFile($full = false)
    {
        return $full ? $this->getLangFile(self::DEFAULT_FILE_KEY) : $this->defaultFile;
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
        if (in_array($fileType, Collection::getFormats(), true)) {
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

    /**
     * @return bool
     */
    public function isIgnoreError()
    {
        return $this->ignoreError;
    }

    /**
     * @param bool $ignoreError
     */
    public function setIgnoreError($ignoreError)
    {
        $this->ignoreError = (bool)$ignoreError;
    }

    /*********************************************************************************
     * interface implementing
     *********************************************************************************/

    /**
     * Retrieve an external iterator
     * @link http://php.net/manual/en/iteratoraggregate.getiterator.php
     * @return Traversable An instance of an object implementing <b>Iterator</b> or
     * <b>Traversable</b>
     * @since 5.0.0
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->data);
    }

    /**
     * Whether a offset exists
     * @link http://php.net/manual/en/arrayaccess.offsetexists.php
     * @param mixed $offset <p>
     * An offset to check for.
     * </p>
     * @return boolean true on success or false on failure.
     * </p>
     * <p>
     * The return value will be casted to boolean if non-boolean was returned.
     * @since 5.0.0
     */
    public function offsetExists($offset)
    {
        return $this->has($offset);
    }

    /**
     * Offset to retrieve
     * @link http://php.net/manual/en/arrayaccess.offsetget.php
     * @param mixed $offset <p>
     * The offset to retrieve.
     * </p>
     * @return mixed Can return all value types.
     * @since 5.0.0
     */
    public function offsetGet($offset)
    {
        return $this->translate($offset);
    }

    /**
     * Offset to set
     * @link http://php.net/manual/en/arrayaccess.offsetset.php
     * @param mixed $offset <p>
     * The offset to assign the value to.
     * </p>
     * @param mixed $value <p>
     * The value to set.
     * </p>
     * @return void
     * @since 5.0.0
     */
    public function offsetSet($offset, $value)
    {
        $this->set($offset, $value);
    }

    /**
     * Offset to unset
     * @link http://php.net/manual/en/arrayaccess.offsetunset.php
     * @param mixed $offset <p>
     * The offset to unset.
     * </p>
     * @return void
     * @since 5.0.0
     */
    public function offsetUnset($offset)
    {
        unset($this->data[$offset]);
    }

    /**
     * Count elements of an object
     * @link http://php.net/manual/en/countable.count.php
     * @return int The custom count as an integer.
     * </p>
     * <p>
     * The return value is cast to an integer.
     * @since 5.1.0
     */
    public function count()
    {
        return count($this->data);
    }
}
