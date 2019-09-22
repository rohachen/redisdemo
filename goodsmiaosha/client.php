<?php
require '../vendor/predis/predis/autoload.php';
//第三步，客户端执行下单操作，下单前判断redis队列库存量
$redis = new Predis\Client([
    'scheme' => 'tcp',
    'host'   => '127.0.0.1',
    'port'   => 6379,
]);
// $redis = Redis::connection();
// $redis = new Redis();
// $redis->connect("127.0.0.1",6379);
//从链表的头部删除一个元素，返回删除的元素，$count为true
$count = $redis->lpop('goods_store');
if(!$count){
    echo "抢购失败";
    return;
}else{
	echo  $redis->llen('goods_store');
}