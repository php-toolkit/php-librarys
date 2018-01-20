<?php
/**
 * Created by PhpStorm.
 * User: Inhere
 * Date: 2018/1/20 0020
 * Time: 23:57
 */

require __DIR__ . '/s-autoload.php';

$finder = \Inhere\Library\Files\SimpleFinder::create()
    ->files()
    ->inDir(__DIR__)
    ->name('*.php')
;

foreach ($finder as $file) {
    // var_dump($file);die;
    echo "+ {$file->getFilename()}\n";
}
