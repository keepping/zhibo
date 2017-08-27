<?php

class TestAction {
	
    function TestAction() {
    	require_once APP_ROOT_PATH."/admin/Lib/Action/RedisCommon.class.php";
    	$redisobj = new Ridescommon();
    	$user_id = 290;
    	//关注人数
    	$result = $redisobj->video_follw_list($user_id);
    	//print_r($result);
    	//粉丝人数
    	$result_1 = $redisobj->video_follw_list($user_id,1,0);
    	//print_r($result_1);
    	//主播贡献榜
    	/*$video_id = 0;
    	$user_id = 7959;
    	$podcast_id = 7959;*/
    	//本场贡献榜
    	$video_id = 13893;
    	$user_id = 8414;
    	$podcast_id = 0;
    	$result_2 = $redisobj->video_contribute_list($user_id,$video_id,$podcast_id);
    	//print_r($result_2);
    	//更新话题列表
    	//修改话题  $data :id sort   desc
    	$cate_name = '我们';
    	$data = array('id'=>630,'desc'=>'sdafsdf','sort'=>'2');
    	$result_3 =$redisobj->video_cate_list($cate_name,$data);
    	//print_r($result_3);
    	
    	/*$video_id=13917;
    	$sort =20;*/
    	$video_id=0;
    	$sort =0;
    	$result_4 =$redisobj->video_redis_list($user_id,$video_id,$sort);
    	//print_r($result_4);
    }
}
?>