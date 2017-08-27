<?php

class usersig_auto_cache extends auto_cache{
	public function load($param)
	{
		$m_config =  load_auto_cache("m_config");//参数
		$sdkappid = $m_config['tim_sdkappid'];
		
		$id = strim($param['id']);
		$key = "usersig:".$sdkappid.":".$id;
		
		$root = $GLOBALS['cache']->get($key);

		$open_usersig_cache = intval($m_config['open_usersig_cache']);

		if($root === false||$open_usersig_cache)
		{
			$private_pem_path = APP_ROOT_PATH."system/tim/ec_key.pem";
			
			$root = array();
			
			if(!file_exists($private_pem_path))
			{
				$root['error'] = "私钥文件不存在:".$private_pem_path;
				$root['status'] = 0;
				
			}elseif ($id == ''){
				$root['error'] = "参数id不能为空";
				$root['status'] = 0;
			}else{
				require_once(APP_ROOT_PATH.'system/tim/TimApi.php');
				require_once(APP_ROOT_PATH.'system/tim/TimRestApi.php');
				

				$identifier = $m_config['tim_identifier'];
				
				$api = createRestAPI();
				$api->init($sdkappid, $identifier);
				
				
				//var_dump($api);
				
				$signature = get_signature();
				$expiry_after = 86400;//一天有效期
				$ret = $api->generate_user_sig((string)$id, $expiry_after, $private_pem_path, $signature);
				
				if($ret == null || strstr($ret[0], "failed")){
					$root['error'] = $sdkappid.":获取usrsig失败, 请确保:".$signature." 文件有执行的权限.";
					$root['status'] = 0;
				}else{
					$root['usersig'] = $ret[0];
					$root['status'] = 1;
					
					$GLOBALS['cache']->set($key,$root,$expiry_after - 60);
					
					//$expiry_after = NOW_TIME + 86400;
					//$GLOBALS['db']->query("update ".DB_PREFIX."user set usersig = '".$ret[0]."',expiry_after=".$expiry_after." where id = '".$id."'");
				}
			}
		}
		
		return $root;
	}
	
	public function rm($param)
	{
		$m_config =  load_auto_cache("m_config");//参数
		$sdkappid = $m_config['tim_sdkappid'];
		
		$id = strim($param['id']);
		$key = "usersig:".$sdkappid.":".$id;
		
		$GLOBALS['cache']->rm($key);
	}
	
	public function clear_all($param)
	{
		$m_config =  load_auto_cache("m_config");//参数
		$sdkappid = $m_config['tim_sdkappid'];
		
		$id = strim($param['id']);
		$key = "usersig:".$sdkappid.":".$id;
		
		$GLOBALS['cache']->rm($key);
	}
}
?>