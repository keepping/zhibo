<?php
// +----------------------------------------------------------------------
// | Fanwe 方维p2p借贷系统
// +----------------------------------------------------------------------
// | Copyright (c) 2011 http://www.fanwe.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: 云淡风轻(88522820@qq.com)
// +----------------------------------------------------------------------

class videoModule  extends baseModule
{

	/**
	 * 当前房间用户列表（包括机器人，但不包括虚拟人数）
	 */
	public function viewer(){
 		$root = array();
		$group_id = strim($_REQUEST['group_id']);//聊天群id
		
		$video_id = intval($_REQUEST['room_id']);//房间号ID
		
		$page = intval($_REQUEST['p']);//取第几页数据
		$root = load_auto_cache("video_viewer",array('group_id'=>$group_id,'video_id'=>$video_id, 'page'=>$page));
		/*
		fanwe_require(APP_ROOT_PATH.'mapi/lib/redis/VideoViewerRedisService.php');
		$video_viewer_redis = new VideoViewerRedisService();
		$root = $video_viewer_redis->get_viewer_list2($video_id,$page,100);
		*/
		
		ajax_return($root);
	}







	/**
	 * 直播结束
	 */
	public function end_video(){
		$root = array();

		//$GLOBALS['user_info']['id'] = 1;

		if(!$GLOBALS['user_info']){
			$root['error'] = "用户未登陆,请先登陆.";
			$root['status'] = 0;
			$root['user_login_status'] = 0;//有这个参数： user_login_status = 0 时，表示服务端未登陆、要求登陆，操作
		}else{
			$user_id = intval($GLOBALS['user_info']['id']);
			$room_id = strim($_REQUEST['room_id']);//房间号id
			$video_vid = strim($_REQUEST['video_url']);//视频地址
			if ($video_vid == 'null') $video_vid = '';
			//$root['error'] = $video_vid;
			$sql="";
			if (OPEN_PAI_MODULE==1) {
				$sql = "select id,user_id,max_watch_number,virtual_watch_number,robot_num,vote_number,group_id,room_type,begin_time,end_time,channelid,cate_id,pai_id from ".DB_PREFIX."video where id = ".$room_id." and user_id = ".$user_id;

			}else {
				$sql = "select id,user_id,max_watch_number,virtual_watch_number,robot_num,vote_number,group_id,room_type,begin_time,end_time,channelid,cate_id from ".DB_PREFIX."video where id = ".$room_id." and user_id = ".$user_id;

			}
			$video = $GLOBALS['db']->getRow($sql,true,true);

			//只有主播自己能结束
			if ($user_id == $video['user_id']){
				do_end_video($video,$video_vid,0,$video['cate_id']);

				fanwe_require(APP_ROOT_PATH . 'mapi/lib/redis/BaseRedisService.php');
				fanwe_require(APP_ROOT_PATH . 'mapi/lib/redis/VideoRedisService.php');
				$video_redis = new VideoRedisService();

				$root['watch_number'] = intval($video['max_watch_number']);
				$root['vote_number'] = intval($video['vote_number'])+intval($video_redis->getOne_db($video['id'],'game_vote_number'));//获得印票
				//


				/*
				 if ($video_data['live_in'] == 1){
				$video_data['watch_number'] = $video_redis->get_video_watch_num($room_id);
				}
				*/
				//redis_do_end_video($video_redis,$video_data,$video_vid,0,$video_data['cate_id']);


				//$root['viewer_num'] = $root['watch_number'] + $root['virtual_watch_number'];
				//总观看人数
				//			$root['watch_number'] = $video['watch_number'] + $video['robot_num'] + $video['virtual_watch_number'];//观看人数
				//$root['watch_number'] = $video_data['max_watch_number'] ;


				//$root['vote_number'] = $video_redis->get_video_ticket_num($room_id);
				//$root['room_type'] = $video['room_type'];//房间类型 : 1私有群（Private）,0公开群（Public）,2聊天室（ChatRoom）,3互动直播聊天室（AVChatRoom）

				$time_len =  NOW_TIME -  $video['begin_time'];//私有聊天或小于5分钟的视频，不保存
				$m_config =  load_auto_cache("m_config");
				$short_video_time = $m_config['short_video_time']?$m_config['short_video_time']:300;

				if ($video['room_type'] == 1 || $time_len < $short_video_time){

					$root['has_delvideo'] = 0;//1：显示删除视频按钮; 0:不显示；

				}else {
					$root['has_delvideo'] = 1;//1：显示删除视频按钮; 0:不显示；
				}

				//$root['has_delvideo'] = 1;
				/*if (OPEN_PAI_MODULE==1&&intval($video['pai_id'])>0) {
					//关闭竞拍
					$data=array();
					$data['podcast_id']=$video['user_id'];
					$data['pai_id']=$video['pai_id'];
					$data['video_id']=$room_id;
					$rs = FanweServiceCall("pai_podcast","stop_pai",$data);
				}*/
			}
			rm_auto_cache("select_video");
			$root['status'] = 1;
		}

		ajax_return($root);
	}



	/**
	 * 删除录制的视频
	 */
	public function del_video(){
		$root = array();

		//$GLOBALS['user_info']['id'] = 1;

		if(!$GLOBALS['user_info']){
			$root['error'] = "用户未登陆,请先登陆.";
			$root['status'] = 0;
			$root['user_login_status'] = 0;//有这个参数： user_login_status = 0 时，表示服务端未登陆、要求登陆，操作
		}else{
			$user_id = intval($GLOBALS['user_info']['id']);
			$room_id = strim($_REQUEST['room_id']);//房间号id

			$sql = "update ".DB_PREFIX."video set is_delete = 1 where id = ".$room_id." and user_id = ".$user_id;
			$GLOBALS['db']->query($sql);
			if($GLOBALS['db']->affected_rows()){
				$root['status'] = 1;

				sync_video_to_redis($room_id,'is_delete',false);
			}else{
				$root['status'] = 0;
			}

		}
		//$root['status'] = 1;
		ajax_return($root);
	}

