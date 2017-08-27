<?php
/**
 * Created by PhpStorm.
 * User: linyunming
 * Date: 2016/5/16
 * Time: 18:05
 */

/*
$saas_public_dir = APP_ROOT_PATH."install/Runtime".$GLOBALS['saas_public']."/";
if(!file_exists($saas_public_dir))@mkdir($saas_public_dir,0777);

$saas_public_dir = APP_ROOT_PATH."public".$GLOBALS['saas_public']."/";
if(!file_exists($saas_public_dir))@mkdir($saas_public_dir,0777);

$saas_public_dir = APP_ROOT_PATH."public".$GLOBALS['saas_public']."/attachment/";
if(!file_exists($saas_public_dir))@mkdir($saas_public_dir,0777);


$saas_public_dir = APP_ROOT_PATH."public".$GLOBALS['saas_public']."/db_backup/";
if(!file_exists($saas_public_dir))@mkdir($saas_public_dir,0777);

$saas_public_dir = APP_ROOT_PATH."public".$GLOBALS['saas_public']."/runtime/";
if(!file_exists($saas_public_dir))@mkdir($saas_public_dir,0777);

$saas_public_dir = APP_ROOT_PATH."public".$GLOBALS['saas_public']."/runtime/admin/";
if(!file_exists($saas_public_dir))@mkdir($saas_public_dir,0777);

$saas_public_dir = APP_ROOT_PATH."public".$GLOBALS['saas_public']."/session/";
if(!file_exists($saas_public_dir))@mkdir($saas_public_dir,0777);

$saas_public_dir = APP_ROOT_PATH."public".$GLOBALS['saas_public']."/uc_data/";
if(!file_exists($saas_public_dir))@mkdir($saas_public_dir,0777);

$saas_public_dir = APP_ROOT_PATH."public".$GLOBALS['saas_public']."/timezone_config.php";
if(!file_exists($saas_public_dir))
{
    @file_put_contents($saas_public_dir, @file_get_contents(APP_ROOT_PATH."public".$GLOBALS['saas_public']."/timezone_config.php"));
}

$saas_public_dir = APP_ROOT_PATH."public".$GLOBALS['saas_public']."/sys_config.php";
if(!file_exists($saas_public_dir))
{
    @file_put_contents($saas_public_dir, @file_get_contents(APP_ROOT_PATH."public".$GLOBALS['saas_public']."/sys_config.php"));
}

$saas_public_dir = APP_ROOT_PATH."public".$GLOBALS['saas_public']."/version.php";
if(!file_exists($saas_public_dir))
{
    @file_put_contents($saas_public_dir, @file_get_contents(APP_ROOT_PATH.'system/version.php'));
}
*/
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



//end 引入时区配置及定义时间函数


define("NOW_TIME",get_gmtime());   //当前UTC时间戳
define("CLIENT_IP",get_client_ip());  //当前客户端IP
define("SITE_DOMAIN",get_domain());   //站点域名
define("TIME_ZONE",app_conf('DEFAULT_TIMEZONE'));  //时区

if(file_exists(APP_ROOT_PATH."public/install.lock")){
    update_sys_config();
}
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


if(IS_DEBUG)
    error_reporting(E_ALL ^ E_NOTICE ^ E_WARNING ^ E_DEPRECATED);
else
    error_reporting(0);




//end 引入数据库的系统配置及定义配置函数
require APP_ROOT_PATH.'system/db/db.php';
require APP_ROOT_PATH.'system/utils/es_cookie.php';
require APP_ROOT_PATH.'system/utils/es_session.php';
//es_session::start();

