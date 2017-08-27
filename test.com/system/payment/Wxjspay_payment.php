<?php
// +----------------------------------------------------------------------
// | EaseTHINK 易想团购系统
// +----------------------------------------------------------------------
// | Copyright (c) 2010 http://www.easethink.com All rights reserved.
// +----------------------------------------------------------------------

$payment_lang = array(
    'name'      => '微信支付(PC扫码支付)',
    'appid'     => '微信公众号ID',
    'appsecret' => '微信公众号SECRT',
    'mchid'     => '微信支付MCHID',
    //    'partnerid'    =>    '商户ID',
    //'partnerkey'    =>    '商户key',
    'key'       => '商户支付密钥Key/api秘钥',
    //'sslcert'=>'apiclient_cert证书路径',
    //'sslkey'=>'apiclient_key证书路径',
);
$config = array(
    'appid'     => array(
        'INPUT_TYPE' => '0',
    ), //微信公众号ID
    'appsecret' => array(
        'INPUT_TYPE' => '0',
    ), //微信公众号SECRT
    'mchid'     => array(
        'INPUT_TYPE' => '0',
    ), //微信支付MCHID

    'key'       => array(
        'INPUT_TYPE' => '0',
    ), //商户支付密钥Key
    //    'sslcert'    =>    array(
    //        'INPUT_TYPE'    =>    '0',
    //    ), //apiclient_cert证书路径
    //    'sslkey'    =>    array(
    //        'INPUT_TYPE'    =>    '0',
    //    ), //apiclient_key证书路径

);
/* 模块的基本信息 */
if (isset($read_modules) && $read_modules == true) {
    $module['class_name'] = 'Wxjspay';

    /* 名称 */
    $module['name'] = $payment_lang['name'];

    /* 支付方式：1：在线支付；0：线下支付;2:手机wap;3:手机sdk */
    $module['online_pay'] = '1';

    /* 配送 */
    $module['config'] = $config;

    $module['lang']    = $payment_lang;
    $module['reg_url'] = '';
    return $module;
}