	/**
	 * 删除回看录制的视频
	 */
	public function del_video_history(){
		$root = array();

		//$GLOBALS['user_info']['id'] = 1;

		if(!$GLOBALS['user_info']){
			$root['error'] = "用户未登陆,请先登陆.";
			$root['status'] = 0;
			$root['user_login_status'] = 0;//有这个参数： user_login_status = 0 时，表示服务端未登陆、要求登陆，操作
		}else{
			$user_id = intval($GLOBALS['user_info']['id']);
			$room_id = strim($_REQUEST['room_id']);//房间号id

			$sql = "update ".DB_PREFIX."video_history set is_delete = 1 where live_in = 0 and id = ".$room_id." and user_id = ".$user_id;
			$GLOBALS['db']->query($sql);
			if($GLOBALS['db']->affected_rows()){
				$sql = "select count(*) as num from ".DB_PREFIX."video_history where is_delete = 0 and is_del_vod = 0 and user_id = '".$user_id."'";
				$video_count = $GLOBALS['db']->getOne($sql);

				$sql = "update ".DB_PREFIX."user set video_count = ".$video_count." where id = ".$user_id;
				$GLOBALS['db']->query($sql);

                fanwe_require(APP_ROOT_PATH.'mapi/lib/redis/BaseRedisService.php');
                fanwe_require(APP_ROOT_PATH.'mapi/lib/redis/UserRedisService.php');
                $user_redis = new UserRedisService();
                $user_data = array();
                $user_data['video_count'] = $video_count;
                $user_redis->update_db($user_id, $user_data);
				/*
				$sql = "select destroy_group_status,group_id from ".DB_PREFIX."video where id = ".$room_id;
				//$video = $video_redis->getRow_db($video_id);
				$video_data = $GLOBALS['db']->getRow($sql);

				//如果是删除状态,则解散群组
				if ($video_data['destroy_group_status'] == 1){
					fanwe_require(APP_ROOT_PATH.'mapi/lib/redis/VideoRedisService.php');
					$video_redis = new VideoRedisService();

					if ($video_data['group_id'] != ''){
						fanwe_require(APP_ROOT_PATH.'system/tim/TimApi.php');
						$api = createTimAPI();
						$ret = $api->group_destroy_group($video_data['group_id']);
						$destroy_group_status = $ret['ErrorCode'];

						$video_redis->del_video_group_db($video_data['group_id']);//只有在：解散 聊天组时，才删除
					}else{
						$destroy_group_status = 0;
					}

					$sql = "update ".DB_PREFIX."video_history set destroy_group_status = ".$destroy_group_status." where id = ".$room_id." and user_id = ".$user_id;
					$GLOBALS['db']->query($sql);

					$data = array();
					$data['destroy_group_status'] = $destroy_group_status;
					$video_redis->update_db($room_id, $data);
				}
				*/
				$root['status'] = 1;
				$root['error'] = "已删除";
			}else{
				$root['status'] = 0;
				$root['error'] = "只能删除非上架的视频";
			}


		}
		//$root['status'] = 1;
		ajax_return($root);
	}
	/**
	 * 主播心跳监听，每30秒监听一次;监听数据：时间点，印票数，房间人数
	 */
	public function monitor(){

		$root = array();
		$root['status'] = 1;

		if(!$GLOBALS['user_info']){
			$root['error'] = "用户未登陆,请先登陆.";
			$root['status'] = 0;
			$root['user_login_status'] = 0;//有这个参数： user_login_status = 0 时，表示服务端未登陆、要求登陆，操作
		}else{

			$user_id = intval($GLOBALS['user_info']['id']);//用户ID
			$room_id = intval($_REQUEST['room_id']);//直播ID 也是room_id
			fanwe_require(APP_ROOT_PATH.'mapi/lib/redis/VideoRedisService.php');
			$video_redis = new VideoRedisService();
			$fields = array('vote_number','watch_number','is_live_pay','live_pay_time','live_pay_type','live_fee','live_is_mention','robot_num','virtual_watch_number','group_id');
			$video_number = $video_redis->getRow_db($room_id,$fields);
			$vote_number = intval($video_number['vote_number']);//获得印票数
			$watch_number = intval($video_number['watch_number']);//当前观看人数
			$group_id  = strim($video_number['group_id']);//聊天组ID
			$live_pay_time = $video_number['live_pay_time'];//开始收费时间
			$live_pay_type = intval($video_number['live_pay_type']);//收费模式
			$live_fee = intval($video_number['live_fee']);//付费直播 收费多少
			$live_is_mention = intval($video_number['live_is_mention']);//收费模式 是否已经提档过

			if(intval($_REQUEST['watch_number']) > 0){
				//客户端有返回：当前观看人数 则取客户端返回的
				$watch_number = intval($_REQUEST['watch_number']);//当前观看人数
			}
            $lianmai_num = intval($_REQUEST['lianmai_num']);//当前连麦数量

            $live_quality = json_decode($_REQUEST['live_quality'],true);
            $appCPURate = intval($live_quality['appCPURate']);//appcpu占用率
            $sysCPURate = intval($live_quality['sysCPURate']);//系统cpu占用率
            $sendKBps = intval($live_quality['sendKBps']);//上行速率
            $recvKBps = intval($live_quality['recvKBps']);//下行速率
            $sendLossRate = intval($live_quality['sendLossRate']);//上行丢包率
            $fps = intval($live_quality['fps']);//视频帧率fps
            $device = strim($live_quality['device']);//设备系统

			$monitor_time = to_date(NOW_TIME,'Y-m-d H:i:s');

			//00:00; 05:00; 10:00; 15:00; ....; 55:00;
			$i_time = to_date(NOW_TIME,'i');
			$s_time = to_date(NOW_TIME,'s');

			if ($i_time >=55 && $s_time > 0){
				//放在下一小时的：00:00 时段
				$statistic_time = to_date(NOW_TIME + 330,'Y-m-d H:00:00');
			}else{

				if ($i_time >=50){
					$i_time2 = '55';
				}else if ($i_time >=45){
					$i_time2 = '50';
				}else if ($i_time >=40){
					$i_time2 = '45';
				}else if ($i_time >=35){
					$i_time2 = '40';
				}else if ($i_time >=30){
					$i_time2 = '35';
				}else if ($i_time >=25){
					$i_time2 = '30';
				}else if ($i_time >=20){
					$i_time2 = '25';
				}else if ($i_time >=15){
					$i_time2 = '20';
				}else if ($i_time >=10){
					$i_time2 = '15';
				}else if ($i_time >=5){
					$i_time2 = '10';
				}else{
					$i_time2 = '05';
				}

				$statistic_time = to_date(NOW_TIME,'Y-m-d H:').$i_time2.':00';
			}

			//更新最后心跳时间点
			$sql = "update ".DB_PREFIX."video set monitor_time = '".$monitor_time."' where live_in =1 and id = ".$room_id." and user_id = ".$user_id;
			$GLOBALS['db']->query($sql);
			if($GLOBALS['db']->affected_rows()){

				$video_monitor = array();
				$video_monitor['user_id'] = $user_id;
				$video_monitor['video_id'] = $room_id;
				$video_monitor['vote_number'] = $vote_number;
				$video_monitor['watch_number'] = $watch_number;
				$video_monitor['lianmai_num'] = $lianmai_num;
                $video_monitor['monitor_time'] = $monitor_time;
                $video_monitor['statistic_time'] = $statistic_time;
                $video_monitor['appCPURate'] = $appCPURate;
                $video_monitor['sysCPURate'] = $sysCPURate;
                $video_monitor['sendKBps'] = $sendKBps;
                $video_monitor['recvKBps'] = $recvKBps;
                $video_monitor['sendLossRate'] = $sendLossRate;
                $video_monitor['fps'] = $fps;
                $video_monitor['device'] = $device;
				$GLOBALS['db']->autoExecute(DB_PREFIX."video_monitor", $video_monitor,"INSERT");
				
				//在主播心跳接口monitor,做一次：连麦用户的IM通知更新，确保不漏单
				$root['push_lianmai'] = $this->push_lianmai($room_id);

				if($lianmai_num>0){
					$this->mix_stream2($room_id,0);
				}
			}
		}
		$live_pay= $GLOBALS['db']->getRow("SELECT id,class FROM ".DB_PREFIX."plugin WHERE is_effect=1 and type = 1 ");
		if((defined('OPEN_LIVE_PAY')&&OPEN_LIVE_PAY==1)&&$live_pay){
			$m_config =  load_auto_cache("m_config");//初始化手机端配置
			$root['live']['allow_live_pay'] = 0;
			$root['live']['allow_mention'] = 0;
			$root['live']['live_fee'] = $live_fee;
			$root['live']['live_is_mention'] = $live_is_mention;
			$live_pay_type = intval($video_number['live_pay_type']);
			
			if(intval($m_config['live_pay_num'])<=intval($video_number['watch_number']+$video_number['robot_num']+$video_number['virtual_watch_number'])){				
				if($live_pay_time!=''&&$live_fee>0){
					$root['live']['allow_live_pay'] = 2;//已经付费过
				}else{
					$root['live']['allow_live_pay'] = 1;//可以付费
				}
			}
			if((intval($m_config['live_pay_rule']*60) <= intval(NOW_TIME-$live_pay_time))&&$live_pay_time>0&&$live_pay_type==0){
				if($live_is_mention){
					$root['live']['allow_mention'] = 2;//已经提档
				}else{
					$root['live']['allow_mention'] = 1;//可以提档
				}
			}
			//直播间主播获得的印票
			$sql = "select ticket from ".DB_PREFIX."user  where id = ".$user_id;
			$users = $GLOBALS['db']->getRow($sql,true,true);
			$root['live']['ticket'] = intval($users['ticket']);
		
			//默认价格
			$root['live']['live_fee'] = intval($root['live']['live_fee'])>0?intval($root['live']['live_fee']):1;
			$live_time = $live_pay_time-NOW_TIME;
			$live_time = $live_time>0?intval($live_time):0;
			//实际付费人数
			if($live_pay_type==0){
				if($live_time==0){
					$times = get_gmtime()-120;
					$sql ="select id from ".DB_PREFIX."live_pay_log where video_id =".$room_id." and pay_time_end>=".$times." group by from_user_id ";
					$live_list = $GLOBALS['db']->getAll($sql,true,true);
				}else{
					$live_list = array();
				}
			}else{
				$live_list = $GLOBALS['db']->getAll("select id from ".DB_PREFIX."live_pay_log where video_id =".$room_id." group by from_user_id " ,true,true);
			}
			$live_viewer = count($live_list);
			$root['live']['live_viewer'] = intval($live_viewer);
			//收费类型 0是按时付费、1按场付费、2 普通付费
			if($live_pay_type==0&&intval($video_number['is_live_pay'])){
				$root['live']['live_pay_type'] = 0;
			}else if($live_pay_type==1&&intval($video_number['is_live_pay'])){
				$root['live']['live_pay_type'] = 1;
			}else{
				$root['live']['live_pay_type'] = 2;
			}		
		}
		/*推送内容大小被限制,暂时不能用
		//推送观众列表
		//$dev_type = strim($_REQUEST['sdk_type']);
		//if($dev_type=='android'){
			$ext = array();
		    $ext['type'] = 42; //42 通用数据格式
		    $ext['data_type'] = 0 ;//直播间观众列表
			//消息发送者
	        //$sender = array();
	        //$ext['sender'] = $sender;
	        //观众列表
	        //$list = load_auto_cache("video_viewer",array('group_id'=>$group_id,'page'=>0));
	        //$ext['list'] = $list; //礼物id
	        //观众列表
	        fanwe_require(APP_ROOT_PATH.'mapi/lib/redis/VideoViewerRedisService.php');
	        $video_viewer_redis = new VideoViewerRedisService();
	        $viewer = $video_viewer_redis->get_viewer_list2($room_id,1,50);
	        $ext['data'] =  $viewer;
	        //$ext['group_id'] =  $group_id;
	        
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
	        fanwe_require(APP_ROOT_PATH.'system/tim/TimApi.php');
	        $api = createTimAPI();
	        //$api->group_send_group_system_notification();
	        $ret = $api->group_send_group_msg2($user_id, $group_id, $msg_content);

	        if ($ret['ActionStatus'] == 'FAIL' && $ret['ErrorCode'] == 10002){
	            //10002 系统错误，请再次尝试或联系技术客服。
	            $ret = $api->group_send_group_msg2($user_id, $group_id, $msg_content);
	        }
		//}
	        $root['group_id'] = $group_id;
	        $root['ret'] = $ret;
	        $root['msg_content'] = $msg_content;
	    */    
		
		$root['ret'] = push_viewer($room_id,$group_id,50);
		
		ajax_return($root);
	}


/**
	 * 获得一个正在直播的房间（停用）
	 */
	function get_video(){
		$root = array();
		
		$root['error'] = "请升级后,观看视频【我的==》设置==》检查版本】";
		$root['status'] = 0;
		ajax_return($root);

		//$GLOBALS['user_info']['id'] = 278;
		if(!$GLOBALS['user_info']){
			$root['error'] = "用户未登陆,请先登陆.";
			$root['status'] = 0;
			$root['user_login_status'] = 0;//有这个参数： user_login_status = 0 时，表示服务端未登陆、要求登陆，操作
		}else{
			//客服端手机类型dev_type=android;dev_type=ios
			$dev_type = strim($_REQUEST['sdk_type']);
			if (($dev_type == 'ios' || $dev_type == 'android')){
	//				$api_log = array();
	//				$api_log['ip'] = CLIENT_IP;
	//				$api_log['ctl_act'] = $GLOBALS['user_info']['id'];
	//				$api_log['parma'] = print_r($_REQUEST,1);
	//				$GLOBALS['db']->autoExecute(DB_PREFIX."api_log", $api_log,'INSERT');

				$room_id = intval($_REQUEST['room_id']);//房间号id; 如果有的话，则返回当前房间信息;
				$user_id = intval($GLOBALS['user_info']['id']);//用户ID
				$type = intval($_REQUEST['type']);//type: 0:热门;1:最新;2:关注 [随机返回一个type类型下的直播]

				$root = get_video_info($room_id, $user_id, $type, $_REQUEST);
			}else{
				//$root[]=
			}
		}
		ajax_return($root);
	}
	/**
	 * 获得一个正在直播的房间
	 */
	function get_video2(){
		$root = array();

		//$GLOBALS['user_info']['id'] = 278;
		if(!$GLOBALS['user_info']){
			$root['error'] = "用户未登陆,请先登陆.";
			$root['status'] = 0;
			$root['user_login_status'] = 0;//有这个参数： user_login_status = 0 时，表示服务端未登陆、要求登陆，操作
		}else{
			//客服端手机类型dev_type=android;dev_type=ios
			$dev_type = strim($_REQUEST['sdk_type']);  
			if (($dev_type == 'ios' || $dev_type == 'android')){
				$room_id = intval($_REQUEST['room_id']);//房间号id; 如果有的话，则返回当前房间信息;
				$user_id = intval($GLOBALS['user_info']['id']);//用户ID
				$type = intval($_REQUEST['type']);//type: 0:热门;1:最新;2:关注 [随机返回一个type类型下的直播]

				//强制升级不升级无法查看直播
				$status = 1;
				$m_config =  load_auto_cache("m_config");//初始化手机端配置
				if(intval($m_config['forced_upgrade'])){
					$root =$this->compel_upgrade($m_config);
					$status = $root['status'];
				}
				if($status==1){
					$root = get_video_info2($room_id, $user_id, $type, $_REQUEST);
					
					if ($root['live_in'] == 1 && $root['user_id'] == $user_id){
						//主播重新进入自己的房间后，重新推一下：连麦观众消息
						$this->push_lianmai($room_id);
					}
				}

			}
		}
		ajax_return($root);
	}


