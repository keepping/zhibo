<?php
// +----------------------------------------------------------------------
// | Fanwe 方维众筹商业系统
// +----------------------------------------------------------------------
// | Copyright (c) 2011 http://www.fanwe.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: 甘味人生(526130@qq.com)
// +----------------------------------------------------------------------

class VideoAction extends CommonAction{
	public function __construct()
	{
		parent::__construct();
		require_once APP_ROOT_PATH."/admin/Lib/Action/VideoCommonAction.class.php";
	}
/**
 * 在线直播列表
 */
	public function online_index()
	{
        require_once APP_ROOT_PATH.'mapi/lib/redis/BaseRedisService.php';
        require_once(APP_ROOT_PATH.'mapi/lib/redis/VideoRedisService.php');
		$now=get_gmtime();
	
		if(intval($_REQUEST['cate_id'])>0)
		{
			$parameter.= "cate_id=" . intval($_REQUEST['cate_id']). "&";
			$sql_w .= "cate_id=".intval($_REQUEST['cate_id'])." and ";
			
		}
        if(intval($_REQUEST['classified_id'])>0)
        {

            $parameter.= "classified_id=" . intval($_REQUEST['classified_id']). "&";
            $sql_w .= "classified_id=".intval($_REQUEST['classified_id'])." and ";
        }
		
		if(strim($_REQUEST['nick_name'])!=''){
			//name
			$user=M("User")->where("nick_name like '%".trim($_REQUEST['nick_name'])."%' ")->findAll();
			foreach($user as $k=>$v){
				$user_arr_id[$k] =intval($v['id']);
			}			
			$parameter.= "user_id in (".implode(",",$user_arr_id).")&";
			$sql_w .= "user_id in (".implode(",",$user_arr_id).") and ";
			
		}else if(intval($_REQUEST['user_id'])>0)
		{
			$parameter.= "user_id=" . intval($_REQUEST['user_id']). "&";
			$sql_w .= "user_id=".intval($_REQUEST['user_id'])." and ";
		}
		
		$create_time_2=empty($_REQUEST['create_time_2'])?to_date($now,'Y-m-d'):strim($_REQUEST['create_time_2']);
		$create_time_2=to_timespan($create_time_2)+24*3600;
		if(trim($_REQUEST['create_time_1'])!='')
		{
			$parameter.="create_time between '". to_timespan($_REQUEST['create_time_1']) . "' and '". $create_time_2 ."'&";
			$sql_w .="create_time between '". to_timespan($_REQUEST['create_time_1']). "' and '". $create_time_2 ."' and ";
		}
		
		$parameter.= "live_in in (1,3)&";
		$sql_w .= "live_in in (1,3) and ";
		
		$model = D ();

        $sql_str = "SELECT *,".
            "watch_number+virtual_watch_number+robot_num as all_watch_number " .
            " FROM ".DB_PREFIX."video WHERE 1=1 ";

		$count_sql = "SELECT count(*)  as tpcount FROM ".DB_PREFIX."video WHERE 1=1 ";

		$sql_str .= " and ".$sql_w." 1=1";
		
		$count_sql .= " and ".$sql_w." 1=1";
		
		$voList = $this->_Sql_list($model, $sql_str, "&".$parameter,'sort_num',0,$count_sql);

        //取洪峰观看人数
        foreach ($voList as &$value){
            if (intval($value['live_in']) == 3){
                $value['max_watch'] = "回播视频不显示";
            }else{
                $sql_video = "SELECT MAX(watch_number) as max_watch FROM ".DB_PREFIX."video_monitor WHERE video_id=".$value['id'];
                $monitor_res = $GLOBALS['db']->getRow($sql_video);//洪峰观看人数
                $value['max_watch'] = $monitor_res['max_watch'];
            }
        }
        
        if(defined('OPEN_LIVE_PAY')&&OPEN_LIVE_PAY==1){
        	$this->assign ( 'is_pay_live', 1 );
        }else{
        	$this->assign ( 'is_pay_live', 0 );
        }
        
        $this->assign ( 'url_name', get_manage_url_name());

        $this->assign ( 'list', $voList );
		$cate_list = M("VideoCate")->findAll();
        $classified_list = M("VideoClassified")->findAll();
        $this->assign("classified_list",$classified_list);
		$this->assign("cate_list",$cate_list);
		$this->display ();
	}

