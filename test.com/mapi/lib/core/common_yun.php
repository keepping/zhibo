<?php
//结束处理
/*
 * 查询是否有保存（已结束直播）
 * 查询是否分片（有保存）
 * 是否需要合并（有分片）
 */
function crontab_do_end_video_3(){

    try {
        $ret_array = array();
        //视频处理进行操作锁定,辨别字段 video_status 0:未进行任何处理 、1：已保存、2：分片合并中、3：合并完成、4:开始拉取视频 5：视频拉取中、6：拉取完成
        // 结束直播5分钟后，
        $sql = "select id,is_del_vod,video_type,channelid,begin_time,create_time,end_time,user_id,vote_number,destroy_group_status,group_id,video_status,source_url,file_id from ".DB_PREFIX."video where end_time < ".(NOW_TIME - 300)." and live_in = 0 and video_status = 0 limit 10";
        $sql = "select id,is_del_vod,video_type,channelid,begin_time,create_time,end_time,user_id,vote_number,destroy_group_status,group_id,video_status,source_url,file_id from ".DB_PREFIX."video where live_in = 3 and video_status = 0 limit 10";
        $list = $GLOBALS['db']->getAll($sql);

        if ($list){
            fanwe_require(APP_ROOT_PATH.'mapi/lib/redis/VideoRedisService.php');
            $video_redis = new VideoRedisService();
            fanwe_require(APP_ROOT_PATH.'system/tim/TimApi.php');
            $api = createTimAPI();
            foreach ( $list as $k => $v )
            {
                $ret = array();
                $data = array();
                //1:表示已经清空了,录制视频;0:未做清空操作
                $v['is_del_vod'] = 1 ;
                if ($v['is_del_vod'] == 1){//
                    fanwe_require(APP_ROOT_PATH.'mapi/lib/core/video_factory.php');
                    $video_factory = new VideoFactory();
                    $ret_g = $video_factory->GetVodRecordFiles($v['channelid'], $v['create_time']);
                    log_file('GetVodRecordFiles','crontab_do_end_video_3');
                    log_file($ret_g,'crontab_do_end_video_3');
                    $data['video_status'] = 0;
                    if ($ret_g['totalCount'] > 0){
                        //视频存在
                        if ($ret_g['totalCount'] > 1){
                            $sql = "update ".DB_PREFIX."video set is_del_vod='0',video_status = '1'  where id = ".$v['id'];
                            $data['video_status'] = 1;
                        }else{
                            $sql = "update ".DB_PREFIX."video set is_del_vod='0',video_status = '3'  where id = ".$v['id'];
                            $data['video_status'] = 3;
                        }
                        log_file($sql,'crontab_do_end_video_3');
                        $GLOBALS['db']->query($sql);
                        $v['is_del_vod'] = 0;
                        $data['is_del_vod'] = 0;
                    }
                    if($v['video_status']==1){
                        $data['video_status'] = 1;
                    }
                    $ret['id'] = $v['id'];
                    $ret['channelid'] = $v['channelid'];
                    $ret['video_status'] = $data['video_status'];
                    //1、被分片，需要合并处理
                    $ret['concat_status'] = 0;
                    if (intval($data['video_status']) == 1){
                        //进行视频合并
                        $channel_id = $v['channelid'];
                        $new_file_name = $v['channelid']."_".$v['id'];
                        log_file($new_file_name,'crontab_do_end_video_3');
                        $ret_c = $video_factory->ConcatVideo($channel_id,$new_file_name);
                        log_file($ret_c,'crontab_do_end_video_3');
                        //status=1 经过合并 v_status 是否合并成功 1合并完成 0不需要合并
                        if($ret_c['v_status']==1&&$ret_c['status']==1){
                            //合并中
                            $vodtaskid = $ret_c['vodtaskid'];
                            $sql = "update ".DB_PREFIX."video set video_status= 2,vodtaskid='".$vodtaskid."' where id = ".$v['id'];
                            log_file($sql,'crontab_do_end_video_3');
                            $GLOBALS['db']->query($sql);
                            $ret['concat_status'] = 1;
                        }

                    }
                    log_file('data[\'video_status\']','crontab_do_end_video_3');
                    log_file($data['video_status'],'crontab_do_end_video_3');
                    if($data['video_status']==3&&$v['source_url']==''){
                        $file_id = $ret_g['filesInfo'][0]['fileId'];
                        $ret_file = $video_factory->DescribeVodPlayUrls($file_id);
                        log_file($ret_file,'crontab_do_end_video_3');
                        if($ret_file['file_id']!=''){
                            $file_id = $ret_file['file_id'];
                            $source_url = $ret_file['urls'][20];
                            $sql = "update ".DB_PREFIX."video set source_url='".$source_url."',file_id = '".$file_id."'  where id = ".$v['id'];
                            log_file($sql,'crontab_do_end_video_3');
                            $GLOBALS['db']->query($sql);
                        }
                        $ret['file_id'] = $data['file_id'] = $file_id;
                        $ret['source_url'] =$data['source_url'] = $source_url;
                    }
                    $video_redis->update_db($v['id'], $data);
                }
                //直播结束 后相关数据处理（在后台定时执行）
                $ret['func']='do_end_video_3';
                $ret_array[]=$ret;
            }
        }
        return $ret_array;
    } catch (Exception $e) {
        return $e->getMessage();
    }
}
/*
 * 视频拉取处理
 */
