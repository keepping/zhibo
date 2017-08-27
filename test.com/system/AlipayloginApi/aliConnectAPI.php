<?php


class aliConnectAPI{
	 var $alipay_config;

	 var $return_url;
	
	public function __construct($alipay_partner = "", $alipay_key = ""){
		/**************************请求参数**************************/		
		$this->alipay_config['partner'] = $alipay_partner;
		
		//安全检验码，以数字和字母组成的32位字符
		$this->alipay_config['key']	= $alipay_key;
	
		//签名方式 不需修改
		$this->alipay_config['sign_type']  = strtoupper('MD5');
		
		//字符编码格式 目前支持 gbk 或 utf-8
		$this->alipay_config['input_charset']= strtolower('utf-8');
		
		//ca证书路径地址，用于curl中ssl校验
		//请保证cacert.pem文件在当前文件夹目录中
		$this->alipay_config['cacert']    = getcwd().'\\cacert.pem';
		
		//访问模式,根据自己的服务器是否支持ssl访问，若支持请选择https；若不支持请选择http
		$this->alipay_config['transport']  = 'http';
		
		//$this->return_url = SITE_DOMAIN.'/callback/login/alilogin_notify.php';
		
		$this->return_url = SITE_DOMAIN.'/mapi/index.php';
		
	}
	//获取接口的显示
	public function get_display_code(){

		require_once(APP_ROOT_PATH."system/AlipayloginApi/lib/alipay_submit.class.php");

        //目标服务地址
        $target_service = "user.auth.quick.login";
        //必填
        //必填，页面跳转同步通知页面路径
        $return_url = $this->return_url;
        //需http://格式的完整路径，不允许加?id=123这类自定义参数

        //防钓鱼时间戳
        $anti_phishing_key = "";
        //若要使用请调用类文件submit中的query_timestamp函数

        //客户端的IP地址
        $exter_invoke_ip = "";
        //非局域网的外网IP地址，如：221.0.0.1

		/************************************************************/
		
		//构造要请求的参数数组，无需改动
		$parameter = array(
				"service" => "alipay.auth.authorize",
				"partner" => trim($this->alipay_config['partner']),
				"target_service"	=> $target_service,
				"return_url"	=> $return_url,
				"anti_phishing_key"	=> $anti_phishing_key,
				"exter_invoke_ip"	=> $exter_invoke_ip,
				"_input_charset"	=> trim(strtolower($this->alipay_config['input_charset']))
		);
		
		//建立请求
		$alipaySubmit = new AlipaySubmit($this->alipay_config);
		$html_text = $alipaySubmit->buildRequestForm($parameter,"GET", "确认");
		echo $html_text;
	}
	/*
	 * 支付宝回调验证
	 */
	public function verifyreturn()
	{
		require_once(APP_ROOT_PATH."system/AlipayloginApi/lib/alipay_notify.class.php");
		$alipayNotify = new AlipayNotify($this->alipay_config);
		$verify_result = $alipayNotify->verifyReturn();
		return $verify_result;
	}
	
	public function build_html($str)
	{
		$html = '<!DOCTYPE html>
	<html>
	    <head>
	        <meta charset="utf-8">
	        <meta http-equiv="X-UA-Compatible" content="IE=edge">
	        <title>支付宝认证</title>
         	<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=2.0, user-scalable=0,minimum-scale=0.5">
	        <link rel="shortcut icon" href="/favicon.ico">
	        <meta name="apple-mobile-web-app-capable" content="yes">
	        <meta name="apple-mobile-web-app-status-bar-style" content="black">
			<style>
				.img-box{
					margin-top:0px;
				}
				@media screen and (min-width: 770px) {
					.img-box {
						width:600px;
						margin-top: 0px;
					}
				}
				.img-box img{
					width:100%;
				}
				.item-title{
					font-size:18px;
					line-height: 40px;
					margin-top:10px;
					border-bottom:1px solid #ddd;
				}

			</style>
	    </head>
	    <body style="margin:0px;padding:0px;">
        '.$str.'
		</body>
	</html>
';

		return $html;
	}
	
}


?>