	/**
	 * 客户端，创建房间状态 回调
	 * room_id:房间号id
	 * status:1:成功,其它用户可以开始加入;1:失败
	 */
	public function video_cstatus(){
		$root = array();

		//$GLOBALS['user_info']['id'] = 1;

		if(!$GLOBALS['user_info']){
			$root['error'] = "用户未登陆,请先登陆.";
			$root['status'] = 0;
			$root['user_login_status'] = 0;//有这个参数： user_login_status = 0 时，表示服务端未登陆、要求登陆，操作
		}else{
			$user_id = intval($GLOBALS['user_info']['id']);
			$room_id = strim($_REQUEST['room_id']);//房间号id
			$status = intval($_REQUEST['status']);//status: 1:成功,其它用户可以开始加入;0:创建失败; 2:主播离开; 3:主播回来

			//当$status=2,3时，下面3个参数可以不用传;
			$channelid = strim($_REQUEST['channelid']);//旁路直播,频道ID
			$play_rtmp = strim($_REQUEST['play_rtmp']);//旁路直播,播放地址
			$play_flv = strim($_REQUEST['play_flv']);//旁路直播,播放地址
			$play_hls = strim($_REQUEST['play_hls']);//旁路直播,播放地址
            //在返回的hls地址中，加入/live/这一层
            //@author　jiangzuru
            $s1 = $play_hls;
            if ($s1 && strpos($s1, "com/live/") === false) {
	            $pos1 = strpos($s1, "com/");
	            $play_hls = substr_replace($s1, "live/", $pos1+4,0);
	        }

			$group_id = strim($_REQUEST['group_id']);//group_id; Private,Public,ChatRoom,AVChatRoom
			//$room_type = intval($_REQUEST['room_type']);//房间类型 : 1私有群（Private）,0公开群（Public）,2聊天室（ChatRoom）,3互动直播聊天室（AVChatRoom）
			fanwe_require(APP_ROOT_PATH.'mapi/lib/redis/VideoRedisService.php');
			$video_redis = new VideoRedisService();


			if ($status == 2 || $status ==3){
				//online_status 主播在线状态;1:在线(默认); 0:离开
				if ($status == 2){
					$sql = "update ".DB_PREFIX."video set online_status = 0 where id = ".$room_id." and user_id = ".$user_id;
				}else{
					$sql = "update ".DB_PREFIX."video set online_status = 1 where id = ".$room_id." and user_id = ".$user_id;
				}

				$GLOBALS['db']->query($sql);
				if($GLOBALS['db']->affected_rows()){
					$root['status'] = 1;

					sync_video_to_redis($room_id,'online_status',false);

				}else{
					$root['status'] = 0;
				}
			}else{
				$set_fields = "";
				if ($group_id != ''){
					$set_fields .= ",group_id='".$group_id."'";
				}

				if ($channelid != ''){
					$set_fields .= ",channelid = '".$channelid."'";
				}

				if ($play_rtmp != ''){
					$set_fields .= ",play_rtmp = '".$play_rtmp."'";
				}

				if ($play_flv != ''){
					$set_fields .= ",play_flv = '".$play_flv."'";
				}

				if ($play_hls != ''){
					$set_fields .= ",play_hls = '".$play_hls."'";
				}

				$sql = "update ".DB_PREFIX."video set live_in = 1 ".$set_fields." where live_in =2 and id = ".$room_id." and user_id = ".$user_id;
				$GLOBALS['db']->query($sql);

				//live_in:是否直播中 1-直播中 0-已停止;2:正在创建直播;
				if($GLOBALS['db']->affected_rows()){

					$sql = "select user_id,room_type,title,city,cate_id from ".DB_PREFIX."video where id = ".$room_id;
					$video = $GLOBALS['db']->getRow($sql);

					$video_redis->video_online($room_id,$group_id);
					//将mysql数据,同步一份到redis中
					sync_video_to_redis($room_id,'*',false);

					if ($video['cate_id'] > 0){
						$sql = "update ".DB_PREFIX."video_cate a set a.num = (select count(*) from ".DB_PREFIX."video b where b.cate_id = a.id and b.live_in in (1,3)";
                        $m_config =  load_auto_cache("m_config");//初始化手机端配置
                        if((defined('OPEN_ROOM_HIDE')&&OPEN_ROOM_HIDE==1)&&intval($m_config['open_room_hide'])==1){
                            $sql.= " and b.province <> '火星' and b.province <>''";
                        }
                        $sql.=") where a.id = ".$video['cate_id'];
						$GLOBALS['db']->query($sql);
					}

					//
					if ($video['room_type'] == 3){
						crontab_robot($room_id);
					}

					fanwe_require(APP_ROOT_PATH.'mapi/lib/redis/UserRedisService.php');
					$user_redis = new UserRedisService();
					$user_data = $user_redis->getRow_db($user_id,array('id','nick_name','head_image'));
					$pushdata = array(
							'user_id' =>$user_id, //'主播ID',
							'nick_name' => $user_data['nick_name'],//'主播昵称',
							'create_time' =>NOW_TIME, //'创建时间',
							'cate_title' =>$video['title'],// '直播主题',
							'room_id' =>$room_id,// '房间ID',
							'city' =>$video['city'],// '直播城市地址',
							'head_image' =>get_spec_image($user_data['head_image']),
							'status' =>0,//'推送状态(0:未推送，1：推送中；2：已推送）'
					);
					$m_config = load_auto_cache("m_config");
					if(intval($m_config['service_push'])){
						$pushdata['pust_type'] =1; //'推送状态(0:粉丝推送，1：全服推送）';
					}else{
						$pushdata['pust_type'] =0; //'推送状态(0:粉丝推送，1：全服推送）';
					}

					$GLOBALS['db']->autoExecute(DB_PREFIX."push_anchor", $pushdata,'INSERT');

					$root['status'] = 1;
				}else{
					$sql = "update ".DB_PREFIX."video set live_in = 0".$set_fields.", end_time = ".NOW_TIME.", is_delete = 1 where live_in =2 and id = ".$room_id." and user_id = ".$user_id;
					$GLOBALS['db']->query($sql);

					if($GLOBALS['db']->affected_rows()){
						$root['status'] = 1;

						//将mysql数据,同步一份到redis中
						sync_video_to_redis($room_id,'*',false);

					}else{
						$root['status'] = 0;
					}
				}

			}

		}

		ajax_return($root);
	}

	/**
	 * 贡献榜（当天，所有）
	 * room_id: ===>如果有值，则取：本场直播贡献榜排行
	 * user_id:===>取某个用户的：总贡献榜排行
	 * p:不传或传0;则取前50位排行
	 */
	public function cont(){
		$root = array();


		$room_id = intval($_REQUEST['room_id']);//当前正在直播的房间id
		$user_id = intval($_REQUEST['user_id']);//被查看的用户id
		if($room_id == 0 && $user_id == 0){
			$user_id = intval($GLOBALS['user_info']['id']);//取当前用户的id
		}

		if($room_id == 0 && $user_id == 0){
			$root['error'] = "房间ID跟用户ID必须传一个";
			$root['status'] = 0;
		}else{



			$page = intval($_REQUEST['p']);//取第几页数据
			$page_size = 50;
//			if($page==0){
//				$page_size = "50";
//			}else{
//				$page_size=30;
//				//$limit = (($page-1)*$page_size).",".$page_size;
//			}

			fanwe_require(APP_ROOT_PATH.'mapi/lib/redis/VideoContributionRedisService.php');
			$video_con = new VideoContributionRedisService($user_id);

			if ($room_id > 0){
//				//本场直播贡献榜排行
				$root =	$video_con->get_video_contribute($room_id,$page,$page_size);
				$root['total_num'] = intval($root['total_ticket_num']);
                $root['v_icon'] = $root['user']['v_icon'];
			}else{
				//总贡献榜排行
				//用户总票数
				$root =	$video_con->get_podcast_contribute($user_id,$page,$page_size);



				//$sql = "select ticket from ".DB_PREFIX."user where id = ".$user_id;
				$root['total_num'] = intval(floor($root['user']['ticket']));

			}

//			$sql = "select id as user_id,ticket,nick_name,head_image,user_level,sex,v_type,v_icon from ".DB_PREFIX."user where id = ".$user_id;
//			$user = $GLOBALS['db']->getRow($sql);
//			$user['head_image'] = get_abs_img_root($user['head_image']);
//			$root['user'] = $user;


//			if ($room_id > 0){
//				$root['total_num'] = $video['vote_number'];
//			}else{
//				$root['total_num'] = $user['ticket'];
//			}
//
//			foreach($list as $k=>$v){
//				$list[$k]['head_image'] = get_abs_img_root($v['head_image']);
//			}
//			$root['list'] = $list;

//			if($page==0){
//				$root['has_next'] = 0;
//			}else{
//
//				if (count($list) == $page_size)
//					$root['has_next'] = 1;
//				else
//					$root['has_next'] = 0;
//			}

//			$root['page'] = $page;//
//			$root['status'] = 1;

		}
		//print_r($root);exit;

		ajax_return($root);
	}

	/**
	 * 检查用户是否有发起连麦的权限
	 */
	public function check_lianmai()
	{

		//$GLOBALS['user_info']['id'] = 270;
		$root = array();

		if(!$GLOBALS['user_info']){
			$root['error'] = "用户未登陆,请先登陆.";// es_session::id();
			$root['status'] = 0;
			$root['user_login_status'] = 0;//有这个参数： user_login_status = 0 时，表示服务端未登陆、要求登陆，操作
		}else{

			$user_id = $GLOBALS['user_info']['id'];//申请连麦的用户id
			$room_id = intval($_REQUEST['room_id']);//当前正在直播的房间id
			/*
			 fanwe_require(APP_ROOT_PATH . 'mapi/lib/redis/VideoRedisService.php');
			$video_redis = new VideoRedisService();
			$data = $video_redis->getRow_db($room_id, array('game_log_id','video_type'));
			
			if (OPEN_GAME_MODULE) {
			$last_log_id = $data['game_log_id'];
			if ($last_log_id) {
			api_ajax_return(array(
					'status'      => 0,
					'error'       => '正在游戏，不能开始连麦',
			));
			}
			}else{
				$root['status'] = 1;
			}
			*/
			$root['status'] = 1;

		}

		ajax_return($root);
	}

	/**
	 * 开始连麦(主播同意后，主播调用)
	 */
	public function start_lianmai()
	{
		//$GLOBALS['user_info']['id'] = 270;
		$root = array();
		
		if(!$GLOBALS['user_info']){
			$root['error'] = "用户未登陆,请先登陆.";
			$root['status'] = 0;
			$root['user_login_status'] = 0;//有这个参数： user_login_status = 0 时，表示服务端未登陆、要求登陆，操作
		}else{
			$to_user_id = intval($_REQUEST['to_user_id']);//申请连麦的用户id
		
			$room_id = intval($_REQUEST['room_id']);//当前正在直播的房间id
		
			$m_config = load_auto_cache('m_config');
				
			//$root['m_config'] = $m_config;
			//$qcloud_security_key = 'f8639bc67513dbbc3713ddc835b7f156';
			$qcloud_security_key = $m_config['qcloud_security_key'];
			$bizId = $m_config['qcloud_bizid'];
			
			fanwe_require(APP_ROOT_PATH . 'mapi/lib/redis/VideoRedisService.php');
			$video_redis = new VideoRedisService();
			$data = $video_redis->getRow_db($room_id, array('channelid','video_type','play_rtmp','push_rtmp'));
		
				
			$video_lianmai = array();
				
			if ($data['video_type'] == 1 && !empty($qcloud_security_key)) {
				//直播码 方式
				fanwe_require(APP_ROOT_PATH.'mapi/lib/core/video_factory.php');
				$video_factory = new VideoFactory();
				$channel_info = $video_factory->GetChannelInfo($to_user_id,'s',$room_id,$to_user_id);
		
				$video_lianmai['channelid'] = $channel_info['channel_id'];
				$video_lianmai['push_rtmp'] = $channel_info['upstream_address'];
				$video_lianmai['play_rtmp'] = $channel_info['downstream_address']['rtmp'];
		
				//小主播的 push_rtmp 推流地址
				$push_rtmp2 = $video_lianmai['push_rtmp'];
				$root['push_rtmp2'] = $push_rtmp2;//小主播的 push_rtmp 推流地址
		
		
				//小主播的 rtmp_acc 播放地址; 12小时失效
				$play_rtmp2_acc  = $video_lianmai['play_rtmp'] ."?bizid=".$bizId."&".$video_factory->get_acc_sign($qcloud_security_key,$video_lianmai['channelid'],43200);
				$play_rtmp2_acc = $play_rtmp2_acc.'&session_id='.$room_id;//str_pad($room_id,32,'0',STR_PAD_LEFT);
				$root['play_rtmp2_acc'] = $play_rtmp2_acc;//小主播的 rtmp_acc 播放地址;
		
		
				//大主播的 rtmp_acc 播放地址; 12小时失效
				$play_rtmp_acc  = $data['play_rtmp'] ."?bizid=".$bizId."&".$video_factory->get_acc_sign($qcloud_security_key,$data['channelid'],43200);
				$play_rtmp_acc = $play_rtmp_acc.'&session_id='.$room_id;//str_pad($room_id,32,'0',STR_PAD_LEFT);
				$root['play_rtmp_acc'] = $play_rtmp_acc;//大主播的 rtmp_acc 播放地址;
		
		
				$video_lianmai['play_rtmp_acc'] = $root['play_rtmp2_acc'];
				$video_lianmai['v_play_rtmp_acc'] = $root['play_rtmp_acc'];
				
				//$root['data'] = $data;
				//$root['qcloud_security_key'] = $qcloud_security_key;
			}
		
			//如果用户有旧的：连麦没结束,则把它结束掉
			$sql = 'update '.DB_PREFIX."video_lianmai set stop_time ='".NOW_TIME."' where stop_time = 0 and video_id =".$room_id." and user_id =".$to_user_id;
			$GLOBALS['db']->query($sql);
			
			
			$video_lianmai['user_id'] = $to_user_id;
			$video_lianmai['video_id'] = $room_id;
			$video_lianmai['start_time'] = NOW_TIME;
			$GLOBALS['db']->autoExecute(DB_PREFIX."video_lianmai", $video_lianmai,"INSERT");
		
			$video_lianmai_id = $GLOBALS['db']->insert_id();
		
			$root['video_lianmai_id'] = $video_lianmai_id;
		
			if ($video_lianmai_id > 0){
				$root['status'] = 1;
				
				$this->push_lianmai($room_id);
			}else{
				$root['status'] = 0;
				$root['error'] = "连麦数据记录出错";
			}
		
		}
		
		ajax_return($root);
	}

