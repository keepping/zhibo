<?php
/**
 * 将图片直接上传到OSS上,不中转;
 * 参考网址：https://help.aliyun.com/document_detail/31920.html?spm=5176.doc31931.6.206.OEtePt
 * status: 1,
 //上传文件时,必要的3个参数
 AccessKeyId: "",
 AccessKeySecret: "",
 SecurityToken: "",

 //过期时间,客户端不关心
 Expiration: "2016-09-28T10:30:02Z",

 //出错时,返回下面3个参数
 RequestId: "",
 Code: "",
 Message: "",

 //回调地址
 callbackUrl: "",
 callbackBody: "",

 //文件存放目录
 dir: ""
 */
class aliyun_sts_auto_cache extends auto_cache{
	private $key = "aliyun:sts";
	
	public function load($param)
	{
		$rows = $GLOBALS['cache']->get($this->key);
		
		if($rows === false)
		{
			fanwe_require(APP_ROOT_PATH.'system/sts-server/aliyun-php-sdk-core/Config.php');
			fanwe_require(APP_ROOT_PATH.'system/sts-server/aliyun-php-sdk-sts/Sts/Request/V20150401/AssumeRoleRequest.php');
			
			
			$m_config =  load_auto_cache("m_config");//初始化手机端配置
			
			$accessKeyID = $m_config['sts_access_key_id'];
			$accessKeySecret = $m_config['sts_access_key_secret'];;
			$roleArn = $m_config['sts_access_key_role_arn'];
			$tokenExpire = 3600;
			
			$policy = file_get_contents(APP_ROOT_PATH."system/sts-server/policy/bucket_upload_img_policy.txt");
			
			$OSS_BUCKET_NAME = $GLOBALS['distribution_cfg']['OSS_BUCKET_NAME'];
			$policy = str_replace('REPLACE_BUCKET_NAME', $OSS_BUCKET_NAME, $policy);
			//print_r($policy);
			
			$iClientProfile = DefaultProfile::getProfile("cn-hangzhou", $accessKeyID, $accessKeySecret);
			$client = new DefaultAcsClient($iClientProfile);
			
			$request = new AssumeRoleRequest();
			$request->setRoleSessionName("client_name");
			$request->setRoleArn($roleArn);
			$request->setPolicy($policy);
			$request->setDurationSeconds($tokenExpire);
			$response = $client->doAction($request);
			
			//print_r($response);
			
			$rows = array();
			$body = $response->getBody();
			$content = json_decode($body);
			$rows['status'] = $response->getStatus();
			if ($response->getStatus() == 200)
			{
				$rows['AccessKeyId'] = $content->Credentials->AccessKeyId;
				$rows['AccessKeySecret'] = $content->Credentials->AccessKeySecret;
				$rows['Expiration'] = $content->Credentials->Expiration;
				$rows['SecurityToken'] = $content->Credentials->SecurityToken;
					
				
				$rows['bucket'] = $OSS_BUCKET_NAME;
                $endpoint = $GLOBALS['distribution_cfg']['OSS_ENDPOINT'];
				if ($GLOBALS['distribution_cfg']['OSS_ENDPOINT_WITH_BUCKET_NAME']){
					$endpoint = str_replace($OSS_BUCKET_NAME.'.', '', $endpoint);
				}
				
				$endpoint = strtolower($endpoint);
				if (strpos($endpoint, 'http') === false){
					$endpoint = 'http://'.$endpoint;
				}
				
				
				$imgendpoint = $endpoint;
				
				$imgendpoint = str_replace('//oss-', '//img-', $imgendpoint);
				
				
				$rows['oss_domain'] = $GLOBALS['distribution_cfg']['OSS_DOMAIN'].'/';
				$rows['imgendpoint'] = $imgendpoint;
				$rows['endpoint'] = $endpoint;
				
				$rows['RequestId'] = '';
				$rows['Code'] = '';
				$rows['Message'] = '';
				
				$GLOBALS['cache']->set($this->key,$rows,$tokenExpire - 900);
			}
			else
			{
				$rows['AccessKeyId'] = "";
				$rows['AccessKeySecret'] = "";
				$rows['Expiration'] = "";
				$rows['SecurityToken'] = "";
					
				$rows['RequestId'] = $content->RequestId;
				$rows['Code'] = $content->Code;
				$rows['Message'] = $content->Message;
				//$rows['body'] = json_decode($body,1);
			}
			/*
			//回调说明https://help.aliyun.com/document_detail/31922.html?spm=5176.doc31921.6.208.xZgpik
			$rows['callbackUrl'] = '';
			$rows['callbackBody'] = '';
			//上传的目录是由服务端（即PHP）指定的，这样的好处就是安全。 这样就能控制每个客户端只能上传指定到指定的目录，做到安全隔离, 想要修改上传目录地址成abc/(必须以'/'结尾)
			$rows['dir'] = '';
			*/
		}
		
		return $rows;
	}
	
	public function rm($param)
	{
		$GLOBALS['cache']->rm($this->key);
	}
	
	public function clear_all($param)
	{
		$GLOBALS['cache']->rm($this->key);
	}
}
?>