    //修改上线状态
    public function set_demand_video_status(){
        require_once APP_ROOT_PATH."/mapi/lib/core/common.php";
        $ajax = intval($_REQUEST['ajax']);
        $id = $_REQUEST['id'];
        $result['status'] = 0;
        if (isset ( $id )) {
            $condition = array ('id' => array ('in', explode ( ',', $id ) ) );
            $rel_data = M('Video')->where($condition)->findAll();
            $success_info = array();
            $fail_info = array();
            foreach($rel_data as $data)
            {
            	if ($data['live_in'] == 3){
            		//下架
                    $m_config =  load_auto_cache("m_config");
                    if($m_config['ios_check_version'] != ''){
                        $sql = "select u.mobile from ".DB_PREFIX."video v left join ".DB_PREFIX."user u on u.id=v.user_id where v.id = ".$data['id'];
                        $mobile = $GLOBALS['db']->getOne($sql,true,true);
                        if($mobile == '13888888888' || $mobile=='13999999999'){
                            $sql = "select count(*) from ".DB_PREFIX."video v left join ".DB_PREFIX."user u on u.id=v.user_id where v.live_in=3 and (u.mobile = '13888888888' or u.mobile = '13999999999')";
                            $video_count = $GLOBALS['db']->getOne($sql,true,true);
                            if(intval($video_count)<=1){
                                $result['status'] = 0;
                                $result['info'] = '下线失败，审核期间必须有一个审核账号的历史直播！';
								admin_ajax_return($result);
                            }
                        }
                    }
            		$re = video_status($data['id'],1);
            	}
                //redis 同步结束
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
                $result['info'] = '修改成功！';
            }else {
                if($success_info){
                    save_log($success_info.l("DEMAND_VIDEO_STATUS_SUCCESS"),1);
                }
                save_log($fail_info.l("DEMAND_VIDEO_STATUS_FAILED"),0);
                $result['info'] = $fail_info.'修改失败！';
            }
        } else {
            $result['status'] = 0;
            $result['info'] = '编号错误';
        }
		admin_ajax_return($result);
    }

    //查看直播
    /*public function play_bak(){
        $id = $_REQUEST['id'];
        $condition['id'] = $id;
        $video = M('Video')->where($condition)->find();
        $this->assign("video",$video);
        $m_config =  load_auto_cache("m_config");//初始化手机端配置
        $app_id = $m_config['vodset_app_id'];
        $this->assign('app_id',$app_id);
        if($video['live_in']!=1){
            require_once APP_ROOT_PATH."/mapi/lib/core/common.php";
            fanwe_require(APP_ROOT_PATH.'mapi/lib/redis/BaseRedisService.php');
            $root = get_vodset_by_video_id($id);
            if(isset($root['vodset'])){
                $play_list = array();
                $vodset = $root['vodset'];
                foreach($vodset as $k=>$v){
                    $playSet = $v['fileSet'];
                    for($i=sizeof($playSet)-1;$i>=0;$i--){
                        $play_list[] = $playSet[$i]['fileId'];
                    }
                }
                $this->assign("playlist",implode(',',$play_list));
                $this->assign("video_url",$play_list[0]);
                $this->assign("poster",$vodset[0]['fileSet'][sizeof($vodset[0]['fileSet'])-1]['image_url']);
            }else{
                $this->assign("error",$root['error']);
            }
        }
        $this->display();
    }*/

	//查看直播（web2.1播放器）
	public function play(){
		$id = $_REQUEST['id'];
		$condition['id'] = $id;
		$video = M('Video')->where($condition)->find();

		$m_config =  load_auto_cache("m_config");//初始化手机端配置
		$app_id = $m_config['vodset_app_id'];
		$this->assign('app_id',$app_id);
		if($video['live_in']!=1){
			if($video['play_url'] !='') {
				$video['mp4_url'] = $video['play_url'];
			}else{
				require_once APP_ROOT_PATH."/mapi/lib/core/common.php";
				fanwe_require(APP_ROOT_PATH.'mapi/lib/redis/BaseRedisService.php');
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
			}
		}else{
			//直播
			$video['mp4_url'] = $video['play_mp4'];
			$video['m3u8_url'] = $video['play_hls'];
			$video['flv_url'] = $video['play_flv'];
			$video['rtmp_url'] = $video['play_rtmp'];
		}
		$this->assign("video",$video);
		$this->display();
	}

    public function video_set()
    {
        fanwe_require( APP_ROOT_PATH.'mapi/lib/redis/BaseRedisService.php');
        fanwe_require(APP_ROOT_PATH.'mapi/lib/redis/VideoRedisService.php');
        $video_id = intval($_REQUEST['id']);
        $video_redis = new VideoRedisService($video_id);
        $video = $video_redis->getRow_db($video_id,array('id','virtual_number','max_robot_num','virtual_watch_number'));
        $this->assign("video",$video);
        $this->display();
    }

