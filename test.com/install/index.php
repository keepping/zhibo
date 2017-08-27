<?php 
// +----------------------------------------------------------------------
// | ThinkPHP                                                             
// +----------------------------------------------------------------------
// | Copyright (c) 2008 http://thinkphp.cn All rights reserved.      
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>                                  
// +----------------------------------------------------------------------
// $Id$

// 定义ThinkPHP框架路径
define('BASE_PATH','./');
define('THINK_PATH', './ThinkPHP');
//定义项目名称和路径
define('APP_NAME', 'install');
define('APP_PATH', '.');
define('APP_ROOT_PATH', str_replace('install/index.php', '', str_replace('\\', '/', __FILE__)));
require APP_ROOT_PATH.'system/define.php';
require APP_ROOT_PATH.'public/directory_init.php';
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

            if(isset($GLOBALS['redisdb'])){
                $this->mem = $GLOBALS['redisdb'];

            }else{
                $this->mem = new Rediscache($GLOBALS['distribution_cfg']['CACHE_CLIENT'], $GLOBALS['distribution_cfg']['CACHE_PORT'],$GLOBALS['distribution_cfg']['CACHE_PASSWORD']);
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
        if($GLOBALS['distribution_cfg']['SESSION_FILE_PATH']!=""||$GLOBALS['distribution_cfg']['SESSION_TYPE']=="MemcacheSASL"||$GLOBALS['distribution_cfg']['SESSION_TYPE']=="Rediscache"||$GLOBALS['distribution_cfg']['SESSION_TYPE']=="Db")
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
 // 加载框架入口文件 
require(THINK_PATH."/ThinkPHP.php");

//实例化一个网站应用实例
$AppWeb = new App(); 
//应用程序初始化
$AppWeb->run();

?>