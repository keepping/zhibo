<?php
// +----------------------------------------------------------------------
// | Fanwe 方维众筹商业系统
// +----------------------------------------------------------------------
// | Copyright (c) 2011 http://www.fanwe.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: 甘味人生(526130@qq.com)
// +----------------------------------------------------------------------

class VideoPlaybackAction extends CommonAction{
	//回播列表	
	public function playback_index() {

		$now=get_gmtime();

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
		$model = D ('VideoHistory');
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
        if(defined('OPEN_LIVE_PAY')&&OPEN_LIVE_PAY==1){
            $this->assign ( 'is_pay_live', 1 );
        }else{
            $this->assign ( 'is_pay_live', 0 );
        }
		$cate_list = M("VideoCate")->findAll();
		$this->assign("cate_list",$cate_list);
        //服务端开启类型
        $sql = "select val from  ".DB_PREFIX."m_config where  code= 'video_type'";
        $video_type = $GLOBALS['db']->getOne($sql);
        $this->assign("video_type",intval($video_type));
		$this->display ();
	}
    public function set_live_pay()
    {
        fanwe_require( APP_ROOT_PATH.'mapi/lib/redis/BaseRedisService.php');
        fanwe_require(APP_ROOT_PATH.'mapi/lib/redis/VideoRedisService.php');
        $video_id = intval($_REQUEST['id']);
        $video_redis = new VideoRedisService($video_id);
        $video = $video_redis->getRow_db($video_id,array('id','is_live_pay','live_fee'));
        $this->assign("video",$video);
        $this->display();
    }

    public function modify_live_pay(){
        $video_id = intval($_REQUEST['id']);
        $video['id'] = $video_id;
        $video['live_pay_type'] = 0;
        $video['live_fee'] = 0;
        $video['is_live_pay'] = intval($_REQUEST['is_live_pay']);//是否付费
        $live_fee = intval($_REQUEST['live_fee']);//观看费用
        if($video['is_live_pay']){
            $video['live_pay_type'] = 1;
            $video['live_fee'] = $live_fee;
        }

        if($video['live_fee'] && !preg_match('/^[0-9]*[1-9][0-9]*$/', $video['live_fee']))
        {
            $this->error("观看费用必须为大于0的整数");
        }

        $list=M("VideoHistory")->save ($video);
        if (false !== $list) {
            //redis同步
            require_once APP_ROOT_PATH."/mapi/lib/core/common.php";
            fanwe_require(APP_ROOT_PATH.'mapi/lib/redis/BaseRedisService.php');
            sync_video_to_redis($video_id, '*', false);
            save_log($video_id.L("UPDATE_SUCCESS"),1);
            $this->success(L("UPDATE_SUCCESS"));
        } else {
            //错误提示
            save_log($video_id.L("UPDATE_FAILED"),0);
            $this->error(L("UPDATE_SUCCESS"));
        }
    }

