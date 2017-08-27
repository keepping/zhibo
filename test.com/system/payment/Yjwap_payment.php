<?php
// +----------------------------------------------------------------------
// | Fanwe 方维p2p借贷系统
// +----------------------------------------------------------------------
// | Copyright (c) 2011 http://www.fanwe.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: 云淡风轻(88522820@qq.com)
// +----------------------------------------------------------------------

/*
// 商户编号
$merchantaccount = '10000418926';
// 商户私钥
$merchantPrivateKey = 'MIICdwIBADANBgkqhkiG9w0BAQEFAASCAmEwggJdAgEAAoGBALD0Tou2w7EHbP3q5wi5PG5xrvC0CBawXxSI1PlZAGo2iFYhaBK6SsB5UiYT64fSR3YemQGS2vSqQii5vYdOfrffvvDprrr7Vo7BziS6sJQ9B0/DzwN2zY7jJBCz55CLMBsZCtuqDNVxTcsOcZnrgSSMqnhk+usuR4hPoV9qABeHAgMBAAECgYAfnth2UOdxN/F7AkHcpjUtSzVGn/UeENA8vCLKl+PiFvKP6ZJOXmnDMSrD0SVydNn+OoN+634i4FXIL0C18Anmh4IlQM9hj+rFTg1bMSUHvSPKoZpoEfjR0R+3TQF8PycBbaIWgLV/5NA8dMld0DvF5d8bbqpgH6FzEXZPvF8OgQJBANwHRhCu+o/JoCoH0coVhNFuobVYZU0pQRlfDaE4ph0+daiJ4HlT630JrBFb728Ga7E81dsfGMSi1N6QSipJMEECQQDN4kb+O/ecDNQrEsjA0LqDXkaKsRP6iU/HVNyr4Z/7ojHws0F5Vypj1euCII+V6U7StMKRbSaB1GI8Bs34llXHAkEAnIc0KiRBLk+S+LOtZGVgoplgwyEKmBUUMdd0W9BwJHfNvkOwBMBV1BMwbP0JXeOkc2dDAGqj9Sed5mOhz2lXwQJAVeA0TIcm2Ohg9zZ2ljZ6FaGVOvRxqObtZ+91vBv4ZzVYL1YV0U8SV2I7QaPjQFx4jFrpbU9h6HV2JCOSdkX+sQJBAJ+PfNA0b25HuY9n4cTk/hLc2TCWVDsPnONuhNpuRpXqxu9L0p2aHX5JLf1kTUoYxqmlEjx6IYcObcB9Snw0Tf0=';
// 商户公钥
$merchantPublicKey = '';
// 易宝公钥
$yeepayPublicKey = 'MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQCKcSa7wS6OMUL4oTzJLCBsE5KTkPz9OTSiOU6356BsR6gzQ9kf/xa+Wi1ZANTeNuTYFyhlCI7ZCLW7QNzwAYSFStKzP3UlUzsfrV7zge8gTgJSwC/avsZPCWMDrniC3HiZ70l1mMBK5pL0H6NbBFJ6XgDIw160aO9AxFZa5pfCcwIDAQAB';
*/

$payment_lang = array(
	'name'	=>	'易宝一键支付wap',
	'merchantaccount'	=>	'商户编号',
	'merchantPrivateKey'=>	'商户私钥',
	//'merchantPublicKey'	=>	'商户公钥',
	'yeepayPublicKey'	=>	'易宝公钥',
	//'yeepayProductCatalog'	=>	'商品类编码',
);

$config = array(
	'merchantaccount'	=>	array(
		'INPUT_TYPE'	=>	'0',
	), //商户编号
	'merchantPrivateKey'	=>	array(
		'INPUT_TYPE'	=>	'0',
	), //商户私钥
	/*'merchantPublicKey'	=>	array(
		'INPUT_TYPE'	=>	'0',
	), //商户公钥*/
	'yeepayPublicKey'	=>	array(
		'INPUT_TYPE'	=>	'0',
	), //易宝公钥
	/*'yeepayProductCatalog'	=>	array(
		'INPUT_TYPE'	=>	'0',
	), //易宝公钥*/
);

