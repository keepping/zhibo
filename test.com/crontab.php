<?php
//定时任务,在java定时访问调用
header("Content-Type:text/html; charset=utf-8");
define("FANWE_REQUIRE",true);
	require './system/mapi_init.php';
	
	fanwe_require(APP_ROOT_PATH.'mapi/lib/core/common.php');
	//直播消息推送
	fanwe_require(APP_ROOT_PATH.'mapi/lib/core/push.php');
	fanwe_require(APP_ROOT_PATH.'mapi/lib/redis/BaseRedisService.php');
	//fanwe_require(APP_ROOT_PATH.'mapi/lib/redis/VideoViewerRedisService.php');
	
	
	$ret_array = array();
	$ret_array2 = crontab_do_end_video();
	$ret_array3 = crontab_do_end_video_2();
	
	array_push($ret_array,$ret_array2,$ret_array3);


	//$obj = new VideoViewerRedisService();
	//$obj->crontab_robot();
	//添加机器人
	crontab_robot();
	
	//每隔N秒，将在线直播redis计算的数据同步到mysql中
	//推送消息
	push_notice(0,0,array(0,1));
	
	//将发送礼物记录移到mysql数据库中 ，修改将礼物发送记录直接写入mysql,关闭redis同步  @by slf
	//sync_video_prop_to_mysql(-1);
	//回播定时推送观众列表
	crontab_viewer(0);
	
echo json_encode($ret_array);

?>