    /*public function play_bak(){
        require_once APP_ROOT_PATH."/mapi/lib/core/common.php";
        fanwe_require(APP_ROOT_PATH.'mapi/lib/redis/BaseRedisService.php');
        $m_config =  load_auto_cache("m_config");//初始化手机端配置
        $app_id = $m_config['vodset_app_id'];
        $this->assign('app_id',$app_id);
        $id = $_REQUEST['id'];
        $condition['id'] = $id;
        $video = M('VideoHistory')->where($condition)->find();
        if(!empty($video)){
            $root = get_vodset_by_video_id($id);
            if(isset($root['vodset'])){
                $play_list = array();
                $vodset = $root['vodset'];
                foreach($vodset as $k=>$v){
                    $playSet = $v['fileSet'];
                    for($i=sizeof($playSet)-1;$i>=0;$i--){
                        if($playSet[$i]['duration']>1){
                            $play_list[] = $playSet[$i]['fileId'];
                        }
                    }
                }
                $this->assign("playlist",implode(',',$play_list));
                $this->assign("video_url",$play_list[0]);
                $this->assign("poster",$vodset[0]['fileSet'][sizeof($vodset[0]['fileSet'])-1]['image_url']);
            }else{
                $this->assign("error",$root['error']);
            }
        }
        $this->assign("video",$video);
        $this->display();
    }*/
    public function play(){
        require_once APP_ROOT_PATH."/mapi/lib/core/common.php";
        fanwe_require(APP_ROOT_PATH.'mapi/lib/redis/BaseRedisService.php');
        $m_config =  load_auto_cache("m_config");//初始化手机端配置
        $app_id = $m_config['vodset_app_id'];
        $this->assign('app_id',$app_id);
        $id = $_REQUEST['id'];
        $condition['id'] = $id;
        $video = M('VideoHistory')->where($condition)->find();
        if(!empty($video)&&$video['play_url']==''){
            $root = get_vodset_by_video_id($id);
            if(isset($root['vodset'])){
                $play_list = array();
                $vodset = $root['vodset'];
                foreach($vodset as $k=>$v){
                    $playSet = $v['fileSet'];
                    for($i=sizeof($playSet)-1;$i>=0;$i--){
                        $play_list[] = $playSet[$i]['fileId'];
                        $play_url_list[] = $playSet[$i]['playSet'];
                    }
                }
                foreach($play_url_list as $k2=>$v2){
                    foreach($v2 as $kk=>$vv) {
                        //mp4
                        if ($vv['definition'] == 0&&strpos($vv['url'], '.mp4')) {//原画mp4 播放URL
                            $video['mp4_url'] = $vv['url'];
                        }
                        if ($vv['definition'] == 10) {//手机mp4 播放URL
                            $video['mp4_sj_url'] = $vv['url'];
                        }
                        if ($vv['definition'] == 20) {//标清mp4 播放URL
                            $video['mp4_sd_url'] = $vv['url'];
                        }
                        if ($vv['definition'] == 40 || $vv['definition'] == 30) {//高清mp4 播放URL
                            $video['mp4_hd_url'] = $vv['url'];
                        }
                        //m3u8
                        if ($vv['definition'] == 0 &&strpos($vv['url'], '.m3u8')) {//原画m3u8 播放URL
                            $video['m3u8_url'] = $vv['url'];
                        }
                        if (($vv['definition'] == 210)&&strpos($vv['url'], '.m3u8')) {//手机m3u8 播放URL
                            $video['m3u8_url'] = $vv['url'];
                        }
                        if (($vv['definition'] == 220)&&strpos($vv['url'], '.m3u8')) {//标清m3u8 播放URL
                            $video['m3u8_sd_url'] = $vv['url'];
                        }
                        if (($vv['definition'] == 230)&&strpos($vv['url'], '.m3u8')) {//高清m3u8 播放URL
                            $video['m3u8_hd_url'] = $vv['url'];
                        }
                        //flv
                        if (strpos($vv['url'], '.flv')&&$vv['definition'] == 0) {//原画flv 播放URL
                            $video['flv_url'] = $vv['url'];
                        }
                        if (strpos($vv['url'], '.flv')&&$vv['definition'] == 0) {//手机flv 播放URL
                            $video['flv_sj_url'] = $vv['url'];
                        }
                        if (strpos($vv['url'], '.flv')&&$vv['definition'] == 0) {//标清flv 播放URL
                            $video['flv_sd_url'] = $vv['url'];
                        }
                        if (strpos($vv['url'], '.flv')&&$vv['definition'] == 0) {//高清flv 播放URL
                            $video['flv_hd_url'] = $vv['url'];
                        }
                        //rtmp
                        if (!strpos($vv['url'], '.flv')&&!strpos($vv['url'], '.mp4')&&!strpos($vv['url'], '.m3u8')&&$vv['definition'] == 0) {//原画rtmp 播放URL
                            $video['rtmp_url'] = $vv['url'];
                        }
                        if (!strpos($vv['url'], '.flv')&&!strpos($vv['url'], '.mp4')&&!strpos($vv['url'], '.m3u8')&&$vv['definition'] == 0) {//手机rtmp 播放URL
                            $video['rtmp_sj_url'] = $vv['url'];
                        }
                        if (!strpos($vv['url'], '.flv')&&!strpos($vv['url'], '.mp4')&&!strpos($vv['url'], '.m3u8')&&$vv['definition'] == 0) {//标清rtmp 播放URL
                            $video['rtmp_sd_url'] = $vv['url'];
                        }
                        if (!strpos($vv['url'], '.flv')&&!strpos($vv['url'], '.mp4')&&!strpos($vv['url'], '.m3u8')&&$vv['definition'] == 0) {//高清rtmp 播放URL
                            $video['rtmp_hd_url'] = $vv['url'];
                        }
                    }
                }
                $this->assign("poster",$vodset[0]['fileSet'][sizeof($vodset[0]['fileSet'])-1]['image_url']);
            }else{
                $this->assign("error",$root['error']);
            }
        }else{
            //直播
            $video['mp4_url'] = $video['play_mp4'];
            if($video['play_url']) $video['mp4_url'] = $video['play_url'];
            $video['m3u8_url'] = $video['play_hls'];
            $video['flv_url'] = $video['play_flv'];
            $video['rtmp_url'] = $video['play_rtmp'];
        }
        $this->assign("video",$video);
        $this->display();
    }