if(true)
{
    //重写模式
    $current_url = APP_ROOT;
    if(isset($_REQUEST['rewrite_param']))
        $rewrite_param = $_REQUEST['rewrite_param'];
    else
        $rewrite_param = "";

    $rewrite_param = str_replace(array( "\"","'" ), array("",""), $rewrite_param);
    $rewrite_param = explode("/",$rewrite_param);
    $rewrite_param_array = array();
    foreach($rewrite_param as $k=>$param_item)
    {
        if($param_item!='')
            $rewrite_param_array[] = $param_item;
    }
    foreach ($rewrite_param_array as $k=>$v)
    {
        if(substr($v,0,1)=='-')
        {
            //扩展参数
            $v = substr($v,1);
            $ext_param = explode("-",$v);
            foreach($ext_param as $kk=>$vv)
            {
                if($kk%2==0)
                {
                    if(preg_match("/(\w+)\[(\w+)\]/",$vv,$matches))
                    {
                        $_GET[$matches[1]][$matches[2]] = $ext_param[$kk+1];
                    }
                    else
                        $_GET[$ext_param[$kk]] = $ext_param[$kk+1];

                    if($ext_param[$kk]!="p")
                    {
                        $current_url.=$ext_param[$kk];
                        $current_url.="-".$ext_param[$kk+1]."-";
                    }
                }
            }
        }
        elseif($k==0)
        {
            //解析ctl与act
            $ctl_act = explode("-",$v);
            if($ctl_act[0]!='id')
            {

                $_GET[CTL] = !empty($ctl_act[0])?$ctl_act[0]:"";
                $_GET[ACT] = !empty($ctl_act[1])?$ctl_act[1]:"";

                $current_url.="/".$ctl_act[0];
                if(!empty($ctl_act[1]))
                    $current_url.="-".$ctl_act[1]."/";
                else
                    $current_url.="/";
            }
            else
            {
                //扩展参数
                $ext_param = explode("-",$v);
                foreach($ext_param as $kk=>$vv)
                {
                    if($kk%2==0)
                    {
                        if(preg_match("/(\w+)\[(\w+)\]/",$vv,$matches))
                        {
                            $_GET[$matches[1]][$matches[2]] = $ext_param[$kk+1];
                        }
                        else
                            $_GET[$ext_param[$kk]] = $ext_param[$kk+1];

                        if($ext_param[$kk]!="p")
                        {
                            if($kk==0)$current_url.="/";
                            $current_url.=$ext_param[$kk];
                            $current_url.="-".$ext_param[$kk+1]."-";
                        }
                    }
                }
            }

        }elseif($k==1)
        {
            //扩展参数
            $ext_param = explode("-",$v);
            foreach($ext_param as $kk=>$vv)
            {
                if($kk%2==0)
                {
                    if(preg_match("/(\w+)\[(\w+)\]/",$vv,$matches))
                    {
                        $_GET[$matches[1]][$matches[2]] = $ext_param[$kk+1];
                    }
                    else
                        $_GET[$ext_param[$kk]] = $ext_param[$kk+1];

                    if($ext_param[$kk]!="p")
                    {
                        $current_url.=$ext_param[$kk];
                        $current_url.="-".$ext_param[$kk+1]."-";
                    }
                }
            }
        }
    }
    $current_url = substr($current_url,-1)=="-"?substr($current_url,0,-1):$current_url;
}
unset($_REQUEST['rewrite_param']);
unset($_GET['rewrite_param']);






//定义缓存
require APP_ROOT_PATH.'system/cache/Cache.php';
$cache = CacheService::getInstance();
//$cache->celar_con();
require_once APP_ROOT_PATH."system/cache/CacheFileService.php";
$fcache = new CacheFileService();  //专用于保存静态数据的缓存实例
$fcache->set_dir(APP_ROOT_PATH."public/runtime/data/");
//end 定义缓存

//定义DB

define('DB_PREFIX', app_conf('DB_PREFIX'));
if(!file_exists(APP_ROOT_PATH.'public/runtime/app/db_caches/'))
    mkdir(APP_ROOT_PATH.'public/runtime/app/db_caches/',0777);
$pconnect = false;
$db = new mysql_db(app_conf('DB_HOST').":".app_conf('DB_PORT'), app_conf('DB_USER'),app_conf('DB_PWD'),app_conf('DB_NAME'),'utf8',$pconnect);
//end 定义DB


//定义模板引擎
require  APP_ROOT_PATH.'system/template/template.php';
if(!file_exists(APP_ROOT_PATH.'public/runtime/app/tpl_caches/'))
    mkdir(APP_ROOT_PATH.'public/runtime/app/tpl_caches/',0777);
if(!file_exists(APP_ROOT_PATH.'public/runtime/app/tpl_compiled/'))
    mkdir(APP_ROOT_PATH.'public/runtime/app/tpl_compiled/',0777);
$tmpl = new AppTemplate;

//end 定义模板引擎
$_REQUEST = array_merge($_GET,$_POST);
filter_request($_REQUEST);
if(file_exists(APP_ROOT_PATH.'system/wechat/platform_wechat.class.php')){
    require APP_ROOT_PATH.'system/wechat/platform_wechat.class.php';
}
require APP_ROOT_PATH.'system/utils/message_send.php';
$msg = new message_send();

//关于安装的检测
if(!file_exists(APP_ROOT_PATH."public/install.lock"))
{
    app_redirect(APP_ROOT."/install/index.php");
}


if(IS_DEBUG){
    ini_set("display_errors", 1);
    error_reporting(E_ALL ^ E_NOTICE ^ E_WARNING ^ E_DEPRECATED);
    $GLOBALS['msg']->set_debug(true);
}
else
    error_reporting(0);

//输出后台URL文件名称
define('URL_NAME',app_conf("URL_NAME"));
$GLOBALS['tmpl']->assign("URL_NAME",URL_NAME);

define('DEFAULT_MODULE_NAME','index');