    public function modify_video_set(){
        fanwe_require( APP_ROOT_PATH.'mapi/lib/redis/BaseRedisService.php');
        fanwe_require(APP_ROOT_PATH.'mapi/lib/redis/VideoRedisService.php');
        $video_id = intval($_REQUEST['id']);

        $video['virtual_number'] = intval($_REQUEST['virtual_number']);//1用户带机器人最大比例
        $video['virtual_watch_number'] = intval($_REQUEST['virtual_watch_number']);//直接设置机器人数
        $video['max_robot_num'] = intval($_REQUEST['robot_num']);//最大机器人头像数

        $robot_num = M("User")->where("is_effect=1 and is_robot = 1")->count();
        if($video['robot_num']>$robot_num)
        {
            $this->error("最大机器人头像数不能大于系统机器人头像总数".$robot_num);
        }

        $video_redis = new VideoRedisService($video_id);
        $video_redis->update_db($video_id,$video);
        M(MODULE_NAME)->where("id=".$video_id)->setField("max_robot_num",$video['max_robot_num']);
        $video_redis->update('video_virtual_watch_number',array($video_id=> $video['virtual_watch_number']));
		
        save_log(l("ADMIN_MODIFY_ACCOUNT"),1);
        $this->success(L("UPDATE_SUCCESS"));
    }

	//虚拟人数列表
	public function list_virtual(){
		$video_info=M("Video")->where("id='".intval($_REQUEST['id'])."'")->find();
		$this->assign("video_info",$video_info);
		if(intval($_REQUEST['id'])>0)
		{
			$map['room_id'] = intval($_REQUEST['id']);
		}
		if (method_exists ( $this, '_filter' )) {
			$this->_filter ( $map );
		}
		$model = D ("VideoVirtual");
		if (! empty ( $model )) {
			$this->_list ( $model, $map );
		}
		$list = $this->get("list");
		$this->display ();
	}
	//添加虚拟人数
	public function  add_virtual(){
		$video=M("Video")->where("id='".intval($_REQUEST['room_id'])."'")->find();
		$video['nick_name']=M("User")->where("id='".intval($video['user_id'])."'")->getField("nick_name");
		$this->assign("video",$video);
		$this->display ();
	}
	//编辑虚拟人数
	public function  edit_virtual(){
		$id = intval($_REQUEST ['id']);
		$condition['id'] = $id;		
		$vo = M('VideoVirtual')->where($condition)->find();
		$video=M('Video')->where("id='".intval($vo['room_id'])."'")->find();
		$video['nick_name']=M("User")->where("id='".intval($video['user_id'])."'")->getField("nick_name");
		$this->assign("video",$video);
		$this->assign("vo",$vo);
		$this->display ();
	}
	//写入
	public function  insert_virtual(){
		B('FilterString');
		$data = M('VideoVirtual')->create ();
		$this->assign("jumpUrl",u(MODULE_NAME."/add_virtual",array("room_id"=>$_REQUEST['room_id'])));

		if(intval($data['virtual_num'])==0)
		{
			$this->error("请输入虚拟人数");
		}
		if(trim($data['plan_start_time'])=='')
		{
			$this->error("请输入开始时间");
		}
		if(intval($data['plan_end_time'])==0)
		{
			$this->error("请输入结束时间");
		}
		if(intval($data['add_type'])==1&&intval($data['interval_time'])==0)
		{
			$this->error("请输入间隔时间");
		}
		$log_info =$data['room_id']."房间虚拟列表，";
		$list=M('VideoVirtual')->add($data);
		if (false !== $list) {
			save_log($log_info.L("INSERT_SUCCESS"),1);
			$this->success(L("INSERT_SUCCESS"));
		}else{
			//错误提示
			save_log($log_info.L("INSERT_FAILED"),0);
			$this->error(L("INSERT_FAILED"));
		}
	}
	//更新
	public function  update_virtual(){
		B('FilterString');
		$data = M('VideoVirtual')->create ();
		$this->assign("jumpUrl",u(MODULE_NAME."/edit_virtual",array("id"=>$data['id'])));

		if(intval($data['virtual_num'])==0)
		{
			$this->error("请输入虚拟人数");
		}
		if(trim($data['plan_start_time'])=='')
		{
			$this->error("请输入开始时间");
		}
		if(intval($data['plan_end_time'])==0)
		{
			$this->error("请输入结束时间");
		}
		if(intval($data['add_type'])==1&&intval($data['interval_time'])==0)
		{
			$this->error("请输入间隔时间");
		}
		$log_info =$data['room_id']."房间虚拟列表，";
		$list=M('VideoVirtual')->save ($data);
		if (false !== $list) {
			save_log($log_info.L("UPDATE_SUCCESS"),1);
			$this->success(L("UPDATE_SUCCESS"));
		}else{
			//错误提示
			save_log($log_info.L("UPDATE_FAILED"),0);
			$this->error(L("UPDATE_FAILED"),0,$log_info.L("UPDATE_FAILED"));
		}
	}
	
