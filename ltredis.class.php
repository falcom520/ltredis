<?php 
/**
 * Redis缓存基类
 * @支持Redis 2.2.12版本，不向下兼容
 * @author Falcon.C(falcom520@gmail.com)
 * 
 */
class ltredis
{
    private $host = '';
    private $port = 6379;
    private $handle;
    
    public function __construct($conn = 0){
        global $redis_config;
        $this->host = $redis_config[$conn]['host'];
        $this->port = $redis_config[$conn]['port'];
    }
    
    //=======================================keys start=============================================================//
    /**
     * 判断指定的key是否存在
     * Enter description here ...
     * @param string $key
     */
    public function exists($key){
        $cmd = "EXISTS {$key}";
        $response = $this->exec($cmd);
        if (!$this->getError($response)){
            return false;
        }
        return substr($response, 1);
    }
    
    /**
     * 通过key匹配缓存中存在的key的列表
     * Enter description here ...
     * @param string $key    // $key = "*user*";
     */
    public function keys($key){
        $cmd = "KEYS {$key}";
        $response = $this->exec($cmd);
        if (!$this->getError($response)){
            return false;
        }
        $len = intval(substr($response, 1));
        $list = array();
        for ($i=0;$i<$len;$i++){
            $length = substr($this->getResponse(), 1);
            $value = $this->getResponse();
            $list[] = $value;
        }
        return $list;
    }
    
    /**
     * 随机取一个key
     * Enter description here ...
     */
    public function randomkey(){
        $cmd = "RANDOMKEY";
        $response = $this->exec($cmd);
        if (!$this->getError($response)){
            return false;
        }
        return $this->getResponse();
    }
    
    /**
     * 判断指定的key的类型
     * Enter description here ...
     * @param unknown_type $key
     */
    public function type($key){
        $cmd = "TYPE {$key}";
        $response = $this->exec($cmd);
        if (!$this->getError($response)){
            return false;
        }
        return $response;
        return $this->getResponse();
    }
    
    public function sort($key,$order='',$encode = true){
        $cmd = "SORT {$key} {$order}";
        $response = $this->exec($cmd);
        if (!$this->getError($response)) {
            return false;
        }
        $len = substr($response, 1);
        $list = array();
        for ($i = 0; $i < $len; $i++) {
            $length = substr($this->getResponse(), 1);
            $value = $this->getResponse();
            $list[] = $this->unpack_value($value,$encode);
        }
        return $list;
    }
    
    public function ttl($key){
        if (empty($key)) return false;
        $cmd = "TTL {$key}";
        $response = $this->exec($cmd);
        if (!$this->getError($response)) {
            return false;
        }
        return substr($response,1);
    }
    
    public function expire($key,$secord){
        if (empty($key)) return false;
        $cmd = "EXPIRE {$key} {$secord}";
        $response = $this->exec($cmd);
        if (!$this->getError($response)) {
            return false;
        }
        return substr($response,1);
    }
    
    public function persist($key){
        if (empty($key)) return false;
        $cmd = "PERSIST {$key}";
        $response = $this->exec($cmd);
        if (!$this->getError($response)){
            
        }
    }

    public function del($key){
    	if (empty($key)) {
    		return false;
    	}
    	$cmd = "DEL {$key}";
    	$response = $this->exec($cmd);
    	if (!$this->getError($response)){
    		return false;
    	}
    	return substr($response,1);
    }

    //=======================================keys start=============================================================//
    
    //=======================================String 18 cmd start=============================================================//
    
    public function append($key,$value){
        if (empty($key) || empty($value)) return false;
        $value = $this->pack_value($value,false);
        $cmd = "APPEND {$key} {$value}";
        $response = $this->exec($cmd);
        if (!$this->getError($response)) {
            return false;
        }
        return substr($response, 1);
    }
    
    public function getset($key,$value,$encode = false){
        if (empty($key)) return false;
        $value = $this->pack_value($value,$encode);
        $cmd = "GETSET {$key} {$value}";
        $response = $this->exec($cmd);
        if (!$this->getError($response)) {
            return false;
        }
        $response = $this->getResponse();
        return $this->unpack_value($response,$encode);
    }
    
    public function getrange($key,$start = 0,$end = 0,$encode = false){
        if (empty($key)) return false;
        $cmd = "GETRANGE {$key} 0 -1";
        $response = $this->exec($cmd);
        if (!$this->getError($response)) {
            return false;
        }
        $response = $this->getResponse();
        $value = $this->unpack_value($response,$encode);
        if ($end == 0){
            return substr($value, $start);
        }
        return substr($value, $start,$end);
    }
    
