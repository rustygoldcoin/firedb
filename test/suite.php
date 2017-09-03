<?php

require_once __DIR__ . '/../vendor/autoload.php';

use firetest\suite;

$suite = new suite(__DIR__);
$suite->run();
