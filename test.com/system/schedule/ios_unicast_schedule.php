<?php

require_once(APP_ROOT_PATH.'system/libs/schedule.php');
class ios_unicast_schedule implements schedule {
	
	/**
	 * $data 格式
	 * array("dest"=>device_tokens,"content"=>序列化的消息配置);
	 */
	public function exec($data){
		
		require_once(APP_ROOT_PATH. 'system/umeng/notification/ios/IOSUnicast.php');
				
		try {
			$appMasterSecret = $GLOBALS['db']->getOne("select val from ".DB_PREFIX."m_config where code = 'ios_master_secret'");
			$appkey = $GLOBALS['db']->getOne("select val from ".DB_PREFIX."m_config where code = 'ios_app_key'");


			$listcast = new IOSUnicast();
			$listcast->setAppMasterSecret($appMasterSecret);
			$listcast->setPredefinedKeyValue("appkey",           $appkey);
			$listcast->setPredefinedKeyValue("timestamp",        strval(time()));
			// Set your device tokens here
			$listcast->setPredefinedKeyValue("device_tokens",    $data['dest']);
			$listcast->setPredefinedKeyValue("alert", $data['content']);
			$listcast->setPredefinedKeyValue("badge", 1);
			$listcast->setPredefinedKeyValue("sound", "chime");
			// Set 'production_mode' to 'true' if your app is under production mode
			$listcast->setPredefinedKeyValue("production_mode", "true");
			$listcast->setCustomizedField("type",  $data['type']);
			if ($data['type']==5) {
				$listcast->setCustomizedField("url",  $data['url']);
			}
			$result = $listcast->send();

			$res = json_decode($result,1);
			//print("Sent SUCCESS\r\n");
			if ($res['ret'] == 'SUCCESS'){
				$is_success = 1;
			}else{
				$is_success = 0;
				$message = addslashes(print_r($result,true));
			}
				
		} catch (Exception $e) {
			$is_success = 0;
			$message = strim($e->getMessage());
			return false;
		}
	
		$result = array();
		$result['status'] = $is_success;
		$result['attemp'] = 0;
		$result['info'] = $message;
		$result['res'] = $res;
		return $result;
	}	
}
?>