    public function set($key,$value,$encode = false){
        $value = $this->pack_value($value,$encode);
        $cmd = "SET {$key} {$value}";
        $response = $this->exec($cmd);
        if (!$this->getError($response)){
            return false;
        }
        return true;
    }
    
    public function mset($data,$encode = false){
        $cmd = " ";
        if (is_array($data)){
            foreach ($data as $k=>$v){
                $cmd .= $k.' "'.$this->pack_value($v,$encode).'" ';
            }
            $cmd = "MSET ".$cmd;
            $response = $this->exec($cmd);
            if (!$this->getError($response)){
                return false;
            }
            return true;
        }
        return false;
    }
    
    public function get($key,$encode = false){
        $cmd = "GET {$key}";
        $response = $this->exec($cmd);
        if (!$this->getError($response)){
            return false;
        }
        $len = intval(substr($response, 1));
        if ($len > 0) {
            $value = $this->getResponse();
            return $this->unpack_value($value,$encode);
        }
        return false;
    }
    
    public function mget($keys,$encode = false){
        if (is_array($keys)){
			$key = implode(" ",$keys);
        }else{
            $key = $keys;
        }
		$key = trim($key);
        $cmd = "MGET {$key}";
        $response = $this->exec($cmd);
        if (!$this->getError($response)){
            return false;
        }
        $len = intval(substr($response, 1));
        $list = array();
        for ($i = 0; $i < $len; $i++) {
            $length = substr($this->getResponse(), 1);
			if($length > 0){
				$value = $this->getResponse();
				$list[$keys[$i]] = $this->unpack_value($value,$encode);
			}else{
				$list[$keys[$i]] = '';
			}
        }
        return $list;
    }
    
    public function incr($key,$offset = 1){
        if ($offset == 1){
            $cmd = "INCR {$key}";
        }else{
            $cmd = "INCRBY {$key} {$offset}";
        }
        $response = $this->exec($cmd);
        if (!$this->getError($response)){
            return false;
        }
        return substr($response, 1);
    }
    
    public function decr($key,$offset = 1){
        if ($offset == 1){
            $cmd = "DECR {$key}";
        }else{
            $cmd = "DECRBY {$key} {$offset}";
        }
        $response = $this->exec($cmd);
        if (!$this->getError($response)){
            return false;
        }
        return substr($response, 1);
    }

    public function msetnx($data,$encode = false){
        if (empty($data) || !is_array($data)) return false;
        
        $field = " ";
        foreach($data as $k=>$v){
            $field .= $k.' "'.$this->pack_value($v,$encode).'" ';
        }
		$field = trim($field);
        $cmd = "MSETNX {$field}";
        $response = $this->exec($cmd);
        if (!$this->getError($response)) {
            return false;
        }
        return substr($response,1);
    }
    
    public function setex($key,$value,$secord = 3600,$encode = false){
        if (empty($key)) return false;
        $value = $this->pack_value($value,$encode);
        $cmd = "SETEX {$key} {$secord} {$value}";
        $response = $this->exec($cmd);
        if (!$this->getError($response)) {
            return false;
        }
        $response = substr($response,1);
        if ($response == "OK") {
            return true;
        }
        return false;
    }
    
    public function setnx($key,$value,$encode = false){
        if (empty($key) || $value == '') return false;
        $value = $this->pack_value($value,$encode);
        $cmd = "SETNX {$key} {$value}";
        $response = $this->exec($cmd);
        if (!$this->getError($response)) {
            return false;
        }
        return substr($response,1);
    }
    
    public function setrange($key,$offset,$value,$encode = false){
        if (empty($key)) return false;
        $value = $this->pack_value($value,$encode);
        $cmd = "SETRANGE {$key} {$offset} {$value}";
        $response = $this->exec($cmd);
        if (!$this->getError($response)) {
            return false;
        }
        return substr($response,1);
    }
    
    public function strlen($key,$encode = false){
        if (empty($key)) return false;
        if ($encode == true){
            $data = $this->get($key,$encode);
            if (empty($data)){
                return 0;
            }
            return strlen($data);
        }else{
            $cmd = "STRLEN {$key}";
            $response = $this->exec($cmd);
            if (!$this->getError($response)){
                return false;
            }
            return substr($response, 1);
        }
    }
    
    
    //=======================================String end=============================================================//
    
    
    //======================================= hashes 12 cmd end=============================================================//
    
    public function hset($key,$field,$value,$encode = false){
        if (empty($key) || empty($field) || empty($value)) return false;
        $value = $this->pack_value($value,$encode);
        $cmd = "HSET {$key} {$field} {$value}";
        $response = $this->exec($cmd);
        if (!$this->getError($response)) {
            return false;
        }
        return substr($response,1);
    }
    
