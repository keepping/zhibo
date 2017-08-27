<?php
//定时任务,在java定时访问调用
header("Content-Type:text/html; charset=utf-8");
define("FANWE_REQUIRE",true);
	require './system/mapi_init.php';
	
	fanwe_require(APP_ROOT_PATH.'mapi/lib/core/common.php');
	fanwe_require(APP_ROOT_PATH.'mapi/lib/redis/BaseRedisService.php');

	//每隔N秒，将在线直播redis计算的数据同步到mysql中
	crontab_deal_num(5);

?>
