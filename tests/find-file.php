<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017-10-27
 * Time: 10:51
 */
$srcDir = dirname(__DIR__, 2);

$directory = new \RecursiveDirectoryIterator($srcDir);
$filter = new \RecursiveCallbackFilterIterator($directory, function ($current, $key, $iterator) {
    /**
     * @var \SplFileInfo $current
     */
//    var_dump($current);die;

    // Skip hidden files and directories.
    if ($current->getFilename()[0] === '.') {
        return false;
    }

    if ($current->isDir()) {
        // Only recurse into intended subdirectories.
        return $current->getFilename() !== '.git';
    }

    // Only consume files of interest.
    return strpos($current->getFilename(), '.php') !== false;
});
$iterator = new \RecursiveIteratorIterator($filter);
$files = array();

foreach ($iterator as $info) {
//    var_dump($info);die;
    $files[] = $info->getPathname();
}

var_dump($files);