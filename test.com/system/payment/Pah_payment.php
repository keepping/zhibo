<?php
// +----------------------------------------------------------------------
// | Fanwe 方维p2p借贷系统
// +----------------------------------------------------------------------
// | Copyright (c) 2011 http://www.fanwe.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: 云淡风轻(88522820@qq.com)
// +----------------------------------------------------------------------


$payment_lang = array(
	'name'	=>	'平安汇支付宝扫码支付',
	'MERNO'	=>	'商户号',
	'TERMNO'=>	'终端号',
	'RSA'=>	'微信签名密钥',
);

$config = array(
	'MERNO'	=>	array(
		'INPUT_TYPE'	=>	'0',
	), //商户号
	'TERMNO'	=>	array(
		'INPUT_TYPE'	=>	'0',
	), //终端号
	'RSA'	=>	array(
		'INPUT_TYPE'	=>	'0',
	), //微信签名密钥
);

/* 模块的基本信息 */
if (isset($read_modules) && $read_modules == true)
{
    $module['class_name']    = 'Pah';

    /* 名称 */
    $module['name']    = $payment_lang['name'];


	/* 支付方式：1：在线支付；0：线下支付  2:仅wap支付 3:仅app支付 4:兼容wap和app*/
    $module['online_pay'] = '4';

    /* 配送 */
    $module['config'] = $config;
    
    $module['lang'] = $payment_lang;
    
    $module['reg_url'] = '';
    
    return $module;
}

// 平安汇支付模型
require_once(APP_ROOT_PATH.'system/libs/payment.php');
class Pah_payment implements payment {
	
	public function get_payment_code($payment_notice_id)
	{
		$pay = array();
		$pay['is_wap'] = 1;//
		$pay['class_name'] = "Pah";
		$pay['url'] =SITE_DOMAIN.APP_ROOT.'/mapi/index.php?ctl=pay&act=get_display_code&pay_code=Pah&notice_id='.$payment_notice_id;
		$pay['sdk_code'] = array("pay_sdk_type"=>"yjwap","config"=>
			array(
				"url"=>SITE_DOMAIN.APP_ROOT.'/mapi/index.php?ctl=pay&act=get_display_code&pay_code=Pah&notice_id='.$payment_notice_id,
				"is_wap"=>1
			)
		);
		return $pay;
		
	}
	
	public function response($request)
	{}
	
	public function notify($request)
	{
		
	}

