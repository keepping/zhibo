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
	define('APP_ROOT_PATH', str_replace('system/mapi_init_distribution.php', '', str_replace('\\', '/', __FILE__)));
require APP_ROOT_PATH.'system/define.php';
if(IS_DEBUG){
    ini_set("display_errors", 1);
    error_reporting(E_ALL ^ E_NOTICE ^ E_WARNING ^ E_DEPRECATED);
}
else
    error_reporting(0);

//require APP_ROOT_PATH.'public/directory_init.php';


require_once APP_ROOT_PATH."system/cache/Rediscache/Rediscache.php";
$redisdb = new Rediscache($GLOBALS['distribution_cfg']['RDB_CLIENT'], $GLOBALS['distribution_cfg']['RDB_PORT'],$GLOBALS['distribution_cfg']['RDB_PASSWORD']);



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
//es_session::start();

unset($_REQUEST['rewrite_param']);
unset($_GET['rewrite_param']);





require_once APP_ROOT_PATH."system/cache/Rediscache/Rediscache.php";
//定义缓存
require  APP_ROOT_PATH."system/cache/CacheRediscacheService.php";

$cache = new CacheRediscacheService(true);

//$cache->celar_con();

//end 定义缓存

//定义DB

define('DB_PREFIX', app_conf('DB_PREFIX'));
if(IS_LONG_LINK){
    $pconnect = true;
}else{
    $pconnect = false;
}


$db = new mysql_db(app_conf('DB_HOST').":".app_conf('DB_PORT'), app_conf('DB_USER'),app_conf('DB_PWD'),app_conf('DB_NAME'),'utf8',$pconnect);
//end 定义DB



//end 定义模板引擎
$_REQUEST = array_merge($_GET,$_POST);
filter_request($_REQUEST);


