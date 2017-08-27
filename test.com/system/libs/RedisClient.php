<?php
/**
 * This is a Redis exntend class
 */

class RedisClient
{
	public static $instance = NULL;
	public static $linkHandle = array();
	//construct:connect redis
	public function __construct($configs)
	{
		$this->initRedis($configs);
	}

	/**
	 * Get a instance of MyRedisClient
	 *
	 * @param string $key
	 * @return object
	 */
    static function getInstance($configs)
    {
		if (!self::$instance) {
			self::$instance = new self($configs);
		}
		return self::$instance;
    }
    
    /**
     * 初始化Redis
     */
    private function initRedis($conf){
    	foreach ($conf['master'] as $k=>$v){  
    		$obj = new Redis();
    		if($obj->pconnect($v['host'],$v['port'])){
    			$obj->auth($v['auth']);
    			self::$linkHandle['master'][] = $obj;
    			
    		}
    	}
    	
    	foreach ($conf['slave'] as $k=>$v){
    		$obj = new Redis();
    		if($obj->pconnect($v['host'],$v['port'])){
    			$obj->auth($v['auth']);
    			self::$linkHandle['slave'][] = $obj;
    		}
    	}
    }
    
    /**
     * 获得redis Resources
     * 
     * @param $key	 redis存的key/或随机值
     * @param string $tag	master/slave
     */
    public function getRedis($key=null,$tag='master'){
    	empty($key)?$key = uniqid():'';
    	$arr_index = $this->getHostByHash($key,count(self::$linkHandle[$tag])); //获得相应主机的数组下标
    	return self::$linkHandle[$tag][$arr_index];
    }
    
    /**
     * 随机取出主机
     * @param $key		$变量key值
     * @param $n		主机数
     * @return string
     */
	private function getHostByHash($key,$n){
		if($n<2) return 0;
		$u = strtolower($key);
		$id = sprintf("%u", crc32($key));
		
		$m = base_convert( intval(fmod($id, $n)), 10, $n);
		return $m{0};
	}
	
	/**
	 * 关闭连接
	 * pconnect 连接是无法关闭的
	 *  
	 * @param int $flag 关闭选择 0:关闭 Master 1:关闭 Slave 2:关闭所有
	 * @return boolean
	 */
	public function close($flag=2){
		switch($flag){
			// 关闭 Master
			case 0:
				foreach (self::$linkHandle['master'] as $var){
					$var->close();
				}
				break;
				// 关闭 Slave
			case 1:
				foreach (self::$linkHandle['slave'] as $var){
					$var->close();
				}
				break;
				// 关闭所有
			case 1:
				$this->close(0);
				$this->close(1);
				break;
		}
		return true;
	}
	
	//-------------------------------------------------------------------------------
	
	public function hGetAll($key){
		return $this->getRedis($key,'slave')->hGetAll($key);
	}
	
	/**
	 * redis 字符串（String） 类型
	 * 将key和value对应。如果key已经存在了，它会被覆盖，而不管它是什么类型。
	 * @param $key
	 * @param $value
	 * @param $exp 过期时间
	 */
	public function set($key,$value,$exp=0){
		$redis = $this->getRedis($key);
		$redis->set($key,$value);
		!empty($exp) && $redis->expire($key,$exp);
	}
	/**
	 * 返回key的value。如果key不存在，返回特殊值nil。如果key的value不是string，就返回错误，因为GET只处理string类型的values。
	 * @param $key
	 */
	public function get($key){
		return $this->getRedis($key,'slave')->get($key);
	}
	/**
	 * KEYS pattern
	 * 查找所有匹配给定的模式的键
	 * @param $is_key   默认是一个非正则表达试，使用模糊查询
	 * @param $key
	 */
	public function keys($key,$is_key=true){
		if ($is_key) {
			return $this->getRedis($key,'slave')->keys("*$key*");
		}
		return $this->getRedis($key,'slave')->keys("$key");
	}
	
