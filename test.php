<?php

require_once __DIR__ . '/vendor/autoload.php';

$db = new Fire\Db(__DIR__ . '/collections');

$collection = $db->collection('MyObjects');

$configObj = (object) [
    'indexable' => [
        'testing'
    ]
];
$config = new Fire\Db\Collection\Config($configObj);
$collection->setConfiguration($config);
var_dump($collection->getConfiguration());
