<?php
include 'connect.php';
//设置100件商品库存
$goods_number = 300;
//将100件商品存入到Redis链表中
for($i=0;$i<$goods_number;$i++){
    //lpush从链表的头部添加元素
    $redis->lpush('goods_store',$i);
}
//llen返回列表key的长度
echo $redis->llen('goods_store');

?>

<a href="start.php">开始</a>