    //删除视频
    public function del_video(){
        require_once APP_ROOT_PATH."/mapi/lib/core/common.php";

        $ajax = intval($_REQUEST['ajax']);
        $id = $_REQUEST['id'];
        $result['status'] = 0;

        if (isset ( $id )) {
            $condition = array ('id' => array ('in', explode ( ',', $id ) ) );
            $rel_data = M('VideoHistory')->where($condition)->findAll();
            $success_info = array();
            $fail_info = array();

            fanwe_require(APP_ROOT_PATH.'mapi/lib/redis/BaseRedisService.php');
            fanwe_require(APP_ROOT_PATH.'mapi/lib/redis/VideoRedisService.php');
            $video_redis = new VideoRedisService();

            foreach($rel_data as $video)
            {
            	if($video['live_in']==0){
            		
	                $root = del_vodset($video,true);
                    $sql = "update ".DB_PREFIX."video_history set is_delete = 1 where id =".$video['id'];
                    $GLOBALS['db']->query($sql);
	                
	                if($GLOBALS['db']->affected_rows()){
	                    $success_info[] = $video['id'];
	                    $user_id = intval($video['user_id']);
	                    
						$sql = "select count(*) as num from ".DB_PREFIX."video_history where is_delete = 0 and is_del_vod = 0 and user_id = '".$user_id."'";
						$video_count = $GLOBALS['db']->getOne($sql);

                        $sql = "update ".DB_PREFIX."user set video_count = ".$video_count." where id = ".$user_id;
                        $GLOBALS['db']->query($sql);
							
						fanwe_require(APP_ROOT_PATH.'mapi/lib/redis/UserRedisService.php');
						$user_redis = new UserRedisService();
						$user_data = array();
						$user_data['video_count'] = $video_count;
						$user_redis->update_db($user_id, $user_data);
						
	                    $result['status'] = 1;
	                }else{
	                    $fail_info[] = $video['id'];
	                }
	            }else{
            		$fail_info[] = $video['id'].':不是历史状态,不能删除';
	            }
            }
            
            if($success_info) $success_info = implode(",",$success_info);
            if($fail_info) $fail_info = implode(",",$fail_info);
            if (!$fail_info) {
                save_log($success_info.l("FOREVER_DELETE_SUCCESS"),1);
                clear_auto_cache("get_help_cache");
                $result['info'] = '删除成功！';
                //$this->success (l("FOREVER_DELETE_SUCCESS"),$ajax);
            } else {
                if($success_info){
                    save_log($success_info.l("FOREVER_DELETE_SUCCESS"),1);
                }
                save_log($fail_info.l("FOREVER_DELETE_FAILED"),0);
                $result['info'] = $fail_info.'  删除失败！';
                //$this->error (l($fail_info),$ajax);
            }
        } else {
            $result['status'] = 0;
            $result['info'] = '编号错误';
            //$this->error (l("INVALID_OPERATION"),$ajax);
        }
        admin_ajax_return($result);
    }
    
