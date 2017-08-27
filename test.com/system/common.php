<?php
// +----------------------------------------------------------------------
// | Fanwe 方维众筹商业系统
// +----------------------------------------------------------------------
// | Copyright (c) 2011 http://www.fanwe.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: 甘味人生(526130@qq.com)
// +----------------------------------------------------------------------

//前后台加载的函数库



//获取真实路径
function get_real_path()
{
	return APP_ROOT_PATH;
}
//获取GMTime
function get_gmtime()
{
	return (time() - date('Z'));
}

function to_date($utc_time, $format = 'Y-m-d H:i:s') {
	if (empty ( $utc_time )) {
		return '';
	}
	$timezone = intval(app_conf('TIME_ZONE'));
	$time = $utc_time + $timezone * 3600;
	return date ($format, $time );
}


function to_timespan($str, $format = 'Y-m-d H:i:s')
{
	$timezone = intval(app_conf('TIME_ZONE'));
	//$timezone = 8;
	$time = intval(strtotime($str));

	if($time!=0)
		$time = $time - $timezone * 3600;
	return $time;
}


//获取客户端IP
function get_client_ip() {
	if (getenv ( "HTTP_CLIENT_IP" ) && strcasecmp ( getenv ( "HTTP_CLIENT_IP" ), "unknown" ))
		$ip = getenv ( "HTTP_CLIENT_IP" );
	else if (getenv ( "HTTP_X_FORWARDED_FOR" ) && strcasecmp ( getenv ( "HTTP_X_FORWARDED_FOR" ), "unknown" ))
		$ip = getenv ( "HTTP_X_FORWARDED_FOR" );
	else if (getenv ( "REMOTE_ADDR" ) && strcasecmp ( getenv ( "REMOTE_ADDR" ), "unknown" ))
		$ip = getenv ( "REMOTE_ADDR" );
	else if (isset ( $_SERVER ['REMOTE_ADDR'] ) && $_SERVER ['REMOTE_ADDR'] && strcasecmp ( $_SERVER ['REMOTE_ADDR'], "unknown" ))
		$ip = $_SERVER ['REMOTE_ADDR'];
	else
		$ip = "unknown";
	return ($ip);
}

//过滤注入
function filter_injection(&$request)
{
	$pattern = "/(select[\s])|(insert[\s])|(update[\s])|(delete[\s])|(from[\s])|(where[\s])/i";
	foreach($request as $k=>$v)
	{
		if(preg_match($pattern,$k,$match))
		{
			die("SQL Injection denied!");
		}

		if(is_array($v))
		{
			filter_injection($request[$k]);
		}
		else
		{
			if(preg_match($pattern,$v,$match))
			{
				die("SQL Injection denied!");
			}
		}
	}

}

function filter_ma_request(&$str){
	$search = array("../","\n","\r","\t","\r\n","'","<",">","\"","%","\\",".","/");
	return str_replace($search,"",$str);
}

//过滤请求
function filter_request(&$request)
{
	if(MAGIC_QUOTES_GPC)
	{
		foreach($request as $k=>$v)
		{
			if(is_array($v))
			{
				filter_request($v);
			}
			else
			{
				$request[$k] = stripslashes(trim($v));
			}
		}
	}

}

function adddeepslashes(&$request)
{

	foreach($request as $k=>$v)
	{
		if(is_array($v))
		{
			adddeepslashes($v);
		}
		else
		{
			$request[$k] = addslashes(trim($v));
		}
	}
}


function quotes($content)
{
	//if $content is an array
	if (is_array($content))
	{
		foreach ($content as $key=>$value)
		{
			//$content[$key] = mysql_real_escape_string($value);
			$content[$key] = addslashes($value);
		}
	} else
	{
		//if $content is not an array
		//$content=mysql_real_escape_string($content);
		$content=addslashes($content);
	}
	return $content;
}


//request转码
function convert_req(&$req)
{
	foreach($req as $k=>$v)
	{
		if(is_array($v))
		{
			convert_req($req[$k]);
		}
		else
		{
			if(!is_u8($v))
			{
				$req[$k] = iconv("gbk","utf-8",$v);
			}
		}
	}
}

function is_u8($string)
{
	return preg_match('%^(?:
		 [\x09\x0A\x0D\x20-\x7E]            # ASCII
	   | [\xC2-\xDF][\x80-\xBF]             # non-overlong 2-byte
	   |  \xE0[\xA0-\xBF][\x80-\xBF]        # excluding overlongs
	   | [\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}  # straight 3-byte
	   |  \xED[\x80-\x9F][\x80-\xBF]        # excluding surrogates
	   |  \xF0[\x90-\xBF][\x80-\xBF]{2}     # planes 1-3
	   | [\xF1-\xF3][\x80-\xBF]{3}          # planes 4-15
	   |  \xF4[\x80-\x8F][\x80-\xBF]{2}     # plane 16
   )*$%xs', $string);
}


//清除缓存
function clear_cache()
{
	//系统后台缓存
	clear_dir_file(get_real_path()."public/runtime/admin/Cache/");
	clear_dir_file(get_real_path()."public/runtime/admin/Data/_fields/");
	clear_dir_file(get_real_path()."public/runtime/admin/Temp/");
	clear_dir_file(get_real_path()."public/runtime/admin/Logs/");
	@unlink(get_real_path()."public/runtime/admin/~app.php");
	@unlink(get_real_path()."public/runtime/admin/~runtime.php");
	@unlink(get_real_path()."public/runtime/admin/lang.js");
	@unlink(get_real_path()."public/runtime/app/config_cache.php");


	//数据缓存
	clear_dir_file(get_real_path()."public/runtime/app/data_caches/");
	clear_dir_file(get_real_path()."public/runtime/app/db_caches/");
	$GLOBALS['cache']->clear();
	clear_dir_file(get_real_path()."public/runtime/data/");

	//模板页面缓存
	clear_dir_file(get_real_path()."public/runtime/app/tpl_caches/");
	clear_dir_file(get_real_path()."public/runtime/app/tpl_compiled/");
	@unlink(get_real_path()."public/runtime/app/lang.js");

	//脚本缓存
	clear_dir_file(get_real_path()."public/runtime/statics/");



}
function clear_dir_file($path)
{
	if ( $dir = opendir( $path ) )
	{
		while ( $file = readdir( $dir ) )
		{
			$check = is_dir( $path. $file );
			if ( !$check )
			{
				@unlink( $path . $file );
			}
			else
			{
				if($file!='.'&&$file!='..')
				{
					clear_dir_file($path.$file."/");
				}
			}
		}
		closedir( $dir );
		rmdir($path);
		return true;
	}
}


function check_install()
{
	if(!file_exists(get_real_path()."public/install.lock"))
	{
		clear_cache();
		header('Location:'.APP_ROOT.'/install');
		exit;
	}
}



//utf8 字符串截取
function msubstr($str, $start=0, $length=15, $charset="utf-8", $suffix=true)
{
	if(function_exists("mb_substr"))
	{
		$slice =  mb_substr($str, $start, $length, $charset);
		if($suffix&$slice!=$str) return $slice."…";
		return $slice;
	}
	elseif(function_exists('iconv_substr')) {
		return iconv_substr($str,$start,$length,$charset);
	}
	$re['utf-8']   = "/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|[\xe0-\xef][\x80-\xbf]{2}|[\xf0-\xff][\x80-\xbf]{3}/";
	$re['gb2312'] = "/[\x01-\x7f]|[\xb0-\xf7][\xa0-\xfe]/";
	$re['gbk']    = "/[\x01-\x7f]|[\x81-\xfe][\x40-\xfe]/";
	$re['big5']   = "/[\x01-\x7f]|[\x81-\xfe]([\x40-\x7e]|\xa1-\xfe])/";
	preg_match_all($re[$charset], $str, $match);
	$slice = join("",array_slice($match[0], $start, $length));
	if($suffix&&$slice!=$str) return $slice."…";
	return $slice;
}


//字符编码转换
if(!function_exists("iconv"))
{
	function iconv($in_charset,$out_charset,$str)
	{
		require 'libs/iconv.php';
		$chinese = new Chinese();
		return $chinese->Convert($in_charset,$out_charset,$str);
	}
}

//JSON兼容
if(!function_exists("json_encode"))
{
	function json_encode($data)
	{
		require_once 'libs/json.php';
		$JSON = new JSON();
		return $JSON->encode($data);
	}
}
if(!function_exists("json_decode"))
{
	function json_decode($data)
	{
		require_once 'libs/json.php';
		$JSON = new JSON();
		return $JSON->decode($data,1);
	}
}

//邮件格式验证的函数
function check_email($email)
{
	if(!preg_match("/^\w+((-\w+)|(\.\w+))*\@[A-Za-z0-9]+((\.|-)[A-Za-z0-9]+)*\.[A-Za-z0-9]+$/",$email))
	{
		return false;
	}
	else
		return true;
}
/*显示隐藏中间的手机号码*/
function hideMobile($mobile){
	if($mobile!="")
		return preg_replace('#(\d{3})\d{5}(\d{3})#', '${1}*****${2}',$mobile);
	else
		return "";
}
/*显示隐藏中间的邮箱号*/
function hideEmail($email){
	if($email!="")
	{
		return substr($email,0,-8)."*****".substr($email,-3);
	}
	else
	{
		return "";
	}

}
//验证手机号码
function check_mobile($mobile)
{
	if(!empty($mobile) && !preg_match("/^(1[0-9]{10})?$/",$mobile))
	{
		return false;
	}
	else
		return true;
}
//验证邮编
function check_postcode($postcode)
{
	if(!empty($postcode) && !preg_match("/^([0-9]{6})(-[0-9]{5})?$/",$postcode))
	{
		return false;
	}
	else
		return true;
}
//验证验证码
function check_verify_coder($verify_coder){
	if(!empty($verify_coder) && !preg_match("/^([0-9]{6})?$/",$verify_coder))
	{
		return false;
	}
	else
		return true;
}
function get_verify_code($verify_coder){
	$verify_coder_result = check_user("verify_coder",$verify_coder);
	//var_dump($verify_coder_result);exit;
	if($verify_coder_result['status']==0)
	{
		if($verify_coder_result['data']['error']==EMPTY_ERROR)
		{
			$error = "不能为空";
			$type = "form_tip";
		}
		if($verify_coder_result['data']['error']==EXIST_ERROR)
		{
			$error = "错误";
			$type="form_error";
		}
		return array("type"=>$type,"field"=>"verify_coder","info"=>"验证码".$error);
	}
	else
	{
		return array("type"=>"form_success","field"=>"verify_coder","info"=>"");
	}
}
//跳转
function app_redirect($url,$time=0,$msg='')
{
	//多行URL地址支持
	$url = str_replace(array("\n", "\r"), '', $url);
	if(empty($msg))
		$msg    =   "系统将在{$time}秒之后自动跳转到{$url}！";
	if (!headers_sent()) {
		// redirect
		if(0===$time) {
			if(substr($url,0,1)=="/")
			{
				header("Location:".get_domain().$url);
			}
			else
			{
				header("Location:".$url);
			}

		}else {
			header("refresh:{$time};url={$url}");
			echo($msg);
		}
		exit();
	}else {
		$str    = "<meta http-equiv='Refresh' content='{$time};URL={$url}'>";
		if($time!=0)
			$str   .=   $msg;
		exit($str);
	}
}



/**
 * 验证访问IP的有效性
 * @param ip地址 $ip_str
 * @param 访问页面 $module
 * @param 时间间隔 $time_span
 * @param 数据ID $id
 */
function check_ipop_limit($ip_str,$module,$time_span=0,$id=0)
{
	if(intval(app_conf('USER_SUBMIT_TIME'))>0){
		$time_span = intval(app_conf('USER_SUBMIT_TIME'));
	}
	$op = es_session::get($module."_".$id."_ip");
	if(empty($op))
	{
		$check['ip']	=	 get_client_ip();
		$check['time']	=	get_gmtime();
		es_session::set($module."_".$id."_ip",$check);
		return true;  //不存在session时验证通过
	}
	else
	{
		$check['ip']	=	 get_client_ip();
		$check['time']	=	get_gmtime();
		$origin	=	es_session::get($module."_".$id."_ip");

		if($check['ip']==$origin['ip'])
		{
			if($check['time'] - $origin['time'] < $time_span)
			{
				return false;
			}
			else
			{
				es_session::set($module."_".$id."_ip",$check);
				return true;  //不存在session时验证通过
			}
		}
		else
		{
			es_session::set($module."_".$id."_ip",$check);
			return true;  //不存在session时验证通过
		}
	}
}

function gzip_out($content)
{
	header("Content-type: text/html; charset=utf-8");
	header("Cache-control: private");  //支持页面回跳
	$gzip = app_conf("GZIP_ON");
	if( intval($gzip)==1 )
	{
		if(!headers_sent()&&extension_loaded("zlib")&&preg_match("/gzip/i",$_SERVER["HTTP_ACCEPT_ENCODING"]))
		{
			$content = gzencode($content,9);
			header("Content-Encoding: gzip");
			header("Content-Length: ".strlen($content));
			echo $content;
		}
		else
			echo $content;
	}else{
		echo $content;
	}

}


/**
 * 保存图片
 * @param array $upd_file  即上传的$_FILES数组
 * @param array $key $_FILES 中的键名 为空则保存 $_FILES 中的所有图片
 * @param string $dir 保存到的目录
 * @param array $whs
可生成多个缩略图
数组 参数1 为宽度，
参数2为高度，
参数3为处理方式:0(缩放,默认)，1(剪裁)，
参数4为是否水印 默认为 0(不生成水印)
array(
'thumb1'=>array(300,300,0,0),
'thumb2'=>array(100,100,0,0),
'origin'=>array(0,0,0,0),  宽与高为0为直接上传
...
)，
 * @param array $is_water 原图是否水印
 * @return array
array(
'key'=>array(
'name'=>图片名称，
'url'=>原图web路径，
'path'=>原图物理路径，
有略图时
'thumb'=>array(
'thumb1'=>array('url'=>web路径,'path'=>物理路径),
'thumb2'=>array('url'=>web路径,'path'=>物理路径),
...
)
)
....
)
 */
//$img = save_image_upload($_FILES,'avatar','temp',array('avatar'=>array(300,300,1,1)),1);
function save_image_upload($upd_file, $key='',$dir='temp', $whs=array(),$is_water=false,$need_return = false)
{
	require_once APP_ROOT_PATH."system/utils/es_imagecls.php";
	$image = new es_imagecls();
	$image->max_size = intval(app_conf("MAX_IMAGE_SIZE"));

	$list = array();

	if(empty($key))
	{
		foreach($upd_file as $fkey=>$file)
		{
			$list[$fkey] = false;
			$image->init($file,$dir);
			if($image->save())
			{
				$list[$fkey] = array();
				$list[$fkey]['url'] = $image->file['target'];
				$list[$fkey]['path'] = $image->file['local_target'];
				$list[$fkey]['name'] = $image->file['prefix'];
			}
			else
			{
				if($image->error_code==-105)
				{
					if($need_return)
					{
						return array('error'=>1,'message'=>'上传的图片太大');
					}
					else
						echo "上传的图片太大";
				}
				elseif($image->error_code==-104||$image->error_code==-103||$image->error_code==-102||$image->error_code==-101)
				{
					if($need_return)
					{
						return array('error'=>1,'message'=>'非法图像'.$image->error_code);
					}
					else
						echo "非法图像";
				}
				exit;
			}
		}
	}
	else
	{
		$list[$key] = false;
		$image->init($upd_file[$key],$dir);
		if($image->save())
		{
			$list[$key] = array();
			$list[$key]['url'] = $image->file['target'];
			$list[$key]['path'] = $image->file['local_target'];
			$list[$key]['name'] = $image->file['prefix'];
		}
		else
		{
			if($image->error_code==-105)
			{
				if($need_return)
				{
					return array('error'=>1,'message'=>'上传的图片太大');
				}
				else
					echo "上传的图片太大";
			}
			elseif($image->error_code==-104||$image->error_code==-103||$image->error_code==-102||$image->error_code==-101)
			{
				if($need_return)
				{
					return array('error'=>1,'message'=>'非法图像'.$image->error_code);
				}
				else
					echo "非法图像";
			}
			exit;
		}
	}

	$water_image = APP_ROOT_PATH.app_conf("WATER_MARK");
	$alpha = app_conf("WATER_ALPHA");
	$place = app_conf("WATER_POSITION");

	foreach($list as $lkey=>$item)
	{
		//循环生成规格图
		foreach($whs as $tkey=>$wh)
		{
			$list[$lkey]['thumb'][$tkey]['url'] = false;
			$list[$lkey]['thumb'][$tkey]['path'] = false;
			if($wh[0] > 0 || $wh[1] > 0)  //有宽高度
			{
				$thumb_type = isset($wh[2]) ? intval($wh[2]) : 0;  //剪裁还是缩放， 0缩放 1剪裁
				if($thumb = $image->thumb($item['path'],$wh[0],$wh[1],$thumb_type))
				{
					$list[$lkey]['thumb'][$tkey]['url'] = $thumb['url'];
					$list[$lkey]['thumb'][$tkey]['path'] = $thumb['path'];
					if(isset($wh[3]) && intval($wh[3]) > 0)//需要水印
					{
						$paths = pathinfo($list[$lkey]['thumb'][$tkey]['path']);
						$path = $paths['dirname'];
						$path = $path."/origin/";
						if (!is_dir($path)) {
							@mkdir($path);
							@chmod($path, 0777);
						}
						$filename = $paths['basename'];
						@file_put_contents($path.$filename,@file_get_contents($list[$lkey]['thumb'][$tkey]['path']));
						$image->water($list[$lkey]['thumb'][$tkey]['path'],$water_image,$alpha, $place);
					}
				}
			}
		}
		if($is_water)
		{
			$paths = pathinfo($item['path']);
			$path = $paths['dirname'];
			$path = $path."/origin/";
			if (!is_dir($path)) {
				@mkdir($path);
				@chmod($path, 0777);
			}
			$filename = $paths['basename'];
			@file_put_contents($path.$filename,@file_get_contents($item['path']));
			$image->water($item['path'],$water_image,$alpha, $place);
		}
	}
	return $list;
}

