<?php
include 'connect.php';
$redis->expire('goods_store',60);

?>

<a href="client.php">我抢</a>
