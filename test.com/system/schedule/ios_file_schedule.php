<?php

require_once(APP_ROOT_PATH.'system/libs/schedule.php');
class ios_file_schedule implements schedule {
	
	/**
	 * $data 格式
	 * array("dest"=>device_tokens,"content"=>序列化的消息配置);
	 */
	public function exec($data){
		
		
		require_once(APP_ROOT_PATH. 'system/umeng/notification/ios/IOSFilecast.php');
				
		try {
			$appMasterSecret = $GLOBALS['db']->getOne("select val from ".DB_PREFIX."m_config where code = 'ios_master_secret'");
			$appkey = $GLOBALS['db']->getOne("select val from ".DB_PREFIX."m_config where code = 'ios_app_key'");


			$filecast = new IOSFilecast();
			$filecast->setAppMasterSecret($appMasterSecret);
			$filecast->setPredefinedKeyValue("appkey",           $appkey);
			$filecast->setPredefinedKeyValue("timestamp",        strval(time()));
			// Set your device tokens here
			$filecast->uploadContents($data['file_code']);
			$file_id = $filecast->getFileId();
			$filecast->setPredefinedKeyValue("alert", $data['content']);
			$filecast->setPredefinedKeyValue("badge", 1);
			$filecast->setPredefinedKeyValue("sound", "chime");
			// Set 'production_mode' to 'true' if your app is under production mode
			$filecast->setPredefinedKeyValue("production_mode", "true");
			$filecast->setPredefinedKeyValue("file_id",  $file_id);//必填 文件ID
			$filecast->setCustomizedField("room_id",  $data['room_id']);	
			$filecast->setCustomizedField("type",  $data['type']);
			
			$result = $filecast->send();

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
		$result['file_id'] = $file_id;
		return $result;
	}	
}
?>