	/**
	 * 推送：连麦观众列表，到 连麦观众APP端
	 * @param unknown_type $video_id
	 * @return mixed|string
	 */
	private function push_lianmai($video_id){
		$user_id = intval($GLOBALS['user_info']['id']);
		
		
		$m_config = load_auto_cache('m_config');
			
		$qcloud_security_key = $m_config['qcloud_security_key'];
		$bizId = $m_config['qcloud_bizid'];
		
		fanwe_require(APP_ROOT_PATH . 'mapi/lib/redis/VideoRedisService.php');
		$video_redis = new VideoRedisService();
		$video = $video_redis->getRow_db($video_id, array('channelid','group_id','user_id','video_type','play_rtmp','push_rtmp'));

		$receiver_list = array();
		
		//直播码 方式
		if ($video['video_type'] == 1 && !empty($qcloud_security_key)) {
			$receiver_list[] = $video['user_id'];

			fanwe_require(APP_ROOT_PATH.'mapi/lib/core/video_factory.php');
			$video_factory = new VideoFactory();
		
			$data = array();
			
			//大主播的 rtmp_acc 播放地址; 5分钟失效
			$play_rtmp_acc  = $video['play_rtmp'] ."?bizid=".$bizId."&".$video_factory->get_acc_sign($qcloud_security_key,$video['channelid'],3000);
			$play_rtmp_acc = $play_rtmp_acc.'&session_id='.$video_id;//str_pad($room_id,32,'0',STR_PAD_LEFT);
			$data['play_rtmp_acc'] = $play_rtmp_acc;//大主播的 rtmp_acc 播放地址;
			$data['push_rtmp'] = $video['push_rtmp'];//大主播的 push_rtmp 推流地址;
			//获得连麦观众的列表，最多取最新3个
			$sql = "select user_id,push_rtmp,play_rtmp,channelid from ".DB_PREFIX."video_lianmai where stop_time = 0 and video_id =".$video_id ." order by start_time desc limit 3";
			$list = $GLOBALS['db']->getAll($sql,true,true);
		
			$list_lianmai = array();
			
			$total = count($list);
			
			if ($total > 0) {
				$image_layer = 2;
				foreach ( $list as $k => $v )
				{
					$user = array();
					$user['user_id'] = $v['user_id'];
					$receiver_list[] = $v['user_id'];
					
					$user['push_rtmp2'] = $v['push_rtmp'];//小主播的 push_rtmp 推流地址
						
					//小主播的 rtmp_acc 播放地址; 5分钟失效
					$play_rtmp2_acc  = $v['play_rtmp'] ."?bizid=".$bizId."&".$video_factory->get_acc_sign($qcloud_security_key,$v['channelid'],3000);
					$play_rtmp2_acc = $play_rtmp2_acc.'&session_id='.$video_id;//str_pad($room_id,32,'0',STR_PAD_LEFT);
					$user['play_rtmp2_acc'] = $play_rtmp2_acc;//小主播的 rtmp_acc 播放地址;
	
					$user['layout_params'] = $this->get_lianmai_layout($total, $image_layer);
					
					$image_layer ++;
					
					$list_lianmai[] = $user;
				}
			}
			
			$data['list_lianmai'] = $list_lianmai;
		}
		
		
		if (count($receiver_list) > 0){
			$ext = array();
			$ext['type'] = 42; //42 通用数据格式
			$ext['data_type'] = 1;//直播间,连麦观众列表
			$ext['data'] = $data;
			//$ext['receiver_list'] = $receiver_list;
			
			$msg_content = json_encode($ext);
			
			
			fanwe_require(APP_ROOT_PATH.'system/tim/TimApi.php');
			$api = createTimAPI();
			$ret = $api->group_send_group_system_notification2($video['group_id'], $msg_content,$receiver_list);
			//$ret['receiver_list'] = $receiver_list;
			
			return $ret;
		}else{
			$root['status'] = 0;
			$root['error'] = "无效数据";
			
			return $root;
		}
	}
	
	/**
	 * 获得混流小主播大小，位置参数
	 * @param unknown_type $total 小主播个数
	 * @param unknown_type $image_layer 小主播图层标识号,从2开始; 大主播填 1 ;  小主播按照顺序填写2、3、4
	 */
	private function get_layout($total, $image_layer, $video_resolution_type){
		if($video_resolution_type == 1){
			//高清(540*960)
			$width = 540;
			$height = 960;
		}else if ($video_resolution_type == 2){
			//超清(720*1280)
			$width = 720;
			$height = 1280;
		}else{
			$width = 360;
			$height = 640;
		}
		
		$layout_params = $this->get_lianmai_layout($total, $image_layer);
		
		$layout_params['image_width'] = intval($layout_params['image_width'] * $width);//小主播画面宽度
		$layout_params['image_height'] = intval($layout_params['image_height'] * $height);//小主播画面高度
		$layout_params['location_x'] = intval($layout_params['location_x'] * $width);//x偏移：相对于大主播背景画面左上角的横向偏移
		$layout_params['location_y'] = intval($layout_params['location_y'] * $height);//y偏移：相对于大主播背景画面左上角的纵向偏移
		
		
		
		return $layout_params;
	}
	
	/**
	 * app连麦观众 的小窗口排序
	 * @param unknown_type $total
	 * @param unknown_type $image_layer
	 * @return multitype:number unknown
	 */
	private function get_lianmai_layout($total, $image_layer){

		
		$image_width = 0.3;//小主播画面宽度
		$image_height = 0.27;//小主播画面高度
		
		$layout_params = array();
		$layout_params['image_layer'] = $image_layer;//图层标识号：大主播填 1 ;  小主播按照顺序填写2、3、4
		$layout_params['image_width'] = $image_width;//小主播画面宽度
		$layout_params['image_height'] = $image_height;//小主播画面高度
		$layout_params['location_x'] = 0.66;//x偏移：相对于大主播背景画面左上角的横向偏移
		
		if ($total == 1){
		
			$layout_params['location_y'] = 0.61 ;//y偏移：相对于大主播背景画面左上角的纵向偏移
		
		}else if ($total == 2){
		
			if ($image_layer == 2){
				$layout_params['location_y'] = 0.61;//y偏移：相对于大主播背景画面左上角的纵向偏移
			}else{
				$layout_params['location_y'] = 0.61 - $image_height - 0.005;//y偏移：相对于大主播背景画面左上角的纵向偏移
			}
		
		}else{
			if ($image_layer == 2){
				$layout_params['location_y'] = 0.635; //y偏移：相对于大主播背景画面左上角的纵向偏移
			}else if ($image_layer == 3){
				$layout_params['location_y'] = 0.635 - $image_height - 0.005; //y偏移：相对于大主播背景画面左上角的纵向偏移
			}else{
				$layout_params['location_y'] = 0.635 -($image_height + 0.005)*2; //y偏移：相对于大主播背景画面左上角的纵向偏移
			}
		}
	
		return $layout_params;
	}
	
	/**
	 * 结束连麦(主播调用)
	 */
	public function stop_lianmai()
	{

		//$GLOBALS['user_info']['id'] = 270;
		$root = array();

		if(!$GLOBALS['user_info']){
			$root['error'] = "用户未登陆,请先登陆.";
			$root['status'] = 0;
			$root['user_login_status'] = 0;//有这个参数： user_login_status = 0 时，表示服务端未登陆、要求登陆，操作
		}else{
			$user_id = intval($GLOBALS['user_info']['id']);

			$room_id = intval($_REQUEST['room_id']);//当前正在直播的房间id
			
			$to_user_id = intval($_REQUEST['to_user_id']);//申请连麦的用户id
			if ($to_user_id > 0){
				//只有主播，才可以结束其它人的连麦
				fanwe_require(APP_ROOT_PATH . 'mapi/lib/redis/VideoRedisService.php');
				$video_redis = new VideoRedisService();
				$video = $video_redis->getRow_db($room_id, array('user_id','video_type'));
				
				if ($video['user_id'] != $user_id){
					$to_user_id = $user_id;//如果不是主播的话,只能结束自己的连麦
				}
			}else{
				$to_user_id = $user_id;
			}
			
			$sql = 'update '.DB_PREFIX."video_lianmai set stop_time ='".NOW_TIME."' where stop_time = 0 and video_id =".$room_id." and user_id =".$to_user_id;
			$GLOBALS['db']->query($sql);

			//有人：结束连麦,通知：其它连麦用户
			$this->push_lianmai($room_id);
			
			//混合更新
			$this->mix_stream2($room_id,$to_user_id);
			
			$root['status'] = 1;
		}

		ajax_return($root);
	}
	
	/**
	 * 混合
	 * https://www.qcloud.com/document/product/454/8872
	 */
	private function mix_stream2($room_id,$to_user_id){
	

		$m_config = load_auto_cache('m_config');
		
		$qcloud_security_key = $m_config['qcloud_security_key'];
		$bizId = $m_config['qcloud_bizid'];
		
		fanwe_require(APP_ROOT_PATH . 'mapi/lib/redis/VideoRedisService.php');
		$video_redis = new VideoRedisService();
		$video = $video_redis->getRow_db($room_id, array('channelid','video_type','user_id', 'play_rtmp','push_rtmp'));
		//print_r($video);
		
		//直播码 方式  && $user_id == $video['user_id']
		if ($video['video_type'] == 1 && !empty($qcloud_security_key)) {
		
			$data = array();
			$data['timestamp'] = NOW_TIME;//UNIX时间戳，即从1970年1月1日（UTC/GMT的午夜）开始所经过的秒数
			$data['eventId'] = NOW_TIME;//混流事件ID，取时间戳即可，后台使用
			$interface = array();
		
		
			$interface['interfaceName'] = 'Mix_StreamV2';//固定取值"Mix_StreamV2"
		
			$para = array();
		
			$para['app_id'] = $m_config['vodset_app_id'];//# 填写直播APPID
			$para['interface'] = "mix_streamv2.start_mix_stream_advanced";//# 固定取值"mix_streamv2.start_mix_stream_advanced"
			$para['mix_stream_session_id'] = $video['channelid'] ;// 填大主播的流ID
			$para['output_stream_id'] = $video['channelid'] ;// 填大主播的流ID
				
			$input_stream_list = array();
			$user = array();
			$user['input_stream_id'] = $video['channelid'];//流ID
			$user['layout_params']['image_layer'] = 1;//图层标识号：大主播填 1 ;  小主播按照顺序填写2、3、4
			$input_stream_list[] = $user;
				
				
			//获得连麦观众的列表，最多取最新3个
			$sql = "select user_id,play_rtmp,play_rtmp,channelid from ".DB_PREFIX."video_lianmai where stop_time = 0 and video_id =".$room_id ." order by start_time desc limit 3";
			$list = $GLOBALS['db']->getAll($sql,true,true);
				
			$total = count($list);
		
			if ($total > 0) {
				$image_layer = 2;
				foreach ( $list as $k => $v )
				{
					$user = array();
					$user['input_stream_id'] = $v['channelid'];//流ID
					$user['layout_params'] = $this->get_layout($total, $image_layer,$m_config['video_resolution_type']);
		
						
					//$user['layout_params2'] = $this->get_lianmai_layout($total, $image_layer);
		
					$input_stream_list[] = $user;
					$image_layer ++;
				}
			}
				
			$para['input_stream_list'] = $input_stream_list;
				
			$interface['para'] = $para;
		
			$data['interface'] = $interface;
		
			$key = $m_config['qcloud_auth_key'];//$qcloud_security_key
			$t = get_gmtime() + 86400;
			//http://fcgi.video.qcloud.com/common_access?cmd=appid&interface=Mix_StreamV2&t=t&sign=sign
		
			$url = "http://fcgi.video.qcloud.com/common_access?" . http_build_query(array(
					'cmd' => $m_config['vodset_app_id'],
					'interface' => 'Mix_StreamV2',
					't' => $t,
					'sign' => md5($key . $t)
			));
		
			//echo $url;
		
			//print_r($data);
		
		
			fanwe_require(APP_ROOT_PATH .'mapi/lib/core/transport.php');
		
			$trans = new transport();
		
			$post_json = json_encode($data);
			//print_r($post_json);
		
			$req = $trans->request($url,$post_json,'POST');
		
			//print_r($post_json);
		
			//print_r($req);
			if($req)log_err_file(array(__FILE__,__LINE__,__METHOD__,$req));
			$req = json_decode($req['body'],1);
			if ($req['code'] == 0){
				$return['status'] =1;
			}else{
				$return['status'] = 0;
			}
			if(intval(IS_DEBUG)){
				$return['error'] = $req['message'];
			}else{
				$return['error'] = '';
			}
		
			//$return['url'] = $url;
			//$return['key'] = $key;
			//$return['daqcloud_security_key'] = $qcloud_security_key;
		
			//$return['data'] = $data;
		
			$return['req'] = $req;
		}else{
			$return['error'] = "无效直播间";
			$return['status'] =0;
		}
				
	
		return $return;
	}
	
