<?php
// +----------------------------------------------------------------------
// | Fanwe 方维p2p借贷系统
// +----------------------------------------------------------------------
// | Copyright (c) 2011 http://www.fanwe.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: 云淡风轻(88522820@qq.com)
// +----------------------------------------------------------------------
/*
$url = 'https://pay.swiftpass.cn/pay/gateway',
// 商户编号
$merchantaccount = '755437000006';
// 商户私钥
$merchantPrivateKey = '7daa4babae15ae17eee90c9e';
*/
$payment_lang = array(
	'name'	=>	'威富通微信支付',
	'merchantaccount'	=>	'商户编号',
	'merchantAppId'=>	'商户APPID',
	'merchantPrivateKey'=>	'商户密钥',
);

$config = array(
	'merchantaccount'	=>	array(
		'INPUT_TYPE'	=>	'0',
	), //商户编号
	'merchantAppId'	=>	array(
		'INPUT_TYPE'	=>	'0',
	), //商户APPID
	'merchantPrivateKey'	=>	array(
		'INPUT_TYPE'	=>	'0',
	), //商户私钥
);

/* 模块的基本信息 */
if (isset($read_modules) && $read_modules == true)
{
    $module['class_name']    = 'Wxappsdk';

    /* 名称 */
    $module['name']    = $payment_lang['name'];


	/* 支付方式：1：在线支付；0：线下支付  2:仅wap支付 3:仅app支付 4:兼容wap和app*/
    $module['online_pay'] = '4';

    /* 配送 */
    $module['config'] = $config;
    
    $module['lang'] = $payment_lang;
    
    $module['reg_url'] = 'https://citicmch.swiftpass.cn/';
    
    return $module;
}

// 易宝支付模型
require_once(APP_ROOT_PATH.'system/libs/payment.php');
class Wxappsdk_payment implements payment {
	
	public function get_payment_code($payment_notice_id)
	{
		$payment_notice = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."payment_notice where id = ".$payment_notice_id);
		$money = round($payment_notice['money'],2);
		$payment_info = $GLOBALS['db']->getRow("select id,config,logo from ".DB_PREFIX."payment where id=".intval($payment_notice['payment_id']));
		$payment_info['config'] = unserialize($payment_info['config']);

		require_once(APP_ROOT_PATH.'system/payment/Wxappsdk/request.php');
		$wxappsdk = new Request($payment_info['config']['merchantaccount'],$payment_info['config']['merchantPrivateKey']);

		$out_trade_no = $payment_notice['notice_sn'];//网页支付的订单在订单有效期内可以进行多次支付请求，但是需要注意的是每次请求的业务参数都要一致，交易时间也要保持一致。否则会报错“订单与已存在的订单信息不符”
		$body = $payment_notice['recharge_name']!=''?'购买钻石：'.$payment_notice['recharge_name']:'购买钻石：'.$payment_notice['diamonds'];//商品描述
		$total_fee = $money * 100;//订单金额单位为分
		$mch_create_ip = CLIENT_IP; //此参数不是固定的商户服务器ＩＰ，而是用户每次支付时使用的网络终端IP，否则的话会有不友好提示：“检测到您的IP地址发生变化，请注意支付安全”。
		$notify_url = SITE_DOMAIN.APP_ROOT.'/callback/payment/wxappsdk_notify.php';

		$date = array();
		$date['out_trade_no'] = $out_trade_no; //商户订单号：
		$date['body'] = $body;//商品描述：
		$date['total_fee'] =intval($total_fee);//总金额：
		$date['mch_create_ip'] = $mch_create_ip;//终端IP：
		$date['notify_url'] = $notify_url;//回调地址

		$pay = array('status'=>1,'error'=>'');
		$result = $wxappsdk->submitOrderInfo($date);
		$services = explode("|", $result['services']);
		if(!in_array("pay.weixin.app",$services)){
			$pay['status'] = 0;
			$pay['error'] = '支付失败SDK错误，请联系管理员';
			return $pay;
		}
		$pay['pay_info'] = $date['body'];
		$pay['payment_name'] = "微信SDK支付";
		$pay['pay_money'] = $date['total_fee'];
		$pay['pay_id'] = $payment_notice['id'];
		$pay['class_name'] = "Wxappsdk";
		$pay['token_id'] =$result['token_id'];
		$pay['appid'] =$payment_info['config']['merchantAppId'];
		//
		$pay['sdk_code'] = array("pay_sdk_type"=>"wxappsdk","config"=>
			array(
				"token_id"=>$result['token_id'],
				"appid"=>$payment_info['config']['merchantAppId'],
			)
		);
		return $pay;
	}
	public function response($request)
	{

	}
	public function notify($xml)
	{

		$payment = $GLOBALS['db']->getRow("select id,config from ".DB_PREFIX."payment where class_name='Wxappsdk'");
		$payment_info['config'] = unserialize($payment['config']);

		require_once(APP_ROOT_PATH.'system/payment/Wxappsdk/request.php');

		/**
		 *此类文件是有关回调的数据处理文件，根据易宝回调进行数据处理

		 */
		$wxappsdk = new Request($payment_info['config']['merchantaccount'],$payment_info['config']['merchantPrivateKey']);
		try {
			$return = $wxappsdk->callback($xml);
			$payment_notice_sn = $return['out_trade_no'];
			$outer_notice_sn = $return['out_transaction_id'];
			if ($return['status'] == 0){
				$payment_notice = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."payment_notice where notice_sn = '".$payment_notice_sn."'");
				require_once APP_ROOT_PATH."system/libs/cart.php";
				$rs = payment_paid($payment_notice['notice_sn'],$outer_notice_sn);

				if($rs)
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
		}catch (Exception $e) {
			// TODO：添加订单支付异常逻辑代码
			echo 'fail';
		}
	}

	function get_display_code(){

	}
}
?>