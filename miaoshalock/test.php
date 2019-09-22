<?php 
include "../vendor/autoload.php";
class Test {

    private static $instance = null;
    private static $pdo = null;
    // 用单列模式 实例化Redis
    public static function Redis()
    {
        if (self::$instance == null ) {
            $redis = new Predis\Client([
                'scheme' => 'tcp',
                'host'   => '127.0.0.1',
                'port'   => 6379,
            ]);
            
            // $redis=new \Redis();
            // $redis->connect('127.0.0.1',6379);
            self::$instance = $redis;
        }
        return self::$instance;
    }

    public static function Pdo(){
        if(self::$pdo==null){
            $config = array();
            $config['db']['host'] = 'localhost';
            $config['db']['username'] = 'homestead';
            $config['db']['password'] = 'secret';
            $config['db']['port'] = '3306';
            $config['db']['database'] = 'redisdemo';
            $config['temp'] =str_replace("\\", '/',dirname(__FILE__))."/cache/";//临时目录
            $config['db']['charset'] = 'utf8';
            $config['db']['pconnect'] = 0;
            $config['db']['tablepre'] = 'ims_';
            $config['wx']['appid'] = 'wxe00001';//微信APPID
            $config['wx']['appsn'] = '12345678';//微信APPSN
            $config['domain'] = "http://".$_SERVER['HTTP_HOST']."/";//域名
            $pdo = new \Crh\DB($config);
            // $pdo = new \Slim\PDO\Database($dsn, $usr, $pwd);
            self::$pdo = $pdo;
        }
        return self::$pdo;
    }

    // 将商品库存循环到lpush的num里
    public function doPageSaveNum()
    {
        $redis=self::Redis();
        $pdo = self::Pdo();
        $goods_id=1;
        $sql="select id, num, money from ims_hotmallstore_goods where id=".$goods_id;
        $goods = $pdo->fetch($sql);

        if(!empty($goods)){
         for($i=1; $i<=$goods['num']; $i++){
             $redis->lpush('num',$i);
         }
         die('成功！');
        }else{
         $this->echoMsg(0,'商品不存在。');
        }
    }

    // 抢购下单
    public function doPageGoodsStore()
    {
            $pdo = self::Pdo();
            $goods_id=1;
            $sql="select id, num, money from ims_hotmallstore_goods where id=".$goods_id;
            $goods = $pdo->fetch($sql);
            $redis=self::Redis();
            $count=$redis->rpop('num');//每次从num取出1
            if($count==0){
                $this->echoMsg(0,'no num redis');
            }
            $this->doPageGoodsOrder($goods,1);
            
    }

    // 保存日志
    public function echoMsg($status,$msg,$_data="")
    {
        $pdo = self::Pdo();
        $data=json_encode(array('status'=>$status,'msg'=>$msg,'data'=>$_data),JSON_UNESCAPED_UNICODE);
        $order_log['status']=$status;
        $order_log['msg']=$msg;
        $order_log['create_time']=date('Y-m-d H:i:s');
        $goods = $pdo->insert('order_log', $order_log);
        die($data);
    }
    public function orderNo()
    {
        return date('ymd').substr(implode(NULL, array_map('ord', str_split(substr(uniqid(), 7, 13), 1))), 0, 8);
    }
    
    // 下单更新库存
    public function doPageGoodsOrder($goods,$goods_number)
    {   
        $pdo = self::Pdo();
        $orderNo=$this->orderNo();
        $number=$goods['num']-$goods_number;
        if($number<0){
            $this->echoMsg(0,'已没有库存');
        }
        $user_id=rand(1,500);
        $order['user_id']=$user_id;
        $order['goods_id']=$goods['id'];
        $order['number']=$goods_number;
        $order['price']=$goods['money'];
        $order['status']=1;
        $order['sku_id']=2;
        $order['order_sn']=$orderNo;
        $order['create_time']=date('Y-m-d H:i:s');
        $pdo->insert('order', $order);
        
        $sql="update ims_hotmallstore_goods set num=num-".$goods_number." where num>0 and id=".$goods['id'];
        // $sql="update ims_hotmallstore_goods set num=$number where num>0 and id=".$goods['id'];
        $res = $pdo->query($sql);
        if(!empty($res)){
            $this->echoMsg(1,'库存扣减成功'.$number);
        }
        $redis=self::Redis();
        $redis->lpush('num',$goods_number);
        $this->echoMsg(0,'库存扣减失败'.$number);

    }
 }

// 调用--将商品库存循环到lpush的num里
if($_GET['i']==1){
   $model = new Test;
   $model->doPageSaveNum();
}

// 调用--高并发抢购下单
if($_GET['i']==2){
   $model = new Test;
   $model->doPageGoodsStore();
}