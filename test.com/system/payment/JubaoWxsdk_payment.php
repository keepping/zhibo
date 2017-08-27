<?php
// +----------------------------------------------------------------------
// | Fanwe 方维p2p借贷系统
// +----------------------------------------------------------------------
// | Copyright (c) 2011 http://www.fanwe.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: 云淡风轻(88522820@qq.com)
// +----------------------------------------------------------------------
$payment_lang = array(
	'name'	=>	'聚宝付微信支付SDK',
	'partner_id'	=>	'商户编号',
/*	'merchantAppId'=>	'商户APPID',
	'merchantPrivateKey'=>	'商户密钥',*/
);

$config = array(
	'partner_id'	=>	array(
		'INPUT_TYPE'	=>	'0',
	), //商户编号
);

/* 模块的基本信息 */
if (isset($read_modules) && $read_modules == true)
{
    $module['class_name']    = 'JubaoWxsdk';

    /* 名称 */
    $module['name']    = $payment_lang['name'];


	/* 支付方式：1：在线支付；0：线下支付  2:仅wap支付 3:仅app支付 4:兼容wap和app*/
    $module['online_pay'] = '4';

    /* 配送 */
    $module['config'] = $config;
    
    $module['lang'] = $payment_lang;
    
    $module['reg_url'] = 'http://www.jubaopay.com/api/register.htm';
    
    return $module;
}

// 易宝支付模型
require_once(APP_ROOT_PATH.'system/libs/payment.php');
class JubaoWxsdk_payment implements payment {
	
	public function get_payment_code($payment_notice_id)
	{

		$payment_notice = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."payment_notice where id = ".$payment_notice_id);
		$money = round($payment_notice['money'],2);
		$payment_info = $GLOBALS['db']->getRow("select id,config,logo from ".DB_PREFIX."payment where id=".intval($payment_notice['payment_id']));
		$payment_info['config'] = unserialize($payment_info['config']);

		$m_config =  load_auto_cache("m_config");
		$title_name = $m_config['ticket_name'];
		if($title_name=='')$title_name = '虚拟印币';
		$subject = msubstr($title_name,0,40);

		require_once APP_ROOT_PATH . "system/payment/Jubaosdk/jubaopay.php";
		$jubaopay=new jubaopay(APP_ROOT_PATH . "system/payment/Jubaosdk/jubaopay.ini");

		$payid=$payment_notice['notice_sn'];
		$partnerid=$payment_info['config']['partner_id'];
		$amount=$money;
		$payerName=$payment_notice['user_id'];
        $playerid = $GLOBALS['db']->getOne("select nick_name from ".DB_PREFIX."user where id=".$payerName);
		$remark=$subject;
		$returnURL=SITE_DOMAIN . '/callback/payment/jubaowxapp_response.php';    // 可在商户后台设置
		$callBackURL=SITE_DOMAIN . '/callback/payment/jubaowxapp_notify.php';  // 可在商户后台设置
		$goodsName=$subject;
		//////////////////////////////////////////////////////////////////////////////////////////////////
		//商户利用支付订单（payid）和商户号（mobile）进行对账查询
		$jubaopay->setEncrypt("payid", $payid);
		$jubaopay->setEncrypt("partnerid", $partnerid);
		$jubaopay->setEncrypt("amount", $amount);
		$jubaopay->setEncrypt("payerName", $payerName);
		$jubaopay->setEncrypt("remark", $remark);
		$jubaopay->setEncrypt("returnURL", $returnURL);
		$jubaopay->setEncrypt("callBackURL", $callBackURL);
		$jubaopay->setEncrypt("goodsName", $goodsName);
		$jubaopay->interpret();
		$message=$jubaopay->message;
		$signature=$jubaopay->signature;

	    	//
		$pay['sdk_code'] = array("pay_sdk_type"=>"JubaoWxsdk","config"=>
			array(
                "partnerid"=>$partnerid,
                "playerid"=>$playerid,
                "goodsname"=>$goodsName,
                "amount"=>$amount,
                "payid"=>$payid,
                "withType"=>1,
                "message"=>$message,
                "signature"=>$signature,
			)
		);
		return $pay;
	}
	public function response($request)
	{
        $payment           = $GLOBALS['db']->getRow("select id,config from " . DB_PREFIX . "payment where class_name='JubaoWxsdk'");
        $payment['config'] = unserialize($payment['config']);

        require_once APP_ROOT_PATH . "system/payment/Jubaosdk/jubaopay.php";
        $jubaopay=new jubaopay(APP_ROOT_PATH . "system/payment/Jubaosdk/jubaopay.ini");
        $message=$request["message"];
        $signature=$request["signature"];
        $jubaopay->decrypt($message);
        // 校验签名，然后进行业务处理
        $result=$jubaopay->verify($signature);
        if($result == 1) {
            $payment_notice_sn = $jubaopay->getEncrypt("payid");
            $outer_notice_sn = $jubaopay->getEncrypt("orderNo");
            $payment_notice = $GLOBALS['db']->getRow("select * from " . DB_PREFIX . "payment_notice where notice_sn = '" . $payment_notice_sn . "'");
            require_once APP_ROOT_PATH . "system/libs/cart.php";
            $rs = payment_paid($payment_notice['notice_sn'],$outer_notice_sn);
            if($rs['status']==1){
                echo "支付成功";
            } else {
                echo "支付成功,回调失败";
            }
        } else {
            echo "支付失败";
        }
	}
	public function notify($xml)
	{

		$payment = $GLOBALS['db']->getRow("select id,config from ".DB_PREFIX."payment where class_name='JubaoWxsdk'");
		$payment_info['config'] = unserialize($payment['config']);

        require_once APP_ROOT_PATH . "system/payment/Jubaosdk/jubaopay.php";
        $jubaopay=new jubaopay(APP_ROOT_PATH . "system/payment/Jubaosdk/jubaopay.ini");
        $message=$xml["message"];
        $signature=$xml["signature"];
        $jubaopay->decrypt($message);
        // 校验签名，然后进行业务处理
        $result=$jubaopay->verify($signature);
        if ($result==1){
            $payment_notice_sn = $jubaopay->getEncrypt("payid");
            $outer_notice_sn = $jubaopay->getEncrypt("orderNo");
            $payment_notice = $GLOBALS['db']->getRow("select * from " . DB_PREFIX . "payment_notice where notice_sn = '" . $payment_notice_sn . "'");
            require_once APP_ROOT_PATH . "system/libs/cart.php";
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
	}

	function get_display_code(){

	}
}
?>