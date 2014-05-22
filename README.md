Graylog2 log target for Yii2
============================

Credits
-------
Benjamin Zikarsky https://github.com/bzikarsky/gelf-php

Require
-------
You need to install https://packagist.org/packages/graylog2/gelf-php

Usage
-----

Add Graylog target to your log component config:
```
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
                    'class' => 'nex\graylog\GraylogTarget',
                    'levels' => ['error', 'warning', 'info'],
                    'categories' => ['application'],
                    'logVars' => [], // This prevent yii2-debug from crashing ;)
                    'host' => '127.0.0.1',
                    'facility' => 'facility-name',
                ],
            ],
        ],
    ],
    ...
];
```