function crontab_do_end_video_4(){
    try {
        $ret_array = array();
        //视频处理进行操作锁定,辨别字段 video_status 0:未进行任何处理 、1：已保存、2：分片合并中、3：合并完成、4:开始拉取视频 5：视频拉取中、6：拉取完成
        $sql = "select id,is_del_vod,video_type,channelid,begin_time,create_time,end_time,user_id,vote_number,destroy_group_status,group_id,video_status,source_url,file_id from " . DB_PREFIX . "video where  video_status = 3 limit 10";
        $list = $GLOBALS['db']->getAll($sql);
        if ($list){
            foreach ( $list as $k => $v ){
                $sql = "update ".DB_PREFIX."video set video_status = 4  where id = ".$v['id']."  and video_status = 3";
                log_file($sql,'crontab_do_end_video_4');
                $GLOBALS['db']->query($sql);
                if($GLOBALS['db']->affected_rows()){
                    fanwe_require(APP_ROOT_PATH.'mapi/lib/core/video_factory.php');
                    $video_factory = new VideoFactory();
                    $video_info = $video_factory->MultiPullVodFile($v['source_url'], $v['id'], get_gmtime());
                    log_file($video_info,'MultiPullVodFile');
                    if($video_info['codeDesc']=='Success'){
                        $ret_array['sourceurl'] = $video_info['data'][0]['source_url'] ;
                        $ret_array['filename'] = $video_info['data'][0]['file_name'] ;
                        $ret_array['vodtaskid'] = $video_info['data'][0]['vod_task_id'] ;
                        //更新状态 和任务ID:vodtaskid
                        $sql = "update ".DB_PREFIX."video set video_status = 5,vodtaskid = '". $ret_array['vodtaskid']."' where id = ".$v['id']."  and video_status = 4";
                        log_file($sql,'crontab_do_end_video_4');
                        $GLOBALS['db']->query($sql);
                        if($GLOBALS['db']->affected_rows()){

                        }
                    }
                }
            }
        }
        return $ret_array;
    } catch (Exception $e) {
        return $e->getMessage();
    }
}
//获取通知事件
function get_pullevent(){
    fanwe_require(APP_ROOT_PATH.'mapi/lib/core/video_factory.php');
    $video_factory = new VideoFactory();
    //合并是否完成（开始合并）
    $ret_g = $video_factory->PullEvent();
    foreach ($ret_g['data']['eventList'] as $key=>$item) {
        if($item['eventContent']['eventType']=='NewFileUpload'){
            $msghandle['NewFileUpload'][] = $item;
        }
        if($item['eventContent']['eventType']=='PullComplete'){
            $msghandle['PullComplete'][] = $item;
        }
        if($item['eventContent']['eventType']=='TranscodeComplete'){
            $msghandle['TranscodeComplete'][] = $item;
        }
        if($item['eventContent']['eventType']=='ConcatComplete'){
            $msghandle['ConcatComplete'][] = $item;
        }
        if($item['eventContent']['eventType']=='CreateImageSpriteComplete'){
            $msghandle['CreateImageSpriteComplete'][] = $item;
        }
        if($item['eventContent']['eventType']=='CreateSnapshotByTimeOffsetComplete'){
            $msghandle['CreateSnapshotByTimeOffsetComplete'][] = $item;
        }
    }
    log_file($msghandle,'get_pullevent');
    return $msghandle;
}

