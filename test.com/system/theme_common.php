<?php
// +----------------------------------------------------------------------
// | Fanwe 方维众筹商业系统
// +----------------------------------------------------------------------
// | Copyright (c) 2011 http://www.fanwe.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: 甘味人生(526130@qq.com)
// +----------------------------------------------------------------------

define("IS_DEBUG",false);
define("SHOW_DEBUG",0);
define("CTL",'c');
define("ACT",'a');
define("APP_INDEX",'APP_INDEX');
$file_dir = str_replace('\\', '/', __FILE__);

if(!defined('APP_ROOT_PATH'))
	define('APP_ROOT_PATH', substr($file_dir,0,strpos($file_dir,"system/")));


require APP_ROOT_PATH.'public/directory_init.php';
//=====正式环境删掉=========
if(IS_DEBUG){
ini_set("display_errors", 1);
error_reporting(E_ALL ^ E_NOTICE ^ E_WARNING ^ E_DEPRECATED);

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
}else{
	ini_set("display_errors", 0);
	error_reporting(E_ALL ^ E_NOTICE ^ E_WARNING ^ E_DEPRECATED);
}
	// 网站URL根目录
	$_root = dirname(_PHP_FILE_);
	$_root = (($_root=='/' || $_root=='\\')?'':$_root);
	$_root_array = explode('/',$_root);
	if(@isset($_root_array[2])){
		$_root = '/'.$_root_array[2];
	}


}

//=====正式环境删掉=========
$query = $_SERVER["REQUEST_URI"];
$_root = '';
$_root = $_root?$_root:'/theme';

if(!defined('APP_ROOT'))
	define('APP_ROOT', substr($query,0,strpos($query,$_root)));


function get_domain()
{
	/* 协议 */
	$protocol = get_http();


	/* 域名或IP地址 */
	if (isset($_SERVER['HTTP_X_FORWARDED_HOST']))
	{
		$host = $_SERVER['HTTP_X_FORWARDED_HOST'];
	}
	elseif (isset($_SERVER['HTTP_HOST']))
	{
		$host = $_SERVER['HTTP_HOST'];
	}
	else
	{
		/* 端口 */
		if (isset($_SERVER['SERVER_PORT']))
		{
			$port = ':' . $_SERVER['SERVER_PORT'];

			if ((':80' == $port && 'http://' == $protocol) || (':443' == $port && 'https://' == $protocol))
			{
				$port = '';
			}
		}
		else
		{
			$port = '';
		}

		if (isset($_SERVER['SERVER_NAME']))
		{
			$host = $_SERVER['SERVER_NAME'] . $port;
		}
		elseif (isset($_SERVER['SERVER_ADDR']))
		{
			$host = $_SERVER['SERVER_ADDR'] . $port;
		}
	}

	return $protocol . $host;
}

function get_http()
{
	return (isset($_SERVER['HTTPS']) && (strtolower($_SERVER['HTTPS']) != 'off')) ? 'https://' : 'http://';
}

function theme_parse_css($urls)
{

	$url = md5(implode(',',$urls));
	$url = md5($url.$GLOBALS['_root']);
	$css_url = 'public/runtime/statics/'.$url.'.css';
	$url_path = APP_ROOT_PATH.$css_url;
	if(!file_exists($url_path)||IS_DEBUG)
	{
		if(!file_exists(APP_ROOT_PATH.'public/runtime/statics/'))
			mkdir(APP_ROOT_PATH.'public/runtime/statics/',0777);
		$tmpl_path = file_domain().$GLOBALS['_root'];
		if($GLOBALS['distribution_cfg']['IS_JQ']&&$GLOBALS['distribution_cfg']['JQ_URL']){
			$font_path = $GLOBALS['distribution_cfg']['JQ_URL'].APP_ROOT.$GLOBALS['_root'];
		}else{
			$font_path = get_domain().APP_ROOT.$GLOBALS['_root'];
		}


		$css_content = '';
		foreach($urls as $url)
		{
			$css_content .= @file_get_contents($url);
		}
		$css_content = preg_replace("/[\r\n]/",'',$css_content);
		//字体替换
		$css_content = str_replace("../../images/iconfont/",$font_path."/images/iconfont/",$css_content);

		$css_content = str_replace("../../images/",$tmpl_path."/images/",$css_content);
		$css_content = str_replace("../images/",$tmpl_path."/images/",$css_content);
//		@file_put_contents($url_path, unicode_encode($css_content));
		@file_put_contents($url_path, $css_content);


		if($GLOBALS['distribution_cfg']['CSS_JS_OSS']&&$GLOBALS['distribution_cfg']['OSS_TYPE']&&$GLOBALS['distribution_cfg']['OSS_TYPE']!="NONE")
		{
			syn_to_remote_file_server($css_url);
			$GLOBALS['refresh_page'] = true;
		}
	}

 	return file_domain()."/".$css_url;

}