    public function hmset($key,$fields,$encode = false){
        if (empty($key) || empty($fields) || !is_array($fields)){
            return false;
        }
        $field = "";
        foreach($fields as $k=>$v){
            $field .= "$k ".$this->pack_value($v,$encode)." ";
        }
        $field = trim($field);
        $cmd = "HMSET {$key} {$field}";
        $response = $this->exec($cmd);
        if (!$this->getError($response)){
            return false;
        }
        return true;
    }
    
    public function hget($key,$field,$encode = false){
        if (empty($key) || empty($field)) return false;
        $cmd = "HGET {$key} {$field}";
        $response = $this->exec($cmd);
        if (!$this->getError($response)) {
            return false;
        }
        $len = substr($response,1);
        $value = '';
        if ($len>0) {
            $value = $this->getResponse();
            $value = $this->unpack_value($value,$encode);
        }
        return $value;
    }
    
    public function hmget($key,$fields,$encode = false){
        if (empty($key) || empty($fields)) return false;
        $field = "";
        if (is_array($fields)){
            $field = implode(" ", $fields);
        }else{
            $field = $fields;
        }
        $cmd = "HMGET {$key} {$field}";
        $response = $this->exec($cmd);
        if (!$this->getError($response)){
            return false;
        }
        $len = substr($response,1);
        $list = array();
        for ($i = 0;$i < $len; $i++){
            $length = substr($this->getResponse(),1);
            if ($length>0){
                $value = $this->getResponse();
                $list[$fields[$i]] = empty($value) ? '' : $this->unpack_value($value,$encode);
            }else{
                $list[$fields[$i]] = '';
            }
        }
        return $list;
    }
    
    public function hlen($key){
        if (empty($key)) return false;
        $cmd = "HLEN {$key}";
        $response = $this->exec($cmd);
        if (!$this->getError($response)){
            return false;
        }
        return substr($response,1);
    }
    
    public function hdel($key,$fields){
        $field = "";
        if (is_array($fields)) {
            $field = implode(' ', $fields);
        }else{
            $field = $fields;
        }
        $cmd = "HDEL {$key} {$field}";
        $response = $this->exec($cmd);
        if (!$this->getError($response)) {
            return false;
        }
        return substr($response,1);
    }
    
    public function hgetall($key,$encode = false){
        if (empty($key)) return false;
        $cmd = "HGETALL {$key}";
        $response = $this->exec($cmd);
        if (!$this->getError($response)) {
            return false;
        }
        $len = substr($response, 1);
        $list = array();
        for ($i = 0;$i < $len/2;$i++){
            $length = substr($this->getResponse(), 1);
            $key = $this->getResponse();
            $length = substr($this->getResponse(), 1);
            $value = $this->getResponse();
            $value = $this->unpack_value($value,$encode);
            $list[$key] = $value;
        }
        return $list;
    }
    
    public function hkeys($key){
        if (empty($key)) return false;
        
        $cmd = "HKEYS {$key}";
        $response = $this->exec($cmd);
        if (!$this->getError($response)){
            return false;
        }
        $len = substr($response, 1);
        $list = array();
        if ($len>0){
            for($i = 0;$i < $len;$i++){
                $length = substr($this->getResponse(), 1);
                $value = $this->getResponse();
                $list[] = $value;
            }
        }
        return $list;
    }
    
    public function hvals($key,$encode = false){
        if (empty($key)) return false;
        
        $cmd = "HVALS {$key}";
        $response = $this->exec($cmd);
        if (!$this->getError($response)) {
            return false;
        }
        $len = substr($response,1);
        $list = array();
        if ($len>0) {
            for ($i=0;$i<$len;$i++){
                $length = substr($this->getResponse(), 1);
                $value = $this->getResponse();
                $list[] = $this->unpack_value($value,$encode);
            }
        }
        return $list;
    }
    
    public function hexists($key,$field){
        if (empty($key) || empty($field)) return false;
        
        $cmd = "HEXISTS {$key} {$field}";
        $response = $this->exec($cmd);
        if (!$this->getError($response)) {
            return false;
        }
        return substr($response,1);
    }
    
    public function hincrby($key,$field,$offset = 1){
        if (empty($key) || empty($field)) return false;
        $cmd = "HINCRBY {$key} {$field} {$offset}";
        $response = $this->exec($cmd);
        if (!$this->getError($response)) {
            return false;
        }
        return substr($response,1);
    }
    
