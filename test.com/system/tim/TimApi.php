<?php

	require_once(APP_ROOT_PATH.'system/tim/TimRestApi.php');
	/**
	 * sdkappid 是app的sdkappid
	 * identifier 是用户帐号
	 * private_pem_path 为私钥在本地位置
	 * server_name 是服务类型
	 * command 是具体命令
	 */
	 
	function createTimAPI(){

		$m_config =  load_auto_cache("m_config");//参数
		$sdkappid = $m_config['tim_sdkappid'];
		$identifier = $m_config['tim_identifier'];
		
		$ret = load_auto_cache("usersig", array("id"=>$identifier));
		if ($ret['status'] == 1){
			$private_pem_path = APP_ROOT_PATH."system/tim/ec_key.pem";
			if (!file_exists($private_pem_path)&&function_exists('log_err_file')) {
				log_err_file(array(__FILE__,__LINE__,__METHOD__,'system/tim/ec_key.pem,不存在'));
			}

			$api = createRestAPI();
			$api->init($sdkappid, $identifier);
			
			$api->set_user_sig($ret['usersig']);
			
			return $api;
			
		}else{
			//print_r($ret);
			//exit;
			return $ret;
		}
		
		/*
		
		$private_pem_path = APP_ROOT_PATH."system/tim/ec_key.pem";


		$api = createRestAPI();
		$api->init($sdkappid, $identifier);
		
		
		
		//echo 'private_pem_path:'.$private_pem_path;exit;
		if($private_pem_path != "")
		{
			//独立模式
			if(!file_exists($private_pem_path))
			{
				echo "私钥文件不存在, 请确保TimRestApiConfig.json配置字段private_pem_path正确\n";
				return;
			}

	
			$signature = get_signature();
			//echo $signature."<br>";
			
			$ret = $api->generate_user_sig($identifier, '36000', $private_pem_path, $signature);
			if($ret == null || strstr($ret[0], "failed")){
				echo "获取usrsig失败, 请确保TimRestApiConfig.json配置信息正确.\n";
				return -1;
			}
		
		}else{
			echo "请填写TimRestApiConfig.json中private_pem_path(独立模式)或者user_sig(托管模式)字段\n";
			return -1;
		}
		
		return $api;
		*/
	}
	
	/*
	* signature为获取私钥脚本，详情请见 账号登录集成 http://avc.qcloud.com/wiki2.0/im/
	*/
	function get_signature(){
		if(is_64bit()){
			if(PATH_SEPARATOR==':'){
				$signature = "signature/linux-signature64";
			}else{
				$signature = "signature\\windows-signature64.exe";
			}
		}else{
			if(PATH_SEPARATOR==':')
			{
				$signature = "signature/linux-signature32";
			}else{
				$signature = "signature\\windows-signature32.exe";
			}
		}
		return APP_ROOT_PATH."system/tim/".$signature;
	}


?>