	public function mix_stream(){
		
		if(!$GLOBALS['user_info'] && false){
			$return['error'] = "用户未登陆,请先登陆.";
			$return['status'] =0;
			$return['user_login_status'] = 0;//有这个参数： user_login_status = 0 时，表示服务端未登陆、要求登陆，操作
		}else{
			
			$user_id = intval($GLOBALS['user_info']['id']);
			
			$room_id = intval($_REQUEST['room_id']);//当前正在直播的房间id
		
			$to_user_id = intval($_REQUEST['to_user_id']);//app端上传,那个小主播拉流成功，预留
			
			$return = $this->mix_stream2($room_id,$to_user_id);
			
		}
		ajax_return($return);
	}
	
	//开始直播，加入预先创建房间 并修改 begin_time状态
	public function add_video(){
		if(!$GLOBALS['user_info']){
			$return['error'] = "用户未登陆,请先登陆.";
			$return['status'] =0;
			$return['user_login_status'] = 0;//有这个参数： user_login_status = 0 时，表示服务端未登陆、要求登陆，操作
		}else{
			//用户是否禁播，$is_ban=1 永久禁播；$is_ban=0非永久禁播，$ban_time禁播结束时间
			$user_id = intval($GLOBALS['user_info']['id']);
			$sql = "select is_authentication,is_ban,ban_time,mobile,login_ip,ban_type,apns_code,sex,ticket,refund_ticket,user_level,fans_count,head_image,thumb_head_image from ".DB_PREFIX."user where id = ".$user_id;
			$user = $GLOBALS['db']->getRow($sql,true,true);
            //$video_classified=intval($_REQUEST['video_classified']);
			$is_authentication = intval($user['is_authentication']);
			$m_config=load_auto_cache("m_config");
			if(!isset($m_config['video_type'])){
				$re = array("error"=>"直播类型不存在","status"=>0);
				ajax_return($re);
			}
			$dev_type = strim($_REQUEST['sdk_type']);
			$sdk_version_name = strim($_REQUEST['sdk_version_name']);
			//提过限制开播
			$allow = 0;
			if($user['mobile']=='13888888888'&&$m_config['ios_check_version'] != ''&&$m_config['ios_check_version'] == $sdk_version_name){
				$allow = 1;
			}
			if($user['mobile']=='13999999999'&&$m_config['ios_check_version'] != ''){
				$allow = 1;
			}
			
            if($allow){
              $is_authentication = 2;
              $m_config['is_limit_time'] = 0;
            }
            
            if($m_config['must_authentication']==1&&$is_authentication!=2){
            	$re = array("error"=>"请认证后再发起直播 ","status"=>0);
                ajax_return($re);
            }


            if(intval($m_config['is_limit_time'])==1){
            	$now = to_date(get_gmtime(),"H");
            	if(intval($m_config['is_limit_time_end'])==intval($m_config['is_limit_time_start'])){
            		$re = array("error"=>"直播功能已关闭","status"=>0);
            		ajax_return($re);
            	}
            	$to_day = 1;
            	if(intval($m_config['is_limit_time_start'])>intval($m_config['is_limit_time_end'])){
            		$to_day = 0;
            	}  
            	 	
       			if($to_day==0&&intval($m_config['is_limit_time_start'])>$now&&intval($m_config['is_limit_time_end'])<=$now){
                   $re = array("error"=>"请在每天的".intval($m_config['is_limit_time_start'])."时到第二天的".intval($m_config['is_limit_time_end'])."时期间进行直播","status"=>0);
                   ajax_return($re);
	            }

				if($to_day==1&&(intval($m_config['is_limit_time_start'])>$now||intval($m_config['is_limit_time_end'])<=$now)){
                   $re = array("error"=>"请在每天的".intval($m_config['is_limit_time_start'])."时到".intval($m_config['is_limit_time_end'])."时期间进行直播","status"=>0);
                   ajax_return($re);
	            }
            }
            
            $apns_code = addslashes($_REQUEST['apns_code']);			
            if($user['ban_type']==1&&$user['login_ip']==get_client_ip()&&$user['is_ban']==1){
                $re =array("error"=>"请求房间id失败，当前IP已被封停，请联系客服处理","status"=>0);
                ajax_return($re);
            }
                       
            if($user['ban_type']==2&&$user['apns_code']==$apns_code&&$user['is_ban']==1){
              	$re = array("error"=>"请求房间id失败，当前设备已被禁用，请联系客服处理","status"=>0);
                ajax_return($re);
            }

			if(intval($user['is_ban']) == 0 && intval($user['ban_time']) < get_gmtime()){
				//$_REQUEST['title'] = $_REQUEST['title']?$_REQUEST['title']:"#新人直播#";
				$title = strim(str_replace('#','',$_REQUEST['title']));
                

				//$title = iconv("UTF-8","UTF-8//IGNORE",$title);

				//===lym start====
				$cate_name = $title;
				//===lym end===
				$cate_id = intval($_REQUEST['cate_id']);

				$xpoint = floatval($_REQUEST['xpoint']);//x座标(用来计算：附近)
				$ypoint = floatval($_REQUEST['ypoint']);//y座标(用来计算：附近)
				$live_image = strim($_REQUEST['live_image']);//图片地址,手机端图片先上传到oss，然后获得图片地址,再跟其它资料一起提交到服务器

				$location_switch = intval($_REQUEST['location_switch']);//1-上传当前城市名称
				$province = strim($_REQUEST['province']);//省
				$city = strim($_REQUEST['city']);//市

				$is_private = intval($_REQUEST['is_private']);//1：私密聊天; 0:公共聊天
				$share_type = strtolower(strim($_REQUEST['share_type']));//WEIXIN,WEIXIN_CIRCLE,QQ,QZONE,EMAIL,SMS,SINA
				if ($share_type == 'null'){
					$share_type = '';
				}

				//检查话题长度
				if(strlen($title)>60){
					$return['error'] = "话题太长";
					$return['status'] =0;
					ajax_return($return);
				}

				//$private_ids = strim($_REQUEST['private_ids']);//字符串类型的私聊好友id 23,123,3455 以英文逗号分割的字符串 只有私聊时才需要上传这个参数


				$sql = "select id,video_type from ".DB_PREFIX."video where live_in =2 and user_id = ".$user_id;
				$video = $GLOBALS['db']->getRow($sql,true,true);
				if ($video){

					//更新心跳时间，免得被删除了
					$sql = "update ".DB_PREFIX."video set monitor_time = '".to_date(NOW_TIME,'Y-m-d H:i:s')."' where id =".$video['id'];
					$GLOBALS['db']->query($sql);

					if($GLOBALS['db']->affected_rows()){
						//如果数据库中发现，有一个正准备执行中的，则直接返回当前这条记录;
						$return['status'] =1;
						$return['error'] ='';
						$return['room_id'] = intval($video['id']);
						$return['video_type'] = intval($video['video_type']);
						ajax_return($return);
					}
				}



				//关闭 之前的房间,非正常结束的直播,还在通知所有人：退出房间
				$sql = "select id,user_id,watch_number,vote_number,group_id,room_type,begin_time,end_time,channelid,video_vid,cate_id from ".DB_PREFIX."video where live_in =1 and user_id = ".$user_id;
				$list = $GLOBALS['db']->getAll($sql,true,true);
				foreach ( $list as $k => $v )
				{
					//结束直播
					do_end_video($v,$v['video_vid'],1,$v['cate_id']);
				}

				//话题
				if($cate_id){
					//$cate_title = $GLOBALS['db']->getOne("select title from ".DB_PREFIX."video_cate where id=".$cate_id,true,true);
					$cate = load_auto_cache("cate_id",array('id'=>$cate_id));
					$cate_title = $cate['title'];
					if($cate_title != $title){
						$cate_id = 0;
					}
				}

				if ($cate_id == 0 && $title != ''){
					$cate_id = $GLOBALS['db']->getOne("select id from ".DB_PREFIX."video_cate where title='".$title."'",true,true);
					if($cate_id){
						$is_newtitle = 0;
					}else{
						$is_newtitle = 1;
					}
				}


				if($is_newtitle){
					$data_cate = array();
					$data_cate['title'] = $title;
					$data_cate['is_effect'] =1 ;
					$data_cate['is_delete'] =0;
					$data_cate['create_time'] =NOW_TIME;

					$GLOBALS['db']->autoExecute(DB_PREFIX."video_cate", $data_cate,'INSERT');
					$cate_id =  $GLOBALS['db']->insert_id();
				}

				
				if($m_config['must_cate']==1){
					if(!$cate_id){
						$re = array("error"=>"直播话题不能为空","status"=>0);
						ajax_return($re);
					}
				}
                

				//添加位置

				if ($province == 'null'){
					$province = '';
				}

				if ($city == 'null'){
					$city = '';
				}

				$province = str_replace("省", "", $province);

				$city = str_replace("市", "", $city);

				if (($province == '' || $city == '') && $location_switch == 1){
					/*
					 //客户端没有定位到,服务端则用ip再定位一次
					fanwe_require APP_ROOT_PATH . "system/extend/ip.php";
					$ip = new iplocate ();
					$area = $ip->getaddress ( CLIENT_IP );
					$location = $area ['area1'];
					*/

					$ipinfo = get_ip_info();

					$province = $ipinfo['province'];
					$city = $ipinfo['city'];

					//$title = print_r($ipinfo,1);
				}

				if ($province == ''){
					$province= '火星';
				}

				if ($city == ''){
					$city= '火星';
				}
				if($city=='火星'||$province=='火星'){
					$xpoint = '';//x座标(用来计算：附近)
					$ypoint = '';//y座标(用来计算：附近)
				}
				//
				$video_id = get_max_room_id(0);
				$data =array();
				$data['id'] = $video_id;
				//room_type 房间类型 : 1私有群（Private）,0公开群（Public）,2聊天室（ChatRoom）,3互动直播聊天室（AVChatRoom）
				if ($is_private == 1){
					$data['room_type'] = 1;
					$data['private_key'] = md5($video_id.rand(1,9999999));//私密直播key
				}else
					$data['room_type'] = 3;



				$data['virtual_number'] = intval($m_config['virtual_number']);
				$data['max_robot_num'] = intval($m_config['robot_num']);//允许添加的最大机器人数;

				/*$sql = "select sex,ticket,refund_ticket,user_level,fans_count,head_image,thumb_head_image from ".DB_PREFIX."user where id = ".$user_id;
				$user = $GLOBALS['db']->getRow($sql,true,true);*/

				//图片,应该从客户端上传过来,如果没上传图片再用会员头像
				
				if ($live_image!=''&&$live_image!='./(null)'){
					fanwe_require(APP_ROOT_PATH .'mapi/lib/core/transport.php');
					$trans = new transport();
					$req = $trans->request(get_spec_image($live_image),'','GET');
					if(strlen($req['body'])>1000){
						$data['live_image'] = $live_image;
					}else{
						$data['live_image'] = $user['head_image'];
					}
				}else{
					$data['live_image'] = $user['head_image'];
				}

				$data['head_image'] = $user['head_image'];
				$data['thumb_head_image'] = $user['thumb_head_image'];

				$data['sex'] = intval($user['sex']);//性别 0:未知, 1-男，2-女

				$data['xpoint'] = $xpoint;
				$data['ypoint'] = $ypoint;

				$data['video_type'] = intval($m_config['video_type']);//0:腾讯云互动直播;1:腾讯云直播

				if($data['video_type'] > 0){
					require_once(APP_ROOT_PATH.'system/tim/TimApi.php');
					$api = createTimAPI();
					$ret = $api->group_create_group('AVChatRoom', (string)$user_id, (string)$user_id, (string)$video_id);
					if ($ret['ActionStatus'] != 'OK'){
						ajax_return(array(
							'status' => 0,
							'error' => $ret['ErrorCode'].$ret['ErrorInfo']
						));
					}

					$data['group_id'] = $ret['GroupId'];

					fanwe_require(APP_ROOT_PATH.'mapi/lib/core/video_factory.php');
					$video_factory = new VideoFactory();
					$channel_info = $video_factory->Create($video_id,'mp4',$user_id);
					if(! empty($channel_info['video_type'])) {
						$data['video_type'] = $channel_info['video_type'];
					}
					
					$data['channelid'] = $channel_info['channel_id'];
					$data['push_rtmp'] = $channel_info['upstream_address'];
					$data['play_flv'] = $channel_info['downstream_address']['flv'];
					$data['play_rtmp'] = $channel_info['downstream_address']['rtmp'];
					$data['play_hls'] = $channel_info['downstream_address']['hls'];

				}

				$data['monitor_time'] = to_date(NOW_TIME,'Y-m-d H:i:s');//主播心跳监听

				$data['push_url'] = '';//video_type=1;1:腾讯云直播推流地址
				$data['play_url'] = '';//video_type=1;1:腾讯云直播播放地址(rmtp,flv)

				$data['share_type'] = $share_type;
				$data['title'] = $title;
				$data['cate_id'] = $cate_id;
                //$data['video_classified'] = $video_classified;
				$data['user_id'] = $user_id;
				$data['live_in'] = 2;//live_in:是否直播中 1-直播中 0-已停止;2:正在创建直播;
				$data['watch_number'] = '';//'当前观看人数';
				$data['vote_number'] = '';//'获得票数';
				$data['province'] = $province;//'省';
				$data['city'] = $city;//'城市';

				$data['create_time'] = NOW_TIME;//'创建时间';
				$data['begin_time'] = NOW_TIME;//'开始时间';
				$data['end_time'] = '';//'结束时间';
				$data['is_hot'] = 1;//'1热门; 0:非热门';
				$data['is_new'] =1; //'1新的; 0:非新的,直播结束时把它标识为：0？'

				$data['online_status'] = 1;//主播在线状态;1:在线(默认); 0:离开

				//sort_init(初始排序权重) = (用户可提现印票：fanwe_user.ticket - fanwe_user.refund_ticket) * 保留印票权重+ 直播/回看[回看是：0; 直播：9000000000 直播,需要排在最上面 ]+ fanwe_user.user_level * 等级权重+ fanwe_user.fans_count * 当前有的关注数权重
				$sort_init = (intval($user['ticket']) - intval($user['refund_ticket'])) * floatval($m_config['ticke_weight']);

				$sort_init += intval($user['user_level']) * floatval($m_config['level_weight']);
				$sort_init += intval($user['fans_count']) * floatval($m_config['focus_weight']);

				$data['sort_init'] = 200000000 + $sort_init;
				$data['sort_num'] = $data['sort_init'];


				// 1、创建视频时检查表是否存在，如不存在创建礼物表，表命名格式 fanwe_ video_ prop_201611、格式同fanwe_ video_ prop相同
				// 2、将礼物表名称写入fanwe_video 中，需新建字段
				// 3、记录礼物发送时候读取fanwe_video 的礼物表名，写入对应的礼物表
				// 4、修改所有读取礼物表的地方，匹配数据
				$data['prop_table'] = createPropTable();
				//直播分类
				//$data['classified_id'] = $video_classified;

				$GLOBALS['db']->autoExecute(DB_PREFIX."video", $data,'INSERT');
				//$video_id =  $GLOBALS['db']->insert_id();

				if($GLOBALS['db']->affected_rows()){
					$return['status'] =1;
					$return['error'] ='';
					$return['room_id'] = $video_id;
					$return['video_type'] = intval($data['video_type']);

					sync_video_to_redis($video_id,'*',false);

				}else{
					$return['status'] =0;
					$return['error'] ='创建房间失败！';
				}
			}else{
                if(intval($user['is_ban']&&intval($user['ban_type']==0))){
                    $return['status'] =0;
                    $return['error'] ='请求房间id失败，您被禁播，请联系客服处理。';
                }elseif(intval($user['is_ban']&&intval($user['ban_type']==1))){
                    $return['status'] =0;
                    $return['error'] ='请求房间id失败，当前IP已被封停，请联系客服处理。';
                }elseif(intval($user['is_ban']&&intval($user['ban_type']==2))){
                    $return['status'] =0;
                    $return['error'] ='请求房间id失败，当前设备已被禁用，请联系客服处理。';
                }
                else{
                    $return['status'] =0;
                    $return['error'] ='由于您的违规操作，您被封号暂时不能直播，封号时间截止到：'.to_date(intval($user['ban_time']),'Y-m-d H:i:s').'。';
                }

			}
		}
		if($m_config['must_authentication']==1){
            if($is_authentication!=2){
               $return['room_id'] = 0;
            }
        }
		//-------------------------------------
		//sdk_type 0:使用腾讯SDK、1：使用金山SDK
		//映射关系类型  腾讯云直播, 金山云，星域，方维云 ，阿里云
		//video_type     1  		2		 3		4		5
		//sdk_type       0			1		 -		-		-
		$return['sdk_type'] = get_sdk_info($m_config['video_type']);

		ajax_return($return);
	}