	public function del_virtual(){
		$id = $_REQUEST ['id'];
		$ajax = 1;
		if (isset ( $id )) {
				$condition = array ('id' => array ('in', explode ( ',', $id ) ) );		
				$rel_data = M("VideoVirtual")->where($condition)->findAll();
				foreach($rel_data as $data)
				{
					$room_id = $data['room_id'];
					$info[] = $data['id'];	
				}
				if($info) $info = implode(",",$info);
				$info = "房间ID".$room_id."的虚拟人数:".$info;
				$list = M("VideoVirtual")->where ( $condition )->delete();
							
				if ($list!==false) {		
					save_log($info.l("FOREVER_DELETE_SUCCESS"),1);
                    $result['info'] = "删除成功！";
                    $result['status'] = 1;
					admin_ajax_return($result);
				} else {
					save_log($info.l("FOREVER_DELETE_FAILED"),0);
                    $result['info'] = "删除失败！";
                    $result['status'] = 0;
					admin_ajax_return($result);
				}
			} else {
				$this->error (l("INVALID_OPERATION"),$ajax);
		}
	}
	
	function push_anchor(){
		$room_id = $_REQUEST ['id'];
		//创建直播推送消息
		$video = $GLOBALS['db']->getRow("select v.title,v.city,u.id as user_id,u.nick_name,u.head_image from ".DB_PREFIX."video as v left join ".DB_PREFIX."user as u on u.id=v.user_id where v.id=".$room_id);
		if($video){
			$pushdata = array(
					'user_id' =>$video['user_id'], //'主播ID',
					'nick_name' => $video['nick_name'],//'主播昵称',
					'create_time' =>NOW_TIME, //'创建时间',
					'cate_title' =>$video['title'],// '直播主题',
					'room_id' =>$room_id,// '房间ID',
					'city' =>$video['city'],// '直播城市地址',
					'head_image' =>$video['head_image'],
					'status' =>0, //'推送状态(0:未推送，1：推送中；2：已推送）'
			);
			$list = $GLOBALS['db']->autoExecute(DB_PREFIX."push_anchor", $pushdata,'INSERT');
			if ($list!==false) {		
				save_log('推送'.$video['nick_name'].'给粉丝',1);
				$this->success ('推送成功',1);
			} else {
				save_log('推送'.$video['nick_name'].'给粉丝',0);
				$this->error ('推送成功',1);
			}
		}
			
			
	}
	
	function push_anchor_all(){
		$room_id = $_REQUEST ['id'];
		//创建直播推送消息
		$video = $GLOBALS['db']->getRow("select v.title,v.city,u.id as user_id,u.nick_name,u.head_image from ".DB_PREFIX."video as v left join ".DB_PREFIX."user as u on u.id=v.user_id where  v.id=".$room_id);
		if($video){
			$pushdata = array(
					'user_id' =>$video['user_id'], //'主播ID',
					'nick_name' => $video['nick_name'],//'主播昵称',
					'create_time' =>NOW_TIME, //'创建时间',
					'cate_title' =>$video['title'],// '直播主题',
					'room_id' =>$room_id,// '房间ID',
					'city' =>$video['city'],// '直播城市地址',
					'head_image' =>$video['head_image'],
					'status' =>0, //'推送状态(0:未推送，1：推送中；2：已推送）'
					'pust_type' =>1,
			);
			$list = $GLOBALS['db']->autoExecute(DB_PREFIX."push_anchor", $pushdata,'INSERT');
			if ($list!==false) {		
				save_log('推送'.$video['nick_name'].'到全服',1);
				$this->success ('推送成功',1);
			} else {
				save_log('推送'.$video['nick_name'].'到全服',0);
				$this->error ('推送成功',1);
			}
		}	
	}

    //关闭房间
    function close_live(){
		$common = new VideoCommon();
		$data = $_REQUEST;
		$common->close_live($data);
	}

