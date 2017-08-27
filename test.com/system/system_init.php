<?php 
// +----------------------------------------------------------------------
// | Fanwe 方维众筹商业系统
// +----------------------------------------------------------------------
// | Copyright (c) 2011 http://www.fanwe.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: 甘味人生(526130@qq.com)
// +----------------------------------------------------------------------
define("CTL",'ctl');
define("ACT",'act');
 if(!defined('APP_ROOT_PATH')) 
	define('APP_ROOT_PATH', str_replace('system/system_init.php', '', str_replace('\\', '/', __FILE__)));

require APP_ROOT_PATH.'public/directory_init.php';

require APP_ROOT_PATH.'system/define.php';
require_once APP_ROOT_PATH."system/cache/Rediscache/Rediscache.php";
$redisdb = new Rediscache($GLOBALS['distribution_cfg']['RDB_CLIENT'], $GLOBALS['distribution_cfg']['RDB_PORT'],$GLOBALS['distribution_cfg']['RDB_PASSWORD']);

//关于session
if(!class_exists("FanweSessionHandler"))
{

    class FanweSessionHandler
    {
        private $savePath;
        private $mem;  //Memcache使用
        private $db;	//数据库使用
        private $table; //数据库使用
        private $prefix;

        function open($savePath, $sessionName)
        {
            $this->savePath = APP_ROOT_PATH.$GLOBALS['distribution_cfg']['SESSION_FILE_PATH'];

            if($GLOBALS['distribution_cfg']['RDB_CLIENT']==$GLOBALS['distribution_cfg']['SESSION_CLIENT']){
                $this->mem = $GLOBALS['redisdb'];

            }else{
                $this->mem = new Rediscache($GLOBALS['distribution_cfg']['SESSION_CLIENT'], $GLOBALS['distribution_cfg']['SESSION_PORT'],$GLOBALS['distribution_cfg']['SESSION_PASSWORD']);
            }
            $this->prefix = $GLOBALS['distribution_cfg']['REDIS_PREFIX'];

            return true;
        }

        function close()
        {
            return true;
        }

        function read($id)
        {
            $sess_id = "sess_".$id;
            return $this->mem->get("$this->prefix.$this->savePath/$sess_id");
        }

        function write($id, $data)
        {

            $sess_id = "sess_".$id;
            return $this->mem->set("$this->prefix.$this->savePath/$sess_id",$data,SESSION_TIME);
        }

        function destroy($id)
        {

            $sess_id = "sess_".$id;
            return $this->mem->delete("$this->prefix.$this->savePath/$sess_id");
            return true;
        }

        function gc($maxlifetime)
        {
            return true;
        }
    }
}


//关于session的开启

function es_session_start()
{
    session_set_cookie_params(0,$GLOBALS['distribution_cfg']['COOKIE_PATH'],$GLOBALS['distribution_cfg']['DOMAIN_ROOT'],false,true);
    if($GLOBALS['distribution_cfg']['SESSION_TYPE']=="MemcacheSASL"||$GLOBALS['distribution_cfg']['SESSION_TYPE']=="Rediscache"||$GLOBALS['distribution_cfg']['SESSION_TYPE']=="Db")
    {

        $handler = new FanweSessionHandler();
        session_set_save_handler(
            array($handler, 'open'),
            array($handler, 'close'),
            array($handler, 'read'),
            array($handler, 'write'),
            array($handler, 'destroy'),
            array($handler, 'gc')
        );
    }

    @session_start();
}



es_session_start();

require APP_ROOT_PATH.'license.php';

?>