	/**
	 * 检查直播状态
	 * room_id:房间号id
	 */
	public function check_status(){
		$root = array();

		//$root['sql'] = $sql;
		if(!$GLOBALS['user_info']){
			$root['error'] = "用户未登陆,请先登陆.";
			$root['status'] = 0;
			$root['user_login_status'] = 0;//有这个参数： user_login_status = 0 时，表示服务端未登陆、要求登陆，操作
		}else{
			$room_id = intval($_REQUEST['room_id']);//房间号id
			$user_id = intval($GLOBALS['user_info']['id']);//用户ID
			$private_key = strim($_REQUEST['private_key']);//私密直播key

			if ($private_key != ''){
				$sql = "select v.id,v.city,v.live_in,v.user_id,v.group_id,u.nick_name,u.head_image,v.video_type from ".DB_PREFIX."video v left join ".DB_PREFIX."user u on u.id = v.user_id where v.room_type = 1 and v.private_key='".$private_key."'";
				$video = $GLOBALS['db']->getRow($sql);

				$room_id = intval($video['id']);
				/*
				$sql = "select id from ".DB_PREFIX."video_private where status = 0 and user_id = ".$user_id. " and video_id =".$room_id;
				$video_private = $GLOBALS['db']->getRow($sql);
				if ($video_private){
					$root['error'] = "您已经被踢出,不能再加入";
					$root['status'] = 0;
					ajax_return($root);
				}
				*/

				fanwe_require(APP_ROOT_PATH.'mapi/lib/redis/VideoPrivateRedisService.php');
				$video_private_redis = new VideoPrivateRedisService();

				if ($video_private_redis->check_user_drop($room_id, $user_id)){
					$root['error'] = "您已经被踢出,不能再加入";
					$root['status'] = 0;
					ajax_return($root);
				}

			}else{
				$sql = "select v.city,v.live_in,v.user_id,v.group_id,u.nick_name,u.head_image,v.live_image,v.video_type from ".DB_PREFIX."video v left join ".DB_PREFIX."user u on u.id = v.user_id where  v.id='".$room_id."'";
				$video = $GLOBALS['db']->getRow($sql);

				if (!$video){
					$sql = "select v.city,v.live_in,v.user_id,v.group_id as group_id,u.nick_name,u.head_image,v.live_image,v.video_type from ".DB_PREFIX."video_history v left join ".DB_PREFIX."user u on u.id = v.user_id where v.id='".$room_id."'";
					$video = $GLOBALS['db']->getRow($sql);
				}
			}

			if ($video){
				$m_config =  load_auto_cache("m_config");//手机端配置

				if ($video['live_in'] == 1){
					$root['room_id'] = $room_id;
					$root['live_in'] = 1;//正在直播
					$root['user_id'] = $video['user_id'];
					$root['group_id'] = $video['group_id'];
					$root['video_type'] =$video['video_type'];
					if($video['live_image']==''){
						$root['live_image'] = get_spec_image($video['head_image']);
						$root['head_image'] = get_spec_image($video['head_image']);
					}else{
						$root['live_image'] = get_spec_image($video['live_image']);
						$root['head_image'] = get_spec_image($video['head_image'],150,150);
					}

					$root['content'] = $video['nick_name']."(".$m_config['account_name'].$video['user_id'].") 正在".$video['city']."直播";
				}else{
					$root['room_id'] = $room_id;
					$root['live_in'] = 0;//直播结束
					$root['user_id'] = $video['user_id'];
					$root['group_id'] = $video['group_id'];
					$root['content'] = $video['nick_name']."(".$m_config['account_name'].$video['user_id'].") 已经直播结束，进入主页查看更多回放？";
				}
				$root['error'] = "";
				$root['status'] = 1;
			}else{
				if ($private_key != ''){
					$root['error'] = "无效的直播房间或直播已结束";
					$root['status'] = 0;
				}else{
					$root['error'] = "无效的直播房间".$room_id;
					$root['status'] = 0;
				}
			}
		}

		ajax_return($root);
	}

