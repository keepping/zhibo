<?php

header("Content-Type:text/html; charset=utf-8");

$json = $GLOBALS['HTTP_RAW_POST_DATA'];
$post = json_decode($json,true);
define("CTL",'ctl');
define("ACT",'act');
if(!defined('APP_ROOT_PATH'))
	define('APP_ROOT_PATH', str_replace('imcallback.php', '', str_replace('\\', '/', __FILE__)));
require APP_ROOT_PATH.'public/directory_init.php';
require APP_ROOT_PATH.'system/define.php';
require APP_ROOT_PATH."system/cache/Rediscache/Rediscache.php";
if ($post['CallbackCommand'] == 'Group.CallbackAfterNewMemberJoin'){

	define("FANWE_REQUIRE",true);
	//require './system/system_init.php';
	require(APP_ROOT_PATH.'mapi/lib/redis/BaseRedisService.php');
	require(APP_ROOT_PATH.'mapi/lib/redis/VideoViewerRedisService.php');
	$video_viewer_obj = new VideoViewerRedisService();

	$video_viewer_obj->member_join($post);

	/*
    //新成员入群之后回调
    $GroupId = $post['GroupId'];

    //新入群成员列表
    foreach ( $post['NewMemberList'] as $k => $v ){

        $video_viewer = array();
        $video_viewer['group_id'] = $GroupId;
        $video_viewer['user_id'] = $v['Member_Account'];
        $video_viewer['begin_time'] = NOW_TIME;
        //$video_viewer['create_time'] = to_date(NOW_TIME);
        $GLOBALS['db']->autoExecute(DB_PREFIX."video_viewer", $video_viewer,"INSERT");

        //log_result2(print_r($video_viewer,1));


    }
    //最大观看人数(每进来一人次加1）
    $sql = "update ".DB_PREFIX."video set watch_number = watch_number + ".count($post['NewMemberList']).",max_watch_number = max_watch_number + ".count($post['NewMemberList'])." where group_id ='".$GroupId."'";
    $GLOBALS['db']->query($sql);
    */
	//log_result2($sql);
}else if ($post['CallbackCommand'] == 'Group.CallbackAfterMemberExit'){

	define("FANWE_REQUIRE",true);
	//require './system/system_init.php';
	require(APP_ROOT_PATH.'mapi/lib/redis/BaseRedisService.php');
	require(APP_ROOT_PATH.'mapi/lib/redis/VideoViewerRedisService.php');
	$video_viewer_obj = new VideoViewerRedisService();

	$video_viewer_obj->member_exit($post);
	/*
    //群成员离开之后回调
    $GroupId = $post['GroupId'];

    //退出群的成员列表
    foreach ( $post['ExitMemberList'] as $k => $v ){
        $sql = "update ".DB_PREFIX."video_viewer set end_time = ".NOW_TIME." where end_time = 0 and user_id = ".$v['Member_Account']." and group_id ='".$GroupId."'";
        $GLOBALS['db']->query($sql);

        if(!$GLOBALS['db']->affected_rows()){
            //更新失败,说明以前加入调回失败
            $video_viewer = array();
            $video_viewer['group_id'] = $GroupId;
            $video_viewer['user_id'] = $v['Member_Account'];
            $video_viewer['begin_time'] = 0;//修正数据时用fanwe_video.begin_time代替
            $video_viewer['end_time'] = NOW_TIME;
            //$video_viewer['create_time'] = to_date(NOW_TIME);
            $GLOBALS['db']->autoExecute(DB_PREFIX."video_viewer", $video_viewer,"INSERT");

        }
    }

    $num = count($post['ExitMemberList']);
    $sql = "update ".DB_PREFIX."video v set v.watch_number = IF(v.watch_number - $num > 0,v.watch_number - $num,(select count(*) from ".DB_PREFIX."video_viewer vv where vv.is_robot = 0 and vv.end_time = 0 and vv.group_id = v.group_id)) where group_id ='".$GroupId."'";
    $GLOBALS['db']->query($sql);
    */
}


$data = array();
$data['ActionStatus'] = 'OK';
$data['ErrorCode'] = 0;
$data['ErrorInfo'] = '';

echo json_encode($data);
exit;


//log_result2(print_r($post,1));

// 打印log
function  log_result2($word)
{
	$file="./imcallback_log/notify_url.log";//log文件路径
	$fp = fopen($file,"a");
	flock($fp, LOCK_EX) ;
	fwrite($fp,"执行日期：".strftime("%Y-%m-%d-%H：%M：%S",time())."\n".$word."\n\n");
	flock($fp, LOCK_UN);
	fclose($fp);
}

?>
