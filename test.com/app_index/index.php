<?php
define("FANWE_REQUIRE",true);

require_once '../system/mapi_init.php';


fanwe_require(APP_ROOT_PATH.'mapi/lib/redis/BaseRedisService.php');
require  APP_ROOT_PATH.'system/template/template.php';
$tmpl = new AppTemplate;

$GLOBALS['tmpl']->cache_dir      = APP_ROOT_PATH . 'public/runtime/app_index/tpl_caches';
$GLOBALS['tmpl']->compile_dir    = APP_ROOT_PATH . 'public/runtime/app_index/tpl_compiled';
$GLOBALS['tmpl']->template_dir   = APP_ROOT_PATH . 'app_index/theme/view';
$GLOBALS['tmpl']->assign("TMPL_REAL",APP_ROOT_PATH."app_index/theme/view");
$tmpl_path = get_domain().APP_ROOT."/theme";
$GLOBALS['tmpl']->assign("TMPL",$tmpl_path);

$jstmpl_path = get_domain().PAP_ROOT."/app_index/";
$GLOBALS['tmpl']->assign("JSTMPL",$jstmpl_path);

fanwe_require(APP_ROOT_PATH.'app_index/lib/core/common.php');
fanwe_require(APP_ROOT_PATH.'mapi/lib/core/common.php');

filter_injection($_REQUEST);

$_REQUEST['ctl'] = filter_ma_request($_REQUEST['ctl']);
$_REQUEST['act'] = filter_ma_request($_REQUEST['act']);

$search = array("../","\n","\r","\t","\r\n","'","<",">","\"","%","\\",".","/");
$itype = str_replace($search,"",$_REQUEST['itype']);

$class = strtolower(strim($_REQUEST['ctl']))?strtolower(strim($_REQUEST['ctl'])):"index";
$class_name = $class;
$lib = $itype?$itype:'app_index';

if($lib=='lib'){
    fanwe_require(APP_ROOT_PATH."mapi/lib/base.action.php");
    @fanwe_require(APP_ROOT_PATH."mapi/lib/".$class.".action.php");
    $class=$class.'Module';
}else{
    fanwe_require(APP_ROOT_PATH."mapi/lib/base.action.php");
    fanwe_require(APP_ROOT_PATH."mapi/".$lib."/base.action.php");
    fanwe_require(APP_ROOT_PATH."mapi/".$lib."/".$class.".action.php");
    $class=$class.'CModule';
}

$act = strtolower(strim($_REQUEST['act']))?strtolower(strim($_REQUEST['act'])):"index";
$GLOBALS['tmpl']->assign("ctl",$class_name);
$GLOBALS['tmpl']->assign("act",$act);

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