function empty_tag($string)
{
	$string = preg_replace(array("/\[img\]\d+\[\/img\]/","/\[[^\]]+\]/"),array("",""),$string);
	if(trim($string)=='')
		return $GLOBALS['lang']['ONLY_IMG'];
	else
		return $string;
	//$string = str_replace(array("[img]","[/img]"),array("",""),$string);
}


/**
 * utf8字符转Unicode字符
 * @param string $char 要转换的单字符
 * @return void
 */
function utf8_to_unicode($char)
{
	switch(strlen($char))
	{
		case 1:
			return ord($char);
		case 2:
			$n = (ord($char[0]) & 0x3f) << 6;
			$n += ord($char[1]) & 0x3f;
			return $n;
		case 3:
			$n = (ord($char[0]) & 0x1f) << 12;
			$n += (ord($char[1]) & 0x3f) << 6;
			$n += ord($char[2]) & 0x3f;
			return $n;
		case 4:
			$n = (ord($char[0]) & 0x0f) << 18;
			$n += (ord($char[1]) & 0x3f) << 12;
			$n += (ord($char[2]) & 0x3f) << 6;
			$n += ord($char[3]) & 0x3f;
			return $n;
	}
}

/**
 * utf8字符串分隔为unicode字符串
 * @param string $str 要转换的字符串
 * @param string $depart 分隔,默认为空格为单字
 * @return string
 */
function str_to_unicode_word($str,$depart=' ')
{
	$arr = array();
	$str_len = mb_strlen($str,'utf-8');
	for($i = 0;$i < $str_len;$i++)
	{
		$s = mb_substr($str,$i,1,'utf-8');
		if($s != ' ' && $s != '　')
		{
			$arr[] = 'ux'.utf8_to_unicode($s);
		}
	}
	return implode($depart,$arr);
}


/**
 * utf8字符串分隔为unicode字符串
 * @param string $str 要转换的字符串
 * @return string
 */
function str_to_unicode_string($str)
{
	$string = str_to_unicode_word($str,'');
	return $string;
}

//分词
function div_str($str)
{
	require_once APP_ROOT_PATH."system/libs/words.php";
	$words = words::segment($str);
	$words[] = $str;
	return $words;
}



/**
 *
 * @param $tag  //要插入的关键词
 * @param $table  //表名
 * @param $id  //数据ID
 * @param $field		// tag_match/name_match/cate_match/locate_match
 */
function insert_match_item($tag,$table,$id,$field)
{
	if($tag=='')
		return;

	$unicode_tag = str_to_unicode_string($tag);
	$sql = "select count(*) from ".DB_PREFIX.$table." where match(".$field.") against ('".$unicode_tag."' IN BOOLEAN MODE) and id = ".$id;
	$rs = $GLOBALS['db']->getOne($sql);
	if(intval($rs) == 0)
	{
		$match_row = $GLOBALS['db']->getRow("select * from ".DB_PREFIX.$table." where id = ".$id);
		if($match_row[$field]=="")
		{
			$match_row[$field] = $unicode_tag;
			$match_row[$field."_row"] = $tag;
		}
		else
		{
			$match_row[$field] = $match_row[$field].",".$unicode_tag;
			$match_row[$field."_row"] = $match_row[$field."_row"].",".$tag;
		}
		$GLOBALS['db']->autoExecute(DB_PREFIX.$table, $match_row, $mode = 'UPDATE', "id=".$id, $querymode = 'SILENT');

	}
}

//封装url

function url($route="index",$param=array())
{
	$key = md5("URL_KEY_".$route.serialize($param));
	if(isset($GLOBALS[$key]))
	{
		$url = $GLOBALS[$key];
		return $url;
	}

	$url = load_dynamic_cache($key);
	if($url!==false)
	{
		$GLOBALS[$key] = $url;
		return $url;
	}

	$route_array = explode("#",$route);

	if(isset($param)&&$param!=''&&!is_array($param))
	{
		$param['id'] = $param;
	}

	$module = strtolower(trim($route_array[0]));
	$action = strtolower(trim($route_array[1]));

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
		$GLOBALS[$key] = $url;
		set_dynamic_cache($key,$url);
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
//		if(substr($url,-1,1)=='/'||substr($url,-1,1)=='-'){
//			$url.='index';
//		}

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
		$GLOBALS[$key] = $url;
		set_dynamic_cache($key,$url);
		return $url;
	}


}

function url_wap($route="index",$param=array())
{
	if($GLOBALS['is_app']){
		$param['from_type'] = $GLOBALS['is_app'];
	}

	$key = md5("URL_WAP_KEY_".$route.serialize($param));
	if(isset($GLOBALS[$key]))
	{
		$url = $GLOBALS[$key];
		return $url;
	}

	$url = load_dynamic_cache($key);
	if($url!==false)
	{
		$GLOBALS[$key] = $url;
		return $url;
	}

	$route_array = explode("#",$route);

	if(isset($param)&&$param!=''&&!is_array($param))
	{
		$param['id'] = $param;
	}

	$module = strtolower(trim($route_array[0]));
	$action = strtolower(trim($route_array[1]));

	if(!$module||$module=='index')$module="";
	if(!$action||$action=='index')$action="";

	if(true)
	{
		//原始模式
		$url = APP_ROOT."/wap/index.php";
		if($module!=''||$action!=''||count($param)>0) //有后缀参数
		{
			$url.="?";
		}

		if($module&&$module!='')
			$url .= "ctl=".$module."&";
		if($action&&$action!='')
			$url .= "act=".$action."&";
		if(count($param)>0)
		{
			foreach($param as $k=>$v)
			{
				if($k&&$v)
					$url =$url.$k."=".urlencode($v)."&";
			}
		}
		if(substr($url,-1,1)=='&'||substr($url,-1,1)=='?') $url = substr($url,0,-1);
		$GLOBALS[$key] = $url;
		set_dynamic_cache($key,$url);
		return $url;
	}
	else
	{
		//重写的默认
		$url = APP_ROOT."/wap";

		if($module&&$module!='')
			$url .= "/".$module;
		if($action&&$action!='')
			$url .= "-".$action;

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

		if($url=='')$url="/";
		$GLOBALS[$key] = $url;
		set_dynamic_cache($key,$url);
		return $url;
	}


}

//封装url

function url_mapi($route="index",$param=array())
{
	$key = md5("URL_APP_KEY_".$route.serialize($param));
	if(isset($GLOBALS[$key]))
	{
		$url = $GLOBALS[$key];
		return $url;
	}

	$url = load_dynamic_cache($key);
	if($url!==false)
	{
		$GLOBALS[$key] = $url;
		return $url;
	}

	$route_array = explode("#",$route);

	if(isset($param)&&$param!=''&&!is_array($param))
	{
		$param['id'] = $param;
	}

	$module = strtolower(trim($route_array[0]));
	$action = strtolower(trim($route_array[1]));

	if(!$module||$module=='index')$module="";
	if(!$action||$action=='index')$action="";

	if(app_conf("URL_MODEL")==0)
	{
		//原始模式
		$url = APP_ROOT."/index.php";
		if($module!=''||$action!=''||count($param)>0) //有后缀参数
		{
			$url.="?";
		}

		if($module&&$module!='')
			$url .= "ctl=".$module."&";
		if($action&&$action!='')
			$url .= "act=".$action."&";
		if(count($param)>0)
		{
			foreach($param as $k=>$v)
			{
				if($k&&$v)
					$url =$url.$k."=".urlencode($v)."&";
			}
		}
		if(substr($url,-1,1)=='&'||substr($url,-1,1)=='?') $url = substr($url,0,-1);
		$GLOBALS[$key] = $url;
		set_dynamic_cache($key,$url);
		return $url;
	}
	else
	{
		//重写的默认
		$url = APP_ROOT;

		if($module&&$module!='')
			$url .= "/".$module;
		if($action&&$action!='')
			$url .= "-".$action;

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

		if($url=='')$url="/";
		$GLOBALS[$key] = $url;
		set_dynamic_cache($key,$url);
		return $url;
	}


}
//封装app_url
function url_app($route="index",$param=array())
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

//PC端 封装url
function url_pc($route="index",$act="action",$param=array())
{
	$route_array = explode("#",$route);
	$act_array = explode("#",$act);

	if(isset($param)&&$param!=''&&!is_array($param))
	{
		$param['id'] = $param;
	}
	if(isset($route_array[0])){
		$module = strtolower(trim($route_array[0]));
	}else{
		$module = "";
	}
	if(isset($act_array[0])){
		$action = strtolower(trim($act_array[0]));
	}else{
		$action = "";
	}




	if(!$module||$module=='index')$module="";
	if(!$action||$action=='action')$action="";

	if(true )
	{
		//原始模式
		$url = APP_ROOT."/app/index.php";
		if($module!=''||$action!=''||count($param)>0) //有后缀参数
		{
			$url.="?";
		}

		if($module&&$module!='')
			$url .= 'ctl'."=".$module."&";
		if($action&&$action!='')
			$url .= 'act'."=".$action."&";
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
				$url .= "/".$action;
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
		$cat = $module."#".$action;
		switch ($route)
		{
			case "xxx":
				break;
			default:
				break;
		}
		switch ($cat)
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

//微信端 封装url
function url_wx($route="index",$act="action",$param=array())
{
	if($GLOBALS['is_app']){
		$param['from_type'] = $GLOBALS['is_app'];
	}

	$key = md5("URL_WAP_KEY_".$route.serialize($param));
	if(isset($GLOBALS[$key]))
	{
		$url = $GLOBALS[$key];
		return $url;
	}

	$url = load_dynamic_cache($key);
	if($url!==false)
	{
		$GLOBALS[$key] = $url;
		return $url;
	}

	$route_array = explode("#",$route);

	if(isset($param)&&$param!=''&&!is_array($param))
	{
		$param['id'] = $param;
	}

	$module = strtolower(trim($route_array[0]));
	$action = strtolower(trim($route_array[1]));

	if(!$module||$module=='index')$module="";
	if(!$action||$action=='index')$action="";

	if(true)
	{
		//原始模式
		$url = APP_ROOT."/index.php";
		if($module!=''||$action!=''||count($param)>0) //有后缀参数
		{
			$url.="?";
		}

		if($module&&$module!='')
			$url .= "ctl=".$module."&";
		if($action&&$action!='')
			$url .= "act=".$action."&";
		if(count($param)>0)
		{
			foreach($param as $k=>$v)
			{
				if($k&&$v)
					$url =$url.$k."=".urlencode($v)."&";
			}
		}
		if(substr($url,-1,1)=='&'||substr($url,-1,1)=='?') $url = substr($url,0,-1);
		$GLOBALS[$key] = $url;
		set_dynamic_cache($key,$url);
		return $url;
	}
	else
	{
		//重写的默认
		$url = APP_ROOT;

		if($module&&$module!='')
			$url .= "/".$module;
		if($action&&$action!='')
			$url .= "-".$action;

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

		if($url=='')$url="/";
		$GLOBALS[$key] = $url;
		set_dynamic_cache($key,$url);
		return $url;
	}


	}
//手机端 访问根目录的url
function url_root($route="index",$param=array())
{
	$key = md5("URL_KEY_".$route.serialize($param));
	if(isset($GLOBALS[$key]))
	{
		$url = $GLOBALS[$key];
		return $url;
	}

	$url = load_dynamic_cache($key);
	if($url!==false)
	{
		$GLOBALS[$key] = $url;
		return $url;
	}

	$route_array = explode("#",$route);

	if(isset($param)&&$param!=''&&!is_array($param))
	{
		$param['id'] = $param;
	}

	$module = strtolower(trim($route_array[0]));
	$action = strtolower(trim($route_array[1]));

	if(!$module||$module=='index')$module="";
	if(!$action||$action=='index')$action="";

	if(app_conf("URL_MODEL")==0)
	{
		//原始模式
		$url = get_domain().REAL_APP_ROOT."/index.php";
		if($module!=''||$action!=''||count($param)>0) //有后缀参数
		{
			$url.="?";
		}

		if($module&&$module!='')
			$url .= "ctl=".$module."&";
		if($action&&$action!='')
			$url .= "act=".$action."&";
		if(count($param)>0)
		{
			foreach($param as $k=>$v)
			{
				if($k&&$v)
					$url =$url.$k."=".urlencode($v)."&";
			}
		}
		if(substr($url,-1,1)=='&'||substr($url,-1,1)=='?') $url = substr($url,0,-1);
		$GLOBALS[$key] = $url;
		set_dynamic_cache($key,$url);
		return $url;
	}
	else
	{
		//重写的默认
		$url = get_domain().REAL_APP_ROOT;

		if($module&&$module!='')
			$url .= "/".$module;
		if($action&&$action!='')
			$url .= "-".$action;

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

		if($url=='')$url="/";
		$GLOBALS[$key] = $url;
		set_dynamic_cache($key,$url);
		return $url;
	}


}

function unicode_encode($name) {//to Unicode
	$name = iconv('UTF-8', 'UCS-2', $name);
	$len = strlen($name);
	$str = '';
	for($i = 0; $i < $len - 1; $i = $i + 2) {
		$c = $name[$i];
		$c2 = $name[$i + 1];
		if (ord($c) > 0) {// 两个字节的字
			$cn_word = '\\'.base_convert(ord($c), 10, 16).base_convert(ord($c2), 10, 16);
			$str .= strtoupper($cn_word);
		} else {
			$str .= $c2;
		}
	}
	return $str;
}

function unicode_decode($name) {//Unicode to
	$pattern = '/([\w]+)|(\\\u([\w]{4}))/i';
	preg_match_all($pattern, $name, $matches);
	if (!empty($matches)) {
		$name = '';
		for ($j = 0; $j < count($matches[0]); $j++) {
			$str = $matches[0][$j];
			if (strpos($str, '\\u') === 0) {
				$code = base_convert(substr($str, 2, 2), 16, 10);
				$code2 = base_convert(substr($str, 4), 16, 10);
				$c = chr($code).chr($code2);
				$c = iconv('UCS-2', 'UTF-8', $c);
				$name .= $c;
			} else {
				$name .= $str;
			}
		}
	}
	return $name;
}


//载入动态缓存数据
function load_dynamic_cache($name)
{
	if(isset($GLOBALS['dynamic_cache'][$name]))
	{
		return $GLOBALS['dynamic_cache'][$name];
	}
	else
	{
		return false;
	}
}

function set_dynamic_cache($name,$value)
{
	if(!isset($GLOBALS['dynamic_cache'][$name]))
	{
		if(count($GLOBALS['dynamic_cache'])>MAX_DYNAMIC_CACHE_SIZE)
		{
			array_shift($GLOBALS['dynamic_cache']);
		}
		$GLOBALS['dynamic_cache'][$name] = $value;
	}
}

function load_auto_cache($key,$param=array(),$is_real=true)
{
	$keys = array('admin_nav','admin_role','api_list','article','article_agreement','article_cates','article_cates_bs','article_notice','article_privacy','banner_list',
		'cache_nav_list','cate_id','cate_top','index_image','lottery_luckyers','m_config','message_cate','mobile_code','new_hepls','page_image',
		'pay_list','prop_id','prop_list','region_list','rule_list','score_cates','tipoff_type_list','user_carry_config','user_level','usersig'
		);
	fanwe_require(APP_ROOT_PATH."system/libs/auto_cache.php");
	//if(!in_array($key,$keys)){
	//	return false;
	//}
	$file =  APP_ROOT_PATH."system/auto_cache/".$key.".auto_cache.php";
//	if(file_exists($file))
//	{
		fanwe_require($file);
		$class = $key."_auto_cache";
		$obj = new $class;
		$result = $obj->load($param,$is_real);
//	}
//	else
//		$result = false;
	return $result;
}

function rm_auto_cache($key,$param=array())
{
	fanwe_require(APP_ROOT_PATH."system/libs/auto_cache.php");
	$file =  APP_ROOT_PATH."system/auto_cache/".$key.".auto_cache.php";
	if(file_exists($file))
	{
		fanwe_require($file);
		$class = $key."_auto_cache";
		$obj = new $class;
		$obj->rm($param);
	}
}


function clear_auto_cache($key,$param)
{
	fanwe_require(APP_ROOT_PATH."system/libs/auto_cache.php");
	$file =  APP_ROOT_PATH."system/auto_cache/".$key.".auto_cache.php";
	if(file_exists($file))
	{
		fanwe_require($file);
		$class = $key."_auto_cache";
		$obj = new $class;
		$obj->clear_all($param);
	}
}
function app_login(){

		$agentArr = agentArr();

		$user_info = es_session::get("user_info");

		if(!$user_info){
			$data['is_login'] = 0;
			$data['user_id'] = '';
			$data['nick_name'] = '';
			$data['mobile'] = '';
		}else{
			$data['is_login'] = 1;
			$data['user_id'] = $user_info['id'];
			$data['nick_name'] = $user_info['nick_name'];
			$data['mobile'] = $user_info['mobile'];
			$data['head_image'] = add_domain_url($user_info['head_image']);
		}
		if($agentArr['sdk_type']=="ios"){
			$data['sdk_data']= "weixin://";
		}
		else{
			$data['sdk_data']= "com.tencent.mm";
		}

		return $data;
	}

/*ajax返回*/
function ajax_return($data,$r_type=4,$is_debug=false)
{
	//$user_info = app_login();
	//$data['user_info'] = $user_info;
	if(!$is_debug){
		header("Content-Type:text/html; charset=utf-8");
		header('Cache-Control: no-cache, no-store, max-age=0, must-revalidate');
		filter_null($data);//过滤null
		//过滤false
		$data = filter_all_false($data);
		/*if($GLOBALS['user_info']['id']==484){
			$data = array('error' => '已更新新版本，请升级最新APP,再使用','status' =>0);
		}*/
		$encrypt = $GLOBALS['encrypt'];
		$data['act'] = $encrypt['act'];
		$data['ctl'] = $encrypt['ctl'];
		if($encrypt['i_type']){
			ajax_return_aes($data,$r_type);
		}else{
			echo(json_encode($data));
		}
		exit;
	}else{


			var_export($data);
			echo "<br />";
			exit;


	}

}
/*admin 后台 ajax返回*/
function admin_ajax_return($data,$is_debug=false)
{
	if(!$is_debug){
		header("Content-Type:text/html; charset=utf-8");
		header('Cache-Control: no-cache, no-store, max-age=0, must-revalidate');
		filter_null($data);//过滤null
		//过滤false
		echo(json_encode($data));
		exit;
	}else{
		var_export($data);
		echo "<br />";
		exit;
	}

}

//过滤null 把null改为空;
function filter_null(&$request)
{
	foreach($request as $k=>$v)
	{

		if(is_array($v))
		{
			filter_null($request[$k]);
		}
		else
		{
			if(is_null($v))
			{
				$request[$k] = '';
			}
		}
	}
}
/*ajax返回*/
function ajax_file_return($data,$is_debug=false)
{
	if(!$is_debug){
		header("Content-Type:text/html; charset=utf-8");
		echo(json_encode($data));
		exit;
	}else{
		if($data['status']==0){
			var_export($data);
			echo "<br />";
			exit;
		}
	}
}

/**
 * 过滤绑定用户名中的奇葩字符：替换成可以存入的3字节
 */
function filterEmoji($string){
	 return preg_replace('/[\x{10000}-\x{10FFFF}]/u', '',$string);

}

/**
 * 屏蔽Emoji表情：去除4字节的表情
 */
/*function filterEmoji1($string){
		$str = preg_replace_callback(
				'/./u',
				function (array $match) {
					return strlen($match[0]) >= 4 ? '' : $match[0];
				},
				$str);

		return $str;
	}*/

function is_animated_gif($filename){
	$fp=fopen($filename, 'rb');
	$filecontent=fread($fp, filesize($filename));
	fclose($fp);
	return strpos($filecontent,chr(0x21).chr(0xff).chr(0x0b).'NETSCAPE2.0')===FALSE?0:1;
}





function gen_qrcode($str,$size = 5)
{

	require_once APP_ROOT_PATH."system/phpqrcode/qrlib.php";

	$root_dir = APP_ROOT_PATH."public/images/qrcode/";
	if (!is_dir($root_dir)) {
		@mkdir($root_dir);
		@chmod($root_dir, 0777);
	}

	$filename = md5($str."|".$size);
	$hash_dir = $root_dir. '/c' . substr(md5($filename), 0, 1)."/";
	if (!is_dir($hash_dir))
	{
		@mkdir($hash_dir);
		@chmod($hash_dir, 0777);
	}

	$filesave = $hash_dir.$filename.'.png';

	if(!file_exists($filesave))
	{
		QRcode::png($str, $filesave, 'Q', $size, 2);
	}
	return APP_ROOT."/public/images/qrcode/c". substr(md5($filename), 0, 1)."/".$filename.".png";
}

function format_price($v)
{
	if(!$v){$v = 0;}
	return "¥".number_format($v,2);
}


//发密码验证邮件
function send_user_password_mail($user_id)
{

	$verify_code = rand(111111,999999);
	$GLOBALS['db']->query("update ".DB_PREFIX."user set password_verify = '".$verify_code."' where id = ".$user_id);
	$user_info = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."user where id = ".$user_id);
	if($user_info)
	{
		$tmpl = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."msg_template where name = 'TPL_MAIL_USER_PASSWORD'");
		$tmpl_content=  $tmpl['content'];
		$user_info['logo']=app_conf("SITE_LOGO");
		$user_info['site_name']=app_conf("SITE_NAME");
		$time=get_gmtime();
		$user_info['send_time']=to_date($time,'Y年m月d日');
		$user_info['send_time_ms']=to_date($time,'Y年m月d日 H时i分');

		$user_info['password_url'] = get_domain().url("settings#password", array("code"=>$user_info['password_verify'],"id"=>$user_info['id']));
		$GLOBALS['tmpl']->assign("user",$user_info);
		$msg = $GLOBALS['tmpl']->fetch("str:".$tmpl_content);
		$msg_data['dest'] = $user_info['email'];
		$msg_data['send_type'] = 1;
		$msg_data['title'] = "重置密码";
		$msg_data['content'] = addslashes($msg);
		$msg_data['send_time'] = 0;
		$msg_data['is_send'] = 0;
		$msg_data['create_time'] = get_gmtime();
		$msg_data['user_id'] = $user_info['id'];
		$msg_data['is_html'] = $tmpl['is_html'];
		$GLOBALS['db']->autoExecute(DB_PREFIX."deal_msg_list",$msg_data); //插入
	}

}

