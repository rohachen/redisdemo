<?php
require '../vendor/predis/predis/autoload.php';
//链接redis
// $redis = new Redis();
// $result = $redis->connect("127.0.0.1",6379);
$redis = new Predis\Client([
    'scheme' => 'tcp',
    'host'   => '127.0.0.1',
    'port'   => 6379,
]);