	/**
	 * 批量填充HASH表。不是字符串类型的VALUE，自动转换成字符串类型。使用标准的值。NULL值将被储存为一个空的字符串。
	 * 
	 * 可以批量添加更新 value,key 不存在将创建，存在则更新值
	 * 
	 * @param  $key
	 * @param  $fieldArr
	 * @return
	 * 如果命令执行成功，返回OK。
	 * 当key不是哈希表(hash)类型时，返回一个错误。
	 */
	public function hMSet($key,$fieldArr){
		return $this->getRedis($key)->hmset($key,$fieldArr);
	}
	/**
	 * 向已存在于redis里的Hash 添加多个新的字段及值
	 * 
	 * @param  $key			redis 已存在的key
	 * @param  $field_arr	kv形数组
	 */
	public function hAddFieldArr($key,$field_arr){
		foreach ($field_arr as $k=>$v){
			$this->hAddFieldOne($key, $k, $v);
		}
	}
	
	/**
	 * 向已存在于redis里的Hash 添加一个新的字段及值
	 * @param  $key
	 * @param  $field_name
	 * @param  $field_value
	 * @return bool
	 */
	public function hAddFieldOne($key,$field_name,$field_value){
		return $this->getRedis($key)->hsetnx($key,$field_name,$field_value);
	}
	
	/**
	 * 向Hash里添加多个新的字段或修改一个已存在字段的值
	 * @param $key
	 * @param $field_arr
	 */
	public function hAddOrUpValueArr($key,$field_arr){
		foreach ($field_arr as $k=>$v){
			$this->hAddOrUpValueOne($key, $k, $v);
		}
	}
	/**
	 * 向Hash里添加多个新的字段或修改一个已存在字段的值
	 * @param  $key
	 * @param  $field_name
	 * @param  $field_value
	 * @return boolean 
	 * 1 if value didn't exist and was added successfully, 
	 * 0 if the value was already present and was replaced, FALSE if there was an error.
	 */
	public function hAddOrUpValueOne($key,$field_name,$field_value){
		return $this->getRedis($key)->hset($key,$field_name,$field_value);
	}
	
	/**
	 *  删除哈希表key中的多个指定域，不存在的域将被忽略。
	 * @param $key
	 * @param $field_arr
	 */
	public function hDel($key,$field_arr){
		foreach ($field_arr as $var){
			$this->hDelOne($key,$var);
		}
	}
	
	/**
	 * 删除哈希表key中的一个指定域，不存在的域将被忽略。
	 * 
	 * @param $key
	 * @param $field
	 * @return BOOL TRUE in case of success, FALSE in case of failure
	 */
	public function hDelOne($key,$field){
		return $this->getRedis($key)->hdel($key,$field);
	}
	
	/**
	 * 重命名key
	 * 
	 * @param $oldkey
	 * @param $newkey
	 */
	public function renameKey($oldkey,$newkey){
		return $this->getRedis($oldkey)->rename($oldkey,$newkey);
	}
	
	/**
	 * 删除一个或多个key
	 * @param $keys
	 */
	public function delKey($keys){
		if(is_array($keys)){
			foreach ($keys as $key){
				$this->getRedis($key)->del($key);
			}
		}else {
			$this->getRedis($key)->del($key);
		}
	}
	/**
	 * 添加一个字符串值到LIST容器的顶部（左侧），如果KEY不存在，曾创建一个LIST容器，如果KEY存在并且不是一个LIST容器，那么返回FLASE。
	 * 
	 * @param unknown $key
	 * @param unknown $val
	 */
	public function lPush($key,$val){
		$this->getRedis($key)->lPush($key,$val);
	}
	
	/**
	 * 返回LIST顶部（左侧）的VALUE，并且从LIST中把该VALUE弹出。
	 * @param unknown $key
	 */
	public function lPop($key){
		$this->getRedis($key)->lPop($key);
	}


	/**
	 * 批量的添加多个key 到redis
	 * @param $fieldArr
	 */
	public function mSetnx($fieldArr){

		$this->getRedis()->mSetnx($fieldArr);
	}
}
?>