    public function hsetnx($key,$field,$value,$encode = false){
        if (empty($key) || empty($field)) return false;
        $value = $this->pack_value($value,$encode);
        $cmd = "HSETNX {$key} {$field} {$value}";
        $response = $this->exec($cmd);
        if (!$this->getError($response)) {
            return false;
        }
        return substr($response, 1);
    }
    
    //======================================= hashes end=============================================================//
    
    
    // ================================== LIST start =====================================================//
    public function lpush($key,$value,$encode = true){
        $value = $this->pack_value($value,$encode);
        $cmd = "LPUSH {$key} {$value}";
        $response = $this->exec($cmd);
        if (!$this->getError($response)){
            return false;
        }
        return substr($response,1);
    }
    
    public function lpop($key,$encode = true){
        $cmd = "LPOP {$key}";
        $response = $this->exec($cmd);
        if (!$this->getError($response)){
            return false;
        }
        $len = substr($response,1);
        $list = '';
        if ($len>0){
            $value = $this->getResponse();
            $list = $this->unpack_value($value,$encode);
        }
        return $list;
    }
    
    public function rpush($key,$value,$encode = true){
        $value = $this->pack_value($value,$encode);
        $cmd = "RPUSH {$key} {$value}";
        $response = $this->exec($cmd);
        if (!$this->getError($response)){
            return false;
        }
        return substr($response,1);
    }
    
    public function rpop($key,$encode = true){
        if (empty($key)) return false;
        $cmd = "RPOP {$key}";
        $response = $this->exec($cmd);
        if (!$this->getError($response)){
            return false;
        }
        $len = substr($response,1);
        $list = '';
        if ($len>0){
            $value = $this->getResponse();
            $list = $this->unpack_value($value,$encode);
        }
        return $list;
    }
    
    public function lrem($key,$value,$count = 0){
        $value = $this->pack_value($value);
        $cmd = "LREM {$key} {$count} {$value}";
        $response = $this->exec($cmd);
        if (!$this->getError($response)){
            return false;
        }
        return substr($response, 1);
    }
    
    public function lrange($key,$offset = 0,$limit = -1,$encode = true){
        $end = $offset + $limit;
        $cmd = "LRANGE {$key} {$offset} {$end}";
        $response = $this->exec($cmd);
        if (!$this->getError($response)){
            return ;
        }
        $count = intval(substr($response, 1));
        $list = array();
        for ($i = 0;$i < $count;$i++){
            $length = substr($this->getResponse(),1);
            $value = $this->getResponse();
            $list[] = $this->unpack_value($value,$encode);
        }
        return $list;
    }
    
    public function llen($key){
        if (empty($key)) return false;
        $cmd = "LLEN {$key}";
        $response = $this->exec($cmd);
        if (!$this->getError($response)) {
            return false;
        }
        return substr($response,1);
    }
    
    public function lset($key,$index,$value){
        if (empty($key) || empty($index) || empty($value)) return false;
        $cmd = "LSET {$key} {$index} {$value}";
        $response = $this->exec($cmd);
        if (!$this->getError($response)) {
            return false;
        }
        if(substr($response,1) == 'OK'){
            return true;
        }
        return false;
    }
    
    public function lindex($key,$index = 0,$encode = true){
        if (empty($key)) return false;
        $cmd = "LINDEX {$key} {$index}";
        $response = $this->exec($cmd);
        if (!$this->getError($response)){
            return false;
        }
        $len = substr($response, 1);
        $list = '';
        if ($len>0){
            $value = $this->getResponse();
            $list = $this->unpack_value($value,$encode);
        }
        return $list;
    }
    
    // $flag BEFORE|AFTER 插入之前还是之后
    public function linsert($key,$pivot,$value,$flag = "BEFORE"){
        if (empty($key) || empty($pivot) || empty($value)) return false;
        $pivot = $this->pack_value($pivot);
        $value = $this->pack_value($value);
        $cmd = "LINSERT {$key} {$flag} {$pivot} {$value}";
        $response = $this->exec($cmd);
        if (!$this->getError($response)) {
            return false;
        }
        return substr($response,1);
    }
    
    // ================================== LIST end =====================================================//

    
    // ================================== Set start 此数据类型中key不能用:作为分隔符=====================================================//
    
    public function sadd($key,$value,$encode = false){
        if (empty($key) || empty($value)) return false;
        $value = $this->pack_value($value,$encode);
        $cmd = "SADD {$key} {$value}";
        $response = $this->exec($cmd);
        if (!$this->getError($response)){
            return false;
        }
        return substr($response,1);
    }
    
