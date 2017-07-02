<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/7/2
 * Time: 上午10:00
 */

require __DIR__ . '/s-autoload.php';

$lg = new \inhere\library\collections\LanguageManager([
    'basePath' => __DIR__ . '/tmp',
    'fileType' => 'php',
    'ignoreError' => 1,// if is true, will ignore not exists lang file

    'langFiles' => [
        // key => file path
        // if no file key, default use file name. e.g: app.yml -> app
        'default.php',
        'app.php',
        'contents.php',
        'user.php',
    ],
]);

//var_dump($lg);die;

// set data to default fileKey
$lg->set('test', 'value');
$lg->set('test1', 0);
$lg->set('test2', [
    'name' => 'value1',
    'name2' => 'value2',
    'name3' => 0,
]);

$lg->set('app:test', 'value in app');

//var_dump($lg);

var_dump($lg['name'], $lg['test'], $lg['app:test'], $lg['test2'], $lg['not-exists']);
