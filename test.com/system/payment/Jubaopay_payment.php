<?php
// +----------------------------------------------------------------------
// | Fanwe 方维p2p借贷系统
// +----------------------------------------------------------------------
// | Copyright (c) 2011 http://www.fanwe.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: 云淡风轻(88522820@qq.com)
// +----------------------------------------------------------------------

/*
// 测试商户编号
partner_id = '14061642390911131749';
*/


$payment_lang = array(
	'name'=>'聚宝付（WAP支付）',
    'partner_id' => '商户号',
);
$config = array(
    'partner_id' => array(
        'INPUT_TYPE' => '0',
    ),
);
/* 模块的基本信息 */
if (isset($read_modules) && $read_modules == true) {
    $module['class_name'] = 'Jubaopay';

    /* 名称 */
    $module['name'] = $payment_lang['name'];

	/* 支付方式：1：在线支付；0：线下支付  2:仅wap支付 3:仅app支付 4:兼容wap和app*/
    $module['online_pay'] = '4';

    /* 配送 */
    $module['config'] = $config;

    $module['lang']    = $payment_lang;
    $module['reg_url'] = '';
    return $module;
}
require_once APP_ROOT_PATH . 'system/libs/payment.php';
class Jubaopay_payment implements payment
{
    public function get_payment_code($payment_notice_id)
    {
        $pay = array();
		$pay['is_wap'] = 1;//
		$pay['class_name'] = "Jubaopay";
		$pay['url'] =SITE_DOMAIN.APP_ROOT.'/mapi/index.php?ctl=pay&act=get_display_code&pay_code=Jubaopay&notice_id='.$payment_notice_id;
		$pay['sdk_code'] = array("pay_sdk_type"=>"yjwap","config"=>
			array(
				"url"=>SITE_DOMAIN.APP_ROOT.'/mapi/index.php?ctl=pay&act=get_display_code&pay_code=Jubaopay&notice_id='.$payment_notice_id,
				"is_wap"=>1
			)
		);
		return $pay;
    }
    public function notify($request)
    {
        $payment           = $GLOBALS['db']->getRow("select id,config from " . DB_PREFIX . "payment where class_name='Jubaopay'");
        $payment['config'] = unserialize($payment['config']);
		require_once APP_ROOT_PATH . "system/payment/Jubaopay/jubaopay.php";
		$jubaopay=new jubaopay(APP_ROOT_PATH . "system/payment/Jubaopay/jubaopay.ini");
		$message=$request["message"];
		$signature=$request["signature"];
		$jubaopay->decrypt($message);
		// 校验签名，然后进行业务处理
		$result=$jubaopay->verify($signature);
		if($result==1) {
			$payment_notice_sn = $jubaopay->getEncrypt("payid");
			$outer_notice_sn = $jubaopay->getEncrypt("orderNo");
			$payment_notice = $GLOBALS['db']->getRow("select * from " . DB_PREFIX . "payment_notice where notice_sn = '" . $payment_notice_sn . "'");
			require_once APP_ROOT_PATH . "system/libs/cart.php";
			$rs = payment_paid($payment_notice['notice_sn'],$outer_notice_sn);
            if($rs['status']==1){
				echo "success"; // 像服务返回 "success";
			} else {
				echo "pay failed"; // 像服务返回 "success"
			}
		} else {
			echo "verify failed";;
		}
    }
    public function response($request)
    {
        $payment           = $GLOBALS['db']->getRow("select id,config from " . DB_PREFIX . "payment where class_name='Jubaopay'");
        $payment['config'] = unserialize($payment['config']);

		require_once APP_ROOT_PATH . "system/payment/Jubaopay/jubaopay.php";
		$jubaopay=new jubaopay(APP_ROOT_PATH . "system/payment/Jubaopay/jubaopay.ini");
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
				echo '<!DOCTYPE html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=2.0, user-scalable=0,minimum-scale=0.5"><title></title><script>function close_page(){try{App.close_page();}catch(e){alert("SDK调用失败");}}</script></head><body><div style="width:120px;height:40px;line-height:40px;font-size:14px;text-align:center;background:#ff4d7f;color:#fff;margin:20px auto;border-radius:5px;" onclick="close_page();">支付成功!关闭当前页面</div></body></html>';
			} else {
				echo '<!DOCTYPE html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=2.0, user-scalable=0,minimum-scale=0.5"><title></title><script>function close_page(){try{App.close_page();}catch(e){alert("SDK调用失败");}}</script></head><body><div style="width:120px;height:40px;line-height:40px;font-size:14px;text-align:center;background:#ff4d7f;color:#fff;margin:20px auto;border-radius:5px;" onclick="close_page();">支付成功,回调失败!关闭当前页面</div></body></html>';
			}
		} else {
            echo '<!DOCTYPE html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=2.0, user-scalable=0,minimum-scale=0.5"><title></title><script>function close_page(){try{App.close_page();}catch(e){alert("SDK调用失败");}}</script></head><body><div style="width:120px;height:40px;line-height:40px;font-size:14px;text-align:center;background:#ff4d7f;color:#fff;margin:20px auto;border-radius:5px;" onclick="close_page();">支付成功,回调失败!关闭当前页面</div></body></html>';
        }
		
    }
    public function get_display_code()
    {

    }
	
	public function display_code($payment_notice_id)
	{	
		if($payment_notice_id){
				$payment_notice = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."payment_notice where id = ".$payment_notice_id);
				$money = round($payment_notice['money'],2);
				$payment_info = $GLOBALS['db']->getRow("select id,config,logo from ".DB_PREFIX."payment where id=".intval($payment_notice['payment_id']));
				$payment_info['config'] = unserialize($payment_info['config']);

				$m_config =  load_auto_cache("m_config");
				$title_name = $m_config['ticket_name'];
				if($title_name=='')$title_name = '虚拟印币';
				$subject = msubstr($title_name,0,40);

				require_once APP_ROOT_PATH . "system/payment/Jubaopay/jubaopay.php";

				$jubaopay=new jubaopay(APP_ROOT_PATH . "system/payment/Jubaopay/jubaopay.ini");

				$payid=$payment_notice['notice_sn'];
				$partnerid=$payment_info['config']['partner_id'];
				$amount=$money;
				$payerName=$payment_notice['user_id'];
				$remark=$subject;
				$returnURL=SITE_DOMAIN . '/callback/payment/jubaopay_response.php';    // 可在商户后台设置
				$callBackURL=SITE_DOMAIN . '/callback/payment/jubaopay_notify.php';  // 可在商户后台设置
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
				$payLinks = '<form method="post" action="https://mapi.jubaopay.com/apiwapsyt.htm" id="payForm">';
				$payLinks .= '<input type="hidden" name="message" id="message" value="' . $message . '" />';
				$payLinks .= '<input type="hidden" name="signature" id="signature" value="' . $signature. '"/>';
				$payLinks .= '</form>';
				$payLinks .= '<script type="text/javascript">document.getElementById("payForm").submit();</script>';
				return $payLinks;
		}
		else
		{
			return '';
		}
	}	
}
