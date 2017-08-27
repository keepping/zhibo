<?php 
// +----------------------------------------------------------------------
// | EaseTHINK 易想团购系统 mapi 插件
// +----------------------------------------------------------------------
// | Copyright (c) 2010 http://www.fanwe.com All rights reserved.
// +----------------------------------------------------------------------

//define('APP_ROOT','zhongc');
define("FANWE_REQUIRE",true);
require './lib/core/mapi_function.php';
require '../public/directory_init.php';

$_REQUEST['ctl'] = filter_ma_request_mapi($_REQUEST['ctl']);
$_REQUEST['act'] = filter_ma_request_mapi($_REQUEST['act']);
$class = strtolower(strim_mapi($_REQUEST['ctl']))?strtolower(strim_mapi($_REQUEST['ctl'])):"index";
$act = strtolower(strim_mapi($_REQUEST['act']))?strtolower(strim_mapi($_REQUEST['act'])):"index";
$fun_class = $class.'#'.$act;
$itype = filter_ma_request_mapi($_REQUEST['itype']);

if (count($GLOBALS['distribution_cfg']['REDIS_DISTRIBUTION_FUN'])>0&&in_array($fun_class,$GLOBALS['distribution_cfg']['REDIS_DISTRIBUTION_FUN'])&&($itype=='lib'||$itype=='')) {
	require '../system/mapi_init_distribution.php';
}else{
	require '../system/mapi_init.php';

}

//数据解密
$_REQUEST['i_type'] = filter_ma_request_mapi($_REQUEST['i_type']);
global $encrypt;
$encrypt['ctl'] = $class;
$encrypt['act'] = $act;
$sdk_version_name = strim($_REQUEST['sdk_version_name']);
$encrypt['sdk_version_name'] = $sdk_version_name;
$encrypt['i_type'] = intval(strim_mapi($_REQUEST['i_type']))?intval(strim_mapi($_REQUEST['i_type'])):0;
if($encrypt['i_type']){
	aes_request_decode();
}








