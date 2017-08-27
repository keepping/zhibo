<?php

$payment_lang = array(
	'name'	=>	'苹果应用内支付',
		
);
$config = array(

);

/* 模块的基本信息 */
if (isset($read_modules) && $read_modules == true)
{
    $module['class_name']    = 'Iappay';

    /* 名称 */
    $module['name']    = $payment_lang['name'];


    /* 支付方式：1：在线支付；0：线下支付  2:仅wap支付 3:仅app支付 4:兼容wap和app*/
    $module['online_pay'] = '3';

    /* 配送 */
    $module['config'] = $config;
    
    $module['lang'] = $payment_lang;
    $module['reg_url'] = '';
    return $module;
}

// 支付宝手机支付模型
require_once(APP_ROOT_PATH.'system/libs/payment.php');
class Iappay_payment implements payment {

	public function get_payment_code($product_id)
	{
		//$payment_notice = $GLOBALS['db']->getRow("select product_id,money,recharge_name from ".DB_PREFIX."payment_notice where id = ".$payment_notice_id);
		//$money = round($payment_notice['money'],2);

		//$user_id = intval($GLOBALS['user_info']['id']);//用户ID
		
		//$notify_url = SITE_DOMAIN."/callback/payment/iappay_notify.php?user_id=".$user_id."&notice_id=".$payment_notice_id;
		//$product_id = $payment_notice['product_id'];
		
		//$notify_url = SITE_DOMAIN."/callback/payment/iappay_notify.php?user_id=".$user_id;
		$pay = array();
		$pay['pay_info'] = '';
		$pay['payment_name'] = '';//$payment_notice['recharge_name'];
		$pay['pay_money'] = 0;//$money;
		$pay['class_name'] = "Iappay";
		$pay['config'] = array();
		$pay['sdk_code'] = array("pay_sdk_type"=>"iappay", "config"=>array("product_id"=>$product_id));
		
		return $pay;
	}
	
	
	
	public function notify($request)
	{	
		//ctl=app&act=iappay 中处理了
	}
		//响应通知
	function response($request)
	{}
	
	//获取接口的显示
	function get_display_code()
	{
		return "苹果应用内支付";
	}
	
}




?>