    public function saddbatch($key,$data,$encode = false){
        if (empty($key) || empty($data) || !is_array($data)) return false;
        $cmd = "";
        foreach ($data as $v){
            $value = $this->pack_value($v,$encode);
            $cmd .= "SADD {$key} {$value}\r\n";
        }
        $cmd = trim($cmd,"\r\n");
        $response = $this->exec($cmd);
        if ($this->getError($response)){
            return false;
        }
        return true;
    }
    
    public function scard($key){
        if (empty($key)) return false;
        $cmd = "SCARD {$key}";
        $response = $this->exec($cmd);
        if (!$this->getError($response)){
            return false;
        }
        return substr($response,1);
    }
    
    public function sdiff($keys,$encode = false){
        if (empty($keys)) return false;
        if (is_array($keys)) {
            $keys = implode(' ', $keys);
        }
        $cmd = "SDIFF {$keys}";
        $response = $this->exec($cmd);
        if (!$this->getError($response)){
            return false;
        }
        $len = substr($response,1);
        $list = array();
        if ($len>0) {
            for ($i=0;$i<$len;$i++){
                $length = substr($this->getResponse(), 1);
                $value = $this->getResponse();
                $list[] = $this->unpack_value($value,$encode);
            }
        }
        return $list;
    }
    
    public function sdiffstore($dest,$keys){
        if (empty($dest) || empty($keys)) return false;
        if (is_array($keys)){
            $keys = implode(" ", $keys);
        }
        $cmd = "SDIFFSTORE {$dest} {$keys}";
        $response = $this->exec($cmd);
        if (!$this->getError($response)) {
            return false;
        }
        $len = substr($response, 1);
        if ($len>0) {
            return $len;
        }
        return false;
    }
    
    public function spop($key,$encode = false){
        if (empty($key)) return false;
        $cmd = "SPOP {$key}";
        $response = $this->exec($cmd);
        if (!$this->getError($response)){
            return false;
        }
        $len = substr($response,1);
        if ($len > 0){
            return $this->unpack_value($this->getResponse(),$encode);
        }
        return false;
    }
    
    public function sismember($key,$value,$encode = false){
        if (empty($key) || empty($value)) return false;
        $value = $this->pack_value($value,$encode);
        $cmd = "SISMEMBER {$key} {$value}";
        $response = $this->exec($cmd);
        if (!$this->getError($response)) {
            return false;
        }
        return substr($response,1);
    }
    
    public function smembers($key,$encode = false){
        if (empty($key)) return false;
        $cmd = "SMEMBERS {$key}";
        $response = $this->exec($cmd);
        if (!$this->getError($response)) {
            return false;
        }
        $len = substr($response,1);
        $list = array();
        if ($len>0){
            for ($i = 0;$i<$len;$i++){
                $length = substr($this->getResponse(), 1);
                $value = $this->getResponse();
                $list[] = $this->unpack_value($value,$encode);
            }
        }
        return $list;
    }
    
    public function smove($source,$dest,$member,$encode = false){
        if (empty($source) || empty($dest) || empty($member)) return false;
        $member = $this->pack_value($member,$encode);
        $cmd = "SMOVE {$source} {$dest} {$member}";
        $response = $this->exec($cmd);
        if (!$this->getError($response)) {
            return false;
        }
        return substr($response,1);
    }
    
    public function srandmember($key,$encode = false){
        if (empty($key)) return false;
        $cmd = "SRANDMEMBER {$key}";
        $response = $this->exec($cmd);
        if (!$this->getError($response)){
            return false;
        }
        $len = substr($response, 1);
        $value = '';
        if ($len>0){
            $value = $this->getResponse();
            $value = $this->unpack_value($value,$encode);
        }
        return $value;
    }
    
    public function srem($key,$member){
        if (empty($key) || empty($member)) return false;
        $cmd = "SREM {$key} {$member}";
        $response = $this->exec($cmd);
        if (!$this->getError($response)){
            return false;
        }
        return substr($response,1);
    }
    
    public function sinter($keys,$encode = false){
        if (empty($keys)) return false;
        if (is_array($keys)){
            $keys = implode(" ", $keys);
        }
        $cmd = "SINTER {$keys}";
        $response = $this->exec($cmd);
        if (!$this->getError($response)) {
            return false;
        }
        $len = substr($response,1);
        $list = array();
        if ($len>0){
            for ($i=0;$i<$len;$i++){
                $length = substr($this->getResponse(),1);
                $value = $this->getResponse();
                $list[] = $this->unpack_value($value,$encode);
            }
        }
        return $list;
    }
    
    public function sinterstore($dest,$keys,$encode = false){
        if (empty($dest) || empty($keys)) return false;
        if (is_array($keys)){
            $keys = implode(" ", $keys);
        }
        $cmd = "SINTERSTORE {$dest} {$keys}";
        $response = $this->exec($cmd);
        if (!$this->getError($response)) {
            return false;
        }
        return substr($response, 1);
    }
    
