<?php
/*
 * 生成新浪的短链接或还原新浪短链接
*/
class shorturl{
	//新浪APPKEY
	const APPKEY='3310867184';
	const SHORTURL= 'http://t.cn/';
	
	//CURL
	private static function CURLQueryString($url){
		//设置附加HTTP头
		$addHead=array("Content-type: application/json");
		//初始化curl
		$curl_obj=curl_init();
		//设置网址
		curl_setopt($curl_obj,CURLOPT_URL,$url);
		//附加Head内容
		curl_setopt($curl_obj,CURLOPT_HTTPHEADER,$addHead);
		//是否输出返回头信息
		curl_setopt($curl_obj,CURLOPT_HEADER,0);
		//将curl_exec的结果返回
		curl_setopt($curl_obj,CURLOPT_RETURNTRANSFER,1);
		//设置超时时间
		curl_setopt($curl_obj,CURLOPT_TIMEOUT,8);
		//执行
		$result=curl_exec($curl_obj);
		//关闭curl回话
		curl_close($curl_obj);
		return $result;
	}
	//处理返回结果
	private static function doWithResult($result,$field){
		$result=json_decode($result,true);
		return isset($result[0][$field])?$result[0][$field]:'';
	}
	
	//获取短链接
	public static function getShort($url_long){
		
		fanwe_require(APP_ROOT_PATH.'mapi/lib/redis/ShortUrlRedisService.php');
		$short_url_redis = new ShortUrlRedisService();
		$url_short = $short_url_redis->get_short($url_long);
		if (empty($url_short)){
			$url='http://api.t.sina.com.cn/short_url/shorten.json?source='.self::APPKEY.'&url_long='.$url_long;
			$result=self::CURLQueryString($url);
			$url_short = self::doWithResult($result,'url_short');
			$short_url_redis->set_short($url_long,$url_short);
		}
		
		return $url_short;
	}
	//获取长链接
	public static function getLong($url){
		$url='http://api.t.sina.com.cn/short_url/expand.json?source='.self::APPKEY.'&url_short='.$url;
		$result=self::CURLQueryString($url);
		return self::doWithResult($result,'url_long');
	}
}

?>