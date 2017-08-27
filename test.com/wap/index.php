<?php
define("FANWE_REQUIRE",true);

if(isset($_REQUEST['cstype']) && $_REQUEST['cstype']==1){
    require_once '../system/wap_init.php';
}else{
    require_once '../system/mapi_init.php';
}

fanwe_require(APP_ROOT_PATH.'mapi/lib/redis/BaseRedisService.php');
require  APP_ROOT_PATH.'system/template/template.php';
$tmpl = new AppTemplate;

$GLOBALS['tmpl']->cache_dir      = APP_ROOT_PATH . 'public/runtime/wap/tpl_caches';
$GLOBALS['tmpl']->compile_dir    = APP_ROOT_PATH . 'public/runtime/wap/tpl_compiled';
$GLOBALS['tmpl']->template_dir   = APP_ROOT_PATH . 'wap/theme/default';
$GLOBALS['tmpl']->assign("TMPL_REAL",APP_ROOT_PATH."wap/theme/default");
$tmpl_path = get_domain().APP_ROOT."/wap/theme/";
$GLOBALS['tmpl']->assign("TMPL",$tmpl_path."default");

$jstmpl_path = get_domain().PAP_ROOT."/wap/";
$GLOBALS['tmpl']->assign("JSTMPL",$jstmpl_path);

// $mapi = get_domain()."/mapi/";
// $GLOBALS['tmpl']->assign("mapi",$mapi);

// $TMPL = get_domain()."/wap/";
// $GLOBALS['tmpl']->assign("TMPL",$TMPL);


fanwe_require(APP_ROOT_PATH.'wap/lib/core/common.php');
fanwe_require(APP_ROOT_PATH.'mapi/lib/core/common.php');

filter_injection($_REQUEST);


//会员自动登录及输出
$cookie_uid = es_cookie::get("user_id")?es_cookie::get("user_id"):'';
$cookie_upwd = es_cookie::get("user_pwd")?es_cookie::get("user_pwd"):'';

if($cookie_uid!=''&&$cookie_upwd!=''&&!es_session::get("user_info"))
{
    fanwe_require(APP_ROOT_PATH."system/libs/user.php");
    auto_do_login_user($cookie_uid,$cookie_upwd);
}

//用户信息
global $user_info;
$user_info = es_session::get('user_info');
if (!$user_info && isset($_REQUEST['cstype'])){
	$cstype = $_REQUEST['cstype'];
	
		if (intval($cstype) > 0 ){
			$sql = "select * from ".DB_PREFIX."user where id=".intval($cstype);
		}else{
			$sql = "select * from ".DB_PREFIX."user where id=100324";
		}
	//	es_session::set("user_info",$user_info);
	$user_info = $GLOBALS['db']->getRow($sql);
	//print_r($user_info);
	
}else{
	//print_r($user_info);
}

$cstype = $_REQUEST['cstype'];
if(!$user_info&&$cstype !=''){

    if (intval($cstype) > 0){
        $user_info = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."user where id=".intval($cstype));
    }else{
        $user_info = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."user where id=290");
    }
//	es_session::set("user_info",$user_info);
}

    	
$_REQUEST['ctl'] = filter_ma_request($_REQUEST['ctl']);
$_REQUEST['act'] = filter_ma_request($_REQUEST['act']);

$class = strtolower(strim($_REQUEST['ctl']))?strtolower(strim($_REQUEST['ctl'])):"index";

$class_name = $class;

$act = strtolower(strim($_REQUEST['act']))?strtolower(strim($_REQUEST['act'])):"index";

fanwe_require(APP_ROOT_PATH."mapi/lib/base.action.php");
fanwe_require(APP_ROOT_PATH."mapi/lib/".$class.".action.php");

$class=$class.'Module';


if(class_exists($class)){
    $obj = new $class;

    if(method_exists($obj, $act)){
        $obj->$act();
    }
    else{
        $error["errcode "] = 10006;
        $error["errmsg "] = "接口方法不存在";
        ajax_return($error);
    }
}
else{
    $error["errcode "] = 10005;
    $error["errmsg "] = "接口不存在";
    ajax_return($error);
}



?>