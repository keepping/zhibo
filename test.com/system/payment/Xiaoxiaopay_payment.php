<?php
// +----------------------------------------------------------------------
// | Fanwe 方维p2p借贷系统
// +----------------------------------------------------------------------
// | Copyright (c) 2011 http://www.fanwe.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: 云淡风轻(88522820@qq.com)
// +----------------------------------------------------------------------

$payment_lang = array(
	'name'	=>	'小小贝支付',
	'merchantID'	=>	'商户号',
	'appkey'=>	'商户私钥',
	'platpkey'	=>	'平台公钥',
);

$config = array(
	'merchantID'	=>	array(
		'INPUT_TYPE'	=>	'0',
	), //商户号
	'appkey'	=>	array(
		'INPUT_TYPE'	=>	'0',
	), //商户私钥
	'platpkey'	=>	array(
		'INPUT_TYPE'	=>	'0',
	), //平台公钥
);

/* 模块的基本信息 */
if (isset($read_modules) && $read_modules == true)
{
	$module['class_name']    = 'Xiaoxiaopay';

	/* 名称 */
	$module['name']    = $payment_lang['name'];


	/* 支付方式：1：在线支付；0：线下支付  2:仅wap支付 3:仅app支付 4:兼容wap和app*/
	$module['online_pay'] = '4';

	/* 配送 */
	$module['config'] = $config;

	$module['lang'] = $payment_lang;

	$module['reg_url'] = 'http://act.life.alipay.com/systembiz/fangwei/';

	return $module;
}

// 易宝支付模型
require_once(APP_ROOT_PATH.'system/libs/payment.php');
class Xiaoxiaopay_payment implements payment {

	public function get_payment_code($payment_notice_id)
	{
		$pay = array();
		$pay['is_wap'] = 1;//
		$pay['is_without'] = 1;//跳转外部浏览器
		$pay['class_name'] = "Xiaoxiaopay";
		$pay['url'] =SITE_DOMAIN.APP_ROOT.'/mapi/index.php?ctl=pay&act=get_display_code&pay_code=Xiaoxiaopay&notice_id='.$payment_notice_id;
		$pay['sdk_code'] = array("pay_sdk_type"=>"yjwap","config"=>
			array(
				"url"=>SITE_DOMAIN.APP_ROOT.'/mapi/index.php?ctl=pay&act=get_display_code&pay_code=Xiaoxiaopay&notice_id='.$payment_notice_id,
				"is_wap"=>1
			)
		);
		return $pay;
	}

