<?php

//目前腾讯云支持三种消息类型的通知：0 — 断流； 1 — 推流；100 — 新的录制文件已生成；200 — 新的截图文件已生成。
//https://www.qcloud.com/document/product/267/5957
header("Content-Type:text/html; charset=utf-8");
require '../system/system_init.php';
//require '../system/common.php';
$json = $GLOBALS['HTTP_RAW_POST_DATA'];
$lvb_notify = json_decode($json,true);

$ret = array();
$ret['code'] = 1;

if (count($lvb_notify) > 0){
	
	
	$lvb_notify['create_time'] = to_date(get_gmtime());
	$GLOBALS['db']->autoExecute(DB_PREFIX."lvb_notify", $lvb_notify,'INSERT');
	$lvb_id = $GLOBALS['db']->insert_id();
	
	if ($lvb_id > 0)
		$ret['code'] = 0;
}



echo json_encode($ret);