// 支付宝手机支付模型
require_once APP_ROOT_PATH . 'system/libs/payment.php';
class Wxjspay_payment implements payment
{
    public function __construct()
    {
        require_once APP_ROOT_PATH . "system/payment/Wxjspay/WxPayPubHelper.php";
    }
    /**
     * 获取登录信息
     * @return [type] [description]
     */
    protected static function getUserId()
    {
        $user_info = es_session::get("user_info");
        if (!$user_info) {
            ajax_return(array(
                'status' => 0,
                'info'   => '未登录',
            ));
        }
        return $user_info['id'];
    }
    /**
     * 根据订单id获取订单
     * @param  [type] $payment_notice_id [description]
     * @param  string $field             [description]
     * @return [type]                    [description]
     */
    protected static function getPaymentNotice($payment_notice_id, $field = '*')
    {
        $table = DB_PREFIX . 'payment_notice';
        $where = 'id =' . intval($payment_notice_id);
        return $GLOBALS['db']->getRow("SELECT * FROM $table WHERE $where");
    }
    /**
     * 根据订单SN获取订单
     * @param  [type] $sn    [description]
     * @param  string $field [description]
     * @return [type]        [description]
     */
    protected static function getPaymentNoticeBySN($sn, $field = '*')
    {
        $table = DB_PREFIX . 'payment_notice';
        $where = "`notice_sn` ='$sn'";
        return $GLOBALS['db']->getRow("SELECT * FROM $table WHERE $where");
    }
    /**
     * 获取配置信息
     * @param  [type] $payment_id [description]
     * @param  string $field      [description]
     * @return [type]             [description]
     */
    protected static function getPaymentConfig($payment_id, $field = '*')
    {
        $table        = DB_PREFIX . 'payment';
        $where        = 'id =' . intval($payment_id);
        $payment_info = $GLOBALS['db']->getRow("SELECT `config` FROM $table WHERE $where");
        return unserialize($payment_info['config']);
    }
    /**
     * 统一下单
     * @param  [type] $payment_notice_id [description]
     * @return [type]                    [description]
     */
    public function get_payment_code($payment_notice_id)
    {
        $field          = '`notice_sn`,`money`,`payment_id`,`order_id`,`num`,`order_status`';
        $payment_notice = self::getPaymentNotice(intval($payment_notice_id), $field);

        $order_sn   = $payment_notice['notice_sn'];
        $money      = round($payment_notice['money'], 2) * 100;
        $notify_url = get_domain() . APP_ROOT . "/wxpay_web/notify_url.php";
        $wx_config  = self::getPaymentConfig($payment_notice['payment_id']);

        $unifiedOrder = new UnifiedOrder_pub();
        $unifiedOrder->update_config(
            $wx_config['appid'],
            $wx_config['appsecret'],
            $wx_config['mchid'],
            $wx_config['partnerid'],
            $wx_config['partnerkey'],
            $wx_config['key'],
            $wx_config['sslcert'],
            $wx_config['sslkey']
        );
        require_once APP_ROOT_PATH . 'system/extend/ip.php';
        $iplocation = new iplocate();
        $unifiedOrder->setParameter('spbill_create_ip', $iplocation->getIP());
        $unifiedOrder->setParameter('out_trade_no', $order_sn);
        $unifiedOrder->setParameter('total_fee', $money);
        $unifiedOrder->setParameter('notify_url', $notify_url);
        $unifiedOrder->setParameter('body', '充值');
        $unifiedOrder->setParameter('trade_type', 'NATIVE');
        $result = $unifiedOrder->getResult();

        if ($result['return_code'] == 'SUCCESS') {
            if ($result['result_code'] == 'SUCCESS') {
                $data_info['code_url'] = $result['code_url'];
                return array(
                    'status'   => 1,
                    'code_url' => $result['code_url'],
                    'method'   => 'img',
                    'id'       => $payment_notice_id,
                );
            } else {
                $error_info = $result['err_code_des'] . ',原因：';
                switch ($result['err_code']) {
                    case 'NOAUTH':
                        $error_info .= '请商户前往申请此接口权限';
                        break;
                    case 'NOTENOUGH':
                        $error_info .= '用户帐号余额不足，请用户充值或更换支付卡后再支付';
                        break;
                    case 'ORDERPAID':
                        $error_info .= '商户订单已支付，无需更多操作';
                        break;
                    case 'ORDERCLOSED':
                        $error_info .= '当前订单已关闭，请重新下单';
                        break;
                    case 'SYSTEMERROR':
                        $error_info .= '系统异常，请用相同参数重新调用单';
                        break;
                    case 'APPID_NOT_EXIST':
                        $error_info .= '请检查APPID是否正确';
                        break;
                    case 'MCHID_NOT_EXIST':
                        $error_info .= '请检查MCHID是否正确';
                        break;
                    case 'APPID_MCHID_NOT_MATCH':
                        $error_info .= '请确认appid和mch_id是否匹配';
                        break;
                    case 'LACK_PARAMS':
                        $error_info .= '请检查参数是否齐全';
                        break;
                    case 'OUT_TRADE_NO_USED':
                        $error_info .= '请核实商户订单号是否重复提交';
                        break;
                    case 'SIGNERROR':
                        $error_info .= '请检查签名参数和方法是否都符合签名算法要求';
                        break;
                    case 'XML_FORMAT_ERROR':
                        $error_info .= '请检查XML参数格式是否正确';
                        break;
                    case 'REQUIRE_POST_METHOD':
                        $error_info .= '请检查请求参数是否通过post方法提交';
                        break;
                    case 'POST_DATA_EMPTY':
                        $error_info .= '请检查post数据是否为空';
                        break;
                    case 'NOT_UTF8':
                        $error_info .= '请使用NOT_UTF8编码格式';
                        break;
                    default:
                        $error_info = '';
                }
                return array(
                    'status' => 0,
                    'info'   => $error_info,
                );
            }
        } else {
            return array(
                'status' => 0,
                'info'   => $result['return_msg'],
            );
        }
    }
    /**
     * 扫码反馈
     * @param  [type] $request [description]
     * @return [type]          [description]
     */
    public function response($request)
    {
        $payment_notice_id = intval($request['payment_notice_id']);
        if (!$payment_notice_id) {
            ajax_return(array(
                'status' => 0,
                'info'   => '参数错误',
            ));
        }
        $user_id        = self::getUserId();
        $payment_notice = self::getPaymentNotice($payment_notice_id, '`notice_sn`,`payment_id`,`user_id`');
        if ($user_id != $payment_notice['user_id']) {
            ajax_return(array(
                'status' => 0,
                'info'   => '用户订单信息错误',
            ));
        }
        $order_sn  = $payment_notice['notice_sn'];
        $wx_config = self::getPaymentConfig($payment_notice['payment_id']);

        $order_query = new OrderQuery_pub();
        $order_query->update_config(
            $wx_config['appid'],
            $wx_config['appsecret'],
            $wx_config['mchid'],
            $wx_config['partnerid'],
            $wx_config['partnerkey'],
            $wx_config['key'],
            $wx_config['sslcert'],
            $wx_config['sslkey']
        );
        $order_query->setParameter('out_trade_no', $order_sn);
        $result = $order_query->getResult();
        if ($result['return_code'] == 'SUCCESS') {
            if ($result['result_code'] == 'SUCCESS') {
                ajax_return(array(
                    'status' => 1,
                    'info'   => $result['trade_state'],
                    // SUCCESS—支付成功
                    // REFUND—转入退款
                    // NOTPAY—未支付
                    // CLOSED—已关闭
                    // REVOKED—已撤销（刷卡支付）
                    // USERPAYING--用户支付中
                    // PAYERROR--支付失败(其他原因，如银行返回失败)
                ));
            } else {
                ajax_return(array(
                    'status' => $result['err_code'],
                    'info'   => $result['err_code_des'],
                ));
            }
        } else {
            ajax_return(array(
                'status' => $result['return_code'],
                'info'   => $result['return_msg'],
            ));
        }
    }
    /**
     * 支付通知
     * @param  [type] $request [description]
     * @return [type]          [description]
     */
    public function notify($request)
    {
        $payment_notice = self::getPaymentNoticeBySN($request['out_trade_no'], '`payment_id`');
        $wx_config      = self::getPaymentConfig($payment_notice['payment_id']);

        $notify = new Notify_pub();
        $notify->setReturnParameter('return_code', 'FAIL');
        if (!$wx_config) {
            $notify->setReturnParameter('return_msg', '配置错误');
            die($notify->returnXml());
        }

        $notify->update_config(
            $wx_config['appid'],
            $wx_config['appsecret'],
            $wx_config['mchid'],
            $wx_config['partnerid'],
            $wx_config['partnerkey'],
            $wx_config['key'],
            $wx_config['sslcert'],
            $wx_config['sslkey']
        );
        $notify->setData($request);

        if (!$notify->checkSign()) {
            $notify->setReturnParameter('return_msg', '签名失败');
            die($notify->returnXml());
        }

        require_once APP_ROOT_PATH . "system/libs/cart.php";
        $rs = payment_paid($request['out_trade_no'], $request['trade_no']);

        $notify->setReturnParameter('return_code', 'SUCCESS');
        die($notify->returnXml());
    }
    /**
     * 展示代码
     * @return [type] [description]
     */
    public function get_display_code()
    {
    }
}