function theme_parse_script($urls,$encode_url=array())
{
	$url = md5(implode(',',$urls));
	$url = md5($url.$GLOBALS['_root']);
	$js_url = 'public/runtime/statics/'.$url.'.js';
	$url_path = APP_ROOT_PATH.$js_url;
	if(!file_exists($url_path)||IS_DEBUG)
	{
		if(!file_exists(APP_ROOT_PATH.'public/runtime/statics/'))
			mkdir(APP_ROOT_PATH.'public/runtime/statics/',0777);


		$js_content = '';
		foreach($urls as $url)
		{
			$append_content = @file_get_contents($url)."\r\n";

			$js_content .= $append_content;
		}

		@file_put_contents($url_path,$js_content);

		if($GLOBALS['distribution_cfg']['CSS_JS_OSS']&&$GLOBALS['distribution_cfg']['OSS_TYPE']&&$GLOBALS['distribution_cfg']['OSS_TYPE']!="NONE")
		{
			syn_to_remote_file_server($js_url);
			$GLOBALS['refresh_page'] = true;
		}
	}


	return file_domain()."/".$js_url;
}
function show_css_script($pagecss){

	$css = theme_parse_css($pagecss);
	echo '<link rel="stylesheet" href="'.$css.'">';
}

function show_js_script($pagejs){

	$js = theme_parse_script($pagejs);
	echo '<script src="'.$js.'"></script>';
}

function show_header($header){
	$pagecss = array();
	$pagecss[] = "js/fanwe_utils/sui-mobile/sm.min.css";
	$pagecss[] = "js/fanwe_utils/sui-mobile/sm-extend.min.css";
	$pagecss[] = "css/fanwe_utils/animate.css";
	$pagecss[] = "css/common_css/base.reset.css";
	$pagecss[] = "css/common_css/base.frame.css";
	$pagecss[] = "css/common_css/base.ui.css";
	$pagecss[] = "css/common_css/base.theme.css";
	$pagecss[] = "css/common_css/style.css";
	$pagecss[] = "css/banner.css";
	$pagecss[] = "css/index.css";
	$pagecss[] = "css/login.css";
	$pagecss[] = "css/user_center.css";

	$pagejs = array();
	$pagejs[] = 'js/fanwe_utils/sui-mobile/zepto.min.js';
	$pagejs[] = 'js/fanwe_utils/zepto.picLazyLoad.js';
	$pagejs[] = 'js/fanwe_utils/fanweUI.js';
	$pagejs[] = 'js/fanwe_utils/plupload/plupload.full.min.js';
	$pagejs[] = 'js/fanwe_utils/vue/vue.min.js';
	$pagejs[] = 'js/fanwe_utils/vue/vue-resource.js';
	$pagejs[] = 'js/common_js/script.js';

	$css = theme_parse_css($pagecss);
	$js =  theme_parse_script($pagejs);
	$header = str_replace('{$css}',$css,$header);
	$header = str_replace('{$js}',$js,$header);

	echo $header;
}

function show_footer($foot){
	$pagejs = array();
	$pagejs[] = "js/fanwe_utils/sui-mobile/sm.min.js";
	$pagejs[] = "js/fanwe_utils/sui-mobile/sm-extend.min.js";

	$pagejs[] = "js/editdata.js";
	$pagejs[] = "js/sui_mobile_footer.js";
	$js = theme_parse_script($pagejs);
	$foot = str_replace('{$js}',$js,$foot);
	echo $foot;
}

