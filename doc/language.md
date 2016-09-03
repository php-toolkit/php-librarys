## Language

> [Return](document.md)

config the language translator.

```
// language config
$config   = [
    'lang'     => $config->get('language', 'en'),
    'basePath' => '@resources/languages',
    'defaultFile' => 'default',

    'langFiles' => [
        // file key => file path
        // if no file key, default use file name. e.g: app
        'app.yml', // is relation path. will tranlate to '{basePath}/{lang}/app.yml'
        'contents.yml',
        'user.yml',
        '/var/www/xx/zz/yy.yml'
    ],
];

$translator = new LanguageManager($config);

```

use language translator:

```

// If no file is specified it reads '{basePath}/{lang}/defaut.yml'
$msg = $translator->tran('key');
// can also 
$msg = $translator->tl('key'); // tl() is alias method of the tran()

```

more information

1. allow multi arguments. `tran(string $key , array [$arg1 , $arg2], string $default)`

example

```
 // on language config file
userNotFound: user [%s] don't exists!

 // on code
$msg = $translator->tran('userNotFound', 'demo');
// $msg : user [demo] don't exists!
```

2. allow fetch other config file data

@example

```
// on default config file (e.g. `en/default.yml`)
userNotFound: user [%s] don't exists!

// on app config file (e.g. `en/app.yml`)
userNotFound: the app user [%s] don't exists!

// on code
// will fetch value at `en/default.yml`
$msg = $translator->tran('userNotFound', 'demo');
//output $msg: user [demo] don't exists!

// will fetch value at `en/app.yml`
$msg = $translator->tran('app:userNotFound', 'demo');
//output $msg: the app user [demo] don't exists!

```