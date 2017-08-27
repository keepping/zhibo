<?php
// +----------------------------------------------------------------------
// | Fanwe 方维众筹商业系统
// +----------------------------------------------------------------------
// | Copyright (c) 2011 http://www.fanwe.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: 甘味人生(526130@qq.com)
// +----------------------------------------------------------------------

//相同操作

class VideoCommon extends CommonAction{
    //关闭房间
    function close_live($data){
        $m_config =  load_auto_cache("m_config");
        $system_user_id =$m_config['tim_identifier'];//系统消息
        $podcast_id = trim($data ['user_id']);
        $podcast = $GLOBALS['db']->getRow("select u.id as user_id,u.nick_name,u.head_image from ".DB_PREFIX."user as u where  u.id=".$podcast_id);
        $room_id = trim($data ['room_id']);

        $ext = array();
        $ext['type'] = 17;
        $ext['desc'] = '违规直播，立即关闭直播';
        $ext['room_id'] = $room_id;
        #构造高级接口所需参数
        $msg_content = array();
        //创建array 所需元素
        $msg_content_elem = array(
            'MsgType' => 'TIMCustomElem',       //自定义类型
            'MsgContent' => array(
                'Data' => json_encode($ext),
                'Desc' => '',
                //	'Ext' => $ext,
                //	'Sound' => '',
            )
        );
        //将创建的元素$msg_content_elem, 加入array $msg_content
        array_push($msg_content, $msg_content_elem);
        require_once(APP_ROOT_PATH.'system/tim/TimApi.php');
        $api = createTimAPI();
        $ret = $api->openim_send_msg2($system_user_id, $podcast_id, $msg_content);
        //结束直播
        $sql = "select id,user_id,max_watch_number,virtual_watch_number,robot_num,vote_number,group_id,room_type,begin_time,end_time,channelid,cate_id,video_vid from ".DB_PREFIX."video where id = ".$room_id." and user_id = ".$podcast_id;
        $video = $GLOBALS['db']->getRow($sql,true,true);

        if($video&&$ret['ActionStatus'] != 'OK'){
            $result = admin_do_end_video($video,$video['video_vid'],0,$video['cate_id']);
            $room_id = $video['id'];
            if ($video['group_id'] != ''&&$result){
                //=========================================================
                //广播：直播结束
                $ext = array();
                $ext['type'] = 18; //18：直播结束（全体推送的，用于更新用户列表状态）
                $ext['room_id'] = $room_id;//直播ID 也是room_id;只有与当前房间相同时，收到消息才响应
                //发送广播：直播结束
                fanwe_require(APP_ROOT_PATH.'system/tim/TimApi.php');
                $api = createTimAPI();
                //18：直播结束（全体推送的，用于更新用户列表状态）
                $api->group_send_group_system_notification($m_config['on_line_group_id'],json_encode($ext),null);
                //=========================================================
            }
            $this->success ('操作成功',1);
        }
        if ($ret['ActionStatus'] == 'OK'){
            $result=M("Video")->where("id=".$room_id)->setField("live_in",0);
            save_log('管理员结束'.$podcast['nick_name'].'直播',1);
            $this->success ('操作成功',1);
        }else {
            if($result){
                save_log('管理员结束'.$podcast['nick_name'].'直播',1);
                $this->success ('操作成功',1);
            }else{
                save_log('管理员结束'.$podcast['nick_name'].'直播',0);
                $this->error ('操作失败',1);
            }
        }
    }
}
	