function url($route="index",$param=array())
{
	$route_array = explode("#",$route);

	if(isset($param)&&$param!=''&&!is_array($param))
	{
		$param['id'] = $param;
	}
	if(isset($route_array[0])){
		$module = strtolower(trim($route_array[0]));
	}else{
		$module = "";
	}
	if(isset($route_array[1])){
		$action = strtolower(trim($route_array[1]));
	}else{
		$action = "";
	}


	if(!$module||$module=='index')$module="";
	if(!$action||$action=='index')$action="";

	if(true )
	{
		//原始模式
		$url = APP_ROOT."/index.php";
		if($module!=''||$action!=''||count($param)>0) //有后缀参数
		{
			$url.="?";
		}

		if($module&&$module!='')
			$url .= CTL."=".$module."&";
		if($action&&$action!='')
			$url .= ACT."=".$action."&";
		if(count($param)>0)
		{
			foreach($param as $k=>$v)
			{
				if($k&&$v)
					$url =$url.$k."=".urlencode($v)."&";
			}
		}
		if(substr($url,-1,1)=='&'||substr($url,-1,1)=='?') $url = substr($url,0,-1);

		return $url;
	}
	else
	{
		//重写的默认
		$url = APP_ROOT;
		if($module==''&&$action==''){
			$url .='/index';
		}else{
			if($module&&$module!='')
				$url .= "/".$module;
			if($action&&$action!='')
				$url .= "-".$action;
		}


		if(count($param)>0)
		{
			$url.="/";
			foreach($param as $k=>$v)
			{
				$url =$url.$k."-".urlencode($v)."-";
			}
		}

		$route = $module."#".$action;
		switch ($route)
		{
			case "xxx":
				break;
			default:
				break;
		}


		if(substr($url,-1,1)=='/'||substr($url,-1,1)=='-') $url = substr($url,0,-1);
		$url=trim($url);
		if($url==''){
			$url="/index.html";
		}else{
			if($module=='article_cate'){
				if($param['id']){
					if($GLOBALS['article_cates'][$param['id']]['seo_title']){
						if($param['p']){
							$url=APP_ROOT."/".$GLOBALS['article_cates'][$param['id']]['seo_title']."?p=".$param['p'];
						}else{
							$url=APP_ROOT."/".$GLOBALS['article_cates'][$param['id']]['seo_title'];
						}
					}else{
						$url.='.html';
					}
				}elseif($param['p']){
					$url=APP_ROOT."/article_cate?p=".$param['p'];

				}elseif($param['tag']){
					$url=APP_ROOT."/article_cate?tag=".$param['tag'];
				}
				else{
					$url=APP_ROOT."/article_cate";

				}
			}elseif($module=='article'){
				if($param['id']){
					if($GLOBALS['article_cates'][$GLOBALS['articles'][$param['id']]['cate_id']]['seo_title']){
						$url=APP_ROOT."/".$GLOBALS['article_cates'][$GLOBALS['articles'][$param['id']]['cate_id']]['seo_title']."/".$param['id'].".html";
					}else{
						$url.='.html';
					}
				}else{
					$url=APP_ROOT."/article_cate";
				}
			}else{
				$url.='.html';
			}
		}
		if($url=='')$url="/";

		return $url;
	}

}

function deal_with_content($path){
	$content = file_get_contents($path);
	$content = str_replace("\"images/",'"'.file_domain()."/theme/images/",$content);
	return $content;
}

function get_theme_url(){
	echo get_domain().APP_ROOT.'/theme/';
}
function isWeixin(){
	$agent = strtolower($_SERVER['HTTP_USER_AGENT']);
	$is_weixin = strpos($agent, 'micromessenger') ? true : false ;
	if($is_weixin){
		return true;
	}else{
		return false;
	}
}

/**
 * 同步脚本样式缓存 $url:'public/runtime/statics/biz/'.$url.'.css';
 * @param unknown_type $url
 */
function syn_to_remote_file_server($url)
{
	if ($GLOBALS['distribution_cfg']['OSS_TYPE'] && $GLOBALS['distribution_cfg']['OSS_TYPE'] != "NONE") {
		if ($GLOBALS['distribution_cfg']['OSS_TYPE'] == "ES_FILE") {
			$pathinfo = pathinfo($url);
			$file = $pathinfo['basename'];
			$dir = $pathinfo['dirname'];
			$dir = str_replace("public/", "", $dir);
			$filefull = SITE_DOMAIN . APP_ROOT . "/public/" . $dir . "/" . $file;
			$syn_url = $GLOBALS['distribution_cfg']['OSS_DOMAIN'] . "/es_file.php?username=" . $GLOBALS['distribution_cfg']['OSS_ACCESS_ID'] . "&password=" . $GLOBALS['distribution_cfg']['OSS_ACCESS_KEY'] . "&file=" .
				$filefull . "&path=" . $dir . "/&name=" . $file . "&act=0";
			@file_get_contents($syn_url);
		} elseif ($GLOBALS['distribution_cfg']['OSS_TYPE'] == "ALI_OSS") {
			$pathinfo = pathinfo($url);

			$file = $pathinfo['basename'];
			$dir = $pathinfo['dirname'];

			require_once APP_ROOT_PATH . "system/alioss/sdk.class.php";
			$oss_sdk_service = new ALIOSS();
			//设置是否打开curl调试模式
			$oss_sdk_service->set_debug_mode(true);

			$bucket = $GLOBALS['distribution_cfg']['OSS_BUCKET_NAME'];
			$object = $dir . "/" . $file;
			$file_path = APP_ROOT_PATH . $dir . "/" . $file;

			$oss_sdk_service->upload_file_by_file($bucket, $object, $file_path);
		}
	}
}

function file_domain(){
	if($GLOBALS['distribution_cfg']['CSS_JS_OSS']&&$GLOBALS['distribution_cfg']['OSS_TYPE']&&$GLOBALS['distribution_cfg']['OSS_TYPE']!="NONE")
	{
		$domain = $GLOBALS['distribution_cfg']['OSS_FILE_DOMAIN'];
	}
	else
	{
		$domain =  get_domain().APP_ROOT;
	}
	return $domain;
}
?>