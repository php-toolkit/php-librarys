# asset manager

## AssetLoad

this is a asset load tool.
class is at `inhere\library\asset\AssetLoad`

usage:

```
<?php
use inhere\library\asset\AssetLoad;

echo AssetLoad::css([
    'xx/ss.css',
    'xx/zz.css',
])->dump();
?>

<?php 
echo AssetLoad::js([
    'xx/ss.js',
    'xx/zz.js',
])->dump();

?>
```

It can also compress asset:

```
use inhere\library\asset\AssetLoad;

$config = [
    'useFullUrl' => false,
    'baseUrl'  => '/assets/src',
    'basePath' => '/var/www/project/public/assets/src',
];

$minOptions = [
    'mergeFile'     => 1,       // merge all asset to file.
    'mergeFilePath' => '/var/www/project/public/assets/dist/test.min.css',// merged asset output file.
    'outPath'   => '',
    'webPath'   => '/var/www/project/public',// web access root path
];

$as = AssetLoad::css([
        'app.css', // it real is '/var/www/project/public/assets/src/app.css' ( $config['basePath'] + '/app.css' )
        'frontend/content/list.css',
    ], $config)
    ->compress($minOptions);

// now. will generate new min file ''/var/www/project/public/assets/dist/test.min.css';
// you can load it by '<link href="/assets/dist/test.min.css">' instead of old files.

$minOptions['mergeFilePath'] = '/var/www/project/public/assets/dist/test.min.js';
$as1 = AssetLoad::js([
        'app.js',
        'frontend/content/list.js',
    ], $config)
    ->compress($minOptions);
```

## AssetManager

class is at `inhere\library\asset\AssetManager`

usage:

you need global create a AssetManager instance.

```
$options = [
    ...
];
$am = new AssetManager($options);

... ... 


// register asset file
$am->addJsFile('xx.js')->addCssFile('xx.css');

// register AssetBag
$am->loadAsset(new AssetBag([
    'css' => ['zz.css'],
    'js' => ['hh/ff.js']
]));

// on bofore render html

$html = $am->injectAsset($html);
echo $html;

```

## AssetPublisher

class is at `inhere\library\asset\AssetPublisher`

usage:

```
use inhere\library\asset\AssetPublisher;

$publisher = new AssetPublisher([
    'sourcePath'  => '/var/www/project/vendor/bower',
    'publishPath' => '/var/www/project/public/assets/publish',
]);

$publisher->add([
    'jquery'    => 'jquery',
    'yii2-pjax' => 'yii2-pjax'
])->publish();
```
