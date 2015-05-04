# ltredis
通过php直接解析redis二进制协议实现的phpredis的客户端

为了得到更好的使用体验，请尽量从svn获取最新的版本

一个基于Redis2.2.12版本的php客户端接口，

通过socket的形式通过redis协议与redis服务器通讯和数据交换，在redis本身支持的数据类型的基础上增加了一些扩展，采用原生的redis命令接口，让使用者容易上手，不用话费太多的时间在熟悉每个方法的功能上

增加特性：

1.增加了类似于MC的缓存数组或者对象的扩展，一个数组可以直接被序列号后保存到Redis中

2.增加了list，hash数据类型批量保存，批量获取的接口实现，尽可能的使开发人员能够灵活的使用Redis的数据特性来满足自己的业务需求



include_once "ltredis.class.php";
$redis = new ltredis();

$key = "user:1000";

$redis->set($key,"Falcon.C");

$key = "user:1000:name";

$redis->set($key,"Falcon.C");

$key = "user:1000:addr";

$redis->set($key,"BeiJing");

$data = $redis->keys("");

print_r($data);

print_r($redis->exists("user:10000"));

print_r($redis->randomkey());

print_r($redis->type("user:1000"));

print_r($redis->sort("user_online"));

print_r($redis->ttl("user_online"));

echo $redis->getset("test:01","test_01");

echo $redis->getrange("test:01",-2,0);

$data = array("test:02"=>'this is a test 02',"test:03"=>"this is a test 03","test:04"=>"this is a test 04");

$redis->mset($data);

print_r($redis->get("test:02"));

print_r($redis->mget(array("test:01","user:1000","aaa","test:02","test:03")));

print_r($redis->incr("user_id:INC",1));

print_r($redis->decr("user_id:INC",1));

print_r($redis->msetnx(array("test:06"=>'this is a test 06',"test:05"=>"this is a test 05")));

$redis->setex("user:1000","Falcon.C",10);

echo $redis->setnx("user:1000","Falcon.C");

echo $redis->hset("user:10:info","name","Falcon.C in China");

echo $redis->hset("user:10:info","address","CY beijing");

print_r($redis->hget("user:10:info","name"));

$data = array("name"=>"aaaaaaaaaaa","address"=>"BeiJing"); $redis->hmset("user:11:info",$data);

$redis->hdel("user:11:info",0);

print_r($redis->hkeys("user:11:info")); print_r($redis->hvals("user:11:info"));