function strim($str)
{
	return quotes(htmlspecialchars(trim($str)));
}
function btrim($str)
{
	return quotes(trim($str));
}
function valid_tag($str)
{

	return preg_replace("/<(?!div|ol|ul|li|sup|sub|span|br|img|p|h1|h2|h3|h4|h5|h6|\/div|\/ol|\/ul|\/li|\/sup|\/sub|\/span|\/br|\/img|\/p|\/h1|\/h2|\/h3|\/h4|\/h5|\/h6|blockquote|\/blockquote|strike|\/strike|b|\/b|i|\/i|u|\/u)[^>]*>/i","",$str);
}

//$type = 1(添加) 2(删除)
function update_user_weibo($user_id,$weibo_url,$type=1)
{
	if($weibo_url!="")
	{
		if($type==1)
		{
			if($GLOBALS['db']->getOne("select count(*) from ".DB_PREFIX."user_weibo where weibo_url = '".$weibo_url."' and user_id = ".$user_id)==0)
			{
				$weibo_data['user_id'] = $user_id;
				$weibo_data['weibo_url'] = $weibo_url;
				$GLOBALS['db']->autoExecute(DB_PREFIX."user_weibo",$weibo_data);
			}
		}
		if($type==2)
		{
			$GLOBALS['db']->query("delete from ".DB_PREFIX."user_weibo where user_id = ".$user_id." and weibo_url = '".$weibo_url."'");
		}
	}
}


//同步到微博
function syn_weibo($data)
{
	$api_list = $GLOBALS['db']->getAllCached("select * from ".DB_PREFIX."api_login where is_weibo = 1");
	foreach($api_list as $k=>$v)
	{
		if($GLOBALS['user_info'][strtolower($v['class_name'])."_id"]==""||$GLOBALS['user_info'][strtolower($v['class_name'])."_token"]=="")
		{
			unset($api_list[$k]);
		}
		else
		{
			$class_name = $v['class_name']."_api";
			require_once APP_ROOT_PATH."system/api_login/".$class_name.".php";
			$o = new $class_name($v);
			$o->send_message($data);
		}
	}
}


function check_sms_send($mobile){

	$data = array();
	$data['status'] = 1;
	$data['error'] = "未定义限制";
	/*
	if(!SMS_MOBILE_SEND_COUNT&&!SMS_IP_SEND_COUNT){
		//return false;
		$data['status'] = 1;
		$data['error'] = "未定义限制";
	}*/

	$now_ip = get_client_ip();
	$now_date = to_date(get_gmtime(),'Y-m-d');
	//$now_date = to_timespan($now_date);
	//$to_date = $now_date + 24*3600;
	if (defined('SMS_MOBILE_SEND_COUNT') && SMS_MOBILE_SEND_COUNT > 0){
		$mobile_sql = "select count(*) from ".DB_PREFIX."deal_msg_list where send_type = 0 and dest = '".$mobile."' and send_date ='".$now_date."'";
		$mobile_count = $GLOBALS['db']->getOne($mobile_sql);
		$mobile_count = intval($mobile_count);


		if($mobile_count>SMS_MOBILE_SEND_COUNT){
			$data['status'] = 0;
			$data['error'] = "验证码发送失败，当前手机号已超过今天限额";
		}
	}

	//$ip_now_date = to_date(NOW_TIME,'Y-m-d H:00:00');
	//log_result('ip_now_date:'.$ip_now_date);
	//$ip_now_date = to_timespan($ip_now_date);
	//$ip_now_hour = $ip_now_date;
	//$ip_to_date = $ip_now_hour + 3600;
	if ($data['status'] == 1 && defined('SMS_IP_SEND_COUNT') && SMS_IP_SEND_COUNT > 0){
		$date_h = to_date(get_gmtime(),'H');

		$ip_sql = "select count(*) from ".DB_PREFIX."deal_msg_list where send_type = 0 and client_ip ='".$now_ip."' and send_h = ".$date_h." and send_date ='".$now_date."'";
		$ip_count = $GLOBALS['db']->getOne($ip_sql);
		$ip_count = intval($ip_count);

		if($ip_count>SMS_IP_SEND_COUNT){
			$data['status'] = 0;
			$data['error'] = "验证码发送失败，当前ip已超过今天限额";
		}
	}

	return $data;
}
//发短信验证码
function send_verify_sms($mobile,$code,$type="")
{
	//check_sms_send($mobile);
	//log_result("==send_verify_sms==");
	//log_result($mobile.'---'.$code);
	$type ='sms';
	$dest = $mobile;
	$title='';
	/*
 	 * 发送验证码
 	 */
	 	$user_info = array();

		if(!empty($dest)){

			$user_info = $GLOBALS['db']->getRow("select *,id as user_id from ".DB_PREFIX."user where mobile='".$dest."'");

			$user_info['mobile'] = $dest;
			$user_info['code'] = $code;
			$user_info['tmpl_sms_name'] = 'verify';

			if($title){
				$msg_data['title'] = $title;
			}else{
				$msg_data['title'] = "短信验证码";
			}
			$msg_data['dest'] = $user_info['mobile'];
			$msg_data['user_id'] = $user_info['user_id'];
			$msg_data['is_html'] = 0;
			$msg_data['send_type'] = 0;
			$msg_data['code'] =$code;

			if(app_conf("SMS_ON")!=1&&$type=='sms'){
				return false;
			}

			$tmpl = $GLOBALS['db']->getRowCached("select * from ".DB_PREFIX."msg_template where name = 'TPL_SMS_VERIFY_CODE'");
			//$tmpl = str_replace('{$verify.mobile}',$dest,$tmpl);

			//补充 替换verify.mobile
			$tmpl = str_replace('你的手机号为{$verify.mobile},','',$tmpl);

			$tmpl['content'] = str_replace('{$verify.code}',$code,$tmpl);
			$msg= $tmpl['content'];
 			$msg_data['send_type'] = 0;
			$msg_data['content'] = addslashes($msg['content']);
			$msg_data['send_time'] = 0;
			$msg_data['is_send'] = 0;
			$msg_data['create_time'] = get_gmtime();

			$msg_data['is_html'] = 1;

			$msg_data['client_ip'] = get_client_ip();
			$msg_data['send_date'] = to_date(get_gmtime(),'Y-m-d');
			$msg_data['send_h'] = to_date(get_gmtime(),'H');

		 	if($msg_data){
				$data = $msg_data;
				if(app_conf('IS_SMS_DIRECT')==1){
					if($data['send_type']==0){
						require_once APP_ROOT_PATH."system/utils/es_sms.php";
						$sms = new sms_sender();
						$result = $sms->sendSms($data['dest'],$data['content']);
						$data['is_success'] = intval($result['status']);
						$data['result'] = $result['msg'];
					}
					$data['is_send'] = 1;
					$data['send_time'] = get_gmtime();
				}
		 		return $GLOBALS['db']->autoExecute(DB_PREFIX."deal_msg_list",$data); //插入
		 	}
		}
}
/**
 * 发送投资通短信验证码
 * @param $mobile 手机号
 * @param $code  验证码
 */
function send_tzt_verify_sms($mobile, $code){
	$GLOBALS['msg']->manage_msg('TPL_SMS_TZT_VERIFY_CODE',$mobile,array('code'=>$code,'user_id'=>$GLOBALS['user_info']['id']));
}
//发邮件验证码
function send_verify_email($email,$code,$title="")
{
	$GLOBALS['msg']->manage_msg('TPL_MAIL_USER_VERIFY',$email,array('code'=>$code,'title'=>$title));

}

//获取系统运行上传的值
function get_max_file_size(){
	$system_size=intval(ini_get("post_max_size"))<intval(ini_get("upload_max_filesize"))?intval(ini_get("post_max_size"))*1024*1024:intval(ini_get("upload_max_filesize"))*1024*1024;
	$config_size=app_conf("MAX_IMAGE_SIZE");
	$max_size = $system_size>$config_size?$config_size:$system_size;
	//number_format($system_size/(1024*1024),1)
	if($max_size>=1024*1024){
		return number_format($max_size/(1024*1024),1).'MB';
	}elseif($max_size>=1024){
		return number_format($max_size/(1024),1).'KB';
	}else{
		return $max_size.'B';
	}
}

//获取系统运行上传的值
function get_max_file_size_byte(){
	$system_size=intval(ini_get("post_max_size"))<intval(ini_get("upload_max_filesize"))?intval(ini_get("post_max_size"))*1024*1024:intval(ini_get("upload_max_filesize"))*1024*1024;
	$config_size=app_conf("MAX_IMAGE_SIZE");
	$max_size = $system_size>$config_size?$config_size:$system_size;
	return $max_size;
}

function isMobile() {
	// 如果有HTTP_X_WAP_PROFILE则一定是移动设备
	if (isset ($_SERVER['HTTP_X_WAP_PROFILE'])){
		return true;
	}
	//如果via信息含有wap则一定是移动设备,部分服务商会屏蔽该信息
	if (isset ($_SERVER['HTTP_VIA'])) {
		//找不到为flase,否则为true
		return stristr($_SERVER['HTTP_VIA'], "wap") ? true : false;
	}
	//判断手机发送的客户端标志,兼容性有待提高
	if (isset ($_SERVER['HTTP_USER_AGENT'])) {
		$clientkeywords = array (
			'nokia',
			'sony',
			'ericsson',
			'mot',
			'samsung',
			'htc',
			'sgh',
			'lg',
			'sharp',
			'sie-',
			'philips',
			'panasonic',
			'alcatel',
			'lenovo',
			'iphone',
			'ipod',
			'blackberry',
			'meizu',
			'android',
			'netfront',
			'symbian',
			'ucweb',
			'windowsce',
			'palm',
			'operamini',
			'operamobi',
			'openwave',
			'nexusone',
			'cldc',
			'midp',
			'wap',
			'mobile'
		);
		// 从HTTP_USER_AGENT中查找手机浏览器的关键字
		if (preg_match("/(" . implode('|', $clientkeywords) . ")/i", strtolower($_SERVER['HTTP_USER_AGENT']))) {
			return true;
		}
	}
	//协议法，因为有可能不准确，放到最后判断
	if (isset ($_SERVER['HTTP_ACCEPT'])) {
		// 如果只支持wml并且不支持html那一定是移动设备
		// 如果支持wml和html但是wml在html之前则是移动设备
		if ((strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') !== false) && (strpos($_SERVER['HTTP_ACCEPT'], 'text/html') === false || (strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') < strpos($_SERVER['HTTP_ACCEPT'], 'text/html')))) {
			return true;
		}
	}
}
//发起通知用户审核通过或者失败
function send_investor_status($user_info){
	if($user_info['id']){
		$GLOBALS['msg']->manage_msg("MSG_INVEST_STATUS",$user_info['id'],array('user_info'=>$user_info));
	}
}