/* 模块的基本信息 */
if (isset($read_modules) && $read_modules == true)
{
    $module['class_name']    = 'Yjwap';

    /* 名称 */
    $module['name']    = $payment_lang['name'];


	/* 支付方式：1：在线支付；0：线下支付  2:仅wap支付 3:仅app支付 4:兼容wap和app*/
    $module['online_pay'] = '4';

    /* 配送 */
    $module['config'] = $config;
    
    $module['lang'] = $payment_lang;
    
    $module['reg_url'] = 'http://www.yeepay.com/';
    
    return $module;
}

// 易宝支付模型
require_once(APP_ROOT_PATH.'system/libs/payment.php');
class Yjwap_payment implements payment {
	
	public function get_payment_code($payment_notice_id)
	{
		$pay = array();
		$pay['is_wap'] = 1;//
		$pay['class_name'] = "Yjwap";
		$pay['url'] =SITE_DOMAIN.APP_ROOT.'/mapi/index.php?ctl=pay&act=get_display_code&pay_code=Yjwap&notice_id='.$payment_notice_id;
		$pay['sdk_code'] = array("pay_sdk_type"=>"yjwap","config"=>
			array(
				"url"=>SITE_DOMAIN.APP_ROOT.'/mapi/index.php?ctl=pay&act=get_display_code&pay_code=Yjwap&notice_id='.$payment_notice_id,
				"is_wap"=>1
			)
		);
		return $pay;
	}
	