	public function response($request)
	{
		//log_file('response','Xiaoxiaopay');
		//log_file($request,'Xiaoxiaopay');
		$payment = $GLOBALS['db']->getRow("select id,config from ".DB_PREFIX."payment where class_name='Xiaoxiaopay'");
		$payment_info['config'] = unserialize($payment['config']);

		require_once(APP_ROOT_PATH . 'system/payment/xiaoxiaopay/base.php');
		//if(!parseRespRsa($request, $payment_info['config']['platpkey'])){
		if(1){
			echo '<!DOCTYPE html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=2.0, user-scalable=0,minimum-scale=0.5"><title></title><script>function close_page(){try{App.close_page();}catch(e){alert("SDK调用失败");}}</script></head><body><div style="width:200px;height:40px;line-height:40px;font-size:14px;text-align:center;background:#ff4d7f;color:#fff;margin:20px auto;border-radius:5px;" >支付结果！返回APP刷新查看</div></body></html>';
			exit;
		}else{
			$request=json_decode($request,1);
			if($request['resultCode'] == '20000')
			{
				$payment_notice_sn = $request['info']['pay_order'];
				$outer_notice_sn = $request['info']['transid'];
				$payment_notice = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."payment_notice where notice_sn = '".$payment_notice_sn."'");
				$user_diamonds = $GLOBALS['db']->getOne("select diamonds from ".DB_PREFIX."user where id = '".$payment_notice['user_id']."'");
				require_once APP_ROOT_PATH."system/libs/cart.php";
				$rs = payment_paid($payment_notice['notice_sn'],$outer_notice_sn);
				if($rs['status']==1)
				{
					echo '<!DOCTYPE html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=2.0, user-scalable=0,minimum-scale=0.5"><title></title><script>function close_page(){try{App.close_page();}catch(e){alert("SDK调用失败");}}</script></head><body><div style="width:120px;height:40px;line-height:40px;font-size:14px;text-align:center;background:#ff4d7f;color:#fff;margin:20px auto;border-radius:5px;" >付款成功！当前余额：'.$user_diamonds.'</div></body></html>';
					exit;
				}
				else
				{
					echo '<!DOCTYPE html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=2.0, user-scalable=0,minimum-scale=0.5"><title></title><script>function close_page(){try{App.close_page();}catch(e){alert("SDK调用失败");}}</script></head><body><div style="width:120px;height:40px;line-height:40px;font-size:14px;text-align:center;background:#ff4d7f;color:#fff;margin:20px auto;border-radius:5px;" >回调失败！关闭当前页面</div></body></html>';
					exit;
				}
			} else{
				echo '<!DOCTYPE html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=2.0, user-scalable=0,minimum-scale=0.5"><title></title><script>function close_page(){try{App.close_page();}catch(e){alert("SDK调用失败");}}</script></head><body><div style="width:120px;height:40px;line-height:40px;font-size:14px;text-align:center;background:#ff4d7f;color:#fff;margin:20px auto;border-radius:5px;" >'.$request['msg'].'关闭当前页面</div></body></html>';
				exit;
			}
		}
	}

	public function notify($request)
	{
		$payment = $GLOBALS['db']->getRow("select id,config from ".DB_PREFIX."payment where class_name='Xiaoxiaopay'");
		$payment_info['config'] = unserialize($payment['config']);

		require_once(APP_ROOT_PATH . 'system/payment/xiaoxiaopay/base.php');

		if(!parseRespRsa($request, $payment_info['config']['platpkey'])&&0){
			echo 'failed';
			exit;
		}else{
			$request=json_decode($request,1);
			if($request['resultCode'] == '20000')
			{
				$payment_notice_sn = $request['info']['pay_order'];
				$outer_notice_sn = $request['info']['transid'];
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
			}
		}
	}

	function get_display_code(){

	}

	public function display_code($payment_notice_id)
	{
		if($payment_notice_id) {
			$payment_notice = $GLOBALS['db']->getRow("select * from " . DB_PREFIX . "payment_notice where id = " . $payment_notice_id);
			$money = round($payment_notice['money'], 2);
			$payment_info = $GLOBALS['db']->getRow("select id,config,logo from " . DB_PREFIX . "payment where id=" . intval($payment_notice['payment_id']));
			$payment_info['config'] = unserialize($payment_info['config']);

			require_once(APP_ROOT_PATH . 'system/payment/xiaoxiaopay/base.php');

			$server = "http://api2.xiaoxiaopay.com:7500";                        //小小贝商户后台接入url
			$orderUrl = $server . "/order/";            //预下单接口 url
			$queryResultUrl = $server . "/query/";    //主动查询订单接口 url

			$params = array();
			$params['merchantID'] = $payment_info['config']['merchantID'];
			$params['waresname'] = $payment_notice['recharge_name'];
			$params['cporderid'] = $payment_notice['notice_sn'];
			$params['price'] = number_format($payment_notice['money'], 2);
			$params['returnurl'] = SITE_DOMAIN.APP_ROOT.'/callback/payment/Xiaoxiaopay_response.php';
			$params['notifyurl'] = SITE_DOMAIN.APP_ROOT.'/callback/payment/Xiaoxiaopay_notify.php';
			$params['paytype'] = '10002';

			/*
                10001 微信扫码支付
                10002 微信外WAP支付
                10003 微信内WAP支付
                10004 微信APP支付
                10005 支付宝扫码支付
                10006 支付宝外WAP支付
                10007 支付宝内WAP支付
                10008 支付宝APP支付
                10009 银联PC支付
            */
			$params['ip'] = get_real_ip();
			//$params['ext'] = '';
			$reqData = composeRsa($params, $payment_info['config']['appkey']);
			$paymentData = HttpPost($orderUrl, $reqData);
			$result = array();
			//$paymentData = '{"resultCode":20000,"sign":"jUFl2s1 CPpblAxgJTawNExAP33WfmwFpraNkBxLYiq7TAxRR0UmxSvbzmR8DKdG wsGgmTRMpO5voWgYK0qnQeHluu3yzID0igCaIyY87kOljPgcS2DNYXb06FqJdMZifdsXnVW21toJ8R76Xqbr8Y6mqycfHPYFm5G0FhjST0=","signtype":"RSA","info":{"payurl":"http://recharge.tongle.net/mpway/heewappay.aspx?state=104920170526221715258","nonceStr":"9ff062f14a0c9f2cad5163553324a696"}}';
			//$paymentData = {"msg":"Signature error","resultCode":10012}

			if (!parseRespRsa($paymentData, $payment_info['config']['platpkey'])) { //MD5的验签方法为 parseRespMd5

				if (isset($paymentData)) {
					$callback = json_decode($paymentData);
					$return['errcode'] = 10000;
					//$return['errcode'] = $callback->resultCode;
					$return['message'] = $callback->msg;
				} else {
					$return['errcode'] = 10000;
					$return['message'] = "验签失败";
				}
			} else {
				//解析返回报文
				$callback = json_decode($paymentData);
				//支付调起成功之后获取支付参数
				$url = $callback->info->payurl;
				//获取到支付参数后针对不同支付方式做不同处理。
				$return['errcode'] = 0;
				$return['message'] = $url;
			}
			if($return['errcode']!=0)	{
				echo  $return['message'];
			}else{
				header('Location:'.$return['message']);
			}
		}

	}
}
?>