function get_investor($is_investor){
	switch($is_investor){
		case 0:
			return '普通会员';
			break;
		case 1:
			return '企业会员';
			break;
		/*case 2:
			return '投资机构';
			break;*/
	}
}
function get_investor_status($investor_status){
	switch($investor_status){
		case 0:
			return '未审核';
			break;
		case 1:
			return '待审核';
			break;
		case 2:
			return '审核通过';
			break;
        case 3:
            return '审核未通过';
            break;
	}
}


function LOGIN_DES_KEY(){
	if(!es_session::is_set("DES_KEY")){
		require_once APP_ROOT_PATH."system/utils/es_string.php";
		es_session::set("DES_KEY",es_string::rand_string(50));
	}
	return es_session::get("DES_KEY");
}
//检测手机是否可以绑定
function check_registor_mobile($check_mobile_info,$ajax=1){
	$mobile = $check_mobile_info['mobile'];
	$login_type = $check_mobile_info['login_type'];
	if(strlen($mobile)< 0 || strlen($mobile)== 0){
		$data['status'] = 0;
     	$data['info'] = '请输入手机号码';
	 	ajax_return($data);
	}
	if(!check_mobile($mobile))
	{
		$data['status'] = 0;
     	$data['info'] = '请填写正确的手机号码';
	 	ajax_return($data);
	}
	if(strlen($mobile)>11){
		$data['status'] = 0;
     	$data['info'] = '"手机号码长度不能超过11位';
	 	ajax_return($data);
	}
	$condition=" mobile ='".$mobile."' and login_type=".$login_type;

	$num=$GLOBALS['db']->getOne("select count(*) from  ".DB_PREFIX."user where $condition");
	if($num>0){
		$data['status'] = 0;
     	$data['info'] = '手机已存在,请重新输入';
	 	ajax_return($data);
	}
}
//检测手机是否可以绑定
function check_registor_email($email,$ajax=1){
	if(strlen($email)<=0 ){
		showErr("请输入邮箱",$ajax,"");
	}
	if(!check_email($email))
	{
		showErr("请填写正确的邮箱",$ajax,"");
	}

	$condition=" email='$email'";

	$num=$GLOBALS['db']->getOne("select count(*) from  ".DB_PREFIX."user where $condition");
	if($num>0){
		showErr("邮箱已存在,请重新输入",$ajax,"");
	}
}
/**
 * 验证身份证号
 * @param $vStr
 * @return bool
 */
function isCreditNo($vStr)
{
	$vCity = array(
		'11','12','13','14','15','21','22',
		'23','31','32','33','34','35','36',
		'37','41','42','43','44','45','46',
		'50','51','52','53','54','61','62',
		'63','64','65','71','81','82','91'
	);

	if (!preg_match('/^([\d]{17}[xX\d]|[\d]{15})$/', $vStr)) return false;

	if (!in_array(substr($vStr, 0, 2), $vCity)) return false;

	$vStr = preg_replace('/[xX]$/i', 'a', $vStr);
	$vLength = strlen($vStr);

	if ($vLength == 18)
	{
		$vBirthday = substr($vStr, 6, 4) . '-' . substr($vStr, 10, 2) . '-' . substr($vStr, 12, 2);
	} else {
		$vBirthday = '19' . substr($vStr, 6, 2) . '-' . substr($vStr, 8, 2) . '-' . substr($vStr, 10, 2);
	}

	if (date('Y-m-d', strtotime($vBirthday)) != $vBirthday) return false;
	if ($vLength == 18)
	{
		$vSum = 0;

		for ($i = 17 ; $i >= 0 ; $i--)
		{
			$vSubStr = substr($vStr, 17 - $i, 1);
			$vSum += (pow(2, $i) % 11) * (($vSubStr == 'a') ? 10 : intval($vSubStr , 11));
		}

		if($vSum % 11 != 1) return false;
	}

	return true;
}

//获取来源网站
function set_source_url(){
	if(!es_session::get("source_url")&&!$GLOBALS['user_info']){
		if($_SERVER['HTTP_REFERER']){
			$source_url=$_SERVER['HTTP_REFERER'];
			$url=parse_url($source_url);
			if($url['host']!=$_SERVER['HTTP_HOST']){
				es_session::set("source_url",$url['host']);
			}

		}

	}
}

function get_http()
{
	return (isset($_SERVER['HTTPS']) && (strtolower($_SERVER['HTTPS']) != 'off')) ? 'https://' : 'http://';
}
function get_domain()
{
	/* 协议 */
	$protocol = get_http();

	if(app_conf("SITE_DOMAIN")!="")
	{
		return $protocol.app_conf("SITE_DOMAIN");
	}

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
function get_host()
{


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
		if (isset($_SERVER['SERVER_NAME']))
		{
			$host = $_SERVER['SERVER_NAME'];
		}
		elseif (isset($_SERVER['SERVER_ADDR']))
		{
			$host = $_SERVER['SERVER_ADDR'];
		}
	}
	return $host;
}


/**
 * 将单个图片同步到远程的图片服务器
 * @param string $url 本地的图片地址，"./public/......"
 */
function syn_to_remote_image_server($url,$is_unlink=true)
{
	if($GLOBALS['distribution_cfg']['OSS_TYPE']&&$GLOBALS['distribution_cfg']['OSS_TYPE']!="NONE")
	{
		if($GLOBALS['distribution_cfg']['OSS_TYPE']=="ES_FILE")
		{
			$pathinfo = pathinfo($url);
			$file = $pathinfo['basename'];
			$dir = $pathinfo['dirname'];
			$dir = str_replace("./public/", "", $dir);
			$filefull = SITE_DOMAIN.APP_ROOT."/public/".$dir."/".$file;
			$syn_url = $GLOBALS['distribution_cfg']['OSS_DOMAIN']."/es_file.php?username=".$GLOBALS['distribution_cfg']['OSS_ACCESS_ID']."&password=".$GLOBALS['distribution_cfg']['OSS_ACCESS_KEY']."&file=".
				$filefull."&path=".$dir."/&name=".$file."&act=0";
			@file_get_contents($syn_url);
		}
		elseif($GLOBALS['distribution_cfg']['OSS_TYPE']=="ALI_OSS")
		{
			$pathinfo = pathinfo($url);
			$file = $pathinfo['basename'];
			$dir = $pathinfo['dirname'];
			$dir = str_replace("./public/", "public/", $dir);

			require_once APP_ROOT_PATH."system/alioss/sdk.class.php";
			$oss_sdk_service = new ALIOSS();
			//设置是否打开curl调试模式
			$oss_sdk_service->set_debug_mode(FALSE);

			$bucket = $GLOBALS['distribution_cfg']['OSS_BUCKET_NAME'];
			$object = $dir."/".$file;
			$file_path = APP_ROOT_PATH.$dir."/".$file;

			$oss_sdk_service->upload_file_by_file($bucket,$object,$file_path);

			if($is_unlink&&intval($GLOBALS['distribution_cfg']['OSS_NO_SAVE_LOCALHOST'])==0){
				$info = (array)$oss_sdk_service->is_object_exist($bucket,$object);
				if(file_exists($file_path)&&$info['status']==200)unlink($file_path);
			}

		}
	}

}
function format_image_path($out)
{
	//对图片路径的修复
	if($GLOBALS['distribution_cfg']['OSS_TYPE']&&$GLOBALS['distribution_cfg']['OSS_TYPE']!="NONE")
	{
		$domain = $GLOBALS['distribution_cfg']['OSS_DOMAIN'];
	}
	else
	{
		$domain = SITE_DOMAIN.APP_ROOT;
	}
	$out = str_replace(APP_ROOT."./public/",$domain."/public/",$out);
	$out = str_replace("./public/",$domain."/public/",$out);
	return $out;

}

function replace_public($str){
	//对图片路径的修复
	if($GLOBALS['distribution_cfg']['OSS_TYPE']&&$GLOBALS['distribution_cfg']['OSS_TYPE']!="NONE")
	{
		$domain = $GLOBALS['distribution_cfg']['OSS_DOMAIN'];
	}
	else
	{
		$domain = SITE_DOMAIN.APP_ROOT;
	}

	return str_replace($domain."/public/","./public/",$str);

}
/**
 * 同步脚本样式缓存 $url:'public/runtime/statics/biz/'.$url.'.css';
 * @param unknown_type $url
 */
