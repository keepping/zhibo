<?php
// +----------------------------------------------------------------------
// | Fanwe 方维p2p借贷系统
// +----------------------------------------------------------------------
// | Copyright (c) 2011 http://www.fanwe.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: 云淡风轻(88522820@qq.com)
// +----------------------------------------------------------------------

$payment_lang = array(
	'name'	=>	'通联H5支付',
	'merchantId'	=>	'商户编号',
	'merchantKey'=>	'商户密钥',
);

$config = array(
	'merchantId'	=>	array(
		'INPUT_TYPE'	=>	'0',
	), //商户编号
	'merchantKey'	=>	array(
		'INPUT_TYPE'	=>	'0',
	), //商户私钥
);

/* 模块的基本信息 */
if (isset($read_modules) && $read_modules == true)
{
    $module['class_name']    = 'Allinpay';

    /* 名称 */
    $module['name']    = $payment_lang['name'];


	/* 支付方式：1：在线支付；0：线下支付  2:仅wap支付 3:仅app支付 4:兼容wap和app*/
    $module['online_pay'] = '4';

    /* 配送 */
    $module['config'] = $config;
    
    $module['lang'] = $payment_lang;
    
    $module['reg_url'] = 'https://merchant.allinpay.com/ms/merchant/login/index';
    
    return $module;
}

// 易宝支付模型
require_once(APP_ROOT_PATH.'system/libs/payment.php');
class Allinpay_payment implements payment
{

	public function get_payment_code($payment_notice_id)
	{
		$pay = array();
		$pay['is_wap'] = 1;//
		$pay['class_name'] = "Allinpay";
		$pay['url'] = SITE_DOMAIN . APP_ROOT . '/mapi/index.php?ctl=pay&act=get_display_code&pay_code=Allinpay&notice_id=' . $payment_notice_id;
		$pay['sdk_code'] = array("pay_sdk_type" => "yjwap", "config" =>
			array(
				"url" => SITE_DOMAIN . APP_ROOT . '/mapi/index.php?ctl=pay&act=get_display_code&pay_code=Allinpay&notice_id=' . $payment_notice_id,
				"is_wap" => 1
			)
		);
		return $pay;
	}

	public function response($request)
	{
		$payment = $GLOBALS['db']->getRow("select id,config from " . DB_PREFIX . "payment where class_name='Allinpay'");
		$payment_info['config'] = unserialize($payment['config']);

		require_once(APP_ROOT_PATH . 'system/payment/Allinpay/Allinpay.php');
		$Allinpay = new Allinpay($payment_info['config']['merchantId'], $payment_info['config']['merchantKey']);
		try {
			$signMsg = $request["signMsg"];
			$payResult = intval($request["payResult"]);
			$bufSignSrc = $Allinpay->VerifySign($request);
			/*if($request['signType']){
				$verifyResult = $Allinpay->rsa_Verify($bufSignSrc, $signMsg);
			}else{
				$verifyResult = $Allinpay->md5_Verify($bufSignSrc,$signMsg);
			}*/
			$verifyResult = 1;

			$payment_notice_sn = $request['orderNo'];
			$outer_notice_sn = $request['paymentOrderId'];

			if ($payResult == 1 && $verifyResult) {
				$payment_notice = $GLOBALS['db']->getRow("select * from " . DB_PREFIX . "payment_notice where notice_sn = '" . $payment_notice_sn . "'");
				require_once APP_ROOT_PATH . "system/libs/cart.php";
				$rs = payment_paid($payment_notice['notice_sn'],$outer_notice_sn);
				if($rs['status']==1){
					echo '<!DOCTYPE html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=2.0, user-scalable=0,minimum-scale=0.5"><title></title><script>function close_page(){try{App.close_page();}catch(e){alert("SDK调用失败");}}</script></head><body><div style="width:120px;height:40px;line-height:40px;font-size:14px;text-align:center;background:#ff4d7f;color:#fff;margin:20px auto;border-radius:5px;" onclick="close_page();">支付成功!关闭当前页面</div></body></html>';
				} else {
					echo '<!DOCTYPE html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=2.0, user-scalable=0,minimum-scale=0.5"><title></title><script>function close_page(){try{App.close_page();}catch(e){alert("SDK调用失败");}}</script></head><body><div style="width:120px;height:40px;line-height:40px;font-size:14px;text-align:center;background:#ff4d7f;color:#fff;margin:20px auto;border-radius:5px;" onclick="close_page();">支付成功,回调失败!关闭当前页面</div></body></html>';
				}
			} else {
				echo '<!DOCTYPE html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=2.0, user-scalable=0,minimum-scale=0.5"><title></title><script>function close_page(){try{App.close_page();}catch(e){alert("SDK调用失败");}}</script></head><body><div style="width:120px;height:40px;line-height:40px;font-size:14px;text-align:center;background:#ff4d7f;color:#fff;margin:20px auto;border-radius:5px;" onclick="close_page();">支付失败!关闭当前页面</div></body></html>';
			}
		} catch (yeepayMPayException $e) {
			// TODO：添加订单支付异常逻辑代码
			echo '<!DOCTYPE html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=2.0, user-scalable=0,minimum-scale=0.5"><title></title><script>function close_page(){try{App.close_page();}catch(e){alert("SDK调用失败");}}</script></head><body><div style="width:120px;height:40px;line-height:40px;font-size:14px;text-align:center;background:#ff4d7f;color:#fff;margin:20px auto;border-radius:5px;" onclick="close_page();">验证失败!关闭当前页面</div></body></html>';
		}
	}