        //修改上线状态
    public function set_demand_video_status(){
        require_once APP_ROOT_PATH."/mapi/lib/core/common.php";
        $ajax = intval($_REQUEST['ajax']);
        $id = $_REQUEST['id'];
        $result['status'] = 0;
        if (isset ( $id )) {
            $condition = array ('id' => array ('in', explode ( ',', $id ) ) );
            $rel_data = M('VideoHistory')->where($condition)->findAll();
            $success_info = array();
            $fail_info = array();
            $live_pay_id = '';
            foreach($rel_data as $data)
            {
            	if ($data['live_in'] == 0&&(($data['is_live_pay']==1&&$data['live_pay_type'] != 0)||$data['is_live_pay']==0)){
            		//上架
                    fanwe_require(APP_ROOT_PATH . 'mapi/lib/core/video_factory.php');
                    fanwe_require(APP_ROOT_PATH.'mapi/lib/redis/BaseRedisService.php');
                    fanwe_require(APP_ROOT_PATH.'mapi/lib/redis/VideoRedisService.php');
                    $video_factory = new VideoFactory();
                    /*if($data['video_type'] == 1 && $data['channelid']&& strpos($data['channelid'],'_'))
                    {
                        $ret = $video_factory->GetVodRecordFiles($data['channelid'], $data['create_time']);
                    } else {
                        $fileName = $data['id'] . '_' . to_date($data['begin_time'],'Y-m-d-H');
                        if($data['video_type'] == 1){
                            $fileName = 'live'.$data['id'] . '_' . to_date($data['begin_time'],'Y-m-d-H');
                        }
                        $ret = $video_factory->DescribeVodPlayInfo($fileName);
                    }*/
                    $ret = get_vodset_by_video_id($data['id']);
                    /*if ($ret['totalCount'] > 0){*/
                    if ($ret['total_count'] > 0||$data['play_url']!=''){
                        require_once(APP_ROOT_PATH.'system/tim/TimApi.php');
                        $api = createTimAPI();
                        $ret = $api->group_get_group_info2(array('0'=>$data['user_id']));
                        if ($ret['GroupInfo'][0]['ErrorCode']){
                    		//重新创建聊天组
	                    	$ret = $api->group_create_group('AVChatRoom', (string)$data['user_id'], (string)$data['user_id'], (string)$data['id']);
	                    	if ($ret['ActionStatus'] == 'OK'){
	                    		$sql = "update ".DB_PREFIX."video_history set destroy_group_status = 1,group_id=id where id =".$data['id'];
	                    		$GLOBALS['db']->query($sql);
	                    	}
                    	}
                    	
                        $re = video_status($data['id'],0);
                    }else{
                        $sql = "update ".DB_PREFIX."video_history set is_del_vod = 1 where id = ".$data['id'];
                        $GLOBALS['db']->query($sql);

                        $video_redis = new VideoRedisService();
                        $n_data = array();
                        $n_data['is_del_vod'] = 1;
                        $video_redis->update_db($data['id'], $n_data);
                        $result['status'] = 1;
                        $result['info'] = '视频不存在';
                        admin_ajax_return($result);
                    }
            	}else{
            		$live_pay_id .= $data['id'].",";
            		if($data['is_live_pay']==1&&$data['live_pay_type'] != 0){
            			$fail_live_info = $live_pay_id."按时付费直播无法上线";
            		}else{
            			$fail_live_info = $live_pay_id."付费直播上线失败";
            		}
            		
            	}

            	$success_info[] = $data['id'];
            	if($re){
            		$result['status'] = 1;
            	}else{
            		$fail_info[] = $data['id'];
            	}
                    
                /*}else{
                    $fail_info[] = $data['id'];
                }*/
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

    public function add_tecent_video(){
        if(!TECENT_VIDEO){
            admin_ajax_return(array(
                'status' => 0,
                'error'  => "模块开关未打开"
            ));
        }
        $m_config = load_auto_cache("m_config");
        $this->assign('secret_id', $m_config['qcloud_secret_id']);
        $this->display();
    }

    /**
     * 上传视频签名接口
     *
     * @return 签名
     */
    public function sign()
    {
        $args = $_REQUEST['args'];
        fanwe_require(APP_ROOT_PATH . 'mapi/lib/core/video_factory.php');
        $video_factory = new VideoFactory();
        $result = $video_factory->Sign($args);
        $root = array('status' => 1, 'result' => $result);
        ajax_file_return($root);
    }


    public function insert_tecent_video(){
        if(!TECENT_VIDEO){
            admin_ajax_return(array(
                'status' => 0,
                'error'  => "模块开关未打开"
            ));
        }
        $result = array('status'=>1,'error'=>'添加回播视频成功，视频转码需要几分钟，请耐心等待');

        $user_id = intval($_REQUEST['user_id']);
        $title = trim($_REQUEST['title']);
        $video_vid = trim($_REQUEST['file_id']);

        if($user_id == 0)
        {
            admin_ajax_return(array('status'=>'0','error'=>'请输入用户id'));
        }
        if(!check_empty($title))
        {
            admin_ajax_return(array('status'=>'0','error'=>'请输入话题'));
        }
        if(!check_empty($video_vid))
        {
            admin_ajax_return(array('status'=>'0','error'=>'请添加文件'));
        }

        $is_private = false;
        $monitor_time = to_date(NOW_TIME+3600,'Y-m-d H:i:s');
        $data = $this->create_video($user_id,$title,$is_private,$monitor_time);//视频信息写入user表

        fanwe_require(APP_ROOT_PATH . 'mapi/lib/core/video_factory.php');
        $video_factory = new VideoFactory();
        $ret = $video_factory->ModifyVodInfo($video_vid, $data);
        if(! $ret['status'])
        {
            admin_ajax_return($ret);
        }

        // 新上传的视频未生成地址
        $data['is_del_vod'] = 1;
        $data['video_vid'] = $video_vid;
        $list = $GLOBALS['db']->autoExecute(DB_PREFIX . "video_history", $data, 'INSERT');
        if ($list !== false){
            save_log("回播视频上传成功",1);
        }else{
            save_log("回播视频上传失败",0);
        }
        //同步到redis
        require_once APP_ROOT_PATH."/mapi/lib/core/common.php";
        fanwe_require(APP_ROOT_PATH.'mapi/lib/redis/BaseRedisService.php');
        sync_video_to_redis($data['id'],'*',false);

        admin_ajax_return($result);
    }


    public function create_video($user_id, $title, $is_private, $monitor_time, $cate_id = '', $province = '', $city = '', $share_type = '')
    {
        $condition['title'] = $title;
        if ($cate_id == 0 && $title != '') {
            $cate_id = M('video_cate')->where($condition)->getfield('id');
            if ($cate_id) {
                $is_newtitle = 0;
            } else {
                $is_newtitle = 1;
            }
        }

        if ($is_newtitle) {
            $data_cate = array();
            $data_cate['title'] = $title;
            $data_cate['is_effect'] = 1;
            $data_cate['is_delete'] = 0;
            $data_cate['create_time'] = NOW_TIME;
            M('video_cate')->add($data_cate);
            $cate_id = M('video_cate')->where($condition)->getfield('id');
        }

        if ($province == '') {
            $province = '火星';
        }

        if ($city == '') {
            $city = '火星';
        }

        fanwe_require(APP_ROOT_PATH . 'mapi/lib/core/common.php');
        $v_id = get_max_room_id(0);//视频ID
        $data = array();
        $data['id'] = $v_id;

        //room_type 房间类型 : 1私有群（Private）,0公开群（Public）,2聊天室（ChatRoom）,3互动直播聊天室（AVChatRoom）
        $data['room_type'] = 3;

        $m_config = load_auto_cache("m_config");
        $data['virtual_number'] = intval($m_config['virtual_number']);
        $data['max_robot_num'] = intval($m_config['robot_num']);//允许添加的最大机器人数;

        $sql = "select sex,ticket,refund_ticket,user_level,fans_count,head_image,thumb_head_image from " . DB_PREFIX . "user where id = " . $user_id;
        $user = $GLOBALS['db']->getRow($sql, true, true);
        if (!$user){
            admin_ajax_return(array(
                'status' => 0,
                'error' =>'用户ID不存在'
            ));
        }

        $info = origin_image_info($user['head_image']);
        $data['head_image'] = get_spec_image($info['file_name']);
        $data['thumb_head_image'] = $user['thumb_head_image'];

        $data['sex'] = intval($user['sex']);//性别 0:未知, 1-男，2-女
        $data['video_type'] = 1;//0:腾讯云互动直播;1:腾讯云直播

        require_once(APP_ROOT_PATH . 'system/tim/TimApi.php');
        $api = createTimAPI();
        $ret = $api->group_create_group('AVChatRoom', (string)$user_id, (string)$user_id, (string)$v_id);
        if ($ret['ActionStatus'] != 'OK') {
            admin_ajax_return(array(
                'status' => 0,
                'error' => $ret['ErrorCode'] . $ret['ErrorInfo']
            ));
        }

        $data['group_id'] = $ret['GroupId'];
        $data['monitor_time'] = $monitor_time;

        $data['create_type'] = 0;// 0:APP端创建的直播;1:PC端创建的直播
        $data['push_url'] = '';//video_type=1;1:腾讯云直播推流地址
        $data['play_url'] = '';//video_type=1;1:腾讯云直播播放地址(rmtp,flv)

        $data['share_type'] = $share_type;
        $data['title'] = $title;
        $data['cate_id'] = $cate_id;
        $data['user_id'] = $user_id;
        $data['live_in'] = 0;//live_in:是否直播中 1-直播中 0-已停止;2:正在创建直播;
        $data['watch_number'] = '';//'当前观看人数';
        $data['vote_number'] = '';//'获得票数';
        $data['province'] = $province;//'省';
        $data['city'] = $city;//'城市';

        $data['create_time'] = NOW_TIME;//'创建时间';
        $data['begin_time'] = NOW_TIME;//'开始时间';
        $data['end_time'] = '';//'结束时间';
        $data['is_hot'] = 1;//'1热门; 0:非热门';
        $data['is_new'] = 1; //'1新的; 0:非新的,直播结束时把它标识为：0？'

        $data['online_status'] = 1;//主播在线状态;1:在线(默认); 0:离开

        //sort_init(初始排序权重) = (用户可提现印票：fanwe_user.ticket - fanwe_user.refund_ticket) * 保留印票权重+ 直播/回看[回看是：0; 直播：9000000000 直播,需要排在最上面 ]+ fanwe_user.user_level * 等级权重+ fanwe_user.fans_count * 当前有的关注数权重
        $sort_init = (intval($user['ticket']) - intval($user['refund_ticket'])) * floatval($m_config['ticke_weight']);

        $sort_init += intval($user['user_level']) * floatval($m_config['level_weight']);
        $sort_init += intval($user['fans_count']) * floatval($m_config['focus_weight']);

        $data['sort_init'] = 200000000 + $sort_init;
        $data['sort_num'] = $data['sort_init'];
        return $data;
    }
    //合并视频
    public  function vod_concatvideo(){
        $id = $_REQUEST['id'];
        $result['status'] = 0;
        if (isset ( $id )) {
            require_once APP_ROOT_PATH . "/mapi/lib/core/common.php";
            $condition = array ('id' => array ('in', explode ( ',', $id ) ) );
            $rel_data = M('VideoHistory')->where($condition)->findAll();
            $success_info = array();
            $fail_info = array();
            foreach($rel_data as $data) {
                $channel_id =$data['channelid'];
                $new_file_name  = $data['channelid'].'99';
                if($data['is_concatvideo']==0){
                    $result = Com_ConcatVideo($channel_id,$new_file_name);
                }else{
                    $result['status'] = 1;
                    $result['error'] = '视频已经合并，请勿重复操作';
                }
                //更新
                if(intval($result['status'])==1){
                    $sql = "update ".DB_PREFIX."video_history set is_concatvideo = 1 where id =".$data['id'];
                    $GLOBALS['db']->query($sql);
                    $success_info[] = $data['id'];
                }else{
                    $fail_info[] = $data['id'];
                }

            }
            $msg = '';
            if(!empty($success_info)){
                $success_info=implode(',',$success_info);
            }
            if(!empty($fail_info)){
                $fail_info=implode(',',$fail_info);
            }
            if(intval($result['status'])==1){
                if($result['error']==''){
                    if($success_info!=''){
                        $result['info'] = $success_info.'合并成功;';
                        $msg = $result['info'];
                    }
                }else{
                    $result['info'] = $result['error'];
                }
            }else{
                if($fail_info!=''){
                    $result['info'] = $fail_info.'合并失败';
                    $msg .= $result['info'];
                }

            }
            save_log($msg,0);
        }else {
            $result['status'] = 0;
            $result['info'] = '编号错误';
        }
        admin_ajax_return($result);
    }
    public function add_video(){
        $this->display();
    }

    //上传OSS
    function upload_oss(){

        $result = array('status'=>1,'error'=>'添加回播视频成功，视频转码需要几分钟，请耐心等待');

        $kefile_url = trim($_REQUEST['kefile_url']);
        $title = trim($_REQUEST['title']);
        $user_id = trim($_REQUEST['user_id']);

        if($user_id == 0)
        {
            admin_ajax_return(array('status'=>'0','error'=>'请输入用户id'));
        }
        if(!check_empty($title))
        {
            admin_ajax_return(array('status'=>'0','error'=>'请输入话题'));
        }
        if(!check_empty($kefile_url))
        {
            admin_ajax_return(array('status'=>'0','error'=>'文件链接不能为空'));
        }

        $is_private = false;
        $monitor_time = to_date(NOW_TIME+3600,'Y-m-d H:i:s');
        $data = $this->create_video($user_id,$title,$is_private,$monitor_time);//视频信息写入user表

        if($GLOBALS['distribution_cfg']['OSS_TYPE']&&$GLOBALS['distribution_cfg']['OSS_TYPE']=='ALI_OSS'){
            $file_url=get_spec_image($kefile_url);
        }else{
            $file_url=str_replace("./public/",file_domain()."/public/",$kefile_url);
        }

        // 新上传的视频未生成地址
        $data['is_del_vod'] = 0;
        $data['play_url'] = $file_url;
        $list = $GLOBALS['db']->autoExecute(DB_PREFIX . "video_history", $data, 'INSERT');
        if ($list !== false){
            save_log("回播视频上传成功",1);
        }else{
            save_log("回播视频上传失败",0);
        }
        //同步到redis
        require_once APP_ROOT_PATH."/mapi/lib/core/common.php";
        fanwe_require(APP_ROOT_PATH.'mapi/lib/redis/BaseRedisService.php');
        sync_video_to_redis($data['id'],'*',false);

        admin_ajax_return($result);

    }
}
?>