function syn_to_remote_file_server($url)
{
	if($GLOBALS['distribution_cfg']['OSS_TYPE']&&$GLOBALS['distribution_cfg']['OSS_TYPE']!="NONE")
	{
		if($GLOBALS['distribution_cfg']['OSS_TYPE']=="ES_FILE")
		{
			$pathinfo = pathinfo($url);
			$file = $pathinfo['basename'];
			$dir = $pathinfo['dirname'];
			$dir = str_replace("public/", "", $dir);
			$filefull = SITE_DOMAIN.APP_ROOT."/public/".$dir."/".$file;
			$syn_url = $GLOBALS['distribution_cfg']['OSS_DOMAIN']."/es_file.php?username=".$GLOBALS['distribution_cfg']['OSS_ACCESS_ID']."&password=".$GLOBALS['distribution_cfg']['OSS_ACCESS_KEY']."&file=".
				$filefull."&path=".$dir."/&name=".$file."&act=0";
			@file_get_contents($syn_url);
		}
		elseif($GLOBALS['distribution_cfg']['OSS_TYPE']=="ALI_OSS")
		{
			$pathinfo = pathinfo($url);
			$file = $pathinfo['basename'];
			$dir = $pathinfo['dirname'];

			require_once APP_ROOT_PATH."system/alioss/sdk.class.php";
			$oss_sdk_service = new ALIOSS();
			//设置是否打开curl调试模式
			$oss_sdk_service->set_debug_mode(FALSE);

			$bucket = $GLOBALS['distribution_cfg']['OSS_BUCKET_NAME'];
			$object = $dir."/".$file;
			$file_path = APP_ROOT_PATH.$dir."/".$file;

			$oss_sdk_service->upload_file_by_file($bucket,$object,$file_path);
		}
	}

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
function isios() {
	//判断手机发送的客户端标志,兼容性有待提高
	if (isset ($_SERVER['HTTP_USER_AGENT'])) {
		$clientkeywords = array (
			'iphone',
			'ipod',
			'mac',
		);
		// 从HTTP_USER_AGENT中查找手机浏览器的关键字
		if (preg_match("/(" . implode('|', $clientkeywords) . ")/i", strtolower($_SERVER['HTTP_USER_AGENT']))) {
			return true;
		}
	}
}

//显示成功
function showIpsInfo($msg,$jump='')
{
	$GLOBALS['tmpl']->assign('msg',$msg);
	$GLOBALS['tmpl']->assign('jump',$jump);
	$GLOBALS['tmpl']->display("ips_show.html");
	exit;
}

//日期加减
function dec_date($date,$dec){
	//$sysc_start_time = to_timespan(to_date(to_timespan($date),'Y-m-d')) - $dec * 86400;

	return to_date(to_timespan($date)  - $dec * 86400,'Y-m-d');
}


/**
 * 	作用：将xml转为array
 */
function xmlToArray($xml)
{
	//将XML转为array
	$array_data = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
	return $array_data;
}
/**
 * 	作用：array转xml
 */
function arrayToXml($arr)
{
	$xml = "<xml>";
	foreach ($arr as $key=>$val)
	{
		if (is_numeric($val))
		{
			$xml.="<".$key.">".$val."</".$key.">";

		}
		else
			$xml.="<".$key."><![CDATA[".$val."]]></".$key.">";
	}
	$xml.="</xml>";
	return $xml;
}
function  log_result($word)
{
	if (is_array($word)) $word = var_export($word,true);
	$file = APP_ROOT_PATH."/public/notify_url.log";
	$fp = fopen($file,"a");
	flock($fp, LOCK_EX) ;
	fwrite($fp,"执行日期：".strftime("%Y-%m-%d-%H：%M：%S",time())."\n".$word."\n\n");
	flock($fp, LOCK_UN);
	fclose($fp);
}
function  log_result_wx($word)
{
	if (is_array($word)) $word = var_export($word,true);
	$file = APP_ROOT_PATH."/public/notify_url_wx.log";
	$fp = fopen($file,"a");
	flock($fp, LOCK_EX) ;
	fwrite($fp,"执行日期：".strftime("%Y-%m-%d-%H：%M：%S",time())."\n".$word."\n\n");
	flock($fp, LOCK_UN);
	fclose($fp);
}

function  log_result_wx_pay_log($word)
{
    if (!is_dir(APP_ROOT_PATH."public/wx_pay_log")) {
        @mkdir(APP_ROOT_PATH."public/wx_pay_log");
    }
    $file = APP_ROOT_PATH."/public/wx_pay_log/".to_date(get_gmtime()-604800,"Ymd").".log";
    if(file_exists($file)){
        unlink($file);
    }

    $filename = to_date(get_gmtime(),"Ymd");
    if (is_array($word)) $word = var_export($word,true);
    $file = APP_ROOT_PATH."/public/wx_pay_log/".$filename.".log";
    $fp = fopen($file,"a");
    flock($fp, LOCK_EX) ;
    fwrite($fp,"执行日期：".strftime("%Y-%m-%d-%H：%M：%S",time())."\n".$word."\n\n");
    flock($fp, LOCK_UN);
    fclose($fp);
}

/**
 * 分页处理
 * @param string $type 所在页面
 * @param array  $args 参数
 * @param int $total_count 总数
 * @param int $page 当前页
 * @param int $page_size 分页大小
 * @param string $url 自定义路径
 * @param int $offset 偏移量
 * @return array
 */
function buildPage($type,$args,$total_count,$page = 1,$page_size = 0,$url='',$offset = 5){
	$pager['total_count'] = intval($total_count);
	$pager['page'] = $page;
	$pager['page_size'] = ($page_size == 0) ? 20 : $page_size;
	/* page 总数 */
	$pager['page_count'] = ($pager['total_count'] > 0) ? ceil($pager['total_count'] / $pager['page_size']) : 1;

	/* 边界处理 */
	if ($pager['page'] > $pager['page_count'])
		$pager['page'] = $pager['page_count'];

	$pager['limit'] = ($pager['page'] - 1) * $pager['page_size'] . "," . $pager['page_size'];
	$page_prev  = ($pager['page'] > 1) ? $pager['page'] - 1 : 1;
	$page_next  = ($pager['page'] < $pager['page_count']) ? $pager['page'] + 1 : $pager['page_count'];
	$pager['prev_page'] = $page_prev;
	$pager['next_page'] = $page_next;

	if (!empty($url)){
		$pager['page_first'] = $url . 1;
		$pager['page_prev']  = $url . $page_prev;
		$pager['page_next']  = $url . $page_next;
		$pager['page_last']  = $url . $pager['page_count'];
	}
	else{
		$args['page'] = '_page_';
		if(!empty($type)){
			if(strpos($type,'javascript:') === false){
				//$page_url = JKU($type,$args);
				$page_url = u($type,$args);
			}else{
				$page_url = $type;

			}
		}else{
			$page_url = 'javascript:;';
		}
		$pager['page_first'] = str_replace('_page_',1,$page_url);
		$pager['page_prev']  = str_replace('_page_',$page_prev,$page_url);
		$pager['page_next']  = str_replace('_page_',$page_next,$page_url);
		$pager['page_last']  = str_replace('_page_',$pager['page_count'],$page_url);
	}
	$pager['page_nums'] = array();
	if($pager['page_count'] <= $offset * 2){
		for ($i=1; $i <= $pager['page_count']; $i++){
			$pager['page_nums'][] = array('name' => $i,'url' => empty($url) ? str_replace('_page_',$i,$page_url) : $url . $i);
		}
	}else{
		if($pager['page'] - $offset < 2){
			$temp = $offset * 2;
			for ($i=1; $i<=$temp; $i++){
				$pager['page_nums'][] = array('name' => $i,'url' => empty($url) ? str_replace('_page_',$i,$page_url) : $url . $i);
			}
			$pager['page_nums'][] = array('name'=>'...');
			$pager['page_nums'][] = array('name' => $pager['page_count'],'url' => empty($url) ? str_replace('_page_',$pager['page_count'],$page_url) : $url . $pager['page_count']);
		}else{
			$pager['page_nums'][] = array('name' => 1,'url' => empty($url) ? str_replace('_page_',1,$page_url) : $url . 1);
			$pager['page_nums'][] = array('name'=>'...');
			$start = $pager['page'] - $offset + 1;
			$end = $pager['page'] + $offset - 1;
			if($pager['page_count'] - $end > 1){
				for ($i=$start;$i<=$end;$i++){
					$pager['page_nums'][] = array('name' => $i,'url' => empty($url) ? str_replace('_page_',$i,$page_url) : $url . $i);
				}

				$pager['page_nums'][] = array('name'=>'...');
				$pager['page_nums'][] = array('name' => $pager['page_count'],'url' => empty($url) ? str_replace('_page_',$pager['page_count'],$page_url) : $url . $pager['page_count']);
			}else{
				$start = $pager['page_count'] - $offset * 2 + 1;
				$end = $pager['page_count'];
				for ($i=$start;$i<=$end;$i++){
					$pager['page_nums'][] = array('name' => $i,'url' => empty($url) ? str_replace('_page_',$i,$page_url) : $url . $i);
				}
			}
		}
	}
	return $pager;
}

function parse_url_tag_coomon($str)
{
	$str = substr($str,2);
	$str_array = explode("|",$str);
	$route = $str_array[0];
	$param_tmp = explode("&",$str_array[1]);
	$param = array();
	foreach($param_tmp as $item)
	{
		if($item!='')
			$item_arr = explode("=",$item);
		if($item_arr[0]&&$item_arr[1])
			$param[$item_arr[0]] = $item_arr[1];
	}
	return url($route,$param);
}

//解析URL标签
// $str = u:acate#index|id=10&name=abc
function parse_url_tag($str)
{
	$key = md5("URL_TAG_".$str);
	if(isset($GLOBALS[$key]))
	{
		return $GLOBALS[$key];
	}

	$url = load_dynamic_cache($key);
	if($url!==false)
	{
		$GLOBALS[$key] = $url;
		return $url;
	}
	$str = substr($str,2);
	$str_array = explode("|",$str);
	$route = $str_array[0];
	$param_tmp = explode("&",$str_array[1]);
	$param = array();
	foreach($param_tmp as $item)
	{
		if($item!='')
			$item_arr = explode("=",$item);
		if($item_arr[0]&&$item_arr[1])
			$param[$item_arr[0]] = $item_arr[1];
	}
	$GLOBALS[$key]= url($route,$param);
	set_dynamic_cache($key,$GLOBALS[$key]);
	return $GLOBALS[$key];
}

function parse_url_tag_wap($str)
{
	$key = md5("URL_TAG_".$str);
	if(isset($GLOBALS[$key]))
	{
		return $GLOBALS[$key];
	}

	$url = load_dynamic_cache($key);
	if($url!==false)
	{
		$GLOBALS[$key] = $url;
		return $url;
	}
	$str = substr($str,2);
	$str_array = explode("|",$str);
	$route = $str_array[0];
	$param_tmp = explode("&",$str_array[1]);
	$param = array();
	foreach($param_tmp as $item)
	{
		if($item!='')
			$item_arr = explode("=",$item);
		if($item_arr[0]&&$item_arr[1])
			$param[$item_arr[0]] = $item_arr[1];
	}
	$GLOBALS[$key]= url_wap($route,$param);
	set_dynamic_cache($key,$GLOBALS[$key]);
	return $GLOBALS[$key];
}

function HASH_KEY(){
	if(!es_session::is_set("HASH_KEY")){
		require_once APP_ROOT_PATH."system/utils/es_string.php";
		es_session::set("HASH_KEY",es_string::rand_string(50));
	}
	return es_session::get("HASH_KEY");
}

function check_hash_key(){
	if(strim($_REQUEST['fhash'])!="" && md5(HASH_KEY())==md5($_REQUEST['fhash'])){
		return true;
	}
	else
		return false;
}
function number_price_format($price)
{
	if($price*100%100==0)
		$price= number_format(round($price,2));
	else
		$price = number_format(round($price,2),2);
	return $price;
}


/** 获取当前时间戳，精确到毫秒 */
function microtime_float()
{
	list($usec, $sec) = explode(" ", microtime());
	return ((float)$usec + (float)$sec-date('Z'));
}

/** 格式化时间戳，精确到毫秒，x代表毫秒 */
function microtime_format($utc_time,  $format = 'H:i:s.x')
{
	if (empty ( $utc_time )) {
		return '';
	}

	$timezone = intval(app_conf('TIME_ZONE'));
	$time = $utc_time + $timezone * 3600;

	list($usec, $sec) = explode(".", $time);
	$date = date($format,$usec);
	return str_replace('x', $sec, $date);
}
function trim_utf8mb4($str){
	return  preg_replace('/[\x{10000}-\x{10FFFF}]/u', '',$str);
}
/**
 * 只保留字符串首尾字符，隐藏中间用*代替（两个字符时只显示第一个）
 * @param string $user_name 姓名
 * @return string 格式化后的姓名
 */
function substr_cut($user_name){
	$strlen     = mb_strlen($user_name, 'utf-8');
	$firstStr     = mb_substr($user_name, 0, 1, 'utf-8');
	$lastStr     = mb_substr($user_name, -1, 1, 'utf-8');
	return $strlen == 2 ? $firstStr . str_repeat('*', mb_strlen($user_name, 'utf-8') - 1) : $firstStr . str_repeat("*", $strlen - 2) . $lastStr;
}
/*
 * is_wap 0 表示wep 1表示wap 2表示APP
 */
function create_target_url($target,$is_wap = 0,$cart_id=''){
	$return_url = "";
	if(strpos($target,'URL-dealID-')!==FALSE){
		$deal_id = trim($target,'URL-dealID-');
		if($cart_id!=''){
			$id = trim($cart_id,'URL-cartID-');
		}
		if($is_wap==0){
			$return_url = url("deal#show",array('id'=>$deal_id));
		}elseif($is_wap==1){
			if($id){
				$return_url = url_wap("cart#index",array('id'=>$id,'deal_id'=>$deal_id));
			}else{
				$return_url = url_wap("deal#show",array('id'=>$deal_id));
			}
		}
	}
	return $return_url;
}

//验证网址
function check_url($url)
{
	$patern ='/^http[s]?:\/\/'.
		'(([0-9]{1,3}\.){3}[0-9]{1,3}'. // IP形式的URL- 199.194.52.184
		'|'. // 允许IP和DOMAIN（域名）
		'([0-9a-z_!~*\'()-]+\.)*'. // 域名- www.
		'([0-9a-z][0-9a-z-]{0,61})?[0-9a-z]\.'. // 二级域名
		'[a-z]{2,6})'.  // first level domain- .com or .museum
		'(:[0-9]{1,4})?'.  // 端口- :80
		'((\/\?)|'.  // a slash isn't required if there is no file name
		'(\/[0-9a-zA-Z_!~\'\(\)\[\]\.;\?:@&=\+\$,%#-\/^\*\|]*)?)$/';
	if(!empty($url) && !preg_match($patern,$url))
	{
		return false;
	}
	else
		return true;
}
/*
 * 转成元
 * $money 金额
 */
function transform_yuan($money){
	$money =intval($money);
	if($money){
		return $money * 10000;
	}
}
/*
 * 元转成逆转万元
 * $money 金额
 */
function transform_wan($money){
	$money =intval($money);
	if($money){
		return $money/10000;
	}
}


function create_app_js($app_conf){
	$node_app=APP_ROOT_PATH."public/node_app.js";
	if(is_file($node_app)){
		$content=file_get_contents($node_app);
		$url = get_domain().APP_ROOT;
		$content=str_replace("{domain}",$url,$content);
		if($app_conf['IS_SMS_DIRECT']==0){
			$deal_msg_list= 'true';
		}else{
			$deal_msg_list= 'false';
		}
		$content=str_replace("{deal_msg_list}",$deal_msg_list,$content);
		$time = $app_conf['SEND_SPAN']?$app_conf['SEND_SPAN']*1000:500;
		$content=str_replace("{time}",$time,$content);

		$app=APP_ROOT_PATH."public/app.js";
		file_put_contents($app,$content);
	}
}



/*  微信提现
 * */
function wx_withdraw_cash($refund_id){
	$msg_item = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."user_refund where id = ".$refund_id." and is_pay =1 ");
	$order_id = $msg_item['id'];
	$user_info = $GLOBALS['db']->getRow("select gz_openid,nick_name from ".DB_PREFIX."user where id=".$msg_item['user_id']);
	$open_id = $user_info['gz_openid'];
	//过滤中文支付 防止微信发送红包失败 //@、#、【】、
	$user_name = strFilter($user_info['nick_name']);
	$ticket = $msg_item['ticket'];

	if($msg_item)
	{
		$payment_info = $GLOBALS['db']->getRow("select id,config,logo from ".DB_PREFIX."payment where class_name='Wwxjspay'");
		$payment_info['config'] = unserialize($payment_info['config']);
		$wx_config=$payment_info['config'];

		$mch_appid=$wx_config['appid'];
		$mchid=$wx_config['mchid'];//商户号
		$nonce_str='qyzf'.rand(100000, 999999);//随机数
		$partner_trade_no='wx'.$order_id.time().rand(10000, 99999);;//商户订单号

		$openid=$open_id;//用户唯一标识
		$check_name='NO_CHECK';//校验用户姓名选项，NO_CHECK：不校验真实姓名 FORCE_CHECK：强校验真实姓名（未实名认证的用户会校验失败，无法转账）OPTION_CHECK：针对已实名认证的用户才校验真实姓名（未实名认证用户不校验，可以转账成功）
		$re_user_name=$user_name;//用户姓名

		$m_config =  load_auto_cache("m_config");//初始化手机端配置
		//$amount=intval(floatval($ticket*$m_config['exchange_rate'])*100);//金额（以分为单位，必须大于100）
        $amount = floatval($msg_item['money']*100);

		$desc='红包';//描述
		require_once APP_ROOT_PATH.'system/extend/ip.php';
		$iplocation = new iplocate();
		$spbill_create_ip = $iplocation->getIP();
//封装成数据
		$dataArr=array();
		$dataArr['amount']=$amount;
		$dataArr['check_name']=$check_name;
		$dataArr['desc']=$desc;
		$dataArr['mch_appid']=$mch_appid;
		$dataArr['mchid']=$mchid;
		$dataArr['nonce_str']=$nonce_str;
		$dataArr['openid']=$openid;
		$dataArr['partner_trade_no']=$partner_trade_no;
		$dataArr['re_user_name']=$re_user_name;
		$dataArr['spbill_create_ip']=$spbill_create_ip;
		$sign=getSign($dataArr,$wx_config['key']);

		$data="<xml>
<mch_appid>".$mch_appid."</mch_appid>
<mchid>".$mchid."</mchid>
<nonce_str>".$nonce_str."</nonce_str>
<partner_trade_no>".$partner_trade_no."</partner_trade_no>
<openid>".$openid."</openid>
<check_name>".$check_name."</check_name>
<re_user_name>".$re_user_name."</re_user_name>
<amount>".$amount."</amount>
<desc>".$desc."</desc>
<spbill_create_ip>".$spbill_create_ip."</spbill_create_ip>
<sign>".$sign."</sign>
</xml>";
		if(intval(OPEN_TEST_WX)) {
			log_result("==微信提现_调试1-提交到微信的xml数据=");
			log_result($data);
		}
		$ch = curl_init ();
		$MENU_URL="https://api.mch.weixin.qq.com/mmpaymkttransfers/promotion/transfers";
		curl_setopt ( $ch, CURLOPT_URL, $MENU_URL );
		curl_setopt ( $ch, CURLOPT_CUSTOMREQUEST, "POST" );
		curl_setopt ( $ch, CURLOPT_SSL_VERIFYPEER, FALSE );
		curl_setopt ( $ch, CURLOPT_SSL_VERIFYHOST, FALSE );

//		$zs1=APP_ROOT_PATH."public/weixin/apiclient_cert.pem";
//		$zs2=APP_ROOT_PATH."public/weixin/apiclient_key.pem";
		$zs1=APP_ROOT_PATH.$wx_config['sslcert'];
		$zs2=APP_ROOT_PATH.$wx_config['sslkey'];
		curl_setopt($ch,CURLOPT_SSLCERT,$zs1);
		curl_setopt($ch,CURLOPT_SSLKEY,$zs2);
// curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 5.01;
// Windows NT 5.0)');
		curl_setopt ( $ch, CURLOPT_FOLLOWLOCATION, 1 );
		curl_setopt ( $ch, CURLOPT_AUTOREFERER, 1 );
		curl_setopt ( $ch, CURLOPT_POSTFIELDS, $data );
		curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, true );

		$info = curl_exec ( $ch );

		if (curl_errno ( $ch )) {
			log_result_wx('Errno' . curl_error ( $ch ));
		}

		curl_close ( $ch );
		if(intval(OPEN_TEST_WX)){
			log_result("==微信提现_调试2-微信curl回调的info数据=");
			log_result($info);
		}


		$return = (array)simplexml_load_string($info, 'SimpleXMLElement', LIBXML_NOCDATA);
		if(intval(OPEN_TEST_WX)){
			log_result("==微信提现_调试3-curl回调解析成array数据=");
			log_result($return);
		}
		if($return['return_code']=='SUCCESS'&&$return['result_code']=='SUCCESS'){
			$refund_data = array();
			$refund_data = M("UserRefund")->getById($refund_id);
			$refund_data['pay_log'] ='已付款';
			$refund_data['is_pay'] =3;
			$refund_data['partner_trade_no'] =$partner_trade_no;
            $refund_data['ybdrawflowid'] =$return['payment_no'];
			$refund_data['pay_time'] =get_gmtime();
			$refund_data['confirm_cash_ip'] = get_client_ip();
			M("UserRefund")->save($refund_data);
			$res_up_m = M("UserRefund")->save($refund_data);
			if(intval(OPEN_TEST_WX)){
				log_result("==微信提现_调试4-refund_data=");
				log_result($refund_data);
				log_result(M("UserRefund")->GetLastSql());
			}
			if(!$res_up_m){
				$sql = "update ".DB_PREFIX."user_refund set pay_log='已付款',is_pay = 3,partner_trade_no = '".$partner_trade_no."',ybdrawflowid='".$return['payment_no']."',pay_time = ".get_gmtime().",confirm_cash_ip = '".get_client_ip()."' where id = ".$order_id;
				$res = $GLOBALS['db']->query($sql);
			}
			$payment_data = array();
			$payment_data = M("Payment")->getById($payment_info['id']);
			$payment_data['total_amount'] =$payment_data['total_amount']+$msg_item['money'];
			$res_p_m = M("Payment")->save($payment_data);
			if(intval(OPEN_TEST_WX)){
				log_result("==微信提现_调试5-payment_data=");
				log_result($payment_data);
				log_result(M("Payment")->GetLastSql());
			}
			if(!$res_p_m){
				$sql = "update ".DB_PREFIX."payment set total_amount = total_amount + ".$msg_item['money']." where id = ".$payment_info['id'];
				$res = $GLOBALS['db']->query($sql);
			}
			return true;
		}else{
			$refund_data = array();
			$refund_data = M("UserRefund")->getById($order_id);
			$refund_data['pay_log'] =$return['return_msg'].$return['err_code_des'];
			$refund_data['partner_trade_no'] =$partner_trade_no;
			$refund_data['is_pay'] = 4;
			$refund_data['confirm_cash_ip'] = get_client_ip();
			$res_up_m = M("UserRefund")->save($refund_data);
			if(intval(OPEN_TEST_WX)){
				log_result("==微信提现_调试6-=");
				log_result(M("UserRefund")->GetLastSql());
			}
			if(!$res_up_m){
				$GLOBALS['db']->query("update ".DB_PREFIX."user_refund set confirm_cash_ip = '".get_client_ip()."' is_pay=4,pay_log = '".$return['return_msg']."',partner_trade_no = '".$partner_trade_no."' where id = ".$order_id);
			}
			return false;
		}
	}
}


/**
 * 	作用：生成签名
 */
function getSign($Obj,$key)
{
	//var_dump($Obj);//die;
	foreach ($Obj as $k => $v)
	{
		$Parameters[$k] = $v;
	}
	//签名步骤一：按字典序排序参数
	ksort($Parameters);
	$String = formatBizQueryParaMap($Parameters, false);
	//echo '【string1】'.$String.'</br>';
	//签名步骤二：在string后加入KEY
	$String = $String."&key=".$key;
	//echo "【string2】".$String."</br>";
	//签名步骤三：MD5加密
	$String = md5($String);
	//echo "【string3】 ".$String."</br>";
	//签名步骤四：所有字符转为大写
	$result_ = strtoupper($String);
	//echo "【result】 ".$result_."</br>";
	return $result_;
}

/**
 * 	作用：格式化参数，签名过程需要使用
 */
function formatBizQueryParaMap($paraMap, $urlencode)
{
	$buff = "";
	ksort($paraMap);
	foreach ($paraMap as $k => $v)
	{
		if($urlencode)
		{
			$v = urlencode($v);
		}
		//$buff .= strtolower($k) . "=" . $v . "&";
		$buff .= $k . "=" . $v . "&";
	}
	//$reqPar;
	if (strlen($buff) > 0)
	{
		$reqPar = substr($buff, 0, strlen($buff)-1);
	}
	return $reqPar;
}
/**
 * 获得查询次数以及查询时间
 *
 * @access  public
 * @return  string
 */
