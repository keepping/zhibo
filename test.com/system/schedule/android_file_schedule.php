<?php

require_once(APP_ROOT_PATH.'system/libs/schedule.php');
require_once(APP_ROOT_PATH. 'system/umeng/notification/android/AndroidFilecast.php');
class android_file_schedule implements schedule {
	
	
	/**
	 * $data 格式
	 * array("dest"=>device_tokens,"content"=>序列化的消息配置);
	 */
	public function exec($data){
		try {
			$appMasterSecret = $GLOBALS['db']->getOne("select val from ".DB_PREFIX."m_config where code = 'android_master_secret'");
			$appkey = $GLOBALS['db']->getOne("select val from ".DB_PREFIX."m_config where code = 'android_app_key'");
	

			$title = app_conf("SITE_NAME")?app_conf("SITE_NAME"):"直播";
			
			$filecast = new AndroidFilecast();
			$filecast->setAppMasterSecret($appMasterSecret);
			$filecast->setPredefinedKeyValue("appkey",           $appkey);
			$filecast->setPredefinedKeyValue("timestamp",        strval(time()));// 必填 时间戳，10位或者13位均可，时间戳有效期为10分钟 NOW_TIME

			// Set your file_id here
			$filecast->uploadContents($data['file_code']);
			$file_id = $filecast->getFileId();
			
			$filecast->setPredefinedKeyValue("ticker",           $data['content']);//必填 通知栏提示文字
			$filecast->setPredefinedKeyValue("title",            $title);// 必填 通知标题
			$filecast->setPredefinedKeyValue("text",             $data['content']);// 必填 通知文字描述
			$filecast->setPredefinedKeyValue("after_open",       "go_app");//"go_app": 打开应用;"go_url": 跳转到URL;"go_activity": 打开特定的activity;"go_custom": 用户自定义内容。
			$filecast->setPredefinedKeyValue("file_id",  $file_id);//必填 文件ID
			// Set 'production_mode' to 'false' if it's a test device.
			// For how to register a test device, please see the developer doc.
			$filecast->setPredefinedKeyValue("production_mode", "true");//可选 正式/测试模式。测试模式下，只会将消息发给测试设备。
			// Set extra fields
			//$unicast->setExtraField("test", "helloworld");
			$filecast->setExtraField("room_id",  $data['room_id']);
			$filecast->setExtraField("type",  $data['type']);
			//print("Sending unicast notification, please wait...\r\n");
			//json_decode($data) {"ret":"SUCCESS","data":{"msg_id":"uu05362143574400482600"}}		
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
			$message = addslashes($e->getMessage());
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