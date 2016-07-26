<?php
/**
 * Created by PhpStorm.
 * User: Inhere
 * Date: 2015/1/30
 * Use : use inhere\librarys\traits\TraitJsonFormat
 * File: TraitJsonFormat.php
 */

namespace inhere\librarys\traits;


use inhere\librarys\exceptions\NotFoundException;

/**
 * Class TraitJsonFormat
 * @package inhere\librarys\traits
 */
trait TraitJsonFormat
{
    static public function loadFile($file, $toArray=true)
    {
        if (!file_exists($file)) {
            throw new NotFoundException("没有找到或不存在资源文件{$file}");
        }

        $data = file_get_contents($file);

        if ( !$data ) {
            return null;
        }

        $data = preg_replace(array(

            // 去掉所有多行注释/* .... */
            '/\/\*.*?\*\/\s*/is',

            // 去掉所有单行注释//....
            '/\/\/.*?[\r\n]/is',

            // 去掉空白
            '/(?!\w)\s*?(?!\w)/is'

        ),  array('','',' '), $data);

        if ($toArray) {
            return json_decode($data, true);
        }

        return $data;
    }

    /**
     * @param string $input 文件 或 数据
     * @param bool $output 是否输出到文件， 默认返回格式化的数据
     * @param array $options 当 $output=true,此选项有效
     * $options = [
     *      'type'      => 'min' // 输出数据类型 min 压缩过的 raw 正常的
     *      'file'      => 'xx.json' // 输出文件路径;仅是文件名，则会取输入路径
     * ]
     * @return string | bool
     */
    static public function json($input, $output=false, array $options=[])
    {
        if (!is_string($input)) {
            return false;
        }

        $data = trim($input);

        if ( file_exists($input) ) {
            $data = file_get_contents($input);
        }

        if ( !$data ) {
            return false;
        }

        $data = preg_replace(array(

            // 去掉所有多行注释/* .... */
            '/\/\*.*?\*\/\s*/is',

            // 去掉所有单行注释//....
            '/\/\/.*?[\r\n]/is',

            // 去掉空白行
            "/(\n[\r])+/is"

        ),  array('','',"\n"), $data);

        if (!$output) {
            return $data;
        }

        $default = [ 'type' => 'min' ];
        $options = array_merge($default, $options);

        if ( file_exists($input) && (empty($options['file']) || !is_file($options['file']) ) )
        {
            $dir   = dirname($input);
            $name  = basename($input, '.json');
            $file  = $dir . '/' . $name . '.' . $options['type'].'.json';
            $options['file'] = $file;
        }

        static::saveAs($data, $options['file'], $options['type']);

        return $data;
    }

    static public function saveAs($data, $output, $type='min')
    {
        $default = [ 'type' => 'min',  'file' => '' ];
        $options = array_merge($default, $options);

        $dir   = dirname($output);

        if ( !file_exists($dir) ) {
            trigger_error('设置的json文件输出'.$dir.'目录不存在！');
        }

        $name  = basename($output, '.json');
        $file  = $dir . '/' . $name . '.' . $options['type'].'.json';

        if ( $type === 'min' )
        {
            // 去掉空白
            $data = preg_replace('/(?!\w)\s*?(?!\w)/i', '',$data);
        }


        @file_put_contents($file, $data);

    }

}// end class TraitJsonFormat