	function get_display_code(){

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
			
			$body=array();
			$body['MERNO']=$payment_info['config']['MERNO'];
			$body['TERMNO']=$payment_info['config']['TERMNO'];
			$body['AMT']=intval($money*100);
			$body['TYPE']="1";
			
			
			$params=array();
			$params['CommandID']=2318;
			$params['MsgID']=NOW_TIME;
			$params['NodeType']=0;
			$params['NodeID']='app';
			$params['Version']='1.2.0';
			$params['Body']=$body;
			$params['Sign']=$this->MakeSign($params['Body'], $payment_info['config']['RSA']);
			
			
			//$url="http://123.58.32.141:20035/mpay/services";//测试地址
			//$url="https://172.19.0.22:1380/mpos/services/";
			//$url="https://www.lianyinggufen.com/mpos/services/";
			$url="https://www.lianyinggufen.com/mpay/services";
			
			try {
				// 执行HTTP POST请求
				$ch = curl_init(); // 初始化curl
				curl_setopt($ch, CURLOPT_URL, $url); // 服务地址
				curl_setopt($ch, CURLOPT_HEADER, false); // 设置header
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // 要求结果为字符串且输出到屏幕上
				curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST'); // POST请求方式
				curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
				
				/*$ch = curl_init(); // 初始化curl
				curl_setopt($ch, CURLOPT_URL, $url); // 服务地址
				curl_setopt($ch, CURLOPT_HEADER, false); // 设置header
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // 要求结果为字符串且输出到屏幕上
				curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
				curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
				curl_setopt($ch, CURLOPT_POST, true);
				curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));*/
				
				/*$ch = curl_init();
				curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true); // 跳过证书检查
				curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, true);  // 从证书中检查SSL加密算法是否存在
				curl_setopt($ch, CURLOPT_URL, $url);
				curl_setopt($ch, CURLOPT_HTTPHEADER, json_encode($params));
				curl_setopt($ch, CURLOPT_POST, true);
				curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);*/
				
				/*fanwe_require(APP_ROOT_PATH .'mapi/lib/core/transport.php');
				
				$trans = new transport();
				$req = $trans->request($url,$params,'POST');
				var_dump($req);exit();	*/
				
				$data = curl_exec($ch); // 运行curl
				
				$data=json_decode($data,1);
				if(empty($data))
				{
					$data = curl_error($ch);
				}
				curl_close($ch);

				if ($data['RetCode']==0) {
					$body2=$data['Body'];
					//二阶段处理 二维码正扫
					$body['PREORDERID']=$data['Body']['PREORDERID'];
					
					$params=array();
					$params['CommandID']=2308;
					$params['MsgID']=NOW_TIME;
					$params['NodeType']=0;
					$params['NodeID']='app';
					$params['Version']='1.2.0';
					$params['Body']=$body;
										
					$params['Sign']=$this->MakeSign($params['Body'], $payment_info['config']['RSA']);
					
					//var_dump(json_encode($params));
					
					
					$ch = curl_init(); // 初始化curl
					curl_setopt($ch, CURLOPT_URL, $url); // 服务地址
					curl_setopt($ch, CURLOPT_HEADER, false); // 设置header
					curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // 要求结果为字符串且输出到屏幕上
					curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST'); // POST请求方式
					curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
					$data2 = curl_exec($ch); // 运行curl
					
					$data2=json_decode($data2,1);
					if(empty($data2))
					{
						$data2 = curl_error($ch);
					}
					curl_close($ch);
					
					if ($data2['RetCode']==0) {
						//三阶段处理 生成付款链接
						$outer_notice_sn=$data2['Body']['ORDERNO'];
						
						$sql = "update ".DB_PREFIX."payment_notice set outer_notice_sn =  ".$outer_notice_sn." where id =".$payment_notice_id;
						$GLOBALS['db']->query($sql);
						
						$CODE_URL=$data2['Body']['CODE_URL'];
						
						$invite_image_dir =APP_ROOT_PATH."public/pay_image";
						if (!is_dir($invite_image_dir)) {
							@mkdir($invite_image_dir, 0777);
						}
						
						$url=$CODE_URL;
						
						$path_dir = "/public/pay_image/pay_qrcode_".$payment_notice_id.".png";
						$path_logo_dir = "/public/pay_image/pay_qrcode_".$payment_notice_id.".png";
						$qrcode_dir = APP_ROOT_PATH.$path_dir;
						$qrcode_dir_logo = APP_ROOT_PATH.$path_logo_dir;
						if(!is_file($qrcode_dir)||!is_file($qrcode_dir_logo)){
							get_qrcode_png($url,$qrcode_dir,$qrcode_dir_logo);
						}
						
						header('Location:'.SITE_DOMAIN.$path_logo_dir);
						
						
						/*$payLinks ='<div class="weixin-container">';
						$payLinks .='<div class="qrcode-container">';
						$payLinks .='<div class="title">微信支付</div>';
						$payLinks .='<div class="qrcode">';
						$payLinks .='<div class="qrcode-img">';
						$payLinks .='<div class="" id="weixin_qrcode" rel="{'.$CODE_URL.'}"></div>';
						$payLinks .='</div>';
						$payLinks .='<div class="warning"></div>';
						$payLinks .='</div>';
						$payLinks .='<div class="tip">';
						$payLinks .='<i class="icon"></i>';
						$payLinks .='请使用微信扫一扫<br>';
						$payLinks .='扫描二维码支付';
						$payLinks .='</div>';
						$payLinks .='</div>';
						$payLinks .='<div class="mobile">';
						$payLinks .='<div class="img"></div>';
						$payLinks .='</div>';
						$payLinks .='</div>';

						return $payLinks;*/
						
						
					}else{
						return $data2['ErrorMsg'];
					}
					
					
				}else{
					return $data['ErrorMsg'];
				}
												
			}catch (Exception $e) {
				
			}
						
			exit();

		}
		else
		{
			return '';
		}
		
	}
	
	/**
	 * 生成签名
	 * @return 签名，
	 */
	function MakeSign($values,$key)
	{
	
		ksort($values);
		
		//$string = $this->ToUrlParams($values);
		//签名步骤二：在string后加入KEY
		//$string = $string . "&key=".$key;
	
		$string = json_encode($values);
		$string = $string . '&key=' . $key;
		$string = md5($string);
		//签名步骤四：所有字符转为大写
		$result = strtoupper($string);
	
		return $result;
	}
	
	function ToUrlParams($urlObj)
	{
		$buff = "";
		foreach ($urlObj as $k => $v)
		{
			$buff .= $k . "=" . $v . "&";
		}
	
		$buff = trim($buff, "&");
		return $buff;
	}
}
?>