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
	define('APP_ROOT_PATH', str_replace('system/wap_init.php', '', str_replace('\\', '/', __FILE__)));

require APP_ROOT_PATH.'system/define.php';
if(IS_DEBUG){
    ini_set("display_errors", 1);
    error_reporting(E_ALL ^ E_NOTICE ^ E_WARNING ^ E_DEPRECATED);
}
else
    error_reporting(0);

require APP_ROOT_PATH.'public/directory_init.php';



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

            $this->mem = new Rediscache($GLOBALS['distribution_cfg']['SESSION_CLIENT'], $GLOBALS['distribution_cfg']['SESSION_PORT'],$GLOBALS['distribution_cfg']['SESSION_PASSWORD']);

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


//es_session_start();


if (PHP_VERSION >= '5.0.0')
{
    $begin_run_time = @microtime(true);
}
else
{
    $begin_run_time = @microtime();
}

@set_magic_quotes_runtime (0);
define('MAGIC_QUOTES_GPC',get_magic_quotes_gpc()?True:False);
if(!defined('IS_CGI'))
    define('IS_CGI',substr(PHP_SAPI, 0,3)=='cgi' ? 1 : 0 );
if(!defined('_PHP_FILE_')) {
    if(IS_CGI) {
        //CGI/FASTCGI模式下
        $_temp  = explode('.php',$_SERVER["PHP_SELF"]);
        define('_PHP_FILE_',  rtrim(str_replace($_SERVER["HTTP_HOST"],'',$_temp[0].'.php'),'/'));
    }else {
        define('_PHP_FILE_',  rtrim($_SERVER["SCRIPT_NAME"],'/'));
    }
}
if(!defined('APP_ROOT')) {
    // 网站URL根目录
    $_root = dirname(_PHP_FILE_);
    $_root = (($_root=='/' || $_root=='\\')?'':$_root);
    $_root = str_replace("/system","",$_root);
    $_root = str_replace("/wap","",$_root);
    $_root = str_replace("/mapi","",$_root);
    $_root = str_replace("/app","",$_root);
    define('APP_ROOT', $_root  );
}
//引入时区配置及定义时间函数
if(function_exists('date_default_timezone_set')){
    if(app_conf('DEFAULT_TIMEZONE')){
        date_default_timezone_set(app_conf('DEFAULT_TIMEZONE'));
    }else{
        date_default_timezone_set('PRC');
    }

}



//引入数据库的系统配置及定义配置函数
require APP_ROOT_PATH.'system/common.php';

define("NOW_TIME",get_gmtime());   //当前UTC时间戳
define("CLIENT_IP",get_client_ip());  //当前客户端IP
define("SITE_DOMAIN",get_domain());   //站点域名
define("TIME_ZONE",app_conf('DEFAULT_TIMEZONE'));  //时区

$sys_config = require APP_ROOT_PATH.'system/config.php';



//end 分布式
function app_conf($name)
{
    if(isset($GLOBALS['sys_config'][$name])){
        return stripslashes($GLOBALS['sys_config'][$name]);
    }else{
        return false;
    }
}

//定义$_SERVER['REQUEST_URI']兼容性
if (!isset($_SERVER['REQUEST_URI']))
{
    if (isset($_SERVER['argv']))
    {
        $uri = $_SERVER['PHP_SELF'] .'?'. $_SERVER['argv'][0];
    }
    else
    {
        $uri = $_SERVER['PHP_SELF'] .'?'. $_SERVER['QUERY_STRING'];
    }
    $_SERVER['REQUEST_URI'] = $uri;
}
filter_request($_GET);
filter_request($_POST);

//end 引入数据库的系统配置及定义配置函数
require APP_ROOT_PATH.'system/db/db.php';
require APP_ROOT_PATH.'system/utils/es_cookie.php';
require APP_ROOT_PATH.'system/utils/es_session.php';
//es_session::start();

unset($_REQUEST['rewrite_param']);
unset($_GET['rewrite_param']);






//定义缓存
//require  APP_ROOT_PATH."system/cache/CacheRediscacheService.php";
//$cache = new CacheRediscacheService();
//$cache->celar_con();

//end 定义缓存

//定义DB

define('DB_PREFIX', app_conf('DB_PREFIX'));
if(IS_LONG_LINK_MYSQL){
    $pconnect = true;
}else{
    $pconnect = false;
}


global $db;
$db = new mysql_db(app_conf('DB_HOST').":".app_conf('DB_PORT'), app_conf('DB_USER'),app_conf('DB_PWD'),app_conf('DB_NAME'),'utf8',$pconnect);
//end 定义DB



//end 定义模板引擎
$_REQUEST = array_merge($_GET,$_POST);
filter_request($_REQUEST);


