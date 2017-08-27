<?php
// +----------------------------------------------------------------------
// | EaseTHINK 易想团购系统
// +----------------------------------------------------------------------
// | Copyright (c) 2010 http://www.easethink.com All rights reserved.
// +----------------------------------------------------------------------

$payment_lang = array(
	'name'	=>	'微信WAP(充值/提现)',
	'appid'	=>	'微信公众号ID',
	'appsecret'=>'微信公众号SECRT',
	'mchid'	=>	'微信支付MCHID',
  //	'partnerid'	=>	'商户ID',
	//'partnerkey'	=>	'商户key',
	'key'	=>	'商户支付密钥Key/api秘钥',
	'sslcert'=>'apiclient_cert证书路径',
	'sslkey'=>'apiclient_key证书路径',
	'type'=>'类型(V3或V4)',
);
$config = array(
	'appid'=>array(
		'INPUT_TYPE'=>'0',
	),//微信公众号ID
	'appsecret'	=>	array(
		'INPUT_TYPE'	=>	'0',
	), //微信公众号SECRT
	'mchid'	=>	array(
		'INPUT_TYPE'	=>	'0'
	), //微信支付MCHID
// 	'partnerid'	=>	array(
//		'INPUT_TYPE'	=>	'0'
//	), //商户ID
//	'partnerkey'	=>	array(
//		'INPUT_TYPE'	=>	'0'
//	), //商户key
	'key'	=>	array(
		'INPUT_TYPE'	=>	'0',
	), //商户支付密钥Key
	'sslcert'	=>	array(
		'INPUT_TYPE'	=>	'0',
	), //apiclient_cert证书路径
	'sslkey'	=>	array(
		'INPUT_TYPE'	=>	'0',
	), //apiclient_key证书路径
	'type'	=>	array(
		'INPUT_TYPE'	=>	'0',
	), //类型
);
/* 模块的基本信息 */
if (isset($read_modules) && $read_modules == true)
{
    $module['class_name']    = 'Wwxjspay';

    /* 名称 */
    $module['name']    = $payment_lang['name'];


    /* 支付方式：1：在线支付；0：线下支付;2:手机wap;3:手机sdk */
    $module['online_pay'] = '2';

    /* 配送 */
    $module['config'] = $config;
    
    $module['lang'] = $payment_lang;
    $module['reg_url'] = '';
    return $module;
}

// 支付宝手机支付模型
require_once(APP_ROOT_PATH.'system/libs/payment.php');
class Wwxjspay_payment implements payment {

    public function get_payment_code($payment_notice_id)
    {
        $payment_notice = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."payment_notice where id = ".$payment_notice_id);
        //log_result("==payment_notice==");
        //log_result($payment_notice);
        //$order_sn = $GLOBALS['db']->getOne("select order_sn from ".DB_PREFIX."deal_order where id = ".$payment_notice['order_id']);
        $money = round($payment_notice['money'],2);
        $payment_info = $GLOBALS['db']->getRow("select id,config,logo from ".DB_PREFIX."payment where id=".intval($payment_notice['payment_id']));
        $payment_info['config'] = unserialize($payment_info['config']);
        //log_result("==payment_info==");
        //log_result($payment_info);
        $this->init_define($payment_info);

        /*$sql = "select name ".
                          "from ".DB_PREFIX."deal_order_item ".
                          "where order_id =". intval($payment_notice['order_id']);
        $title_name = $GLOBALS['db']->getOne($sql);*/

        $m_config =  load_auto_cache("m_config");
        $title_name = $m_config['ticket_name'];
        if($title_name=='')
            $title_name = '虚拟印币';

        if(empty($title_name))
        {
            $title_name = "充值".round($payment_notice['money'],2)."元";
        }

        $pay['pay_info'] = $title_name;
        $pay['payment_name'] = "微信支付";
        $pay['pay_money'] = $money;
        $pay['pay_id'] = $payment_notice['id'];
        $pay['class_name'] = "Wxxjspay";


        //$subject = msubstr($title_name,0,40);
        $subject = $title_name;
        //$data_return_url = get_domain().APP_ROOT.'/../payment.php?act=return&class_name=Malipay';
        //$notify_url = get_domain().APP_ROOT.'/../shop.php?ctl=payment&act=response&class_name=Malipay';
        $data_notify_url = SITE_DOMAIN.APP_ROOT.'/callback/payment/wwxjspay_notify.php';

        require_once(APP_ROOT_PATH.'system/payment/Wxapp/WxPay.Api.php');
        require_once(APP_ROOT_PATH.'system/payment/Wxapp/WxPay.Notify.php');
        require_once(APP_ROOT_PATH.'system/payment/Wxapp/WxPay.Data.php');
        require_once(APP_ROOT_PATH.'system/payment/Wxapp/WxPay.JsApiPay.php');

