<?php

$payment_lang = array(
	'name'	=>	'通联微信支付',
	'cusid'	=>	'商户号(平台分配)',
	'appid'	=>'应用ID(平台分配)',
	'key'	=>	'密钥KEY(平台分配)',
		
	'sub_appid'	=>	'微信appid',
	'sub_mchid'	=>'微信子商户号',
	'wxapp_key'	=>	'微信密钥KEY',		
);
$config = array(
	'cusid'	=>	array(
		'INPUT_TYPE'	=>	'0',
	), 
		
	'appid'	=>	array(
				'INPUT_TYPE'	=>	'0'
		),

	'key'	=>	array(
		'INPUT_TYPE'	=>	'0'
	),
		'sub_appid'	=>	array(
				'INPUT_TYPE'	=>	'0'
		),
		'sub_mchid'	=>	array(
				'INPUT_TYPE'	=>	'0'
		),
		'wxapp_key'	=>	array(
				'INPUT_TYPE'	=>	'0'
		)
);

/* 模块的基本信息 */
if (isset($read_modules) && $read_modules == true)
{
    $module['class_name']    = 'WxAllin';

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
class WxAllin_payment implements payment {

	public function get_payment_code($payment_notice_id)
	{
		$payment_notice = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."payment_notice where id = ".$payment_notice_id);
		//log_result("==payment_notice==");
		//log_result($payment_notice);
		//$order_sn = $GLOBALS['db']->getOne("select order_sn from ".DB_PREFIX."deal_order where id = ".$payment_notice['order_id']);
		$money = round($payment_notice['money'],2);
		$payment_info = $GLOBALS['db']->getRow("select id,config,logo from ".DB_PREFIX."payment where id=".intval($payment_notice['payment_id']));
		$payment_info['config'] = unserialize($payment_info['config']);

		
		$m_config =  load_auto_cache("m_config");
		$title_name = $m_config['ticket_name'];
		if($title_name=='')
		$title_name = '虚拟印币';
		
		if(empty($title_name))
		{
			$title_name = "充值".round($payment_notice['money'],2)."元";
		}
		
		$pay['pay_info'] = $title_name;
		$pay['payment_name'] = "通联微信支付";
        $pay['pay_money'] = $money;
		$pay['pay_id'] = $payment_notice['id'];
		$pay['class_name'] = "WxAllin";
		
		
		//$subject = msubstr($title_name,0,40);
		$subject = $title_name;
		$data_notify_url = SITE_DOMAIN.APP_ROOT.'/callback/payment/wxallin_notify.php';
		
		require_once(APP_ROOT_PATH.'system/payment/Wxapp/WxPay.Api.php');
		require_once(APP_ROOT_PATH.'system/payment/Wxapp/WxPay.Notify.php');
		require_once(APP_ROOT_PATH.'system/payment/Wxapp/WxPay.Data.php');
		
		
		$nonce_str = WxPayApi::getNonceStr();
		
		$params = array();
		$params['cusid'] = $payment_info['config']['cusid'];//商户号  平台分配
		$params['appid'] = $payment_info['config']['appid'];//应用ID	平台分配的APPID
		$params['version'] = '11';//版本号	接口版本号	可	2	默认填11
		
		$params['trxamt'] = $payment_notice['money'] * 100;//交易金额	单位为分
		$params['reqsn'] = $payment_notice['id'];//商户交易单号	商户的交易订单号	否	32	商户平台唯一
		
		$params['paytype'] = 2;//交易方式	2:微信app支付

		$params['randomstr'] = $nonce_str;//rand(1000000000, 9000000000);//随机字符串	商户自行生成的随机字符串
		
		$params['body'] = $title_name;//订单标题	订单商品名称，为空则以商户名作为商品名称
		
		$params['validtime'] = 86400;//有效时间	订单有效时间，以分为单位，不填默认为15分钟
	
		$params['sub_appid'] = $payment_info['config']['sub_appid'];//微信appid	微信app支付必填, 开发者在微信开放平台申请	是
		;
		$params['sub_mchid'] = $payment_info['config']['sub_mchid'];//微信子商户号	微信app支付必填,由通联平台分配 是
		
		//交易结果通知地址	接收微信支付异步通知回调地址，通知url必须为直接可访问的url，不能携带参数。	是	256	对于微信刷卡支付，该字段无效
		$params['notify_url'] = $data_notify_url;
		
		//指定支付方式	no_credit--指定不能使用信用卡支付	否	32	目前仅支持no_credit
		$params['limit_pay'] = 'no_credit';
			
		
		ksort($params);
		reset($params);
		
		$sign  = '';
		
		foreach ($params as $key => $val) {
			$sign .= "$key=$val&";
		}
		
		$sign     = substr($sign, 0, -1) . $payment_info['config']['key'];
		$sign_md5 = md5($sign);
		
		$params['sign'] = $sign_md5;
		
		//接口地址：https://vsp.allinpay.com/apiweb/weixin/pay
		//接入测试参数：
		/*
		 商户号：990581007426001
		APPID：00000051
		KEY：allinpay888
		URL: http://113.108.182.3:10080/apiweb/weixin
		*/
		
		$url = 'http://113.108.182.3:10080/apiweb/weixin';
		
		//print_r($params);
		
		require_once(APP_ROOT_PATH.'system/utils/transport.php');
		$trans = new transport();
		$req = $trans->request($url,$params);
		
		print_r($req['body']);
		
		$ret = json_decode($req['body'],true);
		
		if ($ret['retcode'] == 'SUCCESS'){
			/*
			cusid	商户号	平台分配的商户号	否	15
			appid	应用ID	平台分配的APPID	否	8
			trxid	交易单号	平台的交易流水号	否	20
			chnltrxid	微信交易单号	微信平台的交易单号	是	50
			reqsn	商户交易单号	商户的交易订单号	否	32
			randomstr	随机字符串	随机生成的字符串	否	32
			trxstatus	交易状态	交易的状态,
			对于微信刷卡支付，该状态表示实际的支付结果，其他为下单状态	否	4	详见3.1
			fintime	交易完成时间	yyyyMMddHHmmss	是	14	对于微信刷卡支付有效
			errmsg	错误原因	失败的原因说明	是	100
			weixinstr	微信支付串	App支付返回json串	是	不限	见第4章节示例
			sign	签名		否	32	详见1.5
			
			
			{"appid":"00000006","cusid":"XXXXXXXX","weixinstr":"{\"partnerId \":\"10000100\",\"timeStamp\":\"1477356696\",\"prepayId\":\"1101000000140415649af9fc314aa427\",\"package\":\"Sign=WXPay\",\"nonceStr\":\"38642\",\"sign\":\"66FF000B739F459D093FE24AB3462170\"}","reqsn":"1610258923119024","retcode":"SUCCESS","sign":"9513D3ABF5983F3FACCD8161931DB1ED","trxid":"180681592","trxstatus":"0000"}
			*/
			
			$result = json_decode($ret['weixinstr'],true);
			
			$timestamp = get_gmtime();
			
			
			define('WXAPP_KEY',$payment_info['config']['wxapp_key']);
			
			
			//调起支付
			$wx_pay = new WxPayDataBase();		
			$wx_pay->Set('appid',$payment_info['config']['sub_appid']);
			$wx_pay->Set('partnerid',$payment_info['config']['sub_mchid']);
			$wx_pay->Set('prepayid',$result['prepay_id']);//预支付交易会话ID
			$wx_pay->Set('package','prepay_id='.$result['prepay_id']);//android 写法	
			$wx_pay->Set('noncestr',$nonce_str);//随机字符串
			$wx_pay->Set('timestamp',$timestamp);//时间戳				
			$wx_pay->SetSign(false);//签名		
			
			$pay['config'] = $wx_pay->GetValues();
			
			$wx_pay = new WxPayDataBase();
			$wx_pay->Set('appid',$payment_info['config']['sub_appid']);
			$wx_pay->Set('partnerid',$payment_info['config']['sub_mchid']);
			$wx_pay->Set('prepayid',$result['prepay_id']);//预支付交易会话ID
			$wx_pay->Set('package','Sign=Wxpay');//ios 写法
			$wx_pay->Set('noncestr',$nonce_str);//随机字符串
			$wx_pay->Set('timestamp',$timestamp);//时间戳
			$wx_pay->SetSign(false);//签名
			
			$pay['config']['ios'] = $wx_pay->GetValues();
	
			$pay['config']['packagevalue'] = 'prepay_id='.$result['prepay_id'];
			$pay['config']['subject'] = $subject;
			$pay['config']['body'] = $title_name;
			$pay['config']['total_fee'] = $money;
			$pay['config']['total_fee_format'] = format_price($money);
			$pay['config']['out_trade_no'] = $payment_notice['notice_sn'];
			$pay['config']['notify_url'] = $data_notify_url;
			
			
			//$pay['mch_id'] = $payment_info['config']['wxapp_partnerid'];
			//$pay['config']['key'] = $payment_info['config']['wxapp_key'];
			//$pay['config']['secret'] = $payment_info['config']['wxapp_secret'];
			
			
			if(isios())
			{
				$pay['sdk_code'] = array("pay_sdk_type"=>"wxpay","config"=>
						array(
								"appid"=>$payment_info['config']['sub_appid'],
								"partnerid"=>$payment_info['config']['sub_mchid'],
								"prepayid"=>$result['prepay_id'],
								"noncestr"=>$nonce_str,
								"timestamp"=>$timestamp,
								"package"=>"Sign=Wxpay",
								"sign" => $pay['config']['ios']['sign']
						)
				);
			}else{
				$pay['sdk_code'] = array("pay_sdk_type"=>"wxpay","config"=>
						array(
								"appid"=>$payment_info['config']['sub_appid'],
								"partnerid"=>$payment_info['config']['sub_mchid'],
								"prepayid"=>$result['prepay_id'],
								"noncestr"=>$nonce_str,
								"timestamp"=>$timestamp,
								"packagevalue"=>'prepay_id='.$result['prepay_id'],
								"sign"=>$pay['config']['sign']
						)
				);
			}
			
			$pay['ret'] = $ret;
		}else{
			//$ret['retmsg']
			$pay = $ret;
		}
		
		
		return $pay;
	}
	
	public function notify($request)
	{	
		/*
		appid	平台分配的APPID
		outtrxid	收银宝平台流水号	通联系统内唯一
		trxcode	交易类型	见附录3.2
		trxid	通联交易流水号	通联系统内唯一
		trxamt	交易金额	分为单位
		trxdate	交易请求日期	yyyyMMdd
		paytime	交易完成时间	yyyyMMddHHmmss
		chnltrxid	微信交易单号	微信订单号
		trxstatus	交易状态	见3.1
		cusid	商户号
		termno	终端号
		termbatchid	终端批次号
		termtraceno	终端流水号
		termauthno	终端授权码
		termrefnum	终端参考号
		trxreserved	交易备注
		srctrxid	原交易ID	对于冲正、撤销、退货等交易时填写
		cusorderid	商户订单号
		acct	支付人帐号	微信支付的openid
		如果信息为空,则默认填写000000
		sign	签名信息	详见1.5
		*/
		
		$payment = $GLOBALS['db']->getRow("select id,config from ".DB_PREFIX."payment where class_name='WxAllin'");
		$payment['config'] = unserialize($payment['config']);
		
		/* 检查数字签名是否正确 */
		ksort($request);
		reset($request);
		
		foreach ($request AS $key=>$val)
		{
			if ($key != 'sign')
			{
				$sign .= "$key=$val&";
			}
		}
		
		$sign = substr($sign, 0, -1) . $payment['config']['key'];
		
		if (md5($sign) == $request['sign'])
		{
			if ($request['return_code'] == 'SUCCESS'){
				$payment_notice_sn = strim($request['cusorderid']);
				$outer_notice_sn = $request['outtrxid'];
				
			   require_once APP_ROOT_PATH."system/libs/cart.php";
			   $rs = payment_paid($payment_notice_sn,$outer_notice_sn);					
			   if ($rs)
			   {
			   	  echo "success";
			   }else{
			   	  echo "success";
			   }
			}else{
			   echo "fail";
			} 
		}else{
			echo "fail";
		}
	}
		//响应通知
	function response($request)
	{}
	
	//获取接口的显示
	function get_display_code()
	{
		return "通联微信支付";
	}
	
}




?>