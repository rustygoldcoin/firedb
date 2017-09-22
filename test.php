<?php

require_once __DIR__ . '/vendor/autoload.php';

$db = new Fire\Db(__DIR__ . '/collection');

$collection = $db->collection('MyObjects');

$config = new Fire\Db\Collection\Config();
$config->setIndexable(['rand']);
$collection->setConfiguration($config);
var_dump($collection->getConfiguration());