        //获取用户openid
        $tools = new JsApiPay();
        $openId = $tools->GetOpenid();
        //统一下单
        $input = new WxPayUnifiedOrder();
        $input->SetAppid($payment_info['config']['appid']);
        $input->SetMch_id($payment_info['config']['mchid']);
        $input->SetBody($payment_notice['notice_sn']);
        $input->SetOut_trade_no($payment_notice['notice_sn']);
        $input->SetTotal_fee($money * 100);
        //$input->SetTime_start(to_date(get_gmtime(),"YmdHis"));
        //$input->SetTime_expire(date("YmdHis", time() + 600));
        //$input->SetGoods_tag($title_name);
        $input->SetNotify_url($data_notify_url);
        $input->SetTrade_type("JSAPI");
        $input->SetOpenid($openId);

        $result = WxPayApi::unifiedOrder($input);

        $jsApiParameters = $tools->GetJsApiParameters($result);
        //微信定时日志 7天以后删除
        //log_result_wx_pay_log("==jsApiParameters==");
        //log_result_wx_pay_log($jsApiParameters);
        return $jsApiParameters;
    }


    function init_define($payment){
        define('WXAPP_APPID',$payment['config']['appid']);
        define('WXAPP_MCHID',$payment['config']['mchid']);
        define('WXAPP_KEY',$payment['config']['key']);
        define('WXAPP_APPSECRET',$payment['config']['appsecret']);

        define('WXAPP_SSLCERT_PATH','');
        define('WXAPP_SSLKEY_PATH','');
        define('WXAPP_CURL_PROXY_HOST',"0.0.0.0");
        define('WXAPP_CURL_PROXY_PORT',0);
        define('WXAPP_REPORT_LEVENL',1);

    }

    public function notify($request)
    {
        //log_result("==notify_request==");
        //log_result($request);
        $payment = $GLOBALS['db']->getRow("select id,config from ".DB_PREFIX."payment where class_name='Wwxjspay'");
        $payment['config'] = unserialize($payment['config']);
        $this->init_define($payment);
        //print_r($payment['config']);
        //log_result("==payment==");
        //log_result($payment);
        require_once(APP_ROOT_PATH.'system/payment/Wxapp/WxPay.Api.php');
        require_once(APP_ROOT_PATH.'system/payment/Wxapp/WxPay.Notify.php');
        require_once(APP_ROOT_PATH.'system/payment/Wxapp/WxPay.Data.php');

        try {
            $xml = $GLOBALS['HTTP_RAW_POST_DATA'];

            $result = WxPayResults::Init($xml);
            $verify = 1;
        } catch (WxPayException $e){
             //微信定时日志 7天以后删除
            log_result_wx_pay_log("提现失败result");
            log_result_wx_pay_log($e->errorMessage());
            $msg = $e->errorMessage();
            //return false;
            $verify = 0;
        }


        if ($verify == 1)
        {
            //微信定时日志 7天以后删除
            //log_result_wx_pay_log("result");
            //log_result_wx_pay_log($result);
            if ($result['return_code'] == 'SUCCESS'){
                $payment_notice_sn = $result['out_trade_no'];
                $outer_notice_sn = $result['transaction_id'];

                $payment_notice = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."payment_notice where notice_sn = '".$payment_notice_sn."'");
                //log_result("==payment_notice==");
                //log_result($payment_notice);

                //$order_info = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."deal_order where id = ".$payment_notice['order_id']);
                require_once APP_ROOT_PATH."system/libs/cart.php";
                $rs = payment_paid($payment_notice['notice_sn'],$outer_notice_sn);
                if ($rs)
                {
                    //file_put_contents(APP_ROOT_PATH."/alipaylog/1.txt","");
                    //$GLOBALS['db']->query("update ".DB_PREFIX."payment_notice set outer_notice_sn = '".$outer_notice_sn."' where id = ".$payment_notice['id']);
                    //order_paid($payment_notice['order_id']);
                    echo "success";
                }else{
                    //file_put_contents(APP_ROOT_PATH."/alipaylog/2.txt","");
                    echo "success";
                }

            }else{
                //file_put_contents(APP_ROOT_PATH."/alipaylog/3.txt","");
                echo "fail";
            }
        }
        else
        {
            //file_put_contents(APP_ROOT_PATH."/alipaylog/4.txt","");
            echo "fail";
        }
        exit;
    }
    //响应通知
    function response($request)
    {}

    //获取接口的显示
    function get_display_code()
    {
        return "微信支付";
    }

}




?>