	//推送通知,私聊用户
	public function private_push_user(){

		if(!$GLOBALS['user_info']){
			$return['error'] = "用户未登陆,请先登陆.";
			$return['status'] =0;
			$return['user_login_status'] = 0;//有这个参数： user_login_status = 0 时，表示服务端未登陆、要求登陆，操作
		}else{
			$user_id = intval($GLOBALS['user_info']['id']);
			$video_id = intval($_REQUEST['room_id']);

			$user_ids = strim($_REQUEST['user_ids']);//字符串类型的私聊好友id 23,123,3455 以英文逗号分割的字符串

			//主播自己或管理员，可以：邀请人员

			$sql = "select id,city,user_id from ".DB_PREFIX."video where room_type = 1 and live_in = 1 and id =".$video_id;
			$video = $GLOBALS['db']->getRow($sql);

			if ($video && $video['user_id'] != $user_id){
				//判断是否管理员
				$sql = "select id from ".DB_PREFIX."user_admin where podcast_id = ".$video['user_id']." and user_id =".$user_id;
				if (intval($GLOBALS['db']->getRow($sql)) == 0){
					//非管理员
					unset($video);
				}
			}


			if ($video){

				//将选中的：私聊 数据添加到数据库中
				$user_list = explode(',',$user_ids);

				if (count($user_list) > 500){
					$return['status'] =0;
					$return['error'] ='一次添加,不能大于500个用户';
					ajax_return($return);
				}else if (count($user_list) > 0 && count($user_list) <= 500){
					foreach ( $user_list as $k => $v )
					{

						/*
						$sql = "select id from ".DB_PREFIX."video_private where user_id = ".$v. " and video_id =".$video_id;
						$video_private_id = intval($GLOBALS['db']->getOne($sql));
						if ($video_private_id == 0){
							$video_private =array();
							$video_private['video_id'] = $video_id;
							$video_private['user_id'] = $v;
							$video_private['status'] = 1;
							//$video_private['ErrorInfo'] = strim($_REQUEST['user_ids']);
							$GLOBALS['db']->autoExecute(DB_PREFIX."video_private", $video_private,'INSERT');
						}else{
							$sql = "update ".DB_PREFIX."video_private set status = 1 where id = ".$video_private_id;
							$GLOBALS['db']->query($sql);
						}
						*/

						fanwe_require(APP_ROOT_PATH.'mapi/lib/redis/VideoPrivateRedisService.php');
						$video_private_redis = new VideoPrivateRedisService();
						$video_private_redis->push_user($video_id, $v);

					}

					fanwe_require(APP_ROOT_PATH.'system/schedule/android_list_schedule.php');
					fanwe_require(APP_ROOT_PATH.'system/schedule/ios_list_schedule.php');

					//推送通知：
					//推送消息文本
					$content ="你的好友：".$GLOBALS['user_info']['nick_name']."正在".$video['city']."直播，邀请你一起";
					$room_id = $video_id;


					$code_sql = "select u.apns_code,u.device_type  from ".DB_PREFIX."user u where u.device_type in (1,2) and u.id in (".$user_ids.")";
					$code_list = $GLOBALS['db']->getAll($code_sql);
					//得到机器码列表
					$apns_app_code_list = array();
					$apns_ios_code_list = array();

					$j=$i=0;
					foreach($code_list as $kk=>$vv){
						//获取android机器码
						if($vv['device_type']==1){
							$apns_app_code_list[$i] = $vv['apns_code'];
							$i++;
						}

						//获取IOS机器码
						if($vv['device_type']==2){
							$apns_ios_code_list[$j] = $vv['apns_code'];
							$j++;
						}
					}

					//安卓推送信息
					if(count($apns_app_code_list)>0){
						$AndroidList = new android_list_schedule();
						$data = array(
								'dest' =>implode(",",$apns_app_code_list),
								'content' =>$content,
								'room_id'=>$room_id,
								'type'=>0,
						);
						$ret_android =$AndroidList->exec($data);
					}

					//ios 推送信息
					if(count($apns_ios_code_list)>0){
						$IosList = new ios_list_schedule();
						$ios_data = array(
								'dest' =>implode(",",$apns_ios_code_list),
								'content' =>$content,
								'room_id'=>$room_id,
								'type'=>0,
						);
						$ret_ios = $IosList->exec($ios_data);
					}

					$return['status'] =1;
					$return['error'] ='已发邀请通知';//.print_r($ret_android,1).print_r($ret_ios,1).$code_sql;
				}
			}else{
				$return['status'] =0;
				$return['error'] ='邀请通知失败！';
			}
		}

		ajax_return($return);
	}

	//踢人私聊用户
	public function private_drop_user(){

		$return['status'] = 1;
		$return['error'] ='操作完成';

		if(!$GLOBALS['user_info']){
			$return['error'] = "用户未登陆,请先登陆.";
			$return['status'] =0;
			$return['user_login_status'] = 0;//有这个参数： user_login_status = 0 时，表示服务端未登陆、要求登陆，操作
		}else{
			$user_id = intval($GLOBALS['user_info']['id']);
			$video_id = intval($_REQUEST['room_id']);

			$user_ids = strim($_REQUEST['user_ids']);//字符串类型的私聊好友id 23,123,3455 以英文逗号分割的字符串 只有私聊时才需要上传这个参数

			$sql = "select id,user_id from ".DB_PREFIX."video where room_type = 1 and live_in = 1 and id =".$video_id;
			$video = $GLOBALS['db']->getRow($sql);

			//主播自己或管理员，可以：踢人
			if ($video && $video['user_id'] != $user_id){
				//判断是否管理员
				$sql = "select id from ".DB_PREFIX."user_admin where podcast_id = ".$video['user_id']." and user_id =".$user_id;
				if (intval($GLOBALS['db']->getRow($sql)) == 0){
					//非管理员
					unset($video);
				}
			}


			if ($video){
				//将选中的：私聊 数据添加到数据库中
				$user_ids = explode(',',$user_ids);
				if (count($user_ids) > 0){

					$ext = array();
					$ext['type'] = 17;
					$ext['room_id'] = $video_id;
					$ext['desc'] = '您被踢出房间';
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
					fanwe_require(APP_ROOT_PATH.'system/tim/TimApi.php');
					$api = createTimAPI();

					foreach ( $user_ids as $k => $v )
					{
						$ret = $api->openim_send_msg2((string)$user_id, $v, $msg_content);

						/*
						$video_private =array();
						$video_private['ActionStatus'] = $ret['ActionStatus'];
						$video_private['ErrorCode'] = $ret['ErrorCode'];
						$video_private['ErrorInfo'] = $ret['ErrorInfo'];
						$video_private['status'] = 0;
						$GLOBALS['db']->autoExecute(DB_PREFIX."video_private", $video_private,'UPDATE'," user_id = ".$v. " and video_id =".$video_id);
						*/

						fanwe_require(APP_ROOT_PATH.'mapi/lib/redis/VideoPrivateRedisService.php');
						$video_private_redis = new VideoPrivateRedisService();
						$video_private_redis->drop_user($video_id, $v);


						if ($ret['ActionStatus'] != 'OK'){
							$return['status'] =0;
							$return['error'] ='踢人失败,'.$ret['ErrorInfo'].";".$ret['ErrorCode'];
						}
					}
				}
			}else{
				$return['status'] =0;
				$return['error'] ='踢人失败！';
			}
		}

		ajax_return($return);
	}


	/**
	 * 已经在私密房间中的用户列表,主要用过：踢出聊天室
	 */
	public function private_room_friends(){

		$root = array();
		$root['status'] = 1;
		//$GLOBALS['user_info']['id'] = 278;
		if(!$GLOBALS['user_info']){
			$root['error'] = "用户未登陆,请先登陆.";
			$root['status'] = 0;
			$root['user_login_status'] = 0;//有这个参数： user_login_status = 0 时，表示服务端未登陆、要求登陆，操作
		}else{

			$user_id = intval($GLOBALS['user_info']['id']);//id
			$video_id = intval($_REQUEST['room_id']);

			$page = intval($_REQUEST['p']);//取第几页数据

			if($page==0){
				$page = 1;
			}

			fanwe_require(APP_ROOT_PATH.'mapi/lib/redis/VideoRedisService.php');
			$video_redis = new VideoRedisService();
			$video_data = $video_redis->getRow_db($video_id,array('group_id','user_id'));

			fanwe_require(APP_ROOT_PATH.'mapi/lib/redis/VideoViewerRedisService.php');
			$video_viewer_redis = new VideoViewerRedisService();
			$group_id = $video_data['group_id'];//聊天群id

			if($group_id){
				$root = $video_viewer_redis->get_viewer_list($group_id,$page);
                if($root['list']){
                    $list = $root['list'];
                    $sql = "select user_id from ".DB_PREFIX."user_admin where podcast_id = ".$video_data['user_id'];
                    $user_admin_list = $GLOBALS['db']->getAll($sql,true,true);
                    $user_admin_list = array_column($user_admin_list,'user_id');
                    foreach($list as $k=>$v){
                        if(in_array($v['user_id'],$user_admin_list) && $video_data['user_id']!=$user_id){
                            unset($list[$k]);
                        }
                    }
                    $list = array_values($list);
                    $root['list'] = $list;
                }
			}else{
				$root = array(
						'list'=>array(),
						'has_next'=>0,
						'page'=>1,
						'status'=>1
				);
			}

			/*
			$page_size=20;
			$limit = (($page-1)*$page_size).",".$page_size;

			//$sql = "select group_id from ".DB_PREFIX."video where room_type = 1 and live_in = 0 and id =".$video_id;
			$sql = "select group_id from ".DB_PREFIX."video where id =".$video_id;
			$video = $GLOBALS['db']->getRow($sql);
			$group_id = $video['group_id'];

			$root['group_id'] = $group_id;
			$root['sql1'] = $sql;
			$sql = "SELECT v.user_id,u.nick_name,u.signature,u.sex,u.head_image,u.user_level FROM ".DB_PREFIX."video_viewer v LEFT JOIN ".DB_PREFIX."user u on u.id = v.user_id WHERE v.end_time = 0 AND v.group_id = '".$group_id."' limit ".$limit;


			$root['sql2'] = $sql;
			$root['sql'] = $sql;

//			fanwe_require(APP_ROOT_PATH.'mapi/lib/redis/UserFollwRedisService.php');
//			$user_redis = new UserFollwRedisService($user_id);
//			$root = $user_redis->get_private_user($page,$page_size);
			$list = $GLOBALS['db']->getAll($sql);
			$root['list'] = $list;


			if($page==0){
				$root['has_next'] = 0;
			}else{
				if (count($list) == $page_size)
					$root['has_next'] = 1;
				else
					$root['has_next'] = 0;
			}

			$root['page'] = $page;//
			*/

		}
		ajax_return($root);
	}

