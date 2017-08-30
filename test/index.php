<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
set_time_limit(0);

$time_start = microtime(true);

require_once __DIR__ . '/../vendor/autoload.php';

$db = new firedb\db(__DIR__ . '/collections');

$test = $db->collection('test');
$indexable = [
    'rand'
];
$test->setIndexable($indexable);
//
// for ($i = 0; $i < 10000; $i++) {
//     // sleep(1);
//     $start = microtime(true);
//     $doc = (object) [
//        'index' => $i,
//        'firstName' => 'Joshua',
//        'lastName' => 'Joshua',
//        'email' => 'josh@ua1.us',
//        'phone' => '4075628773',
//        'rand' => rand(1,2)
//     ];
//
//     $document = $test->insert($doc);
//     $end = microtime(true);
//     $time = ($end - $start) * 1000;
//     echo 'doc#: ' . $i . ' docId: ' . $document->__id . ' time:' . $time . 'ms' . "\n";
// }

$filter = (object) [
    'rand' => 2
];

$result = $test->find(null, 0, 10);
var_dump($result);
var_dump(count($result));

$time_end = microtime(true);
$time = ($time_end - $time_start) * 1000;
echo '<br>Finished in <strong>' . $time . ' milliseconds</strong>';
