<?php
//定时任务,在java定时访问调用
header("Content-Type:text/html; charset=utf-8");
define("FANWE_REQUIRE", true);
require './system/mapi_init.php';

fanwe_require(APP_ROOT_PATH . 'mapi/lib/core/common.php');
fanwe_require(APP_ROOT_PATH . 'mapi/lib/redis/BaseRedisService.php');
$ret_array = array();
//结束异常直播
$video=$GLOBALS['db']->getALl("SELECT stream_id,vhost FROM ".DB_PREFIX."video_aliyun AS a WHERE NOT EXISTS( SELECT * FROM fanwe_video AS v WHERE a.stream_id = v.channelid)");
if(!empty($video)){
    $m_config = load_auto_cache('m_config');
    fanwe_require(APP_ROOT_PATH . 'mapi/lib/core/video_aliyun.php');
    $service = new VideoAliyun($m_config);
    foreach($video as $item){
        $ret_array[]= $service->Stop($item['stream_id'],$item['vhost']);
    }
}

echo json_encode($ret_array);