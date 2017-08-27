<?php
require_once(APP_ROOT_PATH. 'system/umeng/notification/AndroidNotification.php');

class AndroidListcast extends AndroidNotification {
	function __construct() {
		parent::__construct();
		$this->data["type"] = "listcast";
		$this->data["device_tokens"] = NULL;
	}

}