<?php
// +----------------------------------------------------------------------
// | Fanwe 方维众筹商业系统
// +----------------------------------------------------------------------
// | Copyright (c) 2011 http://www.fanwe.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: 甘味人生(526130@qq.com)
// +----------------------------------------------------------------------

class VideoCheckAction extends CommonAction{
	//回播列表	
	public function playback_index() {
		$now=get_gmtime();
        $this->check_account();
        if(strim($_REQUEST['nick_name'])!=''){//name

            $user=M("User")->where("nick_name like '%".strim($_REQUEST['nick_name'])."%' ")->findAll();
            $user_arr_id = array();
            foreach($user as $k=>$v){
                $user_arr_id[$k] =intval($v['id']);
            }
            //$user_str_id = implode(',',$user_arr_id);
            $map['user_id'] = array('in',$user_arr_id);
        }
		if(intval($_REQUEST['cate_id'])>0)
		{
			$map['cate_id'] = intval($_REQUEST['cate_id']);
		}
        if($_REQUEST['live_in']!='')
        {
            $map['live_in'] = intval($_REQUEST['live_in']);
        }

        if(intval($_REQUEST['room_id'])>0)
        {
            $map['id'] = intval($_REQUEST['room_id']);
        }
		
		if(intval($_REQUEST['user_id'])>0)
		{
			$map['user_id'] = intval($_REQUEST['user_id']);
		}
		
		$create_time_2=empty($_REQUEST['create_time_2'])?to_date($now,'Y-m-d'):strim($_REQUEST['create_time_2']);
		$create_time_2=to_timespan($create_time_2)+24*3600;
		if(trim($_REQUEST['create_time_1'])!='')
		{
			$map['create_time'] = array('between',array(to_timespan($_REQUEST['create_time_1']),$create_time_2));
		}
        $map['is_delete'] = 0;
		$map['is_del_vod'] = 0;
        $map['room_type'] = array('in',array(0,2,3));
		//$map['video_vid'] =array("neq",NULL);

		if (method_exists ( $this, '_filter' )) {
			$this->_filter ( $map );
		}
		//$name=$this->getActionName();
		$model = D ('VideoCheck');
		if (! empty ( $model )) {
			$this->_list ( $model, $map );
		}
        $list = $this->get("list");
        foreach($list as &$v){
            if(function_exists('time_len')) {
                $v['len_time'] = time_len(intval($v['len_time']));
            }
            $v['pay_editable'] = 0;
            if(defined('OPEN_LIVE_PAY')&&OPEN_LIVE_PAY==1){
                $v['pay_editable'] = 1;
                if($v['is_live_pay']==1&&$v['live_pay_type']==0){
                    $v['pay_editable'] = 0;
                }
            }
        }
        $this->assign ( 'list', $list );
		$cate_list = M("VideoCate")->findAll();
		$this->assign("cate_list",$cate_list);
		$this->display ();
	}

    public function play(){
        $id = $_REQUEST['id'];
        $condition['id'] = $id;
        $video = M('VideoCheck')->where($condition)->find();
        if(!empty($video)){
            //直播
            $video['mp4_url'] = get_spec_image($video['play_url']);
            $video['m3u8_url'] = get_spec_image($video['play_url']);
            $video['flv_url'] = get_spec_image($video['play_url']);
            $video['rtmp_url'] = get_spec_image($video['play_url']);
        }else{
            $this->assign("error",'');
        }
        $this->assign("video",$video);
        $this->display();
    }
    //修改上线状态
    public function set_demand_video_status(){
        require_once APP_ROOT_PATH."/mapi/lib/core/common.php";
        $id = $_REQUEST['id'];
        $result['status'] = 0;
        if (isset ( $id )) {
            $condition = array ('id' => array ('in', explode ( ',', $id ) ) );
            $rel_data = M('VideoCheck')->where($condition)->findAll();
            $success_info = array();
            $fail_info = array();
            $live_pay_id = '';
            foreach($rel_data as $data)
            {
                if ($data['live_in'] == 0){
                     $re = $this->check_video_status($data['id'],0);
                    if($re){
                        $live_pay_id .= $data['id'].",";
                        $fail_live_info = $live_pay_id."上线成功！";
                    }
                }else{
                    $re = $this->check_video_status($data['id'],1);
                    if($re){
                        $live_pay_id .= $data['id'].",";
                        $fail_live_info = $live_pay_id."下线成功！";
                    }
                }
                $success_info[] = $data['id'];
                if($re){
                    $result['status'] = 1;
                }else{
                    $fail_info[] = $data['id'];
                }
            }


            if($success_info) $success_info = implode(",",$success_info);
            if($fail_info) $fail_info = implode(",",$fail_info);
            if ($re) {
                save_log($success_info.l("DEMAND_VIDEO_STATUS_SUCCESS"),1);
                $result['info'] = '修改成功！'.$fail_live_info;
            }else {
                if($success_info){
                    save_log($success_info.l("DEMAND_VIDEO_STATUS_SUCCESS"),1);
                }
                save_log($fail_info.l("DEMAND_VIDEO_STATUS_FAILED"),0);
                $result['info'] = $fail_info.'修改失败！'.$fail_live_info;
            }
        } else {
            $result['status'] = 0;
            $result['info'] = '编号错误';
        }
        admin_ajax_return($result);
    }