function run_info()
{

	if(!SHOW_DEBUG)return "";

	$query_time = number_format($GLOBALS['db']->queryTime,6);

	if($GLOBALS['begin_run_time']==''||$GLOBALS['begin_run_time']==0)
	{
		$run_time = 0;
	}
	else
	{
		if (PHP_VERSION >= '5.0.0')
		{
			$run_time = number_format(microtime(true) - $GLOBALS['begin_run_time'], 6);
		}
		else
		{
			list($now_usec, $now_sec)     = explode(' ', microtime());
			list($start_usec, $start_sec) = explode(' ', $GLOBALS['begin_run_time']);
			$run_time = number_format(($now_sec - $start_sec) + ($now_usec - $start_usec), 6);
		}
	}

	/* 内存占用情况 */
	if (function_exists('memory_get_usage'))
	{
		$unit=array('B','KB','MB','GB');
		$size = memory_get_usage();
		$used = @round($size/pow(1024,($i=floor(log($size,1024)))),2).' '.$unit[$i];
		$memory_usage = "占用内存 ".$used;
	}
	else
	{
		$memory_usage = '';
	}

	/* 是否启用了 gzip */
	$enabled_gzip = (app_conf("GZIP_ON") && function_exists('ob_gzhandler'));
	$gzip_enabled = $enabled_gzip ? "gzip开启" : "gzip关闭";

	$str = '共执行 '.$GLOBALS['db']->queryCount.' 个查询，用时 '.$query_time.' 秒，'.$gzip_enabled.'，'.$memory_usage.'，程序执行时间 '.$run_time.' 秒';

	foreach($GLOBALS['db']->queryLog as $K=>$sql)
	{
		if($K==0)$str.="<br />SQL语句列表：";
		$str.="<br />行".($K+1).":".$sql;
	}

	return "<div style='width:940px; padding:10px; line-height:22px; border:1px solid #ccc; text-align:left; margin:30px auto; font-size:14px; color:#999; height:150px; overflow-y:auto;'>".$str."</div>";
}


function update_sys_config()
{
	$filename = APP_ROOT_PATH."public/sys_config.php";
	if(!file_exists($filename))
	{
		//定义DB
		require APP_ROOT_PATH.'system/db/db.php';
		$dbcfg = require APP_ROOT_PATH."public/db_config.php";
		define('DB_PREFIX', $dbcfg['DB_PREFIX']);
		if(!file_exists(APP_ROOT_PATH.'public/runtime/app/db_caches/'))
			mkdir(APP_ROOT_PATH.'public/runtime/app/db_caches/',0777);
		$pconnect = false;
		$db = new mysql_db($dbcfg['DB_HOST'].":".$dbcfg['DB_PORT'], $dbcfg['DB_USER'],$dbcfg['DB_PWD'],$dbcfg['DB_NAME'],'utf8',$pconnect);
		//end 定义DB

		$sys_configs = $db->getAll("select * from ".DB_PREFIX."conf");
		$config_str = "<?php\n";
		$config_str .= "return array(\n";
		foreach($sys_configs as $k=>$v)
		{
			$config_str.="'".$v['name']."'=>'".addslashes($v['value'])."',\n";
		}
		$config_str.=");\n ?>";
		file_put_contents($filename,$config_str);
		$url = APP_ROOT."/";
		app_redirect($url);
	}
}
function get_admin_nav($role_id,$adm_name){
	if(CHANGE_NAV=='default'||!defined('CHANGE_NAV')){
		if($adm_name == app_conf('DEFAULT_ADMIN')){
			if(defined("MODULE_ADMIN")&&MODULE_ADMIN==1){
				$navs = require_once APP_ROOT_PATH."system/admnav_cfg_pc.php";
			}else{
				$navs = require_once APP_ROOT_PATH."system/admnav_cfg.php";
			}
		}else{
			$navs = load_auto_cache("admin_nav",array('id'=>$role_id));
		}

		if($_REQUEST['change_nav']){
			$navs = require_once APP_ROOT_PATH."system/admnav_cfg_".$_REQUEST['change_nav'].".php";
		}
	}else{
		$navs = require_once APP_ROOT_PATH."system/admnav_cfg_".CHANGE_NAV.".php";
	}

	return deal_admin_nav($navs);
}

function deal_admin_nav($navs){
	if(!defined("OPEN_FAMILY_MODULE")||OPEN_FAMILY_MODULE==0){
		unset($navs['user']['groups']['family']);
	}
    if(!defined("OPEN_SOCIETY_MODULE")||OPEN_SOCIETY_MODULE==0){
        unset($navs['user']['groups']['society']);
    }
	if(!defined("OPEN_LUCK_NUM")||OPEN_LUCK_NUM==0){
		unset($navs['system']['groups']['lucknum']);
	}
	if(!defined("OPEN_ADS")||OPEN_ADS==0){
		unset($navs['system']['groups']['ads']);
	}
	if(intval(OPEN_SLBGROUP)==0){
		unset($navs['system']['groups']['slbgroupconf']);
	}
	if (!defined("OPEN_GAME_MODULE")||OPEN_GAME_MODULE==0) {
		unset($navs['PlugIn']['groups']['gameconf']);
	} else {
		if (!defined("OPEN_BANKER_MODULE")||OPEN_BANKER_MODULE==0) {
			foreach ($navs['PlugIn']['groups']['gameconf']['nodes'] as $key => $value) {
				if ($value['action']=='bankerLog') {
					unset($navs['PlugIn']['groups']['gameconf']['nodes'][$key]);
				}
			}
		}
		if (defined('OPEN_DIAMOND_GAME_MODULE') && OPEN_DIAMOND_GAME_MODULE == 1) {
			foreach ($navs['PlugIn']['groups']['gameconf']['nodes'] as $key => $value) {
				$navs['PlugIn']['groups']['gameconf']['nodes'][$key]['name'] = str_replace('金币', '钻石', $value['name']);
			}
		}
	}

	if (!defined("SHOPPING_GOODS")||SHOPPING_GOODS==0) {
		unset($navs['PlugIn']['groups']['goodsconf']);
		unset($navs['PlugIn']['groups']['user_goodsconf']);
	}
	if((!defined("OPEN_PAI_MODULE")||OPEN_PAI_MODULE==0) && (!defined("SHOPPING_GOODS")||SHOPPING_GOODS==0)){
		unset($navs['PlugIn']['groups']['goods_complaint']);
	}
	if (!defined("OPEN_PAI_MODULE")||OPEN_PAI_MODULE==0) {
		unset($navs['PlugIn']['groups']['pai_goods']);
		unset($navs['PlugIn']['groups']['goods_order']);
	}
	if(!defined("OPEN_PODCAST_GOODS")||OPEN_PODCAST_GOODS==0){
		foreach ($navs['PlugIn']['groups']['user_goodsconf']['nodes'] as $key => $value) {
			if ($value['module']=='PodcastGoods') {
				unset($navs['PlugIn']['groups']['user_goodsconf']['nodes'][$key]);
			}
		}
	}
	if(!defined("PAI_VIRTUAL_BTN")||PAI_VIRTUAL_BTN==0){
		foreach ($navs['PlugIn']['groups']['goods_order']['nodes'] as $key => $value) {
			if ($value['module']=='PaiTags') {
				unset($navs['PlugIn']['groups']['goods_order']['nodes'][$key]);
			}
		}
	}
    if (!defined("OPEN_EDU_MODULE")||OPEN_EDU_MODULE==0) {
		unset($navs['edu_courses']);
	}
    $m_config =  load_auto_cache("m_config");//初始化手机端配置
    if($m_config['name_limit']==0){
        unset($navs['system']['groups']['mobile']['nodes'][2]);
    }
    if($m_config['has_dirty_words']==0){
        unset($navs['system']['groups']['mobile']['nodes'][1]);
    }
    if(!defined("OPEN_VIP")||OPEN_VIP==0){
        unset($navs['system']['groups']['sysconf']['nodes'][5]);
    }
	if(!defined("OPEN_DISTRIBUTION")||OPEN_DISTRIBUTION==0){
		unset($navs['user']['groups']['distribution']);
	}
	if(!defined("CHECK_VIDEO")||CHECK_VIDEO==0){
		unset($navs['dealcate']['groups']['video']['nodes'][5]);
	}
	return $navs;
}
/*
 * @return 删除过期的验证码
 */
function delete_mobile_verify_code(){
	$time=app_conf("USER_SEND_VERIFY_TIME")?app_conf("USER_SEND_VERIFY_TIME"):300;
	$n_time=get_gmtime()-$time;
	//删除超过时间的验证码
	$GLOBALS['db']->query("DELETE FROM ".DB_PREFIX."mobile_verify_code WHERE create_time <=".$n_time);
}

function theme_parse_css($urls)
{

	$url = md5(implode(',',$urls));
	$css_url = 'public/runtime/statics/'.$url.'.css';
	$url_path = APP_ROOT_PATH.$css_url;
	if(!file_exists($url_path))
	{
		if(!file_exists(APP_ROOT_PATH.'public/runtime/statics/'))
			mkdir(APP_ROOT_PATH.'public/runtime/statics/',0777);
		$tmpl_path = get_domain().APP_ROOT."/theme";

		$css_content = '';
		foreach($urls as $url)
		{
			$css_content .= @file_get_contents($url);
		}
		$css_content = preg_replace("/[\r\n]/",'',$css_content);
		$css_content = str_replace("../../images",$tmpl_path."/images/",$css_content);
		$css_content = str_replace("../images/",$tmpl_path."/images/",$css_content);
//		@file_put_contents($url_path, unicode_encode($css_content));
		@file_put_contents($url_path, $css_content);
	}
	return get_domain().APP_ROOT."/".$css_url."?v=1.0";
}
//添加图片前缀
function add_domain_url($image){
	if(strpos($image, 'http://')===false){
		$image = str_replace('images/',file_domain().'/theme/images/',$image);
		$image = str_replace('./public/',file_domain().'/public/',$image);

	}
	return $image;
}
//删除图片前缀
function del_domain_url($image){
    if(strchr($image,'http://')!==false){

        $image = str_replace(file_domain().'/public/','./public/',$image);
        /*$index = strpos($image,'/public');
        $image = '.'.substr($image,$index);*/
    }
    return $image;
}
//检查会员等级
/*function chack_grade($user_id){
	//$user_info= $GLOBALS['db']->getRow("select u.score,u.online_time,u.user_level from ".DB_PREFIX."user as u where u.id=".$user_id);
	//redis 读取
	require_once(APP_ROOT_PATH.'mapi/lib/redis/BaseRedisService.php');
    require_once(APP_ROOT_PATH.'mapi/lib/redis/UserRedisService.php');
	$user_redis = new UserRedisService();
	$user_info = $user_redis->getRow_db($user_id,array('score','online_time','user_level'));

	$up_score = $user_info['score']+floor($user_info['online_time']/app_conf('ONLINETIME_TO_EXPERIENCE'));
 	$level_info = $GLOBALS['db']->getRow("select ul.name as level_name,ul.level as level,ul.icon as icon from ".DB_PREFIX."user_level as ul where  ul.score<=".$up_score." ORDER BY ul.id DESC limit 0,1");
	if(intval($user_id)>0&&$user_info['user_level']<$level_info['level']){
		$status =$GLOBALS['db']->query("update ".DB_PREFIX."user set user_level=".$level_info['level']." where id=".$user_id);
		//redis 更新
		$user['user_level'] = $level_info['level'];
		$status = $user_redis->update_db($user_id,$user);
	}
}*/

function agentArr(){
	$agent = $_SERVER['HTTP_USER_AGENT'];
	$agent_array = array();
	if($agent){
		$agent_arr = explode(" ",$agent);
		foreach($agent_arr as $k=>$v){
			$kkv = explode("/",$v);
			$agent_array[$kkv[0]] = strim($kkv[1]);
		}
	}
	return $agent_array;
}

/**
 * 散列算法
 * @param unknown_type $value  计算散列的基础值
 * @param unknown_type $count  散列的总基数
 * @return number
 */