    public function sunion($keys,$encode = false){
        if (empty($keys)) return false;
        if (is_array($keys)){
            $keys = implode(" ", $keys);
        }
        $cmd = "SUNION {$keys}";
        $response = $this->exec($cmd);
        if (!$this->getError($response)) {
            return false;
        }
        $len = substr($response,1);
        $list = array();
        if ($len>0){
            for ($i=0;$i<$len;$i++){
                $length = substr($this->getResponse(), 1);
                $value = $this->getResponse();
                $list[] = $this->unpack_value($value,$encode);
            }
        }
        return $list;
    }
    
    public function sunionstore($dest,$keys,$encode = false){
        if (empty($dest) || empty($keys)) return false;
        if (is_array($keys)){
            $keys = implode(" ",$keys);
        }
        $cmd = "SUNIONSTORE {$dest} {$keys}";
        $response = $this->exec($cmd);
        if (!$this->getError($response)){
            return false;
        }
        return substr($response,1);
    }

    
    // ================================== Set end =====================================================//
    
    // ================================== ZSet start 此数据类型中key不能用:作为分隔符 =====================================================//
    public function zadd($key,$score,$member){
        if (empty($key) || empty($member)) return false;
        $cmd = "ZADD {$key} {$score} {$member}";
        $response = $this->exec($cmd);
        if (!$this->getError($response)){
            return false;
        }
        return substr($response,1);
    }
    
    public function zcard($key){
        if (empty($key)) return false;
        $cmd = "ZCARD {$key}";
        $response = $this->exec($cmd);
        if (!$this->getError($response)) {
            return false;
        }
        return substr($response,1);
    }
    
    public function zcount($key,$min,$max){
        if (empty($key) || empty($min) || empty($max)) return false;
        $cmd = "ZADD {$key} {$min} {$max}";
        $response = $this->exec($cmd);
        if (!$this->getError($response)) {
            return false;
        }
        return substr($response,1);
    }
    
    public function zincrby($key,$member,$offset = 1){
        if (empty($key) || empty($member)) return false;
        $cmd = "ZINCRBY {$key} {$offset} {$member}";
        $response = $this->exec($cmd);
        if (!$this->getError($response)){
            return false;
        }
        return substr($response,1);
    }
    
    public function zinterstore($dest,$numkey,$keys){
        if (empty($dest) || empty($numkey) || empty($keys)) return false;
        if (is_array($keys)){
            $keys = implode(" ", $keys);
        }
        $cmd = "ZINTERSTORE {$dest} {$numkey} {$keys}";
        $response = $this->exec($cmd);
        if (!$this->getError($response)) {
            return false;
        }
        return substr($response,1);
    }
    
    public function zrange($key,$start = 0,$stop = -1,$withscores = true){
        if (empty($key)) return false;
        $score = '';
        if ($withscores == true){
            $score = "WITHSCORES";
        }
        $cmd = "ZRANGE {$key} {$start} {$stop} {$score}";
        $response = $this->exec($cmd);
        if (!$this->getError($response)){
            return false;
        }
        $len = substr($response, 1);
        $list = array();
        if ($len>0){
            if ($withscores == true){
                $max = $len/2;
            }else{
                $max = $len;
            }
            for ($i = 0;$i<$max;$i++){
                $length = substr($this->getResponse(), 1);
                $value = $this->getResponse();
                if ($withscores == true){
                    $length = substr($this->getResponse(), 1);
                    $key = $this->getResponse();
                    $list[$key] = $value;
                }else{
                    $list[] = $value;
                }
            }
        }
        return $list;
    }
    
    public function zrangebyscore($key,$min = 0,$max = -1,$withscores = true,$limit = ''){
        if (empty($key)) return false;
        $score = '';
        if ($withscores == true){
            $score = "WITHSCORES";
        }
        $limits = '';
        if (!empty($limit)){
            $limits = "LIMIT ".$limit;
        }
        $cmd = "ZRANGEBYSCORE {$key} {$min} {$max} {$score} {$limits}";
        $cmd = trim($cmd);
        $response = $this->exec($cmd);
        if (!$this->getError($response)) {
            return false;
        }
        $len = substr($response, 1);
        $list = array();
        if ($len>0) {
            if ($withscores == true){
                $max = $len/2;
            }else{
                $max = $len;
            }
            for ($i=0;$i<$max;$i++){
                $length = substr($this->getResponse(),1);
                $value = $this->getResponse();
                if ($withscores == true){
                    $length = substr($this->getResponse(), 1);
                    $key = $this->getResponse();
                    $list[$key] = $value;
                }else{
                    $list[] = $value;
                }
            }
        }
        return $list;
    }
    
