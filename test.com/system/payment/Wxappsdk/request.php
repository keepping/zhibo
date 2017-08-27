<?php
/**
 * 支付接口调测例子
 * ================================================================
 * index 进入口，方法中转
 * submitOrderInfo 提交订单信息
 * queryOrder 查询订单
 * 
 * ================================================================
 */
require('Utils.class.php');
require('class/RequestHandler.class.php');
require('class/ClientResponseHandler.class.php');
require('class/PayHttpClient.class.php');

Class Request{
    //$url = 'http://192.168.1.185:9000/pay/gateway';

    private $resHandler = null;
    private $reqHandler = null;
    private $pay = null;

    public function __construct($account,$merchantPrivateKey){
        $this->account = $account;
        $this->merchantPrivatekey = $merchantPrivateKey;
        $this->url = 'https://pay.swiftpass.cn/pay/gateway';
        $this->version = '1.0';
        $this->resHandler = new ClientResponseHandler();
        $this->reqHandler = new RequestHandler();
        $this->pay = new PayHttpClient();

        $this->reqHandler->setGateUrl($this->url);
        $this->reqHandler->setKey($this->merchantPrivatekey);

     }

    public function Request(){


    }

    public function index(){
        /*$method = isset($_REQUEST['method'])?$_REQUEST['method']:'submitOrderInfo';
        switch($method){
            case 'submitOrderInfo'://提交订单
                $this->submitOrderInfo();
            break;
            case 'queryOrder'://查询订单
                $this->queryOrder();
            break;
            case 'submitRefund'://提交退款
                $this->submitRefund();
            break;
            case 'queryRefund'://查询退款
                $this->queryRefund();
            break;
            case 'callback':
                $this->callback();
            break;
        }*/
    }
    
    /**
     * 提交订单信息
     */
    public function submitOrderInfo($date){
        $this->reqHandler->setReqParams($date,array('method'));
        $this->reqHandler->setParameter('service','unified.trade.pay');//接口类型：pay.weixin.native
        $this->reqHandler->setParameter('mch_id', $this->account);//必填项，商户号，由威富通分配
        $this->reqHandler->setParameter('version',$this->version);
		//$this->reqHandler->setParameter('op_shop_id','1314');
		//$this->reqHandler->setParameter('device_info','长江');
		//$this->reqHandler->setParameter('op_device_id','东风一号');
		$this->reqHandler->setParameter('limit_credit_pay','1');   //是否支持信用卡，1为不支持，0为支持
        //$this->reqHandler->setParameter('groupno','8111100093');
        //通知地址，必填项
		$this->reqHandler->setParameter('notify_url',$date['notify_url']);//通知回调地址，目前默认是空格，商户在测试支付和上线时必须改为自己的，且保证外网能访问到
        $this->reqHandler->setParameter('nonce_str',mt_rand(time(),time()+rand()));//随机字符串，必填项，不长于 32 位
        $this->reqHandler->createSign();//创建签名
        
        $data = Utils::toXml($this->reqHandler->getAllParameters());
        //var_dump($data);
        
        $this->pay->setReqContent($this->reqHandler->getGateURL(),$data);
        if($this->pay->call()){
            $this->resHandler->setContent($this->pay->getResContent());
            $this->resHandler->setKey($this->merchantPrivatekey);
            if($this->resHandler->isTenpaySign()){
                //当返回状态与业务结果都为0时才返回支付二维码，其它结果请查看接口文档
                if($this->resHandler->getParameter('status') == 0 && $this->resHandler->getParameter('result_code') == 0){
                    return (array('token_id'=>$this->resHandler->getParameter('token_id'),
						                   'services'=>$this->resHandler->getParameter('services')));
                                           
                    exit();
                }else{
                    return (array('status'=>500,'msg'=>'Error Code:'.$this->resHandler->getParameter('err_code').' Error Message:'.$this->resHandler->getParameter('err_msg')));
                    exit();
                }
            }
            return (array('status'=>500,'msg'=>'Error Code:'.$this->resHandler->getParameter('status').' Error Message:'.$this->resHandler->getParameter('message')));
        }else{
            return (array('status'=>500,'msg'=>'Response Code:'.$this->pay->getResponseCode().' Error Info:'.$this->pay->getErrInfo()));
        }
    }

    

    /**
     * 界面显示
     */
    public function queryRefund(){
       
        
    }
    
    /**
     * 异步通知方法，demo中将参数显示在result.txt文件中
     */
    public function callback($xml){
        $this->resHandler->setContent($xml);
        $this->resHandler->setKey($this->merchantPrivatekey);
        $result = array();
        if($this->resHandler->isTenpaySign()){
            if($this->resHandler->getParameter('status') == 0 && $this->resHandler->getParameter('result_code') == 0){
                $result['status'] = intval($this->resHandler->getParameter('status'));
                $result['result_code'] =intval($this->resHandler->getParameter('result_code'));
                $result['out_trade_no'] = $this->resHandler->getParameter('out_trade_no');
                $result['out_transaction_id'] = $this->resHandler->getParameter('out_transaction_id');
            }else{
                $result['status'] = 400;
                $result['result_code'] =intval($this->resHandler->getParameter('result_code'));
            }
        }else{
            $result['status'] = 401;
            $result['result_code'] =intval($this->resHandler->getParameter('result_code'));

        }
        return  $result;
    }
}
?>