/*
 *  若 $fun_class 有在 $GLOBALS['distribution_cfg']['REDIS_DISTRIBUTION_FUN']，则调用 简单模式，只加载基本的框架，应对高并发
 *  设计思路
 *  1、随机选择一个只读redis
 *  2、从只读只取数据
 *  3、读取失败加锁，则再从只读数据库中生成一份缓存数据放在redis中
 *  
 *  index#index 热门
 *  index#new_video 最新
 *  index#search_area 热门搜索
 *  video#viewer 房间会员列表
 */
 if (count($GLOBALS['distribution_cfg']['REDIS_DISTRIBUTION_FUN'])>0&&in_array($fun_class,$GLOBALS['distribution_cfg']['REDIS_DISTRIBUTION_FUN'])&&($itype=='lib'||$itype=='')){



	fanwe_require(APP_ROOT_PATH.'mapi/lib/core/common.php');
	fanwe_require(APP_ROOT_PATH.'mapi/lib/redis/BaseRedisService.php');
	fanwe_require("./lib/base.action.php");
	 if($_GET["notify_id"]!=''&&$_GET["sign"]!=''){
	 	 //支付宝认证
		$m_config =  load_auto_cache("m_config");//初始化手机端配置
		require_once(APP_ROOT_PATH . 'system/AlipayloginApi/aliConnectAPI.php');
		$aliConnect = new aliConnectAPI($m_config['alipay_partner'],$m_config['alipay_key']);	
		$verify_result = $aliConnect->verifyreturn();
	
		if($verify_result) {//验证成功
	
			fanwe_require(APP_ROOT_PATH.'system/utils/es_cookie.php');
			fanwe_require(APP_ROOT_PATH.'system/utils/es_session.php');
			
			$cookie_uid = es_cookie::get("user_id")?es_cookie::get("user_id"):'';
			$cookie_upwd = es_cookie::get("user_pwd")?es_cookie::get("user_pwd"):'';
		
			if($cookie_uid!=''&&$cookie_upwd!=''&&!es_session::get("user_info"))
			{
				fanwe_require(APP_ROOT_PATH."system/libs/user.php");
				auto_do_login_user($cookie_uid,$cookie_upwd);
			}
			global $user_info;
			$user_info = es_session::get('user_info');
	
			$class = "user_center";
			$act = "authent_alipay";
		}
	 }
	
	 
	@fanwe_require(APP_ROOT_PATH."mapi/lib/".$class.".action.php");

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

}else{

	$lib = $itype?$itype:'lib';


	 
	fanwe_require(APP_ROOT_PATH.'mapi/lib/core/common.php');
	//fanwe_require(APP_ROOT_PATH.'mapi/lib/core/mapi_function.php');

	fanwe_require(APP_ROOT_PATH.'mapi/lib/redis/BaseRedisService.php');
	/*
    require_once "../system/cache/Rediscache/Rediscache.php";
    $rediscache = new Rediscache($GLOBALS['distribution_cfg']['CACHE_CLIENT'], $GLOBALS['distribution_cfg']['CACHE_PORT'],$GLOBALS['distribution_cfg']['CACHE_PASSWORD']);
    define("REDIS_PREFIX", $GLOBALS['distribution_cfg']['REDIS_PREFIX']);
    */
//======
//------o2o
	if (isset($_FANWE_SAAS_ENV['APP_ID'])&&$_FANWE_SAAS_ENV['APP_ID']!=''){
		define("FANWE_APP_ID",$_FANWE_SAAS_ENV['APP_ID']);
		define("FANWE_AES_KEY",$_FANWE_SAAS_ENV['APP_SECRET']);
	}else {
		define("FANWE_APP_ID",FANWE_APP_ID_YM);
		define("FANWE_AES_KEY",FANWE_AES_KEY_YM);
	}
	
	if ($lib=='o2osdk') {
		fanwe_require(APP_ROOT_PATH."system/saas/SAASAPIServer.php");
		$appid = FANWE_APP_ID;
		$appsecret = FANWE_AES_KEY;
		$server = new SAASAPIServer($appid, $appsecret);
		$ret = $server->verifyRequestParameters();
		if ($ret['errcode'] != 0) {
			die($server->toResponse($ret));
		}

		$data=unserialize($_REQUEST['data']);
		$_REQUEST=array_merge($_REQUEST,$data);
		$lib='sdk';

	}
	
	if ($lib=='h5shop') {
		fanwe_require(APP_ROOT_PATH."system/saas/SAASAPIServer.php");
		$appid = FANWE_APP_ID;
		$appsecret = FANWE_AES_KEY;
		$server = new SAASAPIServer($appid, $appsecret);
		$data=json_decode(base64_decode($_REQUEST['_saas_params']),1);
		$ret = $server->verifyRequestParameters($data);
		if ($ret['errcode'] != 0) {
			die($server->toResponse($ret));
		}		
		$_REQUEST=array_merge($_REQUEST,$data);
		$lib='h5shop';
		
	}
	//临时处理 音乐歌词 有 select[\s])|(insert[\s])|(update[\s])|(delete[\s])|(from[\s])|(where[\s]) 被过滤问题
	if($_REQUEST['act']=='add_music' || $_REQUEST['act']=='search'){
		$lrc_content = $_REQUEST['lrc_content'];
        $audio_name = $_REQUEST['audio_name'];
        $keyword = $_REQUEST['keyword'];
		unset($_REQUEST['lrc_content']);
        unset($_REQUEST['audio_name']);
        unset($_REQUEST['keyword']);
	}
	
	filter_injection($_REQUEST);
	
	if($_REQUEST['act']=='add_music'|| $_REQUEST['act']=='search'){
		$_REQUEST['lrc_content'] = $lrc_content;
        $_REQUEST['audio_name'] = $audio_name;
        $_REQUEST['keyword'] = $keyword;
	}
	
//指定sess_id打开
/*
	global $sess_id;
	$sess_id = strim($_REQUEST['session_id']);
	if($sess_id){
		es_session::set_sessid($sess_id);
		es_session::start();

	}	
	*/
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

	if (!$user_info && isset($_REQUEST['cstype'])  && defined('IS_DEBUG') && IS_DEBUG == 1 ){
		$cstype = $_REQUEST['cstype'];

		if (intval($cstype) > 0){
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
	
	//支付宝认证
	/*$m_config =  load_auto_cache("m_config");//初始化手机端配置
	require_once(APP_ROOT_PATH . 'system/AlipayloginApi/aliConnectAPI.php');
	$aliConnect = new aliConnectAPI($m_config['alipay_partner'],$m_config['alipay_key']);	
	$verify_result = $aliConnect->verifyreturn();
	if($verify_result) {//验证成功
		$class = "user_center";
		$act = "authent_alipay";
	}else{
		$_REQUEST['ctl'] = filter_ma_request($_REQUEST['ctl']);
		$_REQUEST['act'] = filter_ma_request($_REQUEST['act']);
	
		$class = strtolower(strim($_REQUEST['ctl']))?strtolower(strim($_REQUEST['ctl'])):"index";

		$act = strtolower(strim($_REQUEST['act']))?strtolower(strim($_REQUEST['act'])):"index";
	}*/
	 if($lib=='lib'){
		 fanwe_require("./lib/base.action.php");
		 @fanwe_require("./lib/".$class.".action.php");
		 $class=$class.'Module';
	 }else{
	 	 fanwe_require("./lib/base.action.php");
	 	 //@fanwe_require("./lib/".$class.".action.php");
	 	
		 fanwe_require("./".$lib."/base.action.php");
		 if(file_exists(APP_ROOT_PATH."mapi/".$lib."/".$class.".action.php")){
			 @fanwe_require("./".$lib."/".$class.".action.php");
			 $class=$class.'CModule';
		 }else{
			 @fanwe_require("./lib/".$class.".action.php");
			 $class=$class.'Module';
		 }
		 /*
		 if ($lib=='sdk'&&($class=='shopCModule'||$class=='sdkCModule')) {
		 	log_result("==$class==");
		 	log_result($_REQUEST);
		 }
		 */
	 }
	 

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

}




?>