    function  check_account(){
        $check_info = M('User')->where("mobile='13999999999'")->find();
        $sql = "select id,head_image from ".DB_PREFIX."video_check where user_id <>".$check_info['id'];
        $list = $GLOBALS['db']->getAll($sql);

        $check_info_arr = array();
        if($list){
            foreach($list as $value){
                $check_info_arr[]  = $value['id'];
            }
        }
        $check_info_str = implode(',',$check_info_arr);
        if($check_info_str!=''){
            $sql = "update ".DB_PREFIX."video_check as vc set vc.user_id = (SELECT id from ".DB_PREFIX."user where mobile = '13999999999' ),vc.head_image = (SELECT head_image from ".DB_PREFIX."user where mobile = '13999999999' ) where vc.id in (".$check_info_str.");";
            $GLOBALS['db']->query($sql);
        }
    }
    function check_video_status($video_id,$status){

        $pInTrans = $GLOBALS['db']->StartTrans();
        try
        {
            fanwe_require(APP_ROOT_PATH.'mapi/lib/redis/BaseRedisService.php');
            fanwe_require(APP_ROOT_PATH.'mapi/lib/redis/VideoRedisService.php');
            $video_redis = new VideoRedisService();

            if($status == 0){
                //历史直播：上架
                $sql = "update ".DB_PREFIX."video_check set live_in = 3 where live_in = 0 and id = ".$video_id;
                $GLOBALS['db']->query($sql);
                if($GLOBALS['db']->affected_rows()){
                    $sql = "select * from ".DB_PREFIX."video_check  where id = ".$video_id;
                    $video = $GLOBALS['db']->getRow($sql);
                    $video['id'] = get_max_room_id(0);//视频ID;
                    $GLOBALS['db']->autoExecute(DB_PREFIX."video", $video,"INSERT");
	
                    $sql = "update ".DB_PREFIX."video_check set id = ".$video['id']." where live_in = 3 and id = ".$video_id;
                    $GLOBALS['db']->query($sql);
					$video_id = $video['id'];
					
					require_once(APP_ROOT_PATH.'system/tim/TimApi.php');
                    $api = createTimAPI();
					$ret = $api->group_get_group_info2(array('0'=>(string)$video['group_id']));
					if ($ret['GroupInfo'][0]['ErrorCode']){
                        //重新创建聊天组
						$ret = $api->group_create_group('AVChatRoom', (string)$video['user_id'], (string)$video['user_id'], (string)$video['id']);
						if ($ret['ActionStatus'] == 'OK'){
                            $sql = "update ".DB_PREFIX."video set destroy_group_status = 1,group_id=".$ret['GroupId']." where id =".$video['id'];
                            $GLOBALS['db']->query($sql);
							$sql = "update ".DB_PREFIX."video_check set destroy_group_status = 1,group_id=".$ret['GroupId']." where id =".$video['id'];
                            $GLOBALS['db']->query($sql);
                        }
                    }

                    //修改话题
                    if ($video['cate_id'] > 0){
                        $sql = "update ".DB_PREFIX."video_cate a set a.num = (select count(*) from ".DB_PREFIX."video b where b.cate_id = a.id and b.live_in in (1,3)";
                        $m_config =  load_auto_cache("m_config");//初始化手机端配置
                        if((defined('OPEN_ROOM_HIDE')&&OPEN_ROOM_HIDE==1)&&intval($m_config['open_room_hide'])==1){
                            $sql.= " and b.province <> '火星' and b.province <>''";
                        }
                        $sql.=") where a.id = ".$video['cate_id'];
                        $GLOBALS['db']->query($sql);
                    }

                    $user_id = intval($video['user_id']);
                    $sql = "select sex,ticket,refund_ticket,user_level,fans_count from ".DB_PREFIX."user where id = ".$user_id;
                    $user = $GLOBALS['db']->getRow($sql,true,true);
                    $m_config =  load_auto_cache("m_config");

                    //sort_init(初始排序权重) = (用户可提现印票：fanwe_user.ticket - fanwe_user.refund_ticket) * 保留印票权重+ 直播/回看[回看是：0; 直播：9000000000 直播,需要排在最上面 ]+ fanwe_user.user_level * 等级权重+ fanwe_user.fans_count * 当前有的关注数权重
                    $sort_init = (intval($user['ticket']) - intval($user['refund_ticket'])) * floatval($m_config['ticke_weight']);

                    $sort_init += intval($user['user_level']) * floatval($m_config['level_weight']);
                    $sort_init += intval($user['fans_count']) * floatval($m_config['focus_weight']);

                    $sql = "update ".DB_PREFIX."video set sort_init = ".$sort_init.",watch_number=0,robot_num=0 where id = ".$video_id;
                    $GLOBALS['db']->query($sql);

                    //将mysql数据,同步一份到redis中
                    sync_video_to_redis($video_id,'*',false);
                    //付费记录从历史表移到原记录表中
                    $video_redis->video_online($video_id, $video['group_id']);
                }
            }else{
                $sql = "update ".DB_PREFIX."video_check set live_in = 0 where live_in = 3 and id = ".$video_id;
                $GLOBALS['db']->query($sql);
                if($GLOBALS['db']->affected_rows()){
                    //回看直播：下架
                    $sql = "delete from ".DB_PREFIX."video WHERE id=".$video_id;
                    $GLOBALS['db']->query($sql);
                }
            }
            //提交事务,不等 消息推送,防止锁太久
            $GLOBALS['db']->Commit($pInTrans);
            return true;
        }catch(Exception $e){
            //异常回滚
            $GLOBALS['db']->Rollback($pInTrans);
            return true;
        }
    }

}
?>