    //禁言
    public function forbid(){
        $id = $_REQUEST['id'];
        $video = M("Video")->getById($id);
        $this->assign("video",$video);
        if($video){
            $map['group_id'] = $video['group_id'];
            if(strim($_REQUEST['nick_name'])!=''){//name
                $user=M("User")->where("nick_name='".strim($_REQUEST['nick_name'])."'")->find();
                $map['user_id'] = intval($user['id']);

            }
            if (method_exists ( $this, '_filter' )) {
                $this->_filter ( $map );
            }
            $model = D ("VideoForbidSendMsg");
            if (! empty ( $model )) {
                $this->_list ( $model, $map );
            }
        }

        $this->display ();
    }
    //删除禁言
    public function del_forbid_list(){
        $ajax = intval($_REQUEST['ajax']);
        $id = $_REQUEST ['id'];
        if (isset ( $id )) {
            $condition = array ('id' => array ('in', explode ( ',', $id ) ) );
            $rel_data = M("VideoForbidSendMsg")->where($condition)->findAll();
            foreach($rel_data as $data)
            {
                $deal_id = $data['group_id'];
                $info[] = $data['user_id'];
            }
            if($info) $info = implode(",",$info);
            $info = "群组ID".$deal_id."的禁言主播:".$info;
            $list = M("VideoForbidSendMsg")->where ( $condition )->delete();

            if ($list!==false) {
                save_log($info.l("FOREVER_DELETE_SUCCESS"),1);
                $this->success (l("FOREVER_DELETE_SUCCESS"),$ajax);
            } else {
                save_log($info.l("FOREVER_DELETE_FAILED"),0);
                $this->error (l("FOREVER_DELETE_FAILED"),$ajax);
            }
        } else {
            $this->error (l("INVALID_OPERATION"),$ajax);
        }
    }
    public function set_sort()
	{
		$id = intval($_REQUEST['id']);
		$sort = intval($_REQUEST['sort']);
		$name=$this->getActionName();
		$log_info = M($name)->where("id=".$id)->find();
		//print_r($log_info['user_id']);
		if(!check_sort($sort))
		{
			$this->error(l("SORT_FAILED"),1);
		}
		M("Video")->where("id=".$id)->setField("sort",$sort);
		//print_r(M($name)->GetLastSql());
		
		require_once APP_ROOT_PATH.'mapi/lib/redis/BaseRedisService.php';
		require_once APP_ROOT_PATH.'mapi/lib/redis/VideoRedisService.php';
		
		$video_redis = new VideoRedisService($log_info['user_id']);
		//print_r($video_redis);
		//更新视频排序信息
		$return = $video_redis->update_video_sort($log_info['id'],$sort);
		save_log($log_info['title'].l("SORT_SUCCESS"),1);
		//clear_auto_cache("get_help_cache");
		$this->success(l("SORT_SUCCESS"),1);
	}
	//设置推荐
	public function set_recommend()
	{
		$id = intval($_REQUEST['id']);
		$recommend= intval($_REQUEST['recommend']);
		$name=$this->getActionName();
		//$log_info = M($name)->where("id=".$id)->find();
        $c_is_effect = M($name)->where("id=".$id)->getField("is_recommend");  //当前状态
        $n_is_effect = $c_is_effect == 0 ? 1 : 0; //需设置的状态
        $result=M($name)->where("id=".$id)->setField("is_recommend",$n_is_effect);
        save_log("房间号".$id.l("SET_RECOMMEND_".$n_is_effect),1);
        $this->ajaxReturn($n_is_effect,l("SET_BAN_".$n_is_effect),1);
		
	}
    //设备信息
    public function equipment_info(){
        $id = intval($_REQUEST['id']);
        $video = M("Video")->getById($id);
        $user  = M("User")->getById($video['user_id']);

        $sql = "SELECT column_name FROM information_schema.columns WHERE TABLE_SCHEMA = '".$GLOBALS['db_config']['DB_NAME']."' and  TABLE_NAME = 'fanwe_video_monitor' and (column_name = 'appCPURate' or column_name = 'sysCPURate' or column_name = 'sendKBps' or column_name = 'recvKBps' or column_name = 'sendLossRate' or column_name = 'fps' or column_name = 'device')";

        $columns_info = $GLOBALS['db']->getAll($sql);
        $columns_arr = array();
        foreach($columns_info as $val){
            if(!in_array($val['column_name'],$columns_arr)){
                $columns_arr[] = $val['column_name'];
            }
        }

        $info =$GLOBALS['db']->getAll("SELECT watch_number,vote_number,appCPURate,sysCPURate,sendKBps,recvKBps,sendLossRate,fps,monitor_time FROM ".DB_PREFIX."video_monitor WHERE video_id=".$id);
        $count =$GLOBALS['db']->getOne("SELECT count(monitor_time) FROM ".DB_PREFIX."video_monitor WHERE video_id=".$id);


        if($info){
            foreach($info as $k=>$v){
                $watch[$k] =$info[$k]['watch_number'];
                $vote_numberarray[$k] =$info[$k]['vote_number'];
                $appCPUarray[$k] =$info[$k]['appCPURate'];
                $sysCPUarray[$k] =$info[$k]['sysCPURate'];
                $sendKBpsarray[$k] =$info[$k]['sendKBps'];
                $recvKBpsarray[$k] =$info[$k]['recvKBps'];
                $sendLossRatearray[$k] =$info[$k]['sendLossRate'];
                $fpsarray[$k] =$info[$k]['fps'];
                $monitor_time[$k] =$info[$k]['monitor_time'];
            }
        }
        $timesql =$GLOBALS['db']->getCol("SELECT monitor_time FROM ".DB_PREFIX."video_monitor WHERE video_id=".$id,true,true);
        foreach($timesql as $k=>$v){
            $timesql[$k] = date('H:i:s',strtotime($v));
        }
        $watch_number = implode ( ',',$watch);
        $vote_number = implode ( ',',$vote_numberarray);
        $appCPURate = implode ( ',',$appCPUarray);
        $sysCPURate = implode ( ',',$sysCPUarray);
        $sendKBps = implode ( ',',$sendKBpsarray);
        $recvKBps = implode ( ',',$recvKBpsarray);
        $sendLossRate = implode ( ',',$sendLossRatearray);
        $fps = implode ( ',',$fpsarray);
        $monitor_time = implode ( ',',$timesql);

        if($count>15){
            $limit = ceil(count($appCPUarray)/20); //间隔
            $appCPU = array();
            for($i=0;$i<count($appCPUarray);$i+=$limit){
                $appCPU[] = $appCPUarray[$i];

            }
            $appCPURate = implode ( ',',$appCPU);


            for($i=0;$i<count($timesql);$i+=$limit){
                $timearray[] = $timesql[$i];
            }
            $monitor_time = implode(',', $timearray);

            $sysCPU = array();
            for($i=0;$i<count($sysCPUarray);$i+=$limit){
                $sysCPU[] = $sysCPUarray[$i];
            }
            $sysCPURate = implode ( ',',$sysCPU);

            $send = array();
            for($i=0;$i<count($sendKBpsarray);$i+=$limit){
                $send[] = $sendKBpsarray[$i];
            }
            $sendKBps = implode ( ',',$send);

            $recv = array();
            for($i=0;$i<count($recvKBpsarray);$i+=$limit){
                $recv[] = $recvKBpsarray[$i];
            }
            $recvKBps = implode ( ',',$recv);

            $sendLoss = array();
            for($i=0;$i<count($sendLossRatearray);$i+=$limit){
                $sendLoss[] = $sendLossRatearray[$i];
            }
            $sendLossRate = implode ( ',',$sendLoss);

            $FPS = array();
            for($i=0;$i<count($fpsarray);$i+=$limit){
                $FPS[] = $fpsarray[$i];
            }
            $fps = implode ( ',',$FPS);

            $watch_num = array();
            for($i=0;$i<count($watch);$i+=$limit){
                $watch_num[] = $watch[$i];
            }
            $watch_number = implode ( ',',$watch_num);

            $vote_num = array();
            for($i=0;$i<count($vote_numberarray);$i+=$limit){
                $vote_num[] = $vote_numberarray[$i];
            }
            $vote_number = implode ( ',',$vote_num);

        }


        //设备型号
        if(in_array('appCPURate',$columns_arr)){
            $device =$GLOBALS['db']->getOne("SELECT device FROM ".DB_PREFIX."video_monitor WHERE video_id=".$id,true,true);
        }
        $this->assign("monitor_time",$monitor_time);
        $this->assign("vote_number",$vote_number);
        $this->assign("watch_number",$watch_number);
        $this->assign("appCPURate",$appCPURate);
        $this->assign("sysCPURate",$sysCPURate);
        $this->assign("sendKBps",$sendKBps);
        $this->assign("recvKBps",$recvKBps);
        $this->assign("sendLossRate",$sendLossRate);
        $this->assign("fps",$fps);
        $this->assign("device",$device);
        $this->assign("user",$user);
        $this->display ();
    }