	public function response($request)
	{

		$return_res = array(
			'info'=>'',
			'status'=>false,
		);
		$payment = $GLOBALS['db']->getRow("select id,config from ".DB_PREFIX."payment where class_name='Yjwap'");  
    	$payment_info['config'] = unserialize($payment['config']);

		include("yeepay/yeepayMPay.php");
    	
    	/**
    	 *此类文件是有关回调的数据处理文件，根据易宝回调进行数据处理
    	
    	 */
		$yeepay = new yeepayMPay($payment_info['config']['merchantaccount'],$payment_info['config']['merchantPublicKey'],$payment_info['config']['merchantPrivateKey'],$payment_info['config']['yeepayPublicKey']);
    	try {

    		$return = $yeepay->callback(strim($request['data']),strim($request['encryptkey']));

    		// TODO:添加订单处理逻辑代码
    		/*
    		名称 	中文说明 	数据类型 	描述
    		merchantaccount 	商户账户 	string
    		yborderid 	易宝交易流水号 	string
    		orderid 	交易订单 	String
    		amount 	支付金额 	int 	以“分”为单位的整型
    		bankcode 	银行编码 	string 	支付卡所属银行的编码，如ICBC
    		bank 	银行信息 	string 	支付卡所属银行的名称
    		cardtype 	卡类型 	int 	支付卡的类型，1为借记卡，2为信用卡
    		lastno 	卡号后4位 	string 	支付卡卡号后4位
    		status 	订单状态 	int 	1：成功
    		*/
    		
    		$payment_notice_sn = $return['orderid'];
    		$money = intval($return['amount']/100);
    		$outer_notice_sn = $return['yborderid'];

    		if ($return['status'] == 1){   		
	    		$payment_notice = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."payment_notice where notice_sn = '".$payment_notice_sn."'");
	    		require_once APP_ROOT_PATH."system/libs/cart.php";
	    		$rs = payment_paid($payment_notice['notice_sn'],$outer_notice_sn);
				if($rs['status']==1)
	    		{	
	    			echo '<!DOCTYPE html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=2.0, user-scalable=0,minimum-scale=0.5"><title></title><script>function close_page(){try{App.close_page();}catch(e){alert("SDK调用失败");}}</script></head><body><div style="width:120px;height:40px;line-height:40px;font-size:14px;text-align:center;background:#ff4d7f;color:#fff;margin:20px auto;border-radius:5px;" onclick="close_page();">关闭当前页面</div></body></html>';
	    		}
	    		else
	    		{
					echo '<!DOCTYPE html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=2.0, user-scalable=0,minimum-scale=0.5"><title></title><script>function close_page(){try{App.close_page();}catch(e){alert("SDK调用失败");}}</script></head><body><div style="width:120px;height:40px;line-height:40px;font-size:14px;text-align:center;background:#ff4d7f;color:#fff;margin:20px auto;border-radius:5px;" onclick="close_page();">关闭当前页面</div></body></html>';
	    		}
    		}else{
				echo '<!DOCTYPE html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=2.0, user-scalable=0,minimum-scale=0.5"><title></title><script>function close_page(){try{App.close_page();}catch(e){alert("SDK调用失败");}}</script></head><body><div style="width:120px;height:40px;line-height:40px;font-size:14px;text-align:center;background:#ff4d7f;color:#fff;margin:20px auto;border-radius:5px;" onclick="close_page();">关闭当前页面</div></body></html>';
    		}
    	}catch (yeepayMPayException $e) {
    		// TODO：添加订单支付异常逻辑代码
			echo '<!DOCTYPE html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=2.0, user-scalable=0,minimum-scale=0.5"><title></title><script>function close_page(){try{App.close_page();}catch(e){alert("SDK调用失败");}}</script></head><body><div style="width:120px;height:40px;line-height:40px;font-size:14px;text-align:center;background:#ff4d7f;color:#fff;margin:20px auto;border-radius:5px;" onclick="close_page();">关闭当前页面</div></body></html>';
    	}
	}
	
	public function notify($request)
	{

		$payment = $GLOBALS['db']->getRow("select id,config from ".DB_PREFIX."payment where class_name='Yjwap'");  
    	$payment_info['config'] = unserialize($payment['config']);
    	
    	include("yeepay/yeepayMPay.php");
    	
    	/**
    	 *此类文件是有关回调的数据处理文件，根据易宝回调进行数据处理
    	
    	 */
		$yeepay = new yeepayMPay($payment_info['config']['merchantaccount'],$payment_info['config']['merchantPublicKey'],$payment_info['config']['merchantPrivateKey'],$payment_info['config']['yeepayPublicKey']);	
    	try {
    		$return = $yeepay->callback($request['data'], $request['encryptkey']);
    		// TODO:添加订单处理逻辑代码
    		/*
    		名称 	中文说明 	数据类型 	描述
    		merchantaccount 	商户账户 	string
    		yborderid 	易宝交易流水号 	string
    		orderid 	交易订单 	String
    		amount 	支付金额 	int 	以“分”为单位的整型
    		bankcode 	银行编码 	string 	支付卡所属银行的编码，如ICBC
    		bank 	银行信息 	string 	支付卡所属银行的名称
    		cardtype 	卡类型 	int 	支付卡的类型，1为借记卡，2为信用卡
    		lastno 	卡号后4位 	string 	支付卡卡号后4位
    		status 	订单状态 	int 	1：成功
    		*/
    		
    		$payment_notice_sn = $return['orderid'];
    		$money = intval($return['amount']/100);
    		$outer_notice_sn = $return['yborderid'];
    		
    		if ($return['status'] == 1){   		
	    		$payment_notice = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."payment_notice where notice_sn = '".$payment_notice_sn."'");
	    		require_once APP_ROOT_PATH."system/libs/cart.php";
	    		$rs = payment_paid($payment_notice['notice_sn'],$outer_notice_sn);

	    		if($rs['status']==1)
	    		{
					echo 'success';

	    		}
	    		else
	    		{
	    			echo 'fail';
	    		}
    		}else{
    			echo 'fail';
    		}
    	}catch (yeepayMPayException $e) {
    		// TODO：添加订单支付异常逻辑代码
    		echo 'fail';
    	} 
	}

	function get_display_code(){

	}

	public function display_code($payment_notice_id)
	{
		if($payment_notice_id){
			$payment_notice = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."payment_notice where id = ".$payment_notice_id);
			//$order = $GLOBALS['db']->getRow("select order_sn,user_id from ".DB_PREFIX."deal_order where id = ".$payment_notice['order_id']);
			$money = round($payment_notice['money'],2);
			$payment_info = $GLOBALS['db']->getRow("select id,config,logo from ".DB_PREFIX."payment where id=".intval($payment_notice['payment_id']));
			$payment_info['config'] = unserialize($payment_info['config']);

			$order_sn = $payment_notice['notice_sn'];
			$user_id = $payment_notice['user_id'];

			require_once(APP_ROOT_PATH.'system/payment/yeepay/yeepayMPay.php');

			$yeepay = new yeepayMPay($payment_info['config']['merchantaccount'],$payment_info['config']['merchantPublicKey'],$payment_info['config']['merchantPrivateKey'],$payment_info['config']['yeepayPublicKey']);

			$data_return_url = SITE_DOMAIN.APP_ROOT.'/callback/payment/yjwap_response.php';
			$data_notify_url = SITE_DOMAIN.APP_ROOT.'/callback/payment/yjwap_notify.php';

			$order_id = $payment_notice['notice_sn'];//网页支付的订单在订单有效期内可以进行多次支付请求，但是需要注意的是每次请求的业务参数都要一致，交易时间也要保持一致。否则会报错“订单与已存在的订单信息不符”
			$transtime = time();// time();//交易时间，是每次支付请求的时间，注意此参数在进行多次支付的时候要保持一致。
			$product_catalog =$payment_info['config']['yeepayProductCatalog'];//商品类编码是我们业管根据商户业务本身的特性进行配置的业务参数。
			if (empty($product_catalog)){
				$product_catalog = '1';
			}
			$identity_id = $user_id;//用户身份标识，是生成绑卡关系的因素之一，在正式环境此值不能固定为一个，要一个用户有唯一对应一个用户标识，以防出现盗刷的风险且一个支付身份标识只能绑定5张银行卡
			$identity_type = 2;     //支付身份标识类型码
			$user_ip = CLIENT_IP; //此参数不是固定的商户服务器ＩＰ，而是用户每次支付时使用的网络终端IP，否则的话会有不友好提示：“检测到您的IP地址发生变化，请注意支付安全”。
			$user_ua =  $_SERVER['HTTP_USER_AGENT'];//'NokiaN70/3.0544.5.1 Series60/2.8 Profile/MIDP-2.0 Configuration/CLDC-1.1';//用户ua
			$callbackurl = $data_notify_url;//商户后台系统回调地址，前后台的回调结果一样
			$fcallbackurl = $data_return_url;//商户前台系统回调地址，前后台的回调结果一样
			$product_name = '订单号-'.$order_sn;//出于风控考虑，请按下面的格式传递值：应用-商品名称，如“诛仙-3 阶成品天琊”
			$product_desc = '';//商品描述
			$terminaltype = 3;
			$terminalid = '';//其他支付身份信息
			$amount = $money * 100;//订单金额单位为分，支付时最低金额为2分，因为测试和生产环境的商户都有手续费（如2%），易宝支付收取手续费如果不满1分钱将按照1分钱收取。
			$directpaytype =3; //0：默认；1：微信支付；2：支付宝支付；3：一键支付。
			$cardno = '';
			$idcardtype='';
			$idcard='';
			$owner='';
			$url = $yeepay->webPay($order_id,$transtime,$amount,$cardno,$idcardtype,$idcard,$owner,$product_catalog,$identity_id,$identity_type,$user_ip,$directpaytype,$user_ua,$callbackurl,$fcallbackurl,$currency=156,$product_name,$product_desc='',$terminaltype,$terminalid,$orderexp_date=60);//$paytypes,$version
			if( array_key_exists('error_code', $url))	{
				return;
			}else{
				$arr = explode("&",$url);
				$encrypt = explode("=",$arr[1]);
				$data = explode("=",$arr[2]);
				echo($url);
				header('Location:'.$url);
			}
		}
		else
		{
			return '';
		}

	}
}
?>