<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2016/2/24
 * Time: 15:04
 */

namespace inhere\tools\language;

use inhere\tools\collections\DataCollector;
use inhere\tools\exceptions\NotFoundException;

/**
 * Class LanguageManager
 * @package inhere\tools\base
 *
 * property $type
 *  if type equal to 1, use monofile. this is default.
 *
 *  if type equal to 2, use multifile.
 *
 *
 */
class LanguageManager extends DataCollector
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
     * language config file path
     * @var string
     */
    protected $path = '';

    /**
     * type of language config. in [static::USE_MONOFILE, static::USE_MULTIFILE ]
     * @var int
     */
    protected $type = 1;

    /**
     * default file name, when use multifile. (self::type == self::USE_MULTIFILE)
     * @var string
     */
    protected $defaultName = 'default';

    /**
     * the language file type
     * @var string
     */
    protected $fileType = 'yml';

    /**
     * file separator char, when use multifile.
     * e.g:
     *  $language->tran('app:createPage');
     * will fetch `createPage` value at the file `{$this->path}/{$this->lang}/app.yml`
     * @var string
     */
    protected $fileSeparator = ':';

    /**
     * loaded main language config file, data saved in {@link self::$data}
     * @var string
     */
    protected $mainFile = '';

    /**
     * saved other config file data
     * @var DataCollector[]
     */
    protected $others = [];

    /**
     * loaded other config file list.
     * @var array
     */
    protected $loadedOtherFiles = [];

    /**
     * @var DataCollector|array
     */
    protected $fallbackData;

    // use monofile. e.g: at config dir `{$this->path}/en.yml`
    const USE_MONOFILE  = 1;

    // use multifile. e.g: at config dir `{$this->path}/en/default.yml` `en/app.yml`
    const USE_MULTIFILE = 2;

    /**
     * @param array $options
     * @param string $fileType
     */
    public function __construct(array $options, $fileType=self::FORMAT_YML)
    {
        parent::__construct(null, static::FORMAT_PHP, 'language');

        $this->fileType = $fileType;

        $this->prepare($options, $fileType);
    }

    protected function prepare($options, $fileType)
    {
        foreach (['lang', 'fallbackLang', 'path', 'defaultName'] as $key) {
            if ( isset($options[$key]) ) {
                $this->$key = $options[$key];
            }
        }

        if ( isset($options['type']) && in_array($options['type'], $this->getTypes()) ) {
            $this->type = (int)$options['type'];
        }

        $this->mainFile = $this->spliceLangFilePath($this->defaultName);

        // check
        if ( !is_file($this->mainFile) ) {
            throw new NotFoundException("Main language file don't exists! File: {$this->mainFile}");
        }

        // load main language file data.
        $this->load($this->mainFile, $fileType);
    }

    /**SE_MULTIFILE`)
     *
     * @example
     * ```
     * // on default config file (e.g. `en/default.yml`)
     * userNotFound: user [%s] don't exists!
     *
     * // on app config file (e.g. `en/app.yml`)
     * userNotFound: the app user [%s] don't exists!
     *
     * // on code
     * // will fetch value at `en/default.yml`
     * //output: user [demo] don't exists!
     * $msg = $language->tran('userNotFound', 'demo');
     *
     * // will fetch value at `en/app.yml`
     * //output: the app user [demo] don't exists!
     * $msg = $language->tran('app:userNotFound', 'demo');
     *
     * ```
     *
     * @param $key
     * @param array $args
     * @param string $default
     * param string $lang
     * @return string
     */
    public function translate($key, $args = [], $default = 'No translate.')
    {
        if ( !$key || !is_string($key) ) {
            throw new \InvalidArgumentException('A lack of parameters or type error.');
        }

        $key = trim($key);

        // use monofile or multifile ?
        $value = $this->isMonofile() ? $this->get($key) : $this->handleMultiFile($key);

        // translate form fallback language.
        if (!$value) {
            $value = $this->tranByFallbackLang($key, $default);
        }

        $args = $args ? (array)$args : [];

        // $args is not empty?
        if ( $hasArgs = count($args) ) {
            array_unshift($args, $value);
        }

        return $hasArgs ? call_user_func_array('sprintf', $args) : $value;
    }
    public function tran($key, $args = [], $default = 'No translate.')
    {
        return $this->translate($key, $args, $default);
    }
    public function tl($key, $args = [], $default = 'No translate.')
    {
        return $this->translate($key, $args, $default);
    }

    /*********************************************************************************
    * handle multi file config
    *********************************************************************************/

    /**
     * @param $key
     * @param string $default
     * @return mixed|string
     */
    protected function handleMultiFile($key, $default = '')
    {
        $key = trim($key, $this->fileSeparator);

        // no separator, get value form default file.
        if ( ($pos = strpos($key, $this->fileSeparator)) === false ) {
            return $this->get($key, $default);
        }

        // Will try to get the value from the other config file
        $filename    = substr($key, 0, $pos);
        $realKey = substr($key,$pos+1);

        // check exists
        if ( $collector = $this->loadOtherFile($filename) ) {
            return $collector->get($realKey, $default);
        }

        return $default;
    }

    /**
     * @param $filename
     * @return DataCollector
     */
    public function loadOtherFile($filename)
    {
        if ( $this->isMonofile() ) {
            return null;
        }

        // the first time fetch, instantiate it
        if ( !isset($this->others[$filename]) ) {
            $otherFile = $this->spliceLangFilePath($filename);

            if ( is_file($otherFile) ) {
                $this->loadedOtherFiles[$filename]  = $otherFile;
                $this->others[$filename] = DataCollector::make($otherFile, $this->fileType, $filename);
            }
        }

        return isset($this->others[$filename]) ? $this->others[$filename] : null;
    }

    /*********************************************************************************
    * fallback language handle
    *********************************************************************************/

    /**
     * @return DataCollector|array
     */
    public function getFallbackData()
    {
        if ( !$this->fallbackData ) {
            $fallbackFile = $this->spliceLangFilePath($this->defaultName, $this->fallbackLang);
            $collector = new DataCollector;

            if ($this->lang !== $this->fallbackLang && is_file($fallbackFile) ) {
                $collector->load($fallbackFile, $this->fileType);
            }

            $this->fallbackData = $this->isMonofile() ? $collector : [$collector];
        }

        return $this->fallbackData;
    }

    protected function tranByFallbackLang($key, $default='')
    {
        $fallbackData = $this->getFallbackData();

        // if use monofile.
        if ( $this->isMonofile() ) {
            return $fallbackData->get($key, $default);
        }

        // if use multifile.
        $value = $this->handleMultiFile($key, $default);

        $key = trim($key, $this->fileSeparator);

        // no separator, get value form default file.
        if ( ($pos = strpos($key, $this->fileSeparator)) === false ) {
        }

        // Will try to get the value from the other config file
        $filename = substr($key, 0, $pos);
        $realKey  = substr($key, $pos+1);

        // check exists
        if ( $collector = $this->fallbackData[$filename] ) {
            return $collector->get($realKey, $default);
        }

        return $default;
    }

    /**
     * @param $filename
     * @return DataCollector
     */
    public function loadFallbackOtherFile($filename)
    {
        if ( $this->isMonofile() ) {
            return null;
        }

        // the first time fetch, instantiate it
        if ( !isset($this->fallbackData[$filename]) ) {
            $otherFile = $this->spliceLangFilePath($filename);

            if ( is_file($otherFile) ) {
                $this->loadedOtherFiles['fallback'][$filename]  = $otherFile;
                $this->fallbackData[$filename] = DataCollector::make($otherFile, $this->fileType, $filename);
            }
        }

        return isset($this->fallbackData[$filename]) ? $this->fallbackData[$filename] : null;
    }

    /*********************************************************************************
    * helper method
    *********************************************************************************/

    /**
     * @param $filename
     * @return string
     */
    protected function spliceLangFilePath($filename, $lang = '')
    {
        $lang = $lang ?: $this->lang;
        $langFile = $this->isMonofile() ? $lang : $lang . DIRECTORY_SEPARATOR . trim($filename);

        return $this->path . DIRECTORY_SEPARATOR . $langFile . '.' . $this->fileType;
    }

    /*********************************************************************************
    * getter/setter
    *********************************************************************************/

    /**
     * @return string
     */
    public function getLang()
    {
        return $this->lang;
    }

    /**
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * @return array
     */
    public function getTypes()
    {
        return [static::USE_MONOFILE, static::USE_MULTIFILE];
    }

    public function isMonofile()
    {
        return $this->type === static::USE_MONOFILE;
    }

    /**
     * @return int
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return string
     */
    public function getDefaultName()
    {
        return $this->defaultName;
    }

}