    public function set_hot_on()
    {
        $id = intval($_REQUEST['id']);
        log_result($id);
        $ajax = intval($_REQUEST['ajax']);
        $user_info = M("User")->getById($id);
        $c_is_effect = M("User")->where("id=".$id)->getField("is_hot_on");  //当前状态
        $n_is_effect = $c_is_effect == 0 ? 1 : 0; //需设置的状态
        $result=M("User")->where("id=".$id)->setField("is_hot_on",$n_is_effect);
        $user_data = array();
        if($result){
            fanwe_require(APP_ROOT_PATH.'mapi/lib/redis/BaseRedisService.php');
            fanwe_require(APP_ROOT_PATH.'mapi/lib/redis/UserRedisService.php');
            $user_redis = new UserRedisService();
            $user_data['is_hot_on'] = $n_is_effect;
            $user_redis->update_db($id, $user_data);
        }
        save_log($user_info['nick_name'].l("SET_HOT_ON_".$n_is_effect),1);
        $this->ajaxReturn($n_is_effect,l("SET_HOT_ON_".$n_is_effect),1);
    }
	//推流地址
	public function push_url()
	{
		$video_id = intval($_REQUEST['id']);
		$push_url_info =$GLOBALS['db']->getRow("SELECT id,push_rtmp,play_flv,play_rtmp,play_mp4,play_hls FROM ".DB_PREFIX."video WHERE id=".$video_id,true,true);
		$this->assign("push_url",$push_url_info);
		$this->display();
	}
    public function add()
    {
        $send_user = M("User")->where("is_admin=1 ")->findAll();

        $this->assign ( 'send_user', $send_user );
        $this->display();
    }
    public function insert(){
        fanwe_require( APP_ROOT_PATH.'mapi/lib/redis/BaseRedisService.php');
        fanwe_require(APP_ROOT_PATH.'mapi/lib/redis/VideoRedisService.php');
        $video_redis = new VideoRedisService();
        $user_id = intval($_REQUEST['send_user_id']);
        $prop_id = $GLOBALS['db']->getOne("select id from ".DB_PREFIX."prop where name='红包'");
        if(!$prop_id){
            $this->error('道具列表中不存在红包礼物，请添加后再发送');
        }
        $num = intval($_REQUEST['num']);//礼物数量
        $is_plus =0;
        $video_id = intval($_REQUEST['room_id']);;//直播ID 也是room_id
        $prop = load_auto_cache("prop_id",array('id'=>$prop_id));
        $prop['diamonds'] =intval($_REQUEST['diamonds']);
        $prop['score'] =intval($_REQUEST['diamonds']);
        $prop['ticket'] =0;
        $prop['robot_diamonds'] =0;


        if ($num <= 0) $num = 1;
        $total = $num * $prop['diamonds'];


        $total_ticket = intval($num * $prop['ticket']);
        $robot_diamonds = intval($prop['robot_diamonds']);


        $send_diamonds=$GLOBALS['db']->getone("select diamonds from ".DB_PREFIX."user where id =".$user_id);

        if($send_diamonds<$prop['diamonds']){
            $this->error('发送人钻石数量必须大于红包大小');
        }
        if($_REQUEST['send_type']==2){
            $all = array_unique(preg_split("/[\s]+/", $_REQUEST['room_id']));
            $al = implode(",", $all);
            $video = $GLOBALS['db']->getCol("select id from " . DB_PREFIX ."video where live_in = 1 or live_in =3");
            $video_string = implode(",",$video);
            for ($i=0;$i<count($all);$i++){
                if(strpos($video_string,$all[$i])!== false){

                }else{
                    $this->error('您输入的房间号不存在,请确认后重新输入');
                }
            }
        }else {
            $all = $GLOBALS['db']->getCol("select id from " . DB_PREFIX ."video where live_in = 1 or live_in =3");
            $al = implode(",", $all);
            if (empty($all)) {
                $this->error('当前没有正在直播的房间,请重新确认');
            }
        }
        if($prop['diamonds']<count($all)){
            $this->error('红包大小不能小于直播间数量');
        }
        $watch_number= $GLOBALS['db']->getone("select sum(watch_number) from ".DB_PREFIX."video where id in($al)");
        $watch_number= $watch_number+count($all);

        foreach ($all as $k => $v) {
            if($_REQUEST['send_type']==2){
                $group_id = M("Video")->where("id=" . $v)->getField("group_id");
                $video_id =M("Video")->where("id=" . $v)->getField("id");
                $robot_num = M("Video")->where("id=" . $v)->getField("watch_number");
            }else{
                $group_id = M("Video")->where("id=" . $v)->getField("group_id");
                $video_id =M("Video")->where("id=" . $v)->getField("id");
                $robot_num = M("Video")->where("id=" . $v)->getField("watch_number");

            }




            $video = $video_redis->getRow_db($video_id,array('id','user_id','group_id','prop_table','room_type'));
            $podcast_id = intval($video['user_id']);//送给谁，有群组ID(group_id)，除了红包外其它的都是送给：群主
            $room_type = intval($video['room_type']);//直播间类型 房间类型 : 1私有群（Private）,0公开群（Public）,2聊天室（ChatRoom）,3互动直播聊天室（AVChatRoom）
            $total_diamonds =round(($robot_num+1)/$watch_number*$total);
            if(($send_diamonds-$total_diamonds)<$total_diamonds){
                $total_diamonds = intval(($robot_num+1)/$watch_number*$total);
            }


            if($video['room_type'] == 1){
                $total_score =0;
            }else{
                $total_score =$total_diamonds;
            }


            $from='app';//判断发送来源 pc或者app
            fanwe_require (APP_ROOT_PATH.'mapi/lib/deal.action.php');
            $ba=new  dealModule();
            $ba->pack_prop($video['prop_table'],$video_redis,$total_diamonds,$total_score,$total_ticket,$num,$prop,$is_plus,$video_id,$user_id,$prop_id,$podcast_id,$group_id,$room_type,$from,$robot_diamonds);
        }


//        elseif($_REQUEST['send_type']==0) {
//
//            $all= $GLOBALS['db']->getCol("select group_id from ".DB_PREFIX."video where live_in=1");
//            $al = implode(",", $all);
//            if(empty($all)){
//                $this->error('当前没有正在直播的房间,请重新确认');
//            }else{
//                $watch_number= $GLOBALS['db']->getone("select sum(robot_num) from ".DB_PREFIX."video where id in($al)");
//
//                if($watch_number){
//                    foreach ($all as $k => $v) {
//                        $group_id = M("Video")->where("group_id=" . $v)->getField("group_id");
//                        $video_id =$group_id;
//                        $robot_num = M("Video")->where("group_id=" . $group_id)->getField("robot_num");
//                        $total_diamonds = ceil($robot_num/$watch_number*$total);
//                        log_result($total_diamonds);
//                        fanwe_require(APP_ROOT_PATH . 'mapi/lib/redis/VideoRedisService.php');
//                        $video_redis = new VideoRedisService();
//                        $video = $video_redis->getRow_db($video_id, array('id', 'user_id', 'group_id', 'prop_table'));
//                        $podcast_id = intval($video['user_id']);//送给谁，有群组ID(group_id)，除了红包外其它的都是送给：群主
//                        $room_type = intval($video['room_type']);//直播间类型 房间类型 : 1私有群（Private）,0公开群（Public）,2聊天室（ChatRoom）,3互动直播聊天室（AVChatRoom）
//                        $from = strim($_REQUEST['from']);//判断发送来源 pc或者app
//                        $pInTrans = $GLOBALS['db']->StartTrans();
//                        fanwe_require(APP_ROOT_PATH . 'mapi/lib/deal.action.php');
//                        $ba = new  dealModule();
//                        $ba->pack_prop($pInTrans, $total_diamonds, $total_score, $total_ticket, $num, $prop, $is_plus, $video_id,$user_id, $prop_id, $podcast_id, $group_id, $room_type, $from, $robot_diamonds);
//
//                    }
//                }
//            }
//
//
//
//        }


        //成功提示
        save_log($total_diamonds.L("INSERT_SUCCESS"),1);
        $this->success(L("INSERT_SUCCESS"));

    }
    //手动置顶
    public function stick(){
        $room_id = $_REQUEST['id'];
        $sort_init =$GLOBALS['db']->getRow("select sort_init,stick from ".DB_PREFIX."video where id =".$room_id);
        $where = 'id='.$room_id;
        require_once APP_ROOT_PATH.'mapi/lib/redis/BaseRedisService.php';
        require_once APP_ROOT_PATH.'mapi/lib/redis/VideoRedisService.php';
        $video_redis = new VideoRedisService();
        $data['stick'] = 1;
        $info = '';
        if($sort_init['stick']==1) {
            $data['stick'] = 0;
            $info = '取消';
        }

        $list = $GLOBALS['db']->autoExecute(DB_PREFIX."video",$data,'UPDATE',$where);
        if ($list!==false) {
            $video_redis->update_db($room_id, $data);
            $this->success ($info.'置顶成功',1);

        }else{
            $this->error ($info.'置顶失败',1);
        }
    }
}
?>