    public function zrank($key,$member){
        if (empty($key) || empty($member)) return false;
        $cmd = "ZRANK {$key} {$member}";
        $response = $this->exec($cmd);
        if (!$this->getError($response)){
            return false;
        }
        return substr($response,1);
    }
    
    public function zrem($key,$member){
        if (empty($key) || empty($member)) return false;
        $cmd = "ZREM {$key} {$member}";
        $response = $this->exec($cmd);
        if (!$this->getError($response)) {
            return false;
        }
        return substr($response,1);
    }
    
    public function zremrangebyrank($key,$start = 0,$stop = -1){
        if (empty($key)) return false;
        $cmd = "ZREMRANGEBYRANK {$key} {$start} {$stop}";
        $response = $this->exec($cmd);
        if (!$this->getError($response)){
            return false;
        }
        return substr($response,1);
    }
    
    public function zremrangebyscore($key,$min,$max){
        if (empty($key)) return false;
        $cmd = "ZREMRANGEBYSCORE {$key} {$min} {$max}";
        $response = $this->exec($cmd);
        if (!$this->getError($response)) {
            return false;
        }
        return substr($response,1);
    }
    
    public function zrevrange($key,$start = 0,$stop = -1,$withscores = true){
        if (empty($key)) return false;
        $score = '';
        if ($withscores == true){
            $score = "WITHSCORES";
        }
        $cmd = "ZREVRANGE {$key} {$start} {$stop} {$score}";
        $response = $this->exec($cmd);
        if (!$this->getError($response)) {
            return false;
        }
        $len = substr($response, 1);
        $list = array();
        if ($len>0){
            if ($withscores == true){
                $max = $len/2;
            }else{
                $max = $len;
            }
            for($i=0;$i<$max;$i++){
                $length = substr($this->getResponse(), 1);
                $value = $this->getResponse();
            if ($withscores == true){
                    $length = substr($this->getResponse(), 1);
                    $key = $this->getResponse();
                    $list[$key] = $value;
                }else{
                    $list[] = $value;
                }
            }
        }
        return $list;
    }
    
    public function zrevrangebyscore($key,$max,$min,$limit = '',$withscores = true){
        if (empty($key)) return false;
        $score = '';
        if ($withscores == true){
            $score = "WITHSCORES";
        }
        $limits = "";
        if (!empty($limit)){
            $limits = "LIMIT ".$limit;
        }
        $cmd = "ZREVRANGEBYSCORE {$key} {$max} {$min} {$score} {$limits}";
        $cmd = trim($cmd);
        $response = $this->exec($cmd);
        if (!$this->getError($response)) {
            return false;
        }
        $len = substr($response, 1);
        $list = array();
        if ($len>0){
            if ($withscores == true){
                $max = $len/2;
            }else{
                $max = $len;
            }
            for($i=0;$i<$max;$i++){
                $length = substr($this->getResponse(), 1);
                $value = $this->getResponse();
                if ($withscores == true){
                    $length = substr($this->getResponse(), 1);
                    $key = $this->getResponse();
                    $list[$key] = $value;
                }else{
                    $list[] = $value;
                }
            }
        }
        return $list;
    }
    
    public function zrevrank($key,$member){
        if (empty($key) || empty($member)) return false;
        $cmd = "ZREVRANK {$key} {$member}";
        $response = $this->exec($cmd);
        if (!$this->getError($response)) {
            return false;
        }
        return substr($response, 1);
    }
    
    public function zscore($key,$member){
        if (empty($key) || empty($member)) return false;
        $cmd = "ZSCORE {$key} {$member}";
        $response = $this->exec($cmd);
        if (!$this->getError($response)) {
            return false;
        }
        $len = substr($response, 1);
        $value = 0;
        if ($len>0) {
            $value = $this->getResponse();
        }
        return $value;
    }
    
    public function zunionstore($dest,$numkey,$keys){
        if (empty($dest) || empty($keys)) return false;
        if (is_array($keys)){
            $keys = implode(" ", $keys);
        }
        $cmd = "ZUNIONSTORE {$dest} {$numkey} {$keys}";
        $response = $this->exec($cmd);
        if (!$this->getError($response)) {
            return false;
        }
        return substr($response, 1);
    }
    
    // ================================== ZSet end =====================================================//
    
    
    // ================================== Server start =====================================================//
    
