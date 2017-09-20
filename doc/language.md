# Language Manager

> [Return](document.md)

config the language translator.

```php
// language config
$config   = [
    'lang'     => $config->get('language', 'en'),
    'format'  => 'yml',
    'basePath' => '@resources/languages',
    'defaultFile' => 'app',

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

## usage

```php
// If no file is specified it reads '{basePath}/{lang}/defaut.yml'
$msg = $translator->translate('key');
// can also use
$msg = $translator->trans('key'); // trans() is alias method of the translate()
// can also use
$msg = $translator->tl('key'); // tl() is alias method of the translate()
$msg = $translator->t('key'); // t() is alias method of the translate()
```

## more information

1. allow multi arguments. `translate(string $key , array [$arg1 , $arg2], string $default)`

example

```php
 // on language config file
userNotFound: user [%s] don't exists!

 // on code
$msg = $translator->trans('userNotFound', 'demo');
// $msg : user [demo] don't exists!
```

2. allow fetch other config file data

@example

```yaml
// on default config file (e.g. `en/default.yml`)
userNotFound: user [%s] don't exists!

// on app config file (e.g. `en/app.yml`)
userNotFound: the app user [%s] don't exists!
```

get trans text:

```php
// will fetch value at `en/default.yml`
$msg = $translator->trans('userNotFound', 'demo');
//output $msg: user [demo] don't exists!

// will fetch value at `en/app.yml`
$msg = $translator->tran('app.userNotFound', 'demo');
//output $msg: the app user [demo] don't exists!

```