	/**
	 * 点赞数
	 */
	public function like()
	{

		//$GLOBALS['user_info']['id'] = 270;
		$root = array();

		if(!$GLOBALS['user_info']){
			$root['error'] = "用户未登陆,请先登陆.";// es_session::id();
			$root['status'] = 0;
			$root['user_login_status'] = 0;//有这个参数： user_login_status = 0 时，表示服务端未登陆、要求登陆，操作
		}else{

			$user_id = $GLOBALS['user_info']['id'];
			$room_id = intval($_REQUEST['room_id']);

			fanwe_require(APP_ROOT_PATH.'mapi/lib/redis/VideoRedisService.php');
			$video_redis = new VideoRedisService();
			$video_redis->like($room_id,$user_id);


			$root['status'] = 1;
		}

		ajax_return($root);
	}
	//PC端全部直播接口
	public function video_list(){
		$root = array();
		$page=intval($_REQUEST('p'));
		$cate_id=intval($_REQUEST('cate_id'));
		$jump_type=($_REQUEST('jump_type'));
		$page_size = 20;//分页数量
		if($page==0||$page==''){
			$page = 1;
		}
		$param=array('page'=>$page,'page_size'=>$page_size,'cate_id'=>$cate_id);
		$root['cate_top'] = load_auto_cache("cate_top");
		$info = load_auto_cache("all_video",$param);
		$root['list'] = $info['list'];
		$root['status'] = 1;
		$root['has_next'] = $info['has_next'];
		$root['page'] = $info['page'];
		$root['jump_type'] = $jump_type;
		ajax_return($root);

	}
	//PC端话题列表
	public function search_video_cate(){
		$root=array();
		$page = intval($_REQUEST['p']);//取第几页数据
		$title = strim($_REQUEST['title']);
		if($page==0||$page==''){
			$page = 1;
		}
		$page_size=50;
		$limit = (($page-1)*$page_size).",".$page_size;
		if ($title){
			$sql = "select vc.id as cate_id,vc.title,vc.num from ".DB_PREFIX."video_cate as vc
						where vc.is_effect = 1 and vc.title like '%".$title."%' order by vc.sort desc, vc.num desc limit ".$limit;
			$rs_count = $GLOBALS['db']->getAll("select count(id) from ".DB_PREFIX."video_cate where vc.is_effect = 1 and vc.title like '%".$title."%'");
		}else{
			$rs_count = $GLOBALS['db']->getAll("select count(id) from ".DB_PREFIX."video_cate where vc.is_effect = 1");
			$sql = "select vc.id as cate_id,vc.title,vc.num from ".DB_PREFIX."video_cate as vc
						where vc.is_effect = 1  order by vc.sort desc, vc.num desc limit ".$limit;
		}
		//查询话题列表,修改成 从只读数据库中取,但不是高效做法;主并发时,可以加入阿里云的搜索服务
		//https://www.aliyun.com/product/opensearch?spm=5176.8142029.388261.62.tgDxhe
		$list = $GLOBALS['db']->getAll($sql,true,true);
		foreach($list as $k=>$v){
			$list[$k]['title'] ="#".$v['title']."#";
		}
		if($page==0){
			$root['has_next'] = 0;
		}else{
			if ($rs_count >= $page*$page_size)
				$root['has_next'] = 1;
			else
				$root['has_next'] = 0;
		}

		$root['page'] = $page;//
		$root['list'] =$list;
		$root['status'] =1;

		ajax_return($root);
	}
	//我的关注
	public function focus_video(){
		$root=array();
		$page = intval($_REQUEST['p']);//取第几页数据
		$cateid=intval($_REQUEST['cate_id']);
		if(!$GLOBALS['user_info']){
			$root['error'] = "用户未登陆,请先登陆.";
			$root['status'] = 0;
			$root['user_login_status'] = 0;//有这个参数： user_login_status = 0 时，表示服务端未登陆、要求登陆，操作
		}else{
			//关注
			$user_id = intval($GLOBALS['user_info']['id']);//登录用户id
			fanwe_require(APP_ROOT_PATH.'mapi/lib/redis/UserFollwRedisService.php');
			$userfollw_redis = new UserFollwRedisService($user_id);
			$user_list = $userfollw_redis->following();
			//私密直播  video_private,私密直播结束后， 本表会清空
			fanwe_require(APP_ROOT_PATH.'mapi/lib/redis/VideoPrivateRedisService.php');
			$video_private_redis = new VideoPrivateRedisService();
			$private_list = $video_private_redis->get_video_list($user_id);

			/*
            $sql = "select video_id from ".DB_PREFIX."video_private where status = 1 and user_id = ".$user_id;
            $private_list = $GLOBALS['db']->getAll($sql,true,true);
            */

			$list = array();

			if($page==0||$page==''){
				$page=1;
			}

			$page_size=20;

			$param=array('page'=>$page,'page_size'=>$page_size,'has_private'=>1,'cate_id'=>$cateid);
			if(sizeof($private_list) || sizeof($user_list)){
				$info = load_auto_cache("foucs_video",$param);
				$list_all=$info['list'];
				foreach($list_all as $k=>$v){
					if (($v['room_type'] == 1 && in_array($v['room_id'], $private_list)) || ($v['room_type'] == 3 && in_array($v['user_id'], $user_list))) {
						$list[] = $v;
					}
				}
			}

			$root['list'] = $list;
			$root['cate_top'] = load_auto_cache("cate_top");
//			$playback = load_auto_cache("playback_list",array('user_id'=>$user_id));
//
//			$root['playback'] = $playback;
			$root['status'] = 1;
			$root['has_next'] = $info['has_next'];
			$root['page'] = $info['page'];
		}
		ajax_return($root);
	}
	//主播调用列表
	public function p_viewer(){
		$root = array();
		$room_id = strim($_REQUEST['room_id']);//聊天群id
		$group_id = strim($_REQUEST['group_id']);//聊天群id
		$page = intval($_REQUEST['p']);//取第几页数据
		if($room_id){
			$sql = "select is_live_pay from ".DB_PREFIX."video where id = ".$room_id;
        	$is_live_pay = $GLOBALS['db']->getOne($sql,true,true);
		}else{
			$root['error'] = "room_id不存在";
			$root['status'] = 0;
			ajax_return($root);
		}
		$root = load_auto_cache("video_viewer",array('group_id'=>$group_id,'page'=>$page));

		if(intval($is_live_pay)){
			$list = array();
			$live_pay_log_list = array();
			//分页
            if (intval($_REQUEST['page_size'])) {
                $page_size = intval($_REQUEST['page_size']);
            } else {
                $page_size = 100;//分页数量
            }
            if ($page == 0) {
                $page = 1;
            }
            $limit = (($page - 1) * $page_size) . "," . $page_size;
			
			$live_pay_log = $GLOBALS['db']->getAll("select from_user_id as user_id from ".DB_PREFIX."live_pay_log where video_id =".$room_id." limit".$limit);
			foreach($live_pay_log as $v){
				$live_pay_log_list[$v['user_id']] = $v['user_id'];
			}
			
			if($live_pay_log_list){
				foreach($root['list'] as $k=>$v){
					if($live_pay_log_list[$v['user_id']]==$v['user_id']){
						$list[$k] = $v;
					}
				}
				$rs_count = intval(count($list));
				if ($page == 0) {
                    $list['page'] = 0; 
                    $list['has_next'] = 0;
                }else{
                    $has_next = ($rs_count > $page * $page_size) ? '1' : '0';
                    $list['page'] = $page;
                    $list['has_next'] = $has_next;
                }
                $list['status'] = 1;				
			}else{
				$list = array(
						'list'=>array(),
						'has_next'=>0,
						'page'=>1,
						'status'=>1
					);
			}
			$root['list'] = $list;
			$root['watch_number'] =  intval($rs_count);
		}
		ajax_return($root);
	}
	
	/**
	 * 金山连麦 鉴权 ks_auth()
	 * https://github.com/ksvc/KSYRTCLive_Android/wiki/auth
	 * 
	 * stringToSign = "GET\n" + str(expiretime) + "\n"
	 * strResource = "nonce=" + nonce + "&uid=" + uid + "&uniqname=" + UNIQNAME
	 * stringToSign = stringToSign + strResource
	 * 
	 * String authString = "accesskey=D8uDWZ88ZK8/eZHmRm&expire=1470212584&nonce=wybR8MEyhOpALCGh7xg17R5ejDrtk0&public=0&uniqname=apptest&signature=uFByPHHUKbszXR2t5NAuoUgTw%3D&uid＝1000";   

	 */
	public function ks_auth(){
		$root = array();
		$user_id = intval($GLOBALS['user_info']['id']);
		
		//if($user_id == 0){
		//	$user_id = 'test111';
		//}
		
		if($user_id == 0){
			$root['error'] = "用户未登陆,请先登陆.".$user_id;
			$root['status'] = 0;
			$root['user_login_status'] = 0;//有这个参数： user_login_status = 0 时，表示服务端未登陆、要求登陆，操作
		}else{
			
			
			$uid = $user_id;
			
			//开发者ak/sk
			$accesskey = '8+n2Kl2Ta8ofvMM5YHjT';
			$secretkey = 'MJh86NtGhAa+KNjOonq5E0xVDym0ZuorqclugV4z';
			$uniqname = 'fanwe';
			$nonce = rand(1, 100000);
			
			//用于签名的参数，字典序排列
			//用于签名的参数，字典序排列
			$arrrsrc = array(
					'nonce'    => $nonce,
					'uid'    => $uid,
					'uniqname'    => $uniqname,
			);
			$strrsrc = http_build_query($arrrsrc);
			
			//$strrsrc = "nonce=".urlencode($nonce)."&uid=".urlencode($uid)."&uniqname=".urlencode($uniqname);
			//过期时间
			$expire = get_gmtime() + 28800 + 600;
			
			//拼接用于计算签名sign的源字符串
			$strtosign = "GET\n$expire\n$strrsrc";
			
			//计算签名
			$sign = hash_hmac('sha1', $strtosign, $secretkey, true);
			$signature = base64_encode($sign);
			
			$params  = array(
					'nonce'    => $nonce,
					'uid'    => $uid,
					'uniqname'    => $uniqname,
					
					'accesskey'    => $accesskey,
					'expire'    => $expire,
					'signature'    => $signature,
			);
			$auth_string = http_build_query($params);
			
			//$auth_string = "accesskey=".urlencode($accesskey)."&expire=".$expire."&signature=".urlencode($signature)."&".$strrsrc;
			
			
			$root['uniqname'] = $uniqname;
			$root['uid'] = $uid;
			$root['auth_string'] = $auth_string;
			$root['status'] = 1;
			
			/*
			$root['strtosign'] = $strtosign;
			$root['signature'] = $signature;
			
			
			
			$root['nonce'] = $nonce;
			$root['expire'] = $expire;
			$root['accesskey'] = $accesskey;
			$root['secretkey'] = $secretkey;
			*/
			
		}
		ajax_return($root);
	}
	//强制升级不升级无法查看直播
	public function compel_upgrade($m_config)
	{
		$root = array('status'=>1,'error'=>'');
		$dev_type = strim($_REQUEST['sdk_type']);
		$version = strim($_REQUEST['sdk_version']);//升级版本号yyyymmddnn： 2017031502
		if ($dev_type == 'android'){
			$root['serverVersion'] = $m_config['android_version'];//android版本号
			//print_r($m_config);
			if ($version < $root['serverVersion'] && $m_config['android_filename'] != ''){
				$root['status'] = 0;
				$root['filename'] = $m_config['android_filename'];//android下载包名
				$root['android_upgrade'] = $m_config['android_upgrade'];//android版本升级内容

				$root['forced_upgrade'] = 1;//强制升级
				$root['hasfile'] = 1;
				$root['has_upgrade'] = 1;//1:可升级;0:不可升级
				$root['error'] = $m_config['forced_upgrade_tips'];//强制升级提醒
			}else{
				$root['hasfile'] = 0;
				$root['has_upgrade'] = 0;//1:可升级;0:不可升级
			}
		}else if ($dev_type == 'ios'){
			$root['serverVersion'] = $m_config['ios_version'];//IOS版本号
			if ($version < $root['serverVersion']&&$m_config['ios_down_url']!=''){
				$root['status'] = 0;
				$root['ios_down_url'] = $m_config['ios_down_url'];//ios下载地址
				$root['ios_upgrade'] = $m_config['ios_upgrade'];//ios版本升级内容
				$root['has_upgrade'] = 1;//1:可升级;0:不可升级
				$root['forced_upgrade'] = intval($m_config['ios_forced_upgrade']);//0:非强制升级;1:强制升级
				$root['error'] = $m_config['forced_upgrade_tips'];//强制升级提醒
			}else{
				$root['has_upgrade'] = 0;//1:可升级;0:不可升级
			}
		}else{
			$root['hasfile'] = 0;
			$root['has_upgrade'] = 0;//1:可升级;0:不可升级
		}
		return $root;
	}
	
	/**
	 * 声网授权
	 * channe_name: 频道名称,默认是：房间号
	 * @return multitype:string number
	 */
	public function agora_auth(){
		$root = array();
		$user_id = intval($GLOBALS['user_info']['id']);
		if($user_id == 0){
			$root['error'] = "用户未登陆,请先登陆.".$user_id;
			$root['status'] = 0;
			$root['user_login_status'] = 0;//有这个参数： user_login_status = 0 时，表示服务端未登陆、要求登陆，操作
		}else{
			fanwe_require(APP_ROOT_PATH.'mapi/lib/core/DynamicKey5.php');
			$m_config =  load_auto_cache("m_config");//初始化手机端配置
			
			$channe_name = strim($_REQUEST['channe_name']);//频道名称,默认是：房间号

			
			$appID = strim($m_config['agora_app_id']);
			$appCertificate = strim($m_config['agora_app_certificate']);
			$ts = get_gmtime();//Dynamic Key 生成时的时间戳，自1970.1.1开始到当前时间的秒数。授权该 Dynamic Key 在生成后的 5 分钟内可以访问 Agora 服务，如果 5 分钟内没有访问，则该 Dynamic Key 无法再使用。
			$expiredTs = 0;//用户使用 Agora 服务终止的时间戳，在此时间之后，将不能继续使用 Agora 服务（比如进行的通话会被强制终止）；如果对终止时间没有限制，设置为 0。设置服务到期时间并不意味着Dynamic Key 失效，而仅仅用于限制用户使用当前服务的时间。		
			$randomInt = rand(1, 1000000);//随机数
			
			if (!empty($appCertificate)){
				//setClientRole
				$role_key = generateInChannelPermissionKey($appID, $appCertificate, $channe_name, $ts, $randomInt, $user_id, $expiredTs, "0");
				$channe_key = generateMediaChannelKey($appID, $appCertificate, $channe_name, $ts, $randomInt, $user_id, $expiredTs);
	
				//setClientRole
				$root['role_key'] = $role_key;
				//joinChannel
				$root['channe_key'] = $channe_key;
			}else{
				$root['role_key'] = '';
				$root['channe_key'] = '';
			}
			
			$root['agora_anchor_resolution'] = intval($m_config['agora_anchor_resolution']);//大主播分辨率 (0,1,2,3)240*424,360*640,480*848,720*1280
			$root['agora_audience_resolution'] = intval($m_config['agora_anchor_resolution']);//小主播分辨率 (0,1,2,3)180*320,240*424,360*640,480*848
		}
		
		return $root;
	}
}


