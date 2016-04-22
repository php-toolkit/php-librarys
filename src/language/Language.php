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
 * Class Language
 * @package inhere\tools\base
 *
 * property $type
 *  if type equal to 1, use monofile. this is default.
 *
 *  if type equal to 2, use multifile.
 *
 *
 */
class Language extends DataCollector
{
    /**
     * current use language
     * @var string
     */
    protected $lang = 'en';

    /**
     * fallback lang
     * @var string
     */
    protected $fallbackLang = 'en';

    /**
     * language config file path
     * @var string
     */
    protected $path = '';

    /**
     * type of language config
     * @var int
     */
    protected $type = 1;

    /**
     * default file name, when use multifile. (self::type == self::TYPE_MULTIFILE)
     * @var string
     */
    protected $defaultFile = 'default';

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
     * loaded other config file list.
     * @var array
     */
    protected $otherFiles = [];

    /**
     * saved other config file data
     * @var DataCollector[]
     */
    protected $others = [];

    // use monofile. e.g: at config dir `{$this->path}/en.yml`
    const TYPE_MONOFILE  = 1;

    // use multifile. e.g: at config dir `{$this->path}/en/default.yml` `en/app.yml`
    const TYPE_MULTIFILE = 2;

    /**
     * @param array $options
     */
    public function __construct(array $options)
    {
        parent::__construct(null, static::FORMAT_PHP, 'language');

        $this->prepare($options);
    }

    protected function prepare($options)
    {
        foreach (['lang', 'fallbackLang', 'path', 'defaultFile'] as $key) {
            if ( isset($options[$key]) ) {
                $this->$key = $options[$key];
            }
        }

        if ( isset($options['type']) && in_array($options['type'], $this->getTypes()) ) {
            $this->type = (int)$options['type'];
        }

        // maybe use path alias
        // $this->path = Slim::alias($this->path);

        $this->mainFile = $this->type === static::TYPE_MONOFILE ?
            $this->path . DIRECTORY_SEPARATOR . "{$this->lang}.yml" :
            $this->getDirectoryFile($this->defaultFile);

        // check
        if ( !is_file($this->mainFile) ) {
            throw new NotFoundException("Main language file don't exists! File: {$this->mainFile}");
        }

        // load main language file data.
        $this->loadYaml($this->mainFile);
    }

    /**
     * language translate
     *
     * 1. allow multi arguments. `tran(string $key , mixed $arg1 , mixed $arg2, ...)`
     *
     * @example
     * ```
     *  // on language config
     * userNotFound: user [%s] don't exists!
     *
     *  // on code
     * $msg = $language->tran('userNotFound', 'demo');
     * ```
     *
     * 2. allow fetch other config file data, when use multifile. (`static::$type === static::TYPE_MULTIFILE`)
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
     * @param string $lang
     * @return string
     */
    public function translate($key, $args = [], $default = 'No translate.', $lang = '')
    {
        if ( !$key ) {
            throw new \InvalidArgumentException('A lack of parameters or error.');
        }

        // if use multifile.
        if ( $this->type === static::TYPE_MULTIFILE ) {
            $value = $this->handleMultiFile($key, $default);
        } else {
            $value = $this->get($key, $default);
        }

        $args = $args ? (array)$args : [];

        if ( $hasArgs = count($args) ) {
            array_unshift($args, $value);
        }

//        if ( !$args[0] ) {
//            throw new \InvalidArgumentException('No corresponding configuration of the translator. KEY: ' . $key);
//        }

        // $args is not empty?
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

    /**
     * @param $key
     * @param array $args
     * @param string $default
     * @return mixed|string
     */
    protected function handleMultiFile($key, $default = '')
    {
        $key = trim($key, $this->fileSeparator);

        // Will try to get the value from the other config file
        if ( ($pos = strpos($key, $this->fileSeparator)) >0 ) {
            $name    = substr($key, 0, $pos);
            $realKey = substr($key,$pos+1);

            // check exists
            if ( $collector = $this->getOther($name) ) {
                return $collector->get($realKey, $default);
            }
        }

        return $default;
    }

    /**
     * @param $name
     * @return string
     */
    public function getDirectoryFile($name)
    {
        return $this->path . DIRECTORY_SEPARATOR . $this->lang . DIRECTORY_SEPARATOR . trim($name) . '.yml';
    }

    /**
     * @param $name
     * @return DataCollector
     */
    public function getOther($name)
    {
        // the first time fetch, instantiate it
        if ( !isset($this->others[$name]) ) {
            $otherFile = $this->getDirectoryFile($name);

            if ( is_file($otherFile) ) {
                $this->otherFiles[$name]  = $otherFile;
                $this->others[$name] = new DataCollector($otherFile, static::FORMAT_YML, $name);
            }
        }

        return isset($this->others[$name]) ? $this->others[$name] : [];
    }

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
        return [static::TYPE_MONOFILE, static::TYPE_MULTIFILE];
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
    public function getDefaultFile()
    {
        return $this->defaultFile;
    }

}