    public function bgrewriteaof(){
        $cmd = "BGREWRITEAOF";
        $response = $this->exec($cmd);
        if (!$this->getError($response)){
            return false;
        }
        return true;
    }
    
    public function bgsave(){
        $cmd = "BGSAVE";
        $response = $this->exec($cmd);
        if (!$this->getError($response)){
            return false;
        }
        return true;
    }
    
    public function getconfig($param = "*"){
        $cmd = "CONFIG GET {$param}";
        $response = $this->exec($cmd);
        if (!$this->getError($response)){
            return false;
        }
        $len = substr($response,1);
        $list = array();
        if ($len>0){
            for ($i=0;$i<$len;$i++){
                $length = substr($this->getResponse(),1);
                $value = $this->getResponse();
                $list[] = $value;
            }
        }
        return $list;
    }
    
    public function setconfig($key,$value){
        if (empty($key) || empty($value)) return false;
        $cmd = "CONFIG SET {$key} {$value}";
        $response = $this->exec($cmd);
        if (!$this->getError($response)) {
            return false;
        }
        return true;
    }
    
    public function dbsize(){
        $cmd = "DBSIZE";
        $response = $this->exec($cmd);
        if (!$this->getError($response)){
            return false;
        }
        return substr($response,1);
    }
    
    public function info(){
        $cmd = "INFO";
        $response = $this->exec($cmd);
        if (!$this->getError($response)){
            return false;
        }
        $len = substr($response,1);
        $list = array();
        if ($len>0){
            for ($i = 0; $i < $len; $i++) {
                $value = substr($this->getResponse(), 1);
                if (!empty($value)){
                    $list[] = $value;
                    continue;
                }
                break;
            }
        }
        return $list;
    }
    
    public function lastsave(){
        $cmd = "LASTSAVE";
        $response = $this->exec($cmd);
        if (!$this->getError($response)) {
            return false;
        }
        return substr($response, 1);
    }
    
    public function save(){
        $cmd = "SAVE";
        $response = $this->exec($cmd);
        if (!$this->getError($response)) {
            return false;
        }
        return true;
    }
    
    // ================================== Server end =====================================================//
    
    // ================================== Connection start =====================================================//
    
    public function ech($message){
        if (empty($message)) return false;
        $cmd = "ECHO {$message}";
        $response = $this->exec($cmd);
        if (!$this->getError($response)) {
            return false;
        }
        $len = substr($response,1);
        if ($len>0){
            return $this->getResponse();
        }
        return false;
    }
    
    public function ping(){
        $cmd = "PING";
        $response = $this->exec($cmd);
        if ($response == '+PONG'){
            return true;
        }
        return false;
    }
    
    public function select($index){
        $cmd = "SELECT {$index}";
        $response = $this->exec($cmd);
        if (!$this->getError($response)) {
            return false;
        }
        if ($response == '+OK'){
            return true;
        }
        return false;
    }
    
    // ================================== Connection end =====================================================//
    
    /**
     * 连接Redis服务器
     * Enter description here ...
     */
    private function getConnection(){
        if (!$this->handle){
            $errno = $errstr = '';
            if (!$sock = fsockopen($this->host,$this->port,$errno,$errstr,10)){
                return false;
            }
            $this->handle = $sock;
        }
        return $this->handle;
    }
    
    private function pack_value($value,$encode = true){
        if ($encode == false) {
            if($value === ''){
                $value = ' ';
            }
            return preg_replace("/ /","0empty0",$value);
        }
        
        if (is_numeric($value)){
            return trim($value);
        }else{
            $value = serialize($value);
            return preg_replace("/ /","0empty0",$value);
        }
    }
    
    private function unpack_value($value,$encode = true){
        
        if ($encode == false) return preg_replace("/0empty0/", " ", $value);
        
        if (is_numeric($value)){
            return trim($value);
        }
        $value = preg_replace("/0empty0/", " ", $value);
        return unserialize($value);
    }
    
    private function exec($cmd){
        $this->getConnection();
        if (!$this->handle) {
            return false;
        }
        $cmd .= "\r\n";
        $fwrite = 0;
        for ($written = 0;$written < strlen($cmd);$written += $fwrite){
            if (!$fwrite = fwrite($this->handle, substr($cmd, $fwrite))){
                return false;
            }
        }
        return $this->getResponse();
    }
    
    private function getResponse(){
        if (!$this->handle) return false;
        return trim(fgets($this->handle),"\r\n");
    }
    
    private function getError($response){
        if (strpos($response, '-ERR') === 0){
            //return substr($response, 5);
            return false;
        }
        return true;
    }
    
    public function __destruct(){
        if ($this->handle){
            fclose($this->handle);
        }
    }
    
}


?>
