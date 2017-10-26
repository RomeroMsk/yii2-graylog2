Graylog2 log target for Yii2
============================

Credits
-------
Benjamin Zikarsky https://github.com/bzikarsky/gelf-php

Installation
------------
The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
php composer.phar require "krisengine/yii2-graylog2" "*"
```

or run

```
composer require "krisengine/yii2-graylog2" "*"
```

or add

```json
"krisengine/yii2-graylog2" : "*"
```

to the `require` section of your application's `composer.json` file.

Usage
-----

Add Graylog target to your log component config:
```php
<?php
return [
    ...
    'components' => [
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                'file' => [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
                ],
                'graylog' => [
                    'class' => 'krisengine\graylog\GraylogTarget',
                    'levels' => ['error', 'warning', 'info'],
                    'categories' => ['application'],
                    'logVars' => [], // This prevent yii2-debug from crashing ;)
                    'host' => '127.0.0.1',
                    'facility' => 'facility-name',
                    'transport' => 'udp', //GELF transport udp, tcp http or https
                    'sslVerifyPeer' => true, //Enable certificate validation of remote party (GELF transport https)
                    'sslAllowSelfSigned' => true, //Allow self-signed certificates (GELF transport https)
                    'sslCaFile' => null, //Path to custom CA (GELF transport https)
                    'sslCiphers' => null, //List of ciphers the SSL layer may use. Formatted as specified in `ciphers(1)` (GELF transport https)
                    'httpUsername' => true, //Username for HTTP basic authentication (GELF transport http or https)
                    'httpPassword' => true, //Password for HTTP basic authentication (GELF transport http or https)
                    'additionalFields' => [
                        'user-ip' => function($yii) {
                            return $yii->request->getUserIP();
                        },
                        'tag' => 'tag-name'
                    ]
                ],
            ],
        ],
    ],
    ...
];
```

GraylogTarget will use traces array (first element) from log message to set `file` and `line` gelf fields. So if you want to see these fields in Graylog2, you need to set `traceLevel` attribute of `log` component to 1 or more. Also all lines from traces will be sent as `trace` additional gelf field.

You can log not only strings, but also any other types (non-strings will be dumped by `yii\helpers\VarDumper::dumpAsString()`).

By default GraylogTarget will put the entire log message as `short_message` gelf field. But you can set `short_message`, `full_message` and `additionals` by using `'short'`, `'full'` and `'add'` keys respectively:
```php
<?php
// short_message will contain string representation of ['test1' => 123, 'test2' => 456],
// no full_message will be sent
Yii::info([
    'test1' => 123,
    'test2' => 456,
]);

// short_message will contain 'Test short message',
// two additional fields will be sent,
// full_message will contain all other stuff without 'short' and 'add':
// string representation of ['test1' => 123, 'test2' => 456]
Yii::info([
    'test1' => 123,
    'test2' => 456,
    'short' => 'Test short message',
    'add' => [
        'additional1' => 'abc',
        'additional2' => 'def',
    ],
]);
 
// short_message will contain 'Test short message',
// two additional fields will be sent,
// full_message will contain 'Test full message', all other stuff will be lost
Yii::info([
    'test1' => 123,
    'test2' => 456,
    'short' => 'Test short message',
    'full' => 'Test full message',
    'add' => [
        'additional1' => 'abc',
        'additional2' => 'def',
    ],
]);
```