function hash_table($value,$count)
{
	$pid = intval(round(hexdec(md5($value))/pow(10,32))%$count);
	return $pid;
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
//发登录超时短信
function send_tips_sms($mobile,$overtime)
{
	return $GLOBALS['msg']->sms_tips($mobile,$overtime);
}


//获取一个新的用户号,同时记录分配给那个系统使用
function get_max_user_id($sysid=0){
	$user_id = get_max_user_id_fun($sysid);

    if (OPEN_LUCK_NUM == 1){//如果开启了新版靓号功能
        if(check_luck_num($user_id)){
            //重新生成
            $user_id = get_max_user_id($sysid);
        }
    }else {//没开启靓号，继续使用最初版幸运号逻辑
        if (chack_lucky_num($user_id)) {
            $data=array();
            $data['id'] = $user_id ;
            $data['nick_name']= "系统保留吉祥号";
            $data['is_effect'] = 1;
            $data['sex']= 1;
            $data['create_time']= NOW_TIME;
            $data['user_pwd']= md5(rand(100000,999999));
            $data['login_ip'] = CLIENT_IP;
            $data['login_time'] = to_date(NOW_TIME);
            $data['synchronize'] = 0;
            $data['emotional_state'] ='保密';
            if($data['city']==''&&$data['province']==''){
                $data['province'] = '火星';
            }
            $data['job'] = '主播';
            $data['user_level'] = 1;
            $GLOBALS['db']->autoExecute(DB_PREFIX."user",$data);
            $user_info = $data;
//                ===========add  start ===========
//                log_result('==get_max_user_id ==='.$user_id);
//                log_result($data);
            fanwe_require (APP_ROOT_PATH.'mapi/lib/redis/UserRedisService.php');
            $user_redis = new UserRedisService();
            $ridis_data = $user_redis->reg_data($data);
            $user_redis->insert_db($user_id,$ridis_data);
            $user_info = $data;
//                登录成功 同步信息
            accountimport($user_info);
        }
    }
	return $user_id;
}
//获取一个新的用户号,同时记录分配给那个系统使用
function get_max_user_id_fun($sysid=0){
	$sql = "insert into ".DB_PREFIX."user_id (id,sysid) values(0,$sysid)";
	$GLOBALS['db']->query($sql);
	$user_id = $GLOBALS['db']->insert_id();
	return $user_id;
}
//判断 吉祥号
function chack_lucky_num($user_id=0){
	$m_config =  load_auto_cache("m_config");
	$lucky_num	= explode(",",$m_config['lucky_num']);
	return in_array($user_id, $lucky_num);
}

//判断id是否是靓号
function check_luck_num($user_id=0){
	$sql = "SELECT * FROM ".DB_PREFIX."luck_num where luck_num = ".$user_id;
	$result = array();
	$result = $GLOBALS['db']->getRow($sql,true,true);
	return $result;
}


function  log_file($word,$file_name='log_file')
{
	if(!IS_DEBUG){
		return false;
	}
	$file = APP_ROOT_PATH."/public/".$file_name.".log";

	if (is_array($word)) $word = var_export($word,true);

	$fp = fopen($file,"a");
	flock($fp, LOCK_EX) ;
	fwrite($fp,"执行日期：".strftime("%Y-%m-%d-%H：%M：%S",time())."\n".$word."\n\n");
	flock($fp, LOCK_UN);
	fclose($fp);
}


function get_abs_img_root($content)
{

    return str_replace("./public/",file_domain()."/public/",$content);
    //return str_replace('/mapi/','/',$str);
}

/**
 *更新会员等
 *$user_data 要包括会员id,会员等级,会员信用值
 * */
function user_leverl_syn($user_data)
{

    fanwe_require(APP_ROOT_PATH.'mapi/lib/redis/BaseRedisService.php');
    fanwe_require(APP_ROOT_PATH.'mapi/lib/redis/UserRedisService.php');
    //$user_redis = new UserRedisService($user_data['id']);
    $user_redis = new UserRedisService();

    $m_config = load_auto_cache("m_config");
    //合并等级 判断，用户当前等级积分
    $ote = floatval($m_config['onlinetime_to_experience']);
    if ($ote > 0){
    	$user_score = $user_data['score']+floor($user_data['online_time']*$ote);
    }else{
    	$user_score = $user_data['score'];
    }
    //$user_score = $user_data['score']+floor($user_data['online_time']/app_conf('ONLINETIME_TO_EXPERIENCE'));
    //$user_score = $user_data['score'];

    //用户获取当前等级
   	//旧的，获取等级
   	// $user_current_level = $GLOBALS['db']->getRow("select point from ".DB_PREFIX."user_level where `level` = ".intval($user_data['user_level']),true,true);
	//$user_current_level = load_auto_cache("user_level_lv",array('lv'=>intval($user_data['user_level'])));

	//新的，通过redis获取等级
    $user_level_info = load_auto_cache("user_level");
	$user_level = null;
    foreach($user_level_info as $v){
		// 两层关系即可
		unset($user_level['next_level']);
		$v['next_level'] = $user_level;
		$user_level = $v;
    	if($v['point']<=intval($user_score)){
    		break;
    	}
    }
  	//旧的，获取最高等级积分信息
  	//$user_level = $GLOBALS['db']->getRow("select point,level from ".DB_PREFIX."user_level where point <=".intval($user_score)." order by point desc",true,true);

    if(intval($user_data['user_level'])<$user_level['level'])
    {
        //$user_data['user_level'] = intval($user_level['level']);
        $GLOBALS['db']->query("update ".DB_PREFIX."user set user_level = ".$user_level['level']." where id = ".$user_data['id']);

        $data = array();
        $data['user_level'] = $user_level['level'];
        $user_redis->update_db($user_data['id'],$data);
        //$pm_content = "恭喜您，您已经成为".$user_level['name']."等级的会员！";
        //send_notify($user_data['id'], $pm_content, "account#point");
    }
	/*
    if($user_current_level['point']>$user_level['point'])
    {
        $user_data['user_level'] = intval($user_level['level']);
        $GLOBALS['db']->query("update ".DB_PREFIX."user set user_level = ".$user_data['user_level']." where id = ".$user_data['id']);

        $data = array();
        $data['user_level'] = $user_data['user_level'];
        $user_redis->update_db($user_data['id'],$user_data);
        //$pm_content = "很报歉，您的会员等级已经降为".$user_level['name']."！";
        //send_notify($user_data['id'], $pm_content, "account#point");
    }
    */



	$user_level['u_score'] = $user_score;
    return $user_level;
}


/**
 *更新会员等
 *$user_data 要包括会员id,会员等级,会员信用值
 * */
function family_level_syn($family_info)
{
	$family_score = $family_info['score'];

	$family_level_info = load_auto_cache("family_level");
	$family_level = null;
	foreach($family_level_info as $v) {
		// 两层关系即可
		unset($family_level['next_level']);
		$v['next_level'] = $family_level;
		$family_level = $v;
		if ($v['point'] <= intval($family_score)) {
			break;
		}
	}

	if($family_level && intval($family_info['family_level']) < $family_level['level'])
	{
		$GLOBALS['db']->query("update ".DB_PREFIX."family set family_level = ".$family_level['level']." where id = ".$family_info['family_id']);

//		$data = array();
//		$data['family_level'] = $family_level['level'];
//		$$family_redis->update_db($family_info['id'],$data);
	}

	return $family_level;
}

function fanwe_require($file){
	static $_importFile = array();
	$filename = realpath($file);
	if(!isset($_importFile[$filename])){
		//做兼容性，只有强制定义了 FANWE_REQUIRE 常量后,才使用 后台用require，否则使用：require_once
		if(defined('FANWE_REQUIRE')){
			$_importFile[$filename] = require $file;
		}else{
			$_importFile[$filename] = require_once $file;
		}
	}
	return $_importFile[$filename];
}

function FanweServiceCall($class='index',$act='index',$data=array())
{
	if(file_exists(APP_ROOT_PATH."service/config.php")){
		$config = fanwe_require(APP_ROOT_PATH."service/config.php");
	}
	if(!isset($config[$class])){
		$config[$class] = "fanwe";
	}
	@fanwe_require(APP_ROOT_PATH."service/".$config[$class]."/".$class.".service.php");
	$objClass = $class."Service";
	if(class_exists($objClass)){
		$obj=new $objClass;
		if(method_exists($obj, $act)){
			return $obj->$act($data);
		}
		else{
			$error["status"] = 10006;
		    $error["error"] = "接口方法不存在";
		    ajax_return($error);
		}
	}
	else{
		$error["status"] = 10005;
	    $error["error"] = "接口不存在";
	    ajax_return($error);
	}
}
/**
 * 获取毫秒
 */
function get_microtime(){
	list($usec, $sec) = explode(" ", microtime());
	$microtime = ((int)($usec * 1000) + (float)$sec * 1000);
	return $microtime;
}
/*
 * 获取不在 $array_2数组中的值
 */
function array_diff_info($array_2,$array_1 ) {
	$array_2 = array_flip($array_2);
	foreach ($array_1 as $key => $item) {
		if (isset($array_2[$item])) {
			unset($array_1[$key]);
		}
	}
	return $array_1;
}


/**
 * TimApi推送
 * 'MsgType' => 'TIMCustomElem',
 */
function get_tim_api($data){


	#构造高级接口所需参数
	$msg_content = array();
	//创建array 所需元素
	$msg_content_elem = array(
			'MsgType' => 'TIMCustomElem',       //自定义类型
			'MsgContent' => array(
					'Data' => json_encode($data['ext']),
					'Desc' => '',
			)
	);
	//log_result("==get_tim_api==");
	//log_result("msg_content_elem=");
	//log_result($msg_content_elem);
	//将创建的元素$msg_content_elem, 加入array $msg_content
	array_push($msg_content, $msg_content_elem);
	fanwe_require(APP_ROOT_PATH.'system/tim/TimApi.php');
	$api = createTimAPI();

	return $api->group_send_group_msg2(trim($data['podcast_id']), $data['group_id'], $msg_content);
	//$m_config =  load_auto_cache("m_config");
	//$system_user_id =$m_config['tim_identifier'];//系统消息
	//$ret = $api->openim_send_msg2($system_user_id, intval($data['podcast_id']), $msg_content);
	//log_result("ret=");
	//log_result($ret);
}
function timSystemNotify($group_id,$msg,$to = array()){
    fanwe_require(APP_ROOT_PATH.'system/tim/TimApi.php');
    $msg['timestamp'] = microtime(1);
    $msg['user_id'] = sizeof($to) == 1 ? intval($to[0]) : 0;
    $api = createTimAPI();
    return $api->group_send_group_system_notification2($group_id,json_encode($msg),$to);
}


function isApp(){
 	$agent = strtolower($_SERVER['HTTP_USER_AGENT']);
    $is_app = strpos($agent, 'fanwe_app_sdk') ? true : false ;
    if($is_app){
        return true;
    }else{
        return false;
    }
 }

 /**
  * 将 diamonds,use_diamonds,score,ticket,user_level,refund_ticket 几个字段值,同步到redis
  * @param array $user_ids
  */
 function user_deal_to_reids($user_ids)
 {
	fanwe_require(APP_ROOT_PATH.'mapi/lib/redis/BaseRedisService.php');
	fanwe_require(APP_ROOT_PATH.'mapi/lib/redis/UserRedisService.php');
 	$user_redis = new UserRedisService();
    $m_config =  load_auto_cache("m_config");//初始化手机端配置
    /*$fields = 'id,nick_name,head_image,sex,diamonds,use_diamonds,score,ticket,user_level,refund_ticket,is_authentication,v_icon,v_explain,v_type,emotional_state,province,city,job,signature,birthday,video_count';
    if(defined('OPEN_VIP')&&OPEN_VIP==1){
        $fields.=',is_vip,vip_expire_time';
    }
    if((defined('OPEN_GAME_MODULE')&&OPEN_GAME_MODULE==1)){
        $fields.=',coin';
    }*/
	 $fields = ' *  ';
 	foreach ($user_ids as $user_id) {
 		$sql = "select ".$fields." from ".DB_PREFIX."user where id = ".$user_id;
 		//echo $sql."<br>";
 		$user_data = $GLOBALS['db']->getRow($sql);
 		//print_r($user_data);
 		$user_redis->update_db($user_id, $user_data);
 	}
 }
 //将机器人同步到IM
 function accountimport_robots($user_id){
 	 fanwe_require(APP_ROOT_PATH."system/libs/user.php");
 	 $user_data = $GLOBALS['db']->getRow("SELECT id,nick_name,head_image,synchronize FROM ".DB_PREFIX."user where id=".$user_id." and is_robot=1 ");
 	 accountimport($user_data);
 }

 function account_log_com($data,$user_id,$log_msg='',$param=array())
{
	fanwe_require(APP_ROOT_PATH."system/libs/user.php");
	account_log($data,$user_id,$log_msg,$param);
}

function origin_image_info($img_path)
{
	if (!isset($GLOBALS['distribution_cfg']['OSS_TYPE']) || $GLOBALS['distribution_cfg']['OSS_TYPE'] != "ALI_OSS") {
		return array('file_name' => $img_path);
	}

	$i = strrpos($img_path, '@');
	if ($i === false) {
		return array('file_name' => $img_path);
	}

	$result = array('file_name' => substr($img_path, 0, $i));
	foreach (explode('_', substr($img_path, $i + 1, strrpos($img_path, '.') - $i - 1)) as $str) {
		$key = substr($str, -1);
		if (strpos($str, '-') === false) {
			$result[$key] = substr($str, 0, -1);
		} else {
			$result[$key] = explode('-', substr($str, 0, -1));
		}
	}

	return $result;
}

/*
* 获取裁剪规格的图片地址(https://help.aliyun.com/document_detail/32228.html?spm=5176.doc32228.6.558.59iwnS)
* @param string $img_path  图片地址,只支持阿里云oss图片地址
* @param int $x ===》x轴起始坐标
* @param int $y===》y轴起始坐标
* @param int $width ===》高
* @param int $height===》宽
* @param int $rotate ===》旋转角度
* @return String 返回格式化后的图片地址
*
*图片旋转, https://help.aliyun.com/document_detail/32230.html?spm=5176.doc32228.6.560.R1A6Ki
*/
function get_cut_image($img_path,$x=0, $y=0, $width=0,$height=0,$rotate=0)
{
	if (!isset($GLOBALS['distribution_cfg']['OSS_TYPE']) || $GLOBALS['distribution_cfg']['OSS_TYPE'] != "ALI_OSS") {
		return $img_path;
	}

	$pos = strpos($img_path, $GLOBALS['distribution_cfg']['OSS_DOMAIN']);
	$httppos = strpos($img_path, 'http');
	$pathinfo = pathinfo($img_path);
	if ($pos === false && $httppos === false) {
		//未定位到
		$file = $pathinfo['basename'];
		$dir = $pathinfo['dirname'];
		$dir = str_replace("./public/", "/public/", $dir);

		$file_name = $GLOBALS['distribution_cfg']['OSS_DOMAIN'] . $dir . "/" . $file;
		$pos = 0;
	} else {
		$file_name = $img_path;
	}

	if ($pos === 0) {
		if($rotate < 0){
			$rotate = 360 + $rotate;
		}

        if ($GLOBALS['distribution_cfg']['NEW_OSS']) {
            $file_name .= "?x-oss-process=image/crop,x_{$x},y_{$y},w_{$width},h_{$height}/rotate,{$rotate}";
        } else {
            $format = "@{$x}-{$y}-{$width}-{$height}a_{$rotate}r.";
            if (isset($pathinfo['extension'])) {
			    $extension = strtolower($pathinfo['extension']);
		    } else {
			    $extension = "jpg";
		    }
            $file_name .= $format . $extension;
        }
	}
	return $file_name;
}

/*
* 获取相应规格的图片地址(https://help.aliyun.com/document_detail/32206.html?spm=5176.doc32206.6.488.Y4uU6M)
* @param string $img_path  图片地址,只支持阿里云oss图片地址
* @param int $width ===》高
* @param int $height===》宽
* @param int $gen gen=0:保持比例缩放，不剪裁,如高为0，则保证宽度按比例缩放  gen=1：保证长宽，剪裁
*
* @param int $radius ===》模糊效果,取值在 [1,50]， radius越大，越模糊
* @param int $sigma===》模糊效果 ,取值 [1,50]，越大，越模糊
* @return String 返回格式化后的图片地址
*
*模糊效果, https://help.aliyun.com/document_detail/32234.html?spm=5176.doc32233.6.516.zuzpF7
*/
function get_spec_image($img_path,$width=0,$height=0,$gen=0, $radius=0,$sigma=0)
{

	if($img_path==''){
		return ;
	}
//关于ALIOSS的生成
	if ($GLOBALS['distribution_cfg']['OSS_TYPE'] && $GLOBALS['distribution_cfg']['OSS_TYPE'] == "ALI_OSS") {

		$pos = strpos($img_path, $GLOBALS['distribution_cfg']['OSS_DOMAIN']);
		$httppos = strpos($img_path, 'http');
		if ($pos === false && $httppos === false) {
			//未定位到
			$pathinfo = pathinfo($img_path);
			$file = $pathinfo['basename'];
			$dir = $pathinfo['dirname'];
			$dir = str_replace("./public/", "/public/", $dir);
			if($width!=0||$height!=0||$gen!=0||$radius!=0||$sigma!=0||$GLOBALS['distribution_cfg']['OSS_DOMAIN_HTTPS']==''){
				$file_name = $GLOBALS['distribution_cfg']['OSS_DOMAIN'] . $dir . "/" . $file;
			}else{
				$file_name = $GLOBALS['distribution_cfg']['OSS_DOMAIN_HTTPS'] . $dir . "/" . $file;
				return $file_name;
			}
			$pos = 0;
		} else {
			$file_name = $img_path;
		}
		if ($GLOBALS['distribution_cfg']['NEW_OSS']) {
			if ($pos === 0) {
				$format = "";
				if ($width == 0 && $height > 0) {
					$format = "/resize,h_".$height; //高固定,宽按图片大小等比缩放
				} else if ($height == 0 && $width > 0) {
					$format = "/resize,w_".$width;//宽固定,高按图片大小等比缩放
				} else if ($width > 0 && $height > 0) {
					if ($gen == 0)
						$format = "/resize,m_mfit,h_" . $height . ",w_". $width;//宽,高固定,以短边缩放 1e 不剪裁
					else
						$format = "/resize,m_fill,m_mfit,h_" . $height . ",w_". $width;//宽,高固定,以短边缩放 1e 剪裁
				}

				if ($radius > 0 && $sigma > 0) {
					if ($format == '')
						$format = "/blur,r_" . $radius . ",s_" . $sigma;
					else
						$format = $format."/blur,r_" . $radius . ",s_" . $sigma;
				}

				if ($format != '') {
					$i = strrpos($file_name, '?x-oss-process=image');
					if ($i === false) {
						$file_name = $file_name . '?x-oss-process=image' . $format;
					} else {
						$file_name .= $format;
					}
				}
			}
		}else{
			if ($pos === 0) {
				$format = "";
				if ($width == 0 && $height > 0) {
					$format = $height . "h_1l_1x"; //高固定,宽按图片大小等比缩放
				} else if ($height == 0 && $width > 0) {
					$format = $width . "w_1l_1x";//宽固定,高按图片大小等比缩放
				} else if ($width > 0 && $height > 0) {
					if ($gen == 0)
						$format = $width . "w_" . $height . "h_0c_1e_1x"; //宽,高固定,以短边缩放 1e 不剪裁
					else
						$format = $width . "w_" . $height . "h_1c_1e_1x"; //宽,高固定,以短边缩放 1e 剪裁
				}

				if ($radius > 0 && $sigma > 0) {
					if ($format == '')
						$format = $radius . "-" . $sigma . "bl";
					else
						$format = "_" . $radius . "-" . $sigma . "bl";
				}

				if ($format != '') {
					$i = strrpos($file_name, '@');
					if ($i === false) {
						$file_name = $file_name . '@' . $format . ".jpg";
					} else {
						$i = strrpos($file_name, '.');
						$file_name = substr($file_name, 0, $i) . '_' . $format . substr($file_name, $i);
					}
				}
			}
		}
		return $file_name;
	} else {
		$domain = get_domain();
		if($GLOBALS['distribution_cfg']['LOCAL_IMAGE_URL'] != "") {
			$domain = $GLOBALS['distribution_cfg']['LOCAL_IMAGE_URL'];
		}
		$img_path = str_replace("./public/",$domain.APP_ROOT."/public/",$img_path);
		return $img_path;
	}
}

//登录提示
function login_prompt($user_id){
    $root = array('first_login'=>0,'new_level'=>0);
    $m_config =  load_auto_cache("m_config");//初始化手机端配置
    $login_send_score = intval($m_config['login_send_score']);//每日首次登录赠送积分数
    $upgrade_level = intval($m_config['upgrade_level']);//首次登录升级提示等级
    fanwe_require(APP_ROOT_PATH.'mapi/lib/redis/BaseRedisService.php');
    fanwe_require(APP_ROOT_PATH.'mapi/lib/redis/UserRedisService.php');
    $user_redis = new UserRedisService($user_id);
    //每日首次登录赠送积分
    if(defined("OPEN_LOGIN_SEND_SCORE")&&OPEN_LOGIN_SEND_SCORE == 1){
        if($login_send_score>0){
            $now_time = NOW_TIME;
            /*$now_time = to_date($now_time,"Y-m-d 00:00:00");
            $timezone = intval(app_conf('TIME_ZONE')) * 3600;
            $user_log_id = $GLOBALS['db']->getOne("select id from " . DB_PREFIX . "user_log where user_id = ".intval($user_id)." and type = 5 and FROM_UNIXTIME(log_time+".$timezone.",'%Y-%m-%d')='".to_date(NOW_TIME,'Y-m-d')."'");
            */
            $s_now_time = to_timespan(to_date($now_time,"Y-m-d 00:00:00"));
            $e_now_time = to_timespan(to_date($now_time,"Y-m-d 23:59:59"));
            $user_log_id = $GLOBALS['db']->getOne("select id from " . DB_PREFIX . "user_log where " .
            		"user_id = ".intval($user_id)." and type = 5 and log_time>".$s_now_time." and log_time<".$e_now_time);

            if(intval($user_log_id)==0){
                $user = $user_redis->getRow_db($user_id,array("login_time"));
                if($user['login_time']<$now_time){
                    $GLOBALS['db']->query("update ".DB_PREFIX."user set score = score+".$login_send_score." where id =".$user_id);
                    user_deal_to_reids(array($user_id));
                    $user = $user_redis->getRow_db($user_id,array('id','score','online_time','user_level'));
                    user_leverl_syn($user);
                    $root['first_login'] = 1;
                    //写入用户日志
                    $data = array();
                    $data['score'] = $login_send_score;
                    $data['log_admin_id'] = 0;
                    $param['type'] = 5;//类型 0表示充值 1表示提现 2赠送道具 3 兑换印票 4 分享获得印票 5 登录赠送积分
                    $log_msg ='每日首次登录获得'.$login_send_score.'积分';
                    account_log_com($data,$user_id,$log_msg,$param);
                }
            }
        }
    }
    //每次登录等级提示
    if(defined("OPEN_UPGRADE_PROMPT")&&OPEN_UPGRADE_PROMPT == 1){
        if($upgrade_level){
            $user = $user_redis->getRow_db($user_id,array("user_level","last_login_level"));
            if($user['user_level']>=$upgrade_level && $user["last_login_level"]<$user['user_level']){//等级大于等于升级提示并且上一次登录时的等级小于现在的等级
                $root['new_level'] = $user['user_level'];
                //修改上一次登录时的等级
                $GLOBALS['db']->query("update ".DB_PREFIX."user set last_login_level = ".$user['user_level']." where id =".$user_id);
                $user_redis->update_db($user_id,array("last_login_level"=>$user['user_level']));
            }
        }
    }
    return $root;
}
//管理员结束直播
function admin_do_end_video($video,$video_vid,$is_aborted = 0,$cate_id = 0){
	fanwe_require(APP_ROOT_PATH.'mapi/lib/core/common.php');
	$result = do_end_video($video,$video_vid,$is_aborted,$cate_id);
	return $result;

}
//获取后台地址
function get_manage_url_name(){
	$url_name = "m.php";
    $urlname = app_conf("URL_NAME");
    if($urlname!=$url_name&&$urlname!=''){
    	$url_name = $urlname;
    }
    return $url_name;
}

function strFilter($str){
	$str = str_replace('`', '', $str);
	$str = str_replace('·', '', $str);
	$str = str_replace('~', '', $str);
	$str = str_replace('!', '', $str);
	$str = str_replace('！', '', $str);
	$str = str_replace('@', '', $str);
	$str = str_replace('#', '', $str);
	$str = str_replace('$', '', $str);
	$str = str_replace('￥', '', $str);
	$str = str_replace('%', '', $str);
	$str = str_replace('^', '', $str);
	$str = str_replace('……', '', $str);
	$str = str_replace('&', '', $str);
	$str = str_replace('*', '', $str);
	$str = str_replace('(', '', $str);
	$str = str_replace(')', '', $str);
	$str = str_replace('（', '', $str);
	$str = str_replace('）', '', $str);
	$str = str_replace('-', '', $str);
	$str = str_replace('_', '', $str);
	$str = str_replace('——', '', $str);
	$str = str_replace('+', '', $str);
	$str = str_replace('=', '', $str);
	$str = str_replace('|', '', $str);
	$str = str_replace('\\', '', $str);
	$str = str_replace('[', '', $str);
	$str = str_replace(']', '', $str);
	$str = str_replace('【', '', $str);
	$str = str_replace('】', '', $str);
	$str = str_replace('{', '', $str);
	$str = str_replace('}', '', $str);
	$str = str_replace(';', '', $str);
	$str = str_replace('；', '', $str);
	$str = str_replace(':', '', $str);
	$str = str_replace('：', '', $str);
	$str = str_replace('\'', '', $str);
	$str = str_replace('"', '', $str);
	$str = str_replace('“', '', $str);
	$str = str_replace('”', '', $str);
	$str = str_replace(',', '', $str);
	$str = str_replace('，', '', $str);
	$str = str_replace('<', '', $str);
	$str = str_replace('>', '', $str);
	$str = str_replace('《', '', $str);
	$str = str_replace('》', '', $str);
	$str = str_replace('.', '', $str);
	$str = str_replace('。', '', $str);
	$str = str_replace('/', '', $str);
	$str = str_replace('、', '', $str);
	$str = str_replace('?', '', $str);
	$str = str_replace('？', '', $str);
	return trim($str);
}
//系统错误日志
function  log_err_file($word,$file_name='error_file')
{
	//log_err_file(array(__FILE__,__LINE__,__METHOD__,$data));
	if (!is_dir(APP_ROOT_PATH."public/sys_error/")) {
		@mkdir(APP_ROOT_PATH."public/sys_error/");
		@chmod(APP_ROOT_PATH."public/sys_error/", 0777);
	}
	$file = APP_ROOT_PATH."/public/sys_error/".$file_name."_".date('Ymd').".log";

	if (is_array($word)) $word = var_export($word,true);

	$fp = fopen($file,"a");
	flock($fp, LOCK_EX) ;
	fwrite($fp,"执行日期：".strftime("%Y-%m-%d-%H：%M：%S",time())."\n".$word."\n\n");
	flock($fp, LOCK_UN);
	fclose($fp);
	//删除大于一个月的日志文件
	$file_name_old  = $file_name."_".date("Ymd",strtotime("-1 month - day"));
	$file_old = APP_ROOT_PATH."public/sys_error/".$file_name_old.".log";
	if (file_exists($file_old)) {
		@unlink ($file_old);
	}
}
//获取直播信息
function c_get_vodset_by_video_id($video_id){
	fanwe_require(APP_ROOT_PATH.'mapi/lib/core/common.php');
	$result = get_vodset_by_video_id($video_id);
	return $result;
}
/*
  * $url 文件地址
  * $qrcode_name 生成的源文件
  * $qrcode_dir_logo 带logo的源文件
  */
function get_qrcode_png($url,$qrcode_name,$qrcode_dir_logo){
	require_once APP_ROOT_PATH.'system/utils/phpqrcode.php';
	$value = $url; //二维码内容
	$errorCorrectionLevel = 'L';//容错级别
	$matrixPointSize = 6;//生成图片大小
	//生成二维码图片
	QRcode::png($value, $qrcode_name, $errorCorrectionLevel, $matrixPointSize, 2);
	$m_config = load_auto_cache("m_config");//初始化手机端配置
	$logo = $m_config['app_logo'];//准备好的logo图片
	$QR = $qrcode_name;//已经生成的原始二维码图
	if ($logo !== FALSE) {
		$QR = imagecreatefromstring(file_get_contents($QR));
		$logo = imagecreatefromstring(file_get_contents($logo));
		$QR_width = imagesx($QR);//二维码图片宽度
		$QR_height = imagesy($QR);//二维码图片高度
		$logo_width = imagesx($logo);//logo图片宽度
		$logo_height = imagesy($logo);//logo图片高度
		$logo_qr_width = $QR_width / 5;
		$scale = $logo_width/$logo_qr_width;
		$logo_qr_height = $logo_height/$scale;
		$from_width = ($QR_width - $logo_qr_width) / 2;
		//重新组合图片并调整大小
		imagecopyresampled($QR, $logo, $from_width, $from_width, 0, 0, $logo_qr_width,
			$logo_qr_height, $logo_width, $logo_height);
	}
	//输出图片
	imagepng($QR, $qrcode_dir_logo);
}
/*
 * 数据传输加密
 * @param array $date 输出的数组
 * @param int $r_type 0=>base64;1=>json_encode;2=>array; 4=>aec
 */
function ajax_return_aes($data,$r_type=1,$is_debug=0){
	header("Content-Type:text/html; charset=utf-8");
	//$user_info = $GLOBALS['user_info'];
	filter_null($data);//过滤null
	//过滤false
	$data = filter_all_false($data);
	//
	$encrypt = $GLOBALS['encrypt'];
	if(strstr($encrypt['sdk_version_name'],'2.4')){
		$aes_key_list = get_privatekey();
		if(is_array($aes_key_list[0]['aes_key'])){
			$privatekey = $aes_key_list[0]['aes_key'][0];
		}else{
			$privatekey = $aes_key_list[0]['aes_key'];
		}
		$is_aes_extra = get_aes_extra();
		if($encrypt['now_aes_key']!=''&&$is_aes_extra){
			$privatekey = $encrypt['now_aes_key'];
		}
		if(intval(DE_BUGE_AES)){
			log_file('aes_key_list');
			log_file($aes_key_list);
			log_file('privatekey');
			log_file($privatekey);
		}
	}else{
		if(strstr($encrypt['sdk_version_name'],'2.4')){
			$encText['output'] = '';
			echo json_encode($encText);exit;
		}else{
			$m_config = load_auto_cache("m_config");//初始化手机端配置
			$privatekey = $m_config['tim_sdkappid'];//对称加密KEY
			if(trim($privatekey)==''){
				$ret = array('error'=>'tim_sdkappid为空');
				log_err_file(array(__FILE__,__LINE__,__METHOD__,$ret));
			}
			//判断KEY是否16位，如果不是自动填充0，进行16位截取
			//$privatekey = str_pad($privatekey,16,"0",STR_PAD_RIGHT);
			if(strlen(trim($privatekey))<16){
				$privatekey = trim($privatekey).'0000000000000000';
			}
		}
	}
	$privatekey = substr($privatekey,0,16);
	$r_type = intval($r_type);//返回数据格式类型;
	ob_start();
	ob_end_clean();
	if ($r_type == 0)
	{
		echo base64_encode(json_encode($data));
	}else if ($r_type == 1)
	{
		echo json_encode($data);
	}else if ($r_type == 2)
	{
		print_r($data);
	}else if($r_type == 4){
		require_once APP_ROOT_PATH.'system/libs/crypt_aes.php';
		$aes = new CryptAES();
		$aes->set_key($privatekey);
		$aes->require_pkcs5();
		$encText = array();
		$encText['output'] = $aes->encrypt(json_encode($data));
		if(intval(DE_BUGE)){
			$encText['output_debug']['sdk_version_name'] = $encrypt['sdk_version_name'];
			$encText['output_debug']['privatekey'] = $privatekey;
			$encText['output_debug']['data'] = json_encode($data);
		}
		echo json_encode($encText);
	};
	exit;
}
/*
 * 接收的数据解密
 * @param array $_REQUEST['requestData'] 接收的数据集
 */

function aes_request_decode(){
	$encrypt = $GLOBALS['encrypt'];
	if($encrypt['i_type']){
		$request = get_aes_decstring();
		$_REQUEST = array_merge($_REQUEST,$request);
	}
}
//获得时间时长
function time_len($time){
	$total_time_format = '';
	if($time/3600>=1){
		$total_time_format.=intval($time/3600).'小时';
		$time = $time%3600;
	}
	if($time/60>=1){
		$total_time_format.=intval($time/60).'分钟';
		$time = $time%60;
	}
	if($time){
		$total_time_format.=intval($time).'秒';
	}
	return $total_time_format;
}

//登录日志
function  log_login($date)
{
	$login_log = array();
	$now_time = get_gmtime();
	$login_log['create_time'] = $now_time;
	$login_log['ip'] = get_client_ip();
	$login_log['login_date'] = to_date($now_time);
	$login_log['login_time'] = $now_time;
	$login_log['user_id'] = $date['user_id'];
	$login_log['login_type'] = $date['login_type'];
	$login_log['request'] = $date['request'];
	$login_log['ctl_act'] =$GLOBALS['encrypt']['ctl'].'#'.$GLOBALS['encrypt']['act'];

	$GLOBALS['db']->autoExecute(DB_PREFIX."login_log", $login_log,'INSERT');
}
//处理解密
function get_aes_decstring(){
	require_once APP_ROOT_PATH.'system/libs/crypt_aes.php';
	//获取aes_key
	$is_aes_extra = get_aes_extra();
	$aes_key_list = get_privatekey();
	//获取aes_key
	if($aes_key_list) {
		if(count($aes_key_list)>1){
			if(intval(DE_BUGE_AES)){
				log_file('----start----');
				log_file('aes_key_list');
				log_file($aes_key_list);
			}
			foreach($aes_key_list as $k=>$v){
				$privatekey = $v['aes_key'];
				if(count($privatekey)>1){
					foreach($privatekey as $v){
						$request = get_aes_request($v);
						if($request!=''){
							$privatekey = $v;
							break;
						}
					}
				}else{
					$request = get_aes_request($privatekey[0]);
					if(intval(DE_BUGE_AES)){
						$encrypt = $GLOBALS['encrypt'];
						log_file('----多key----');
						if($request){
							log_file($encrypt);
							log_file($privatekey);
							log_file($request);
							log_file('----end----');
						}else{
							log_file('fail');
							log_file($encrypt);
							log_file($privatekey);
							log_file('----end----');
						}
					}
				}
				if($request&&$is_aes_extra){
					$privatekey = $privatekey[0];
					$GLOBALS['encrypt']['now_aes_key'] = $privatekey;
					break;
				}else{
					$GLOBALS['encrypt']['now_aes_key'] = '';
				}
			}
		}else{
			$privatekey = $aes_key_list[0]['aes_key'];
			if(intval(DE_BUGE_AES)){
				log_file('----start----');
				log_file('privatekey');
				log_file($privatekey);
			}
			if(count($privatekey)>1){
				foreach($privatekey as $v){
					$request = get_aes_request($v);
					if($request!=''){
						$privatekey = $v;
						break;
					}
				}
			}else{
				if($privatekey) {
					$privatekey = $privatekey[0];
					$request = get_aes_request($privatekey);
					if(intval(DE_BUGE_AES)){
						$encrypt = $GLOBALS['encrypt'];
						log_file('----单key----');
						if($request){
							log_file($encrypt);
							log_file($privatekey);
							log_file($request);
							log_file('----end----');
						}else{
							log_file('fail');
							log_file($encrypt);
							log_file($privatekey);
							log_file('----end----');
						}
					}
				}
			}
			if($request&&$is_aes_extra){
				$GLOBALS['encrypt']['now_aes_key'] = $privatekey;
			}else{
				$GLOBALS['encrypt']['now_aes_key'] = '';
			}
		}
	}else{
		$m_config = load_auto_cache("m_config");//初始化手机端配置
		$privatekey = $m_config['tim_sdkappid'];//对称加密KEY

		$request = get_aes_request($privatekey);

		if($request!=''&&$is_aes_extra){
			$GLOBALS['encrypt']['now_aes_key'] = $privatekey;
		}else{
			$GLOBALS['encrypt']['now_aes_key'] = '';
		}
	}
	return $request;
}
//获取动态秘钥
function get_privatekey(){
	$is_aes_extra = get_aes_extra();

	if(intval($is_aes_extra)){
		$sql = "SELECT * from ".DB_PREFIX."key_list where  is_effect=1 and is_delete=0  order by id desc ";
	}else{
		$sql = "SELECT * from ".DB_PREFIX."key_list where  is_effect=1 and is_delete=0  order by id desc limit 1 ";
	}
	$aes_key_list = $GLOBALS['db']->getAll($sql);
	if($aes_key_list){
		foreach($aes_key_list as $k=>$v){
			$aes_key_arr = explode("<br />",nl2br($v['aes_key']));
			if($aes_key_arr){
				foreach($aes_key_arr as &$item){
					$item = trim($item);
				}
				$aes_key_list[$k]['aes_key']=$aes_key_arr;
			}
		}
	}else{
		$aes_key_list = array();
	}

	if($GLOBALS['encrypt']['now_aes_key']){
		$aes_key_list[0]['aes_key'] = $GLOBALS['encrypt']['now_aes_key'];
	}
	return $aes_key_list;
}
//解密
function get_aes_request($privatekey){
	//判断KEY是否16位，如果不是自动填充0，进行16位截取
	$privatekey = trim($privatekey);
	if(trim($privatekey)==''){
		$ret = array('error'=>'tim_sdkappid为空');
		log_err_file(array(__FILE__,__LINE__,__METHOD__,$ret));
	}
	if(strlen(trim($privatekey))<16){
		$privatekey = trim($privatekey).'0000000000000000';
	}
	$privatekey = substr($privatekey,0,16);
	$aes = new CryptAES();
	$aes->set_key($privatekey);
	$aes->require_pkcs5();
	$decString = $aes->decrypt(trim($_REQUEST['requestData']));
	$request = json_decode($decString, 1);
	return $request;
}
//判断是否要循环解密
function get_aes_extra(){
	$encrypt = $GLOBALS['encrypt'];
	$is_aes_extra = 0;
	$ctl_act =  $encrypt['ctl']."#".$encrypt['act'];
	if(count($GLOBALS['distribution_cfg']['AES_EXTRA_FUN'])>0&&in_array($ctl_act,$GLOBALS['distribution_cfg']['AES_EXTRA_FUN'])){
		$is_aes_extra = 1;//需要循环解密
	}
	if(DE_BUGE_AES){
		log_file(array($encrypt,$ctl_act,$is_aes_extra));
	}
	return $is_aes_extra;
}
?>