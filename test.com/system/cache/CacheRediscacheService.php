<?php
// +----------------------------------------------------------------------
// | Fanwe 方维众筹商业系统
// +----------------------------------------------------------------------
// | Copyright (c) 2011 http://www.fanwe.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: 甘味人生(526130@qq.com)
// +----------------------------------------------------------------------

class CacheRediscacheService
{

	private $mem;
	private $dir; //模拟的目录，即前缀
    var $prefix;
    var $file_prefix;
   // var $redis_distribution;
    /**
     +----------------------------------------------------------
     * 架构函数
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     */
    public function __construct($is_read=false)
    {
//        $count = intval($_REQUEST['count']);
//        $count = $count?$count: count($GLOBALS['distribution_cfg']['REDIS_DISTRIBUTION']);
//
////        $count = count($GLOBALS['distribution_cfg']['REDIS_DISTRIBUTION']);

        if($GLOBALS['distribution_cfg']['RDB_CLIENT']==$GLOBALS['distribution_cfg']['CACHE_CLIENT']){
            $this->mem = $GLOBALS['redisdb'];

        }else{
            $this->mem = new Rediscache($GLOBALS['distribution_cfg']['CACHE_CLIENT'], $GLOBALS['distribution_cfg']['CACHE_PORT'],$GLOBALS['distribution_cfg']['CACHE_PASSWORD']);
        }

        $this->prefix = $GLOBALS['distribution_cfg']['REDIS_PREFIX'];
        $this->file_prefix = $GLOBALS['distribution_cfg']['REDIS_PREFIX'].'filecache:';
//
//        $count = count($GLOBALS['distribution_cfg']['REDIS_DISTRIBUTION']);
//        if($count){
//            $rid = hash_table(es_session::id(),$count);
//            $redis_distribution = $GLOBALS['distribution_cfg']['REDIS_DISTRIBUTION'][$rid];
//            $this->redis_distribution = new Rediscache($redis_distribution['CACHE_CLIENT'], $redis_distribution['CACHE_PORT'],$redis_distribution['CACHE_PASSWORD']);
//        }else{
//            $this->redis_distribution = $this->mem;
//        }

    }

    /**
     +----------------------------------------------------------
     * 读取缓存
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param string $name 缓存变量名
     +----------------------------------------------------------
     * @return mixed
     +----------------------------------------------------------
     */
    public function get($name,$readonly=false)
    {

    	if(!$this->mem)return false;

    	//if(IS_DEBUG)return false;

    	$var_name = $this->file_prefix.($this->dir.$name);

    	global $$var_name;
    	if($$var_name)
    	{
    		return $$var_name;
    	}


        $data = $this->mem->get($var_name);
    	if($data)
    	{
            $data = unserialize($data);
    		$$var_name = $data;
    	}
    	else
    	{
    		$data = false;
    	}
        return $data;
    }


    /**
     +----------------------------------------------------------
     * 写入缓存
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param string $name 缓存变量名
     * @param mixed $value  存储数据
     +----------------------------------------------------------
     * @return boolen
     +----------------------------------------------------------
     */
	public function set($name, $value,$expire ="-1",$readonly=false)
    {

    	//if(IS_DEBUG)return false;
    	if(!$this->mem)return false;
    	if($expire=='-1') $expire = 3600*24;

        $value = serialize($value);
		$key = $this->file_prefix.($this->dir.$name);
        return $this->mem->set($key,$value,$expire);
    }

    public function  set_lock($name,$exp=10){
        if(!$this->mem)return false;
        $rand_num = rand();
        $key = $this->file_prefix.($this->dir.$name).'_lock';
        $ok = $this->mem->set_lock($key,$rand_num,$exp);
        return $ok;

    }

    public function  del_lock($name){
        if(!$this->mem)return false;

        $key = $this->file_prefix.($this->dir.$name).'_lock';
        $ok = $this->mem->delete($key);
        return $ok;

    }
    /**
     +----------------------------------------------------------
     * 删除缓存
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param string $name 缓存变量名
     +----------------------------------------------------------
     * @return boolen
     +----------------------------------------------------------
     */
    public function rm($name)
    {
    	if(!$this->mem)return false;
    	$key = $this->file_prefix.($this->dir.$name);
		return $this->mem->delete($key);
    }
    
    
    public function clear()
    {
    	if(!$this->mem)return false;
        //获取所有的数据缓存文件
        $keys  = $this->mem->keys($this->file_prefix,true);
        if(!$keys){
            return true;
        }
        $this->mem->delete($keys);

		//$this->mem->flush(0);
    }

    public function clear_by_name($name){
        if(!$this->mem)return false;
        //获取所有的数据缓存文件
        $keys  = $this->mem->keys($this->file_prefix.$name,true);
        if(!$keys){
            return true;
        }
        $this->mem->delete($keys);
    }
    public function celar_con(){
        if(!$this->mem)return false;
        $keys = array();
        //获取所有的数据缓存文件
//        $keys  = $this->mem->keys('fanwe0000001:user_contribution:',true);
//        $keys1  = $this->mem->keys('fanwe0000001:video_contribution:',true);
//        $keys2  = $this->mem->keys('fanwe0000001:video:',true);
//        $keys3  = $this->mem->keys('fanwe0000001:video_gift:',true);
//        $keys4  = $this->mem->keys('fanwe0000001:user_winning:',true);
//        $keys5  = $this->mem->keys('fanwe0000001:video_viewer:',true);
//        $keys6  = $this->mem->keys('fanwe0000001:video_viewer_level:',true);
//        $keys7  = $this->mem->keys('fanwe0000001:user_followed_by:',true);
        //$keys8  = $this->mem->keys('fanwe0000001:cate:',true);
//        $keys9  = $this->mem->keys('fanwe0000001:video_condition',true);
//        $keys = array_merge($keys8);
        //$keys = $keys8;
//        $keys[] = 'fanwe0000001:video_vote_number';
        if(!$keys){
            return true;
        }
        $this->mem->delete($keys);
    }
    public function set_dir($dir='')
    {
    	if($dir!='')
    	{
    		$this->dir =$this->prefix. ($dir);
    	}
    }

    public function close(){
        $this->mem->close();
    }



}//类定义结束

?>