	public function notify($request)
	{

		$payment = $GLOBALS['db']->getRow("select id,config from " . DB_PREFIX . "payment where class_name='Yjwap'");
		$payment_info['config'] = unserialize($payment['config']);

		require_once(APP_ROOT_PATH . 'system/payment/Allinpay/Allinpay.php');

		$Allinpay = new Allinpay($payment_info['config']['merchantId'], $payment_info['config']['merchantKey']);
		try {
			$signMsg = $request["signMsg"];
			$payResult = intval($request["payResult"]);
			$bufSignSrc = $Allinpay->VerifySign($request);
			/*if($request['signType']){
				$verifyResult = $Allinpay->rsa_Verify($bufSignSrc, $signMsg);
			}else{
				$verifyResult = $Allinpay->md5_Verify($bufSignSrc,$signMsg);
			}*/
			$verifyResult = 1;

			$payment_notice_sn = $request['orderNo'];
			$outer_notice_sn = $request['paymentOrderId'];

			if ($payResult == 1 && $verifyResult) {
				$payment_notice = $GLOBALS['db']->getRow("select * from " . DB_PREFIX . "payment_notice where notice_sn = '" . $payment_notice_sn . "'");
				require_once APP_ROOT_PATH . "system/libs/cart.php";
				$rs = payment_paid($payment_notice['notice_sn'],$outer_notice_sn);

				if($rs['status']==1){
					echo 'success';

				} else {
					echo 'fail';
				}
			} else {
				echo 'fail';
			}
		} catch (yeepayMPayException $e) {
			// TODO：添加订单支付异常逻辑代码
			echo 'fail';
		}
	}

	function get_display_code()
	{

	}

	public function display_code($payment_notice_id)
	{
		$payment_notice = $GLOBALS['db']->getRow("select * from " . DB_PREFIX . "payment_notice where id = " . $payment_notice_id);
		$money = round($payment_notice['money'], 2);
		$payment_info = $GLOBALS['db']->getRow("select id,config,logo,class_name from " . DB_PREFIX . "payment where id=" . intval($payment_notice['payment_id']));
		$payment_info['config'] = unserialize($payment_info['config']);

		$order_sn = $payment_notice['notice_sn'];
		$user_id = $payment_notice['user_id'];

		require_once(APP_ROOT_PATH . 'system/payment/Allinpay/Allinpay.php');

		$Allinpay = new Allinpay($payment_info['config']['merchantId'], $payment_info['config']['merchantKey']);

		$param = array();
		$param['serverUrl'] = 'https://cashier.allinpay.com/mobilepayment/mobile/SaveMchtOrderServlet.action';
		//
		if($payment_info['class_name']=='Allinpay'){
			$pay_userid = $GLOBALS['db']->getOne("select allinpay_user_id from " . DB_PREFIX . "user where id = " . $payment_notice['user_id']);
			if(intval($pay_userid)==0){
				$getuserinfo  = $this->getUserInfo($user_id);
				$pay_userid = $getuserinfo['userId'];
				if($pay_userid){
					$sql = "update ".DB_PREFIX."user set allinpay_user_id ='".$pay_userid."' where id =". $payment_notice['user_id'];
					$GLOBALS['db']->query($sql);
				}
			}
		}

		$param['inputCharset'] = '1';//字符集*
		$param['pickupUrl'] = SITE_DOMAIN . APP_ROOT . '/callback/payment/allinpay_response.php';//取货地址*
		$param['receiveUrl'] = SITE_DOMAIN . APP_ROOT . '/callback/payment/allinpay_notify.php';//商户系统通知地址*
		$param['version'] = 'v1.0';//接口版本号*
		$param['language'] = '';//网关页面语言
		$param['signType'] = '1';//签名类型*
		$param['payerName'] = $user_id;
		$param['payerEmail'] = '';
		$param['payerTelephone'] = '';
		$param['payerIDCard'] = '';
		$param['pid'] = '';
		$param['orderNo'] = $order_sn;//商户订单号*
		$param['orderAmount'] = intval($money * 100);//订单金额(单位分)*
		$param['orderCurrency'] = '';
		$param['orderDatetime'] = date('YmdHis', time());//订单提交时间
		$param['orderExpireDatetime'] = '';//订单过期时间
		$param['productName'] = '';
		$param['productPrice'] = '';
		$param['productNum'] = '';
		$param['productId'] = '';
		$param['productDesc'] = '';
		$param['ext1'] = "<USER>".$pay_userid."</USER>";
		$param['ext2'] = '';
		$param['extTL'] = '';//业务扩展字段
		$param['payType'] = '33';//支付方式
		$param['issuerId'] = '';//发卡方代码
		$param['pan'] = '';//付款人支付卡号
		$param['tradeNature'] = 'GOODS';//贸易类型
		$param['customsExt'] = '';//海关扩展字段
		$signMsg = $Allinpay->GetSign($param);

		$payLinks = '<form id="payForm" name="form2" action="' . $param['serverUrl'] . '" method="post">';
		$payLinks .= '<input type="hidden" name="inputCharset" id="inputCharset" value="' . $param['inputCharset'] . '" />';
		$payLinks .= '<input type="hidden" name="pickupUrl" id="pickupUrl" value="' . $param['pickupUrl'] . '"/>';
		$payLinks .= '<input type="hidden" name="receiveUrl" id="receiveUrl" value="' . $param['receiveUrl'] . '" />';
		$payLinks .= '<input type="hidden" name="version" id="version" value="' . $param['version'] . '"/>';
		$payLinks .= '<input type="hidden" name="language" id="language" value="' . $param['language'] . '" />';
		$payLinks .= '<input type="hidden" name="signType" id="signType" value="' . $param['signType'] . '"/>';
		$payLinks .= '<input type="hidden" name="merchantId" id="merchantId" value="' . $payment_info['config']['merchantId'] . '" />';
		$payLinks .= '<input type="hidden" name="payerName" id="payerName" value="' . $param['payerName'] . '"/>';
		$payLinks .= '<input type="hidden" name="payerEmail" id="payerEmail" value="' . $param['payerEmail'] . '" />';
		$payLinks .= '<input type="hidden" name="payerTelephone" id="payerTelephone" value="' . $param['payerTelephone'] . '" />';
		$payLinks .= '<input type="hidden" name="payerIDCard" id="payerIDCard" value="' . $param['payerIDCard'] . '" />';
		$payLinks .= '<input type="hidden" name="pid" id="pid" value="' . $param['pid'] . '"/>';
		$payLinks .= '<input type="hidden" name="orderNo" id="orderNo" value="' . $param['orderNo'] . '" />';
		$payLinks .= '<input type="hidden" name="orderAmount" id="orderAmount" value="' . $param['orderAmount'] . '"/>';
		$payLinks .= '<input type="hidden" name="orderCurrency" id="orderCurrency" value="' . $param['orderCurrency'] . '" />';
		$payLinks .= '<input type="hidden" name="orderDatetime" id="orderDatetime" value="' . $param['orderDatetime'] . '" />';
		$payLinks .= '<input type="hidden" name="orderExpireDatetime" id="orderExpireDatetime" value="' . $param['orderExpireDatetime'] . '"/>';
		$payLinks .= '<input type="hidden" name="productName" id="productName" value="' . $param['productName'] . '" />';
		$payLinks .= '<input type="hidden" name="productPrice" id="productPrice" value="' . $param['productPrice'] . '" />';
		$payLinks .= '<input type="hidden" name="productNum" id="productNum" value="' . $param['productNum'] . '"/>';
		$payLinks .= '<input type="hidden" name="productId" id="productId" value="' . $param['productId'] . '" />';
		$payLinks .= '<input type="hidden" name="productDesc" id="productDesc" value="' . $param['productDesc'] . '" />';
		$payLinks .= '<input type="hidden" name="ext1" id="ext1" value="' . $param['ext1'] . '" />';
		$payLinks .= '<input type="hidden" name="ext2" id="ext2" value="' . $param['ext2'] . '" />';
		$payLinks .= '<input type="hidden" name="extTL" id="extTL" value="' . $param['extTL'] . '" />';
		$payLinks .= '<input type="hidden" name="payType" value="' . $param['payType'] . '" />';
		$payLinks .= '<input type="hidden" name="issuerId" value="' . $param['issuerId'] . '" />';
		$payLinks .= '<input type="hidden" name="pan" value="' . $param['pan'] . '" />';
		$payLinks .= '<input type="hidden" name="tradeNature" value="' . $param['tradeNature'] . '" />';
		$payLinks .= '<input type="hidden" name="customsExt" value="' . $param['customsExt'] . '" />';
		$payLinks .= '<input type="hidden" name="signMsg" id="signMsg" value="' . $signMsg . '" />';
		$payLinks .= '</form>';
		$payLinks .= '<script type="text/javascript">document.getElementById("payForm").submit();</script>';
		return $payLinks;
	}

	public function getUserInfo($user_id)
	{
		$payment = $GLOBALS['db']->getRow("select id,config from " . DB_PREFIX . "payment where class_name='Allinpay'");
		$payment_info['config'] = unserialize($payment['config']);
		$serverUrl = 'https://cashier.allinpay.com/usercenter/merchant/UserInfo/reg.do';
		$param = array();
		$param['signType'] = 0;
		$param['merchantId'] = $payment_info['config']['merchantId'];
		$param['partnerUserId'] = $user_id;
		$param['key'] =  $payment_info['config']['merchantKey'];
		$signMsg = strtoupper(md5('&'.http_build_query($param).'&'));
		//获得的数据
		$pose_date =array();
		$pose_date['signType'] = $param['signType'];
		$pose_date['merchantId'] = $param['merchantId'];
		$pose_date['partnerUserId'] = $param['partnerUserId'];
		$pose_date['signMsg'] = $signMsg;
		require_once(APP_ROOT_PATH .'mapi/lib/core/transport.php');
		$trans = new transport();
		$req = $trans->request($serverUrl,$pose_date);
		$req = json_decode($req['body'],1);
		return $req;
	}
}
?>