//URL转拉完成通知处理
function crontab_do_pullcomplete(){
    //是否拉取完成（开始拉取）
    //视频记录转移到历史表（拉取完成）
    //$msghandle = test_PullComplete();
    $msghandle = get_pullevent();
    if($msghandle['PullComplete']){
        foreach($msghandle['PullComplete'] as $item){
            $PullComplete['vodtaskid'][] = "'".$item['eventContent']['data']['vodTaskId']."'";
            $PullComplete['fileUrl'][$item['eventContent']['data']['vodTaskId']] = $item['eventContent']['data']['fileUrl'];
            $PullComplete['pull_msghandle'][] =  $item['msgHandle'];
        }
    }

    $ret_array = array();
    $pullcomplete_str =  implode(',',$PullComplete['vodtaskid']);
    if($pullcomplete_str!=''){
        $sql = "select id,is_del_vod,video_type,channelid,begin_time,create_time,end_time,user_id,vote_number,destroy_group_status,group_id,video_status,file_id,vodtaskid from " . DB_PREFIX . "video where video_status = 5 and  vodtaskid  in (".$pullcomplete_str.")";
        log_file($sql,'crontab_do_pullcomplete');
        $ret_array['sql'] = $sql;
        $list = $GLOBALS['db']->getAll($sql);
    }
    $url = $PullComplete['fileUrl'];
    if ($list) {
        foreach ($list as $k => $v) {
            //更新拉取完成状态
            $play_url = $url[$v['vodtaskid']];
            $sql = "update ".DB_PREFIX."video set video_status = 6,play_url ='".$play_url."'  where id = ".$v['id']."  and video_status = 5";
            $ret_array['sql1'] = $sql;
            log_file($sql,'crontab_do_pullcomplete');
            $GLOBALS['db']->query($sql);
            if($GLOBALS['db']->affected_rows()){
                //删除原视频
                /*fanwe_require(APP_ROOT_PATH.'mapi/lib/core/video_factory.php');
                $video_factory = new VideoFactory();
                $channel_id = $v['channelid'];
                $video_info = $video_factory->DeleteVodFiles($channel_id);
                $ret_array['video_info'] = $video_info;
                log_file('DeleteVodFiles','crontab_do_pullcomplete');
                log_file($video_info,'crontab_do_pullcomplete');*/
            }
        }
    }
    return $ret_array;
}
//视频拼接完成通知处理
function crontab_do_concatcomplete(){
    //是否拉取完成（开始拉取）
    //视频记录转移到历史表（拉取完成）
    $msghandle = test_ConcatComplete();
    if($msghandle['ConcatComplete']){
        foreach($msghandle['ConcatComplete'] as $item){
            $ConcatComplete['vodtaskid'][] = $item['eventContent']['data']['vodTaskId'];
            $ConcatComplete['concat_msghandle'][] =  $item['msgHandle'];
        }
    }
    return $ConcatComplete;
}
//视频上传完成通知处理
function crontab_do_newfileupload(){
    //是否拉取完成（开始拉取）
    //视频记录转移到历史表（拉取完成）
    $msghandle = test_NewFileUpload();
    if($msghandle['ConcatComplete']){
        foreach($msghandle['ConcatComplete'] as $item){
            $NewFileUpload['vodtaskid'][] = $item['eventContent']['data']['vodTaskId'];
            $NewFileUpload['concat_msghandle'][] =  $item['msgHandle'];
        }
    }
    return $NewFileUpload;
}
//视频转码完成通知处理
function crontab_do_transcodecomplete(){
    //是否拉取完成（开始拉取）
    //视频记录转移到历史表（拉取完成）
    $msghandle = test_TranscodeComplete();
    if($msghandle['ConcatComplete']){
        foreach($msghandle['ConcatComplete'] as $item){
            $TranscodeComplete['vodtaskid'][] = $item['eventContent']['data']['vodTaskId'];
            $TranscodeComplete['concat_msghandle'][] =  $item['msgHandle'];
        }
    }
    return $TranscodeComplete;
}


//获取视频上传完成通知处理
function test_NewFileUpload(){
    $msghandle['NewFileUpload'] = array(
        0 =>
            array (
                'msgHandle' => '476553469977303',
                'eventContent' =>
                    array (
                        'data' =>
                            array (
                                'fileId' => '9031868222912365916',
                                'fileUrl' => 'http://1251020758.vod2.myqcloud.com/8a96e57evodgzp1251020758/75ac9aa89031868222912365916/f0.mp4',
                                'message' => '',
                                'status' => 0,
                                'vodTaskId' => '',
                            ),
                        'eventType' => 'NewFileUpload',
                        'version' => '4.0',
                    ),
            ),
        1 =>
            array (
                'msgHandle' => '476553469977303',
                'eventContent' =>
                    array (
                        'data' =>
                            array (
                                'fileId' => '9031868222912365916',
                                'fileUrl' => 'http://1251020758.vod2.myqcloud.com/8a96e57evodgzp1251020758/75ac9aa89031868222912365916/f0.mp4',
                                'message' => '',
                                'status' => 0,
                                'vodTaskId' => '',
                            ),
                        'eventType' => 'NewFileUpload',
                        'version' => '4.0',
                    ),
            ),
    );
    return $msghandle;
}
//获取URL转拉完成通知处理
function test_PullComplete(){
    $msghandle['PullComplete'] = array(
        0 =>
            array (
                'msgHandle' => '516294617180961',
                'eventContent' =>
                    array (
                        'data' =>
                            array (
                                'fileId' => '9031868222916768589',
                                'fileUrl' => 'http://1251020758.vod2.myqcloud.com/8a96e57evodgzp1251020758/b2ebc5399031868222916768589/f0.mp4',
                                'message' => '',
                                'status' => 0,
                                'vodTaskId' => 'pull-c2c90cc95cb9923d24b98e38c3443dee',
                            ),
                        'eventType' => 'PullComplete',
                        'version' => '4.0',
                    ),
            ),
    );
    return $msghandle;
}
//视频转码完成通知处理
function test_TranscodeComplete(){
    $msghandle['TranscodeComplete'] = array(
        0 =>
            array (
                'msgHandle' => '374104058407037',
                'eventContent' =>
                    array (
                        'data' =>
                            array (
                                'coverUrl' => 'http://1251020758.vod2.myqcloud.com/3ebe8826vodtransgzp1251020758/5782b7239031868222915102568/shotup/f0.100_0.jpg',
                                'duration' => 351,
                                'fileId' => '9031868222915102568',
                                'fileName' => 'edu_20_2017-04-24-17-36-17_2017-04-24-17-36-17',
                                'message' => '',
                                'playSet' =>
                                    array (
                                        0 =>
                                            array (
                                                'definition' => 0,
                                                'url' => 'http://1251020758.vod2.myqcloud.com/8a96e57evodgzp1251020758/5782b7239031868222915102568/f0.mp4',
                                                'vbitrate' => 0,
                                                'vheight' => 0,
                                                'vwidth' => 0,
                                            ),
                                        1 =>
                                            array (
                                                'definition' => 210,
                                                'url' => 'http://1251020758.vod2.myqcloud.com/3ebe8826vodtransgzp1251020758/5782b7239031868222915102568/f0.f210.m3u8',
                                                'vbitrate' => 302291,
                                                'vheight' => 180,
                                                'vwidth' => 320,
                                            ),
                                        2 =>
                                            array (
                                                'definition' => 220,
                                                'url' => 'http://1251020758.vod2.myqcloud.com/3ebe8826vodtransgzp1251020758/5782b7239031868222915102568/f0.f220.m3u8',
                                                'vbitrate' => 577472,
                                                'vheight' => 360,
                                                'vwidth' => 640,
                                            ),
                                        3 =>
                                            array (
                                                'definition' => 20,
                                                'url' => 'http://1251020758.vod2.myqcloud.com/3ebe8826vodtransgzp1251020758/5782b7239031868222915102568/f0.f20.mp4',
                                                'vbitrate' => 511119,
                                                'vheight' => 360,
                                                'vwidth' => 640,
                                            ),
                                        4 =>
                                            array (
                                                'definition' => 230,
                                                'url' => 'http://1251020758.vod2.myqcloud.com/3ebe8826vodtransgzp1251020758/5782b7239031868222915102568/f0.f230.m3u8',
                                                'vbitrate' => 1127687,
                                                'vheight' => 720,
                                                'vwidth' => 1280,
                                            ),
                                    ),
                                'status' => 0,
                                'vodTaskId' => 'transcode-65e1de88d50c494778253a872ac0a69b',
                            ),
                        'eventType' => 'TranscodeComplete',
                        'version' => '4.0',
                    ),
            ),
        1 =>
            array (
                'msgHandle' => '374104058407037',
                'eventContent' =>
                    array (
                        'data' =>
                            array (
                                'coverUrl' => 'http://1251020758.vod2.myqcloud.com/3ebe8826vodtransgzp1251020758/5782b7239031868222915102568/shotup/f0.100_0.jpg',
                                'duration' => 351,
                                'fileId' => '9031868222915102568',
                                'fileName' => 'edu_20_2017-04-24-17-36-17_2017-04-24-17-36-17',
                                'message' => '',
                                'playSet' =>
                                    array (
                                        0 =>
                                            array (
                                                'definition' => 0,
                                                'url' => 'http://1251020758.vod2.myqcloud.com/8a96e57evodgzp1251020758/5782b7239031868222915102568/f0.mp4',
                                                'vbitrate' => 0,
                                                'vheight' => 0,
                                                'vwidth' => 0,
                                            ),
                                        1 =>
                                            array (
                                                'definition' => 210,
                                                'url' => 'http://1251020758.vod2.myqcloud.com/3ebe8826vodtransgzp1251020758/5782b7239031868222915102568/f0.f210.m3u8',
                                                'vbitrate' => 302291,
                                                'vheight' => 180,
                                                'vwidth' => 320,
                                            ),
                                        2 =>
                                            array (
                                                'definition' => 220,
                                                'url' => 'http://1251020758.vod2.myqcloud.com/3ebe8826vodtransgzp1251020758/5782b7239031868222915102568/f0.f220.m3u8',
                                                'vbitrate' => 577472,
                                                'vheight' => 360,
                                                'vwidth' => 640,
                                            ),
                                        3 =>
                                            array (
                                                'definition' => 20,
                                                'url' => 'http://1251020758.vod2.myqcloud.com/3ebe8826vodtransgzp1251020758/5782b7239031868222915102568/f0.f20.mp4',
                                                'vbitrate' => 511119,
                                                'vheight' => 360,
                                                'vwidth' => 640,
                                            ),
                                        4 =>
                                            array (
                                                'definition' => 230,
                                                'url' => 'http://1251020758.vod2.myqcloud.com/3ebe8826vodtransgzp1251020758/5782b7239031868222915102568/f0.f230.m3u8',
                                                'vbitrate' => 1127687,
                                                'vheight' => 720,
                                                'vwidth' => 1280,
                                            ),
                                    ),
                                'status' => 0,
                                'vodTaskId' => 'transcode-65e1de88d50c494778253a872ac0a69b',
                            ),
                        'eventType' => 'TranscodeComplete',
                        'version' => '4.0',
                    ),
            ),
    );
    return $msghandle;
}
//获取视频拼接完成通知处理
function test_ConcatComplete(){
    $msghandle['ConcatComplete'] = array(
        0 =>
            array (
                'msgHandle' => '560197602638589',
                'eventContent' =>
                    array (
                        'data' =>
                            array (
                                'fileInfo' =>
                                    array (
                                        0 =>
                                            array (
                                                'fileId' => '9031868222915958977',
                                                'fileType' => 'mp4',
                                                'fileUrl' => 'http://1251020758.vod2.myqcloud.com/8a96e57evodgzp1251020758/6a89d21a9031868222915958977/playlist.f9.mp4',
                                                'message' => '',
                                                'status' => 0,
                                            ),
                                    ),
                                'vodTaskId' => 'concat-3409059688705824a024587d83a2362b',
                            ),
                        'eventType' => 'ConcatComplete',
                        'version' => '4.0',
                    ),
            ),
        1 =>
            array (
                'msgHandle' => '526513940839864',
                'eventContent' =>
                    array (
                        'data' =>
                            array (
                                'fileInfo' =>
                                    array (
                                        0 =>
                                            array (
                                                'fileId' => '9031868222915981242',
                                                'fileType' => 'mp4',
                                                'fileUrl' => 'http://1251020758.vod2.myqcloud.com/8a96e57evodgzp1251020758/6abc26c79031868222915981242/playlist.f9.mp4',
                                                'message' => '',
                                                'status' => 0,
                                            ),
                                    ),
                                'vodTaskId' => 'concat-344cf1aec7c3693b0df44557600f611d',
                            ),
                        'eventType' => 'ConcatComplete',
                        'version' => '4.0',
                    ),
            ),
        2 =>
            array (
                'msgHandle' => '371074117837562',
                'eventContent' =>
                    array (
                        'data' =>
                            array (
                                'fileInfo' =>
                                    array (
                                        0 =>
                                            array (
                                                'fileId' => '9031868222915982899',
                                                'fileType' => 'mp4',
                                                'fileUrl' => 'http://1251020758.vod2.myqcloud.com/8a96e57evodgzp1251020758/6abccd5a9031868222915982899/playlist.f9.mp4',
                                                'message' => '',
                                                'status' => 0,
                                            ),
                                    ),
                                'vodTaskId' => 'concat-abb69264e2906f69ac81544a1144aa4f',
                            ),
                        'eventType' => 'ConcatComplete',
                        'version' => '4.0',
                    ),
            ),
    );
    return $msghandle;
}


?>