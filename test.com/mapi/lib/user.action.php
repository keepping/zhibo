<?php
// +----------------------------------------------------------------------
// | Fanwe 方维p2p借贷系统
// +----------------------------------------------------------------------
// | Copyright (c) 2011 http://www.fanwe.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: 云淡风轻(88522820@qq.com)
// +----------------------------------------------------------------------

class userModule  extends baseModule
{
	
	public function usersig()
	{
		/**
		 * 获取用户签名
		 **/
		
		//$GLOBALS['user_info']['id'] = 320;
		$root = array();
		
		if(!$GLOBALS['user_info']){
			$root['error'] = "用户未登陆,请先登陆.";// es_session::id();
			//$root['es_session'] = es_session::id();
			//$root['user_info'] = json_encode(es_session::get("user_info"));
			$root['status'] = 0;
			$root['user_login_status'] = 0;//有这个参数： user_login_status = 0 时，表示服务端未登陆、要求登陆，操作
		}else{
		
			$identifier = $GLOBALS['user_info']['id'];// $_REQUEST['identifier'];
			
			$root = load_auto_cache("usersig", array("id"=>$identifier));
			
			/*
			$usersig = $GLOBALS['db']->getOne("select usersig from ".DB_PREFIX."user where id=".$identifier." and expiry_after >" .NOW_TIME);
			if(!$usersig){
				fanwe_require(APP_ROOT_PATH.'system/tim/TimApi.php');
				$api = createRestAPI();
		
				$private_pem_path = APP_ROOT_PATH."system/tim/ec_key.pem";				
				$signature = get_signature();
				
				$ret = $api->generate_user_sig($identifier, '86400', $private_pem_path, $signature);
				$expiry_after = NOW_TIME + 86400;
				//echo "update ".DB_PREFIX."user set usersig = ".$ret[0].",expiry_after=".$expiry_after." where id = ".$identifier;exit;
				$GLOBALS['db']->query("update ".DB_PREFIX."user set usersig = '".$ret[0]."',expiry_after=".$expiry_after." where id = ".$identifier);
				
			}else{
				$ret[0] =$usersig;
			}
			
			if($ret == null || strstr($ret[0], "failed")){
				$root['error'] = "获取usrsig失败, 请确保TimRestApiConfig.json配置信息正确";
				$root['status'] = 0;
			}else{
				$root['usersig'] = $ret[0];
				$root['status'] = 1;
			}
			*/
		}
		if(intval($_REQUEST['ajax_type'])&&intval(IS_DEBUG)){
			print_r($root);echo "<hr/>";
		}
		ajax_return($root);
	}

	/**
	 * 获得用户公开信息;不传identifier时,则获得当前登陆用户的信息
	 */
    public function userinfo(){
        $root = array();

        //$GLOBALS['user_info']['id'] = 292;

        if(!$GLOBALS['user_info']){
            $root['error'] = "用户未登陆,请先登陆.";
            $root['status'] = 0;
            $root['user_login_status'] = 0;//有这个参数： user_login_status = 0 时，表示服务端未登陆、要求登陆，操作
        }else{
            $user_id = intval($GLOBALS['user_info']['id']);

            $podcast_id = intval($_REQUEST['podcast_id']);//主播id，fanwe_user.id
            $to_user_id = intval($_REQUEST['to_user_id']);//需要查看的用户id
            if ($to_user_id == 0){
                $to_user_id = $user_id;
            }


            $root = getuserinfo($user_id,$podcast_id,$to_user_id,$_REQUEST);
            $m_config =  load_auto_cache("m_config");//初始化手机端配置
            $root['init_version'] = intval($m_config['init_version']);//手机端配置版本号
            if($m_config['name_limit']==1){


                    $user = $GLOBALS['db']->getRow("select nick_name from ".DB_PREFIX."user where id = ".$user_id);
                    $nick_name=$user['nick_name'];

                //进入个人中心过滤铭感词汇
                $limit_sql =$GLOBALS['db']->getCol("SELECT name FROM ".DB_PREFIX."limit_name");
                if($GLOBALS['db']->getCol("SELECT name FROM ".DB_PREFIX."limit_name WHERE '$nick_name' like concat('%',name,'%')")){
                    $nick_name=str_replace($limit_sql,'*',$nick_name);
                }

                //判断用户名如果被过滤后为空,格式则变更为： 账号+ID
                if($nick_name==''){
                    $nick_name=('账号'.$user_id);
                }
                //更新数据库
                $sql = "update ".DB_PREFIX."user set nick_name = '$nick_name' where id=".$user_id;
                $GLOBALS['db']->query($sql);
                //更新redis
                user_deal_to_reids(array($user_id));

            }

            $root['status'] = 1;
        }

        ajax_return($root);
    }
	

	
	/**
	 * 关注某个用户
	 */
	public function follow(){
		$root = array('status'=>1,'error'=>'');
	
		//$GLOBALS['user_info']['id'] = 269;
		if(!$GLOBALS['user_info']){
			$root['error'] = "用户未登陆,请先登陆.";
			$root['status'] = 0;
			$root['user_login_status'] = 0;//有这个参数： user_login_status = 0 时，表示服务端未登陆、要求登陆，操作
		}else{
			//
            $user_level = $GLOBALS['db']->getOne("select user_level from ".DB_PREFIX."user where id = ".$GLOBALS['user_info']['id'],true,true);
            $m_config =  load_auto_cache("m_config");//初始化手机端配置
            $show_follow_msg = intval($m_config['show_follow_msg']);//是否现发送关注提示信息 0:不显示; 1:显示
            $show_follow_msg_lv = intval($m_config['show_follow_msg_lv']);//关注提示显示的等级

			$user_id = intval($GLOBALS['user_info']['id']);//当前用户;
			$room_id = intval($_REQUEST['room_id']);//room_id 当前直播房间号，用来：计算权重的;
			
			$to_user_id = intval($_REQUEST['to_user_id']);//被关注或取消关注的用户

            $to_user_id = $GLOBALS['db']->getOne("select id from ".DB_PREFIX."user where id = ".$to_user_id,true,true);
			if(!intval($to_user_id)) {
				$root['error'] = "关注的用户不存在！";
				$root['status'] = 0;
			}else if($user_id!=$to_user_id){
				$root = redis_set_follow($user_id,$to_user_id,false,$room_id);
               	clear_auto_cache("playback_list",array("user_id"=>$user_id));

                if($show_follow_msg && ($user_level>=$show_follow_msg_lv)){
                    $root['follow_msg'] = $GLOBALS['user_info']['nick_name']."关注了主播";
                }else {
                    $root['follow_msg'] = '';
                }
				/*
				fanwe_require(APP_ROOT_PATH.'mapi/lib/redis/UserFollwRedisService.php');
				$user_redis = new UserFollwRedisService($user_id);
				//查看关注人数
				$follow_count = $user_redis->follow_count();
				//粉丝人数
				$follow_by_count = $user_redis->follower_count();
				//update_follow($user_id,$follow_count,$follow_by_count);
				
				//$root = set_follow($user_id,$to_user_id);
				//$root = redis_set_follow($user_id,$to_user_id);
				 */
				if (defined('OPEN_MISSION') && OPEN_MISSION) {
				    require_once APP_ROOT_PATH . 'mapi/lib/core/Model.class.php';
				    Model::$lib = dirname(__FILE__);
				    Model::build('mission')->incProgress($user_id,4);
				}
			}else{
				$root['error'] = "不能关注自己！";
				$root['status'] = 0;
			}
			

		}
		ajax_return($root);
	}
	
	
	/**
	 * 禁言,只有：主群或群管理员才能设置
	 */
	public function forbid_send_msg(){

		$root = array();
		$root['status'] = 0;
        $root['is_forbid'] = 0;
		//$GLOBALS['user_info']['id'] = 1;
		
		if(!$GLOBALS['user_info']){
			$root['error'] = "用户未登陆,请先登陆.";
			$root['status'] = 0;
			$root['user_login_status'] = 0;//有这个参数： user_login_status = 0 时，表示服务端未登陆、要求登陆，操作
		}else{
				
			$user_id = intval($GLOBALS['user_info']['id']);//
			$to_user_id = strim($_REQUEST['to_user_id']);//被禁言的用户id
			$group_id = strim($_REQUEST['group_id']);//群组ID
			$second = intval($_REQUEST['second']);//禁言时间，单位为秒; 为0时表示取消禁言

            $is_nospeaking = $GLOBALS['db']->getOne("select is_nospeaking from ".DB_PREFIX."user where id = ".$to_user_id,true,true);
            if(intval($is_nospeaking)==1){
                $root['error'] = "该用户已被im全局禁言.";
                ajax_return($root);
            }
		
			$second = 10000;
			
			fanwe_require(APP_ROOT_PATH.'mapi/lib/redis/VideoRedisService.php');
			$video_redis = new VideoRedisService();
			$video = $video_redis->getRow_db_ByGroupId($group_id,array('id','user_id'));
			//优化
			$forbid_info = $video_redis->has_forbid_msg($group_id,$to_user_id);//判断某个用户是否被禁言(被禁言返回：true; 未被禁言返回：false)
			if($forbid_info && intval($forbid_info)>NOW_TIME){
				$second = 0;
			}else{
				$second = 10000;
                $video_redis->unset_forbid_msg($group_id,$to_user_id);
			}
			
			//$sql = "select id,user_id,group_id from ".DB_PREFIX."video where group_id = '".$group_id."'";
			//$video = $GLOBALS['db']->getRow($sql,true,true);
			$podcast_id = intval($video['user_id']);
			$room_id = intval($video['id']);
			
			
			//查看自己
			if ($to_user_id == $user_id){
				$root['error'] = "不能自己给自己禁言";
			}else{
				$allow = false;
				//主播查看
				if ($podcast_id == $user_id){
					$allow = true;//主播 有权限禁言
				}else{
					$sql = "select count(id) as num from ".DB_PREFIX."user_admin where podcast_id = ".$podcast_id." and user_id = ".$user_id;
					if ($GLOBALS['db']->getOne($sql,true,true) > 0){
						$allow = true;//管理员 有权限禁言按钮
					}
				}
		
			}

			if ($allow){
				$sql = "select is_robot,nick_name from ".DB_PREFIX."user where id = '".$to_user_id."'";
				$user = $GLOBALS['db']->getRow($sql,true,true);
				$nick_name = $user['nick_name'];

                fanwe_require(APP_ROOT_PATH.'system/tim/TimApi.php');
                $api = createTimAPI();
                //设置：禁言(second>0)，取消禁言(second = 0)
                if($user['is_robot'] != 1){
                    $ret = $api->group_forbid_send_msg($group_id,(string)$to_user_id,$second);
                }
                if ($ret['ActionStatus'] == 'OK' || $user['is_robot'] == 1){

                    //$ret = $api->get_group_shutted_uin($group_id);

                    if ($second > 0){
                        /*
                        $forbid_send_msg = array();
                        $forbid_send_msg['group_id'] = $group_id;
                        $forbid_send_msg['user_id'] = $to_user_id;
                        $forbid_send_msg['shut_up_time'] = NOW_TIME + $second;
                        $GLOBALS['db']->autoExecute(DB_PREFIX."video_forbid_send_msg", $forbid_send_msg,"INSERT");
                        */
                        //禁言到期时间
                        $shutup_time = NOW_TIME+$second;
                        $video_redis->set_forbid_msg($group_id,$to_user_id,$shutup_time);
                        $msg = $nick_name. " 被禁言";//.print_r($ret,1).';user_id:'.$user_id.";second:".$second.";group_id:".$group_id;
                        $root['is_forbid'] = 1;

                    }else{
                        $msg = $nick_name. " 取消禁言";
                        $video_redis->unset_forbid_msg($group_id,$to_user_id);
                        $root['is_forbid'] = 0;
                        //$sql = "delete from ".DB_PREFIX."video_forbid_send_msg where group_id='".$group_id."' and user_id = '".$to_user_id."'";
                        //$GLOBALS['db']->query($sql);
                    }


                    $root['status'] = 1;
                }else{
                    $root['status'] = 0;
                    $root['error'] = $ret['ErrorInfo'].":".$ret['ErrorCode'];
                }

				if ($root['status'] == 1){
					if (!$api){
						fanwe_require(APP_ROOT_PATH.'system/tim/TimApi.php');
						$api = createTimAPI();
					}
					
					//群播一个：禁言通知
					$ext = array();
					$ext['type'] = 4; //0:普通消息;1:礼物;2:弹幕消息;3:主播退出;4:禁言;5:观众进入房间；6：观众退出房间；7:直播结束
					$ext['room_id'] = $room_id;//直播ID 也是room_id;只有与当前房间相同时，收到消息才响应
					$ext['fonts_color'] = '';//字体颜色
					$ext['desc'] = $msg;//禁言通知消息;
					$ext['desc2'] = $msg;//禁言通知消息;
					
					//消息发送者
					$sender = array();
					$sender['user_id'] = $GLOBALS['user_info']['id'];//发送人昵称
					$sender['nick_name'] = $GLOBALS['user_info']['nick_name'];//发送人昵称
					$sender['head_image'] = $GLOBALS['user_info']['head_image'];//发送人头像
					$sender['user_level'] = $GLOBALS['user_info']['user_level'];//用户等级
					
					$ext['sender'] = $sender;
					
					
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
					
					$ret = $api->group_send_group_msg2($GLOBALS['user_info']['id'], $group_id, $msg_content);
				}
				
				
			}else{
				$root['error'] = "无禁言权限";
			}
		}
		ajax_return($root);

		
	}
	
	/**
	 * 设置/取消 管理员
	 */
	public function set_admin(){
		
		$root = array();
		$root['status'] = 1;
		
		if(!$GLOBALS['user_info']){
			$root['error'] = "用户未登陆,请先登陆.";
			$root['status'] = 0;
			$root['user_login_status'] = 0;//有这个参数： user_login_status = 0 时，表示服务端未登陆、要求登陆，操作
		}else{
			//
			$user_id = intval($GLOBALS['user_info']['id']);
			$to_user_id = intval($_REQUEST['to_user_id']);//被关注或取消 管理员用户
	
			$room_id = strim($_REQUEST['room_id']);
			
			
			$sql = "select id from ".DB_PREFIX."user_admin where podcast_id = '".$user_id."' and user_id = ".$to_user_id;
			$user_admin_id = $GLOBALS['db']->getOne($sql);
			if ($user_admin_id > 0){
				//取消管理员操作;
				$sql = "delete from ".DB_PREFIX."user_admin where id = ".$user_admin_id;
				$GLOBALS['db']->query($sql);
	
			}else{
				$sql = "select count(id) as num from ".DB_PREFIX."user_admin where podcast_id = ".$GLOBALS['user_info']['id'];
				if ($GLOBALS['db']->getOne($sql) < 5){
					//设置管理员操作;
					$user_admin = array();
					$user_admin['podcast_id'] = $GLOBALS['user_info']['id'];
					$user_admin['user_id'] = $to_user_id;
					$user_admin['create_time'] = NOW_TIME;
					$GLOBALS['db']->autoExecute(DB_PREFIX."user_admin", $user_admin,"INSERT");	
				}else{
					$root['error'] = "已经超过5位管理员,不能再设置新的管理员";
					$root['status'] = 0;
				}
			}
			
			$sql = "select id from ".DB_PREFIX."user_admin where podcast_id = '".$user_id."' and user_id = ".$to_user_id;
			if ($GLOBALS['db']->getOne($sql) > 0){
				$root['has_admin'] = 1;//0:非管理员;1:是管理员
			}else{
				$root['has_admin'] = 0;
			}
			
			
			if ($root['status'] ==1 && $room_id != ''){
			
				$sql = "select nick_name from ".DB_PREFIX."user where id = '".$to_user_id."'";
				if ($root['has_admin'] == 1){
					$msg = $GLOBALS['db']->getOne($sql). " 被设置为管理员";
				}else{
					$msg = $GLOBALS['db']->getOne($sql). " 管理员被取消";
				}
			
			
				fanwe_require(APP_ROOT_PATH.'system/tim/TimApi.php');
				$api = createTimAPI();
			
				//群播一个：直播消息
				$ext = array();
				$ext['type'] = 9; //0:普通消息;1:礼物;2:弹幕消息;3:主播退出;4:禁言;5:观众进入房间；6：观众退出房间；7:直播结束
				$ext['room_id'] = $room_id;//直播ID 也是room_id;只有与当前房间相同时，收到消息才响应
				$ext['fonts_color'] = '';//字体颜色
				$ext['desc'] = $msg;//禁言通知消息;
				$ext['desc2'] = $msg;//禁言通知消息;
			
				//消息发送者
				$sender = array();
				$sender['user_id'] = $GLOBALS['user_info']['id'];//发送人昵称
				$sender['nick_name'] = $GLOBALS['user_info']['nick_name'];//发送人昵称
				$sender['head_image'] = $GLOBALS['user_info']['head_image'];//发送人头像
				$sender['user_level'] = $GLOBALS['user_info']['user_level'];//用户等级
			
				$ext['sender'] = $sender;
			
			
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
			
			
				$sql = "select group_id from ".DB_PREFIX."video where id = '".$room_id."'";
				$group_id = $GLOBALS['db']->getOne($sql);
				
				$ret = $api->group_send_group_msg2($GLOBALS['user_info']['id'], $group_id, $msg_content);
				if ($ret['ActionStatus'] == 'FAIL' && $ret['ErrorCode'] == 10002){
				//10002 系统错误，请再次尝试或联系技术客服。
				$ret = $api->group_send_group_msg2($GLOBALS['user_info']['id'], $group_id, $msg_content);
				}
			
			}
			
		}
		
		ajax_return($root);
	}

	
	/**
	 * 举报用户
	 */
	public function tipoff(){
	
		$root = array();
		$root['status'] = 1;
	
		if(!$GLOBALS['user_info']){
			$root['error'] = "用户未登陆,请先登陆.";
			$root['status'] = 0;
			$root['user_login_status'] = 0;//有这个参数： user_login_status = 0 时，表示服务端未登陆、要求登陆，操作
		}else{

		
			$to_user_id = intval($_REQUEST['to_user_id']);//被关注的举报用户ID
			$type = intval($_REQUEST['type']);
			$room_id = intval($_REQUEST['room_id']); //被举报的房间id
			
			$tipoff = array();
			$tipoff['from_user_id'] = $GLOBALS['user_info']['id'];
			$tipoff['to_user_id'] = $to_user_id;
			$tipoff['create_time'] = NOW_TIME;
			$tipoff['tipoff_type_id'] = $type;
			$tipoff['video_id'] = $room_id;
			$GLOBALS['db']->autoExecute(DB_PREFIX."tipoff", $tipoff,"INSERT");
			
			if ($room_id > 0){
				//累加举报次数
				$sql = "update ".DB_PREFIX."video set tipoff_count = tipoff_count + 1 where id =".$room_id;
				$GLOBALS['db']->query($sql);
			}
		}

		ajax_return($root);
	}

	
	
	
	/**
	 * 主播管理员列表
	 */
	public function user_admin(){
	
		$root = array();
		$root['status'] = 1;
	
		if(!$GLOBALS['user_info']){
			$root['error'] = "用户未登陆,请先登陆.";
			$root['status'] = 0;
			$root['user_login_status'] = 0;//有这个参数： user_login_status = 0 时，表示服务端未登陆、要求登陆，操作
		}else{
				
			$user_id = intval($GLOBALS['user_info']['id']);//用户ID
			
	
			$sql = "select ua.id, ua.user_id, u.nick_name,u.head_image,u.sex,u.user_level  from ".DB_PREFIX."user_admin ua left join ".DB_PREFIX."user u on u.id = ua.user_id where ua.podcast_id = ".$user_id;
			$list = $GLOBALS['db']->getAll($sql);
			foreach($list as $k=>$v){
				$list[$k]['head_image'] = get_spec_image($v['head_image']);
			}
			$root['list'] = $list;
			
			$root['max_num'] = 5;
			$root['cur_num'] = count($list);
			
			$root['status'] = 1;
		}
	
		ajax_return($root);
	}
	
	/**
	 * 关注的用户，最多不超过100个
	 */
	public function user_follow(){
	
		$root = array();
		$root['status'] = 1;
		//$GLOBALS['user_info']['id'] = 278;
		if(!$GLOBALS['user_info']){
			$root['error'] = "用户未登陆,请先登陆.";
			$root['status'] = 0;
			$root['user_login_status'] = 0;//有这个参数： user_login_status = 0 时，表示服务端未登陆、要求登陆，操作
		}else{
			
			$user_id = intval($GLOBALS['user_info']['id']);//id
			$page = intval($_REQUEST['p']);//取第几页数据

			if($page==0){
				$page = 1;
			}

			if (isset($_REQUEST['to_user_id'])){
				$to_user_id = strim($_REQUEST['to_user_id']);//被查看的用户id
			}else{
				$to_user_id = $user_id;//id
			}

			$page_size = 20;
			fanwe_require(APP_ROOT_PATH.'mapi/lib/redis/UserFollwRedisService.php');
			$user_redis = new UserFollwRedisService($user_id);
			$list = $user_redis->get_follonging_user($to_user_id,$page,$page_size);
            foreach($list as $k=>$v){
                $list[$k]['signature'] = htmlspecialchars_decode($list[$k]['signature']);
                $list[$k]['nick_name'] = htmlspecialchars_decode($list[$k]['nick_name']);
            }
			$root['list'] = $list;
			$rs_count = $user_redis->following();	
			if($page==0){
				$root['has_next'] = 0;
			}else{
				if (count($rs_count) >= $page_size)
					$root['has_next'] = 1;
				else
					$root['has_next'] = 0;
			}
			
			$root['page'] = $page;//
	
		}
		ajax_return($root);
	}
	

	/**
	 * 粉丝列表，最多不超过100个
	 */
	public function user_focus(){
	
		$root = array();
		$root['status'] = 1;
		//$GLOBALS['user_info']['id'] = 278;
		if(!$GLOBALS['user_info']){
			$root['error'] = "用户未登陆,请先登陆.";
			$root['status'] = 0;
			$root['user_login_status'] = 0;//有这个参数： user_login_status = 0 时，表示服务端未登陆、要求登陆，操作
		}else{
			$user_id = intval($GLOBALS['user_info']['id']);//id
			if (isset($_REQUEST['to_user_id'])){
				$to_user_id = strim($_REQUEST['to_user_id']);//被查看的用户id
			}else{
				$to_user_id = $user_id;
			}
			
			$page = intval($_REQUEST['p']);//取第几页数据
			if($page==0){
				$page = 1;
			}
			$page_size = 20;
			fanwe_require(APP_ROOT_PATH.'mapi/lib/redis/UserFollwRedisService.php');
			$user_redis = new UserFollwRedisService($user_id);
			$list = $user_redis->get_follonging_by_user($to_user_id,$page);
            $keys = $user_redis->following();
            foreach($list as $k=>$v){
                if($user_id>0){
                    if (in_array($v['user_id'],$keys)){
                        $list[$k]['follow_id'] = 1;
                    }else{
                        $list[$k]['follow_id'] = 0;
                    }
                }else{
                    $list[$k]['follow_id'] = 0;
                }
                $list[$k]['head_image'] = get_spec_image($v['head_image']);
                $list[$k]['nick_name'] = htmlspecialchars_decode($list[$k]['nick_name']);
            }
			$root['list'] = $list;
			
			
			if($page==0){
				$root['has_next'] = 0;
			}else{		
				if (count($list) >= $page_size)
					$root['has_next'] = 1;
				else
					$root['has_next'] = 0;
			}
			
			$root['page'] = $page;//
	
		}
		ajax_return($root);
	}
	
	
	/**
	 * 分享成功回调
	 * 
	 * type: 分享类型（
WEIXIN,WEIXIN_CIRCLE,QQ,QZONE,EMAIL,SMS,SINA
微信，微信朋友圈，qq，QQ空间，email，短信，新浪微博）
room_id:房间号
	 */
	public function share(){
	
		$root = array();
		$root['status'] = 1;
		if(!$GLOBALS['user_info']){
			$root['error'] = "用户未登陆,请先登陆.";
			$root['status'] = 0;
			$root['user_login_status'] = 0;//有这个参数： user_login_status = 0 时，表示服务端未登陆、要求登陆，操作
		}else{

			$user_id = intval($GLOBALS['user_info']['id']);//用户ID		
			$type = strtolower(strim($_REQUEST['type']));//WEIXIN,WEIXIN_CIRCLE,QQ,QZONE,EMAIL,SMS,SINA
	
			$room_id = intval($_REQUEST['room_id']);//直播ID 也是room_id
			
			//分享加印票功能
			$m_config =  load_auto_cache("m_config");//初始化手机端配置
			//每个房间每个类型可以分享一次，主播分享自己房间不获得
			if(defined('OPEN_SHARE_EXPERIENCE')&&OPEN_SHARE_EXPERIENCE==1&&intval($m_config['open_share_ticket'])){
				
				$sql = "select max(id) from ".DB_PREFIX."video_share  where video_id = ".$room_id." and user_id =".$user_id." and type = '".$type."'";
				$exist_share_id = $GLOBALS['db']->getOne($sql);

				//获取主播ID
				$sql = "select user_id as from_user_id from ".DB_PREFIX."video where id =".$room_id;
				$video_user_info = $GLOBALS['db']->getRow($sql);

                $share_id = intval($exist_share_id);
                if($share_id==0){
                    //分享表中无记录，查分享历史表
                    $sql = "select id  from ".DB_PREFIX."video_share_history  where video_id = ".$room_id." and user_id =".$user_id." and type = '".$type."'";
                    $share_id = intval($GLOBALS['db']->getOne($sql));
                }
				$now_time = NOW_TIME;
				$s_now_time = to_timespan(to_date($now_time,"Y-m-d 00:00:00"));
            	$e_now_time = to_timespan(to_date($now_time,"Y-m-d 23:59:59"));

				if($share_id==0){
					$sql = "select max(id) from ".DB_PREFIX."video_share  where  user_id =".$user_id." and type = '".$type."' and create_time>".$s_now_time." and create_time<".$e_now_time;
					$video_t_share_id = $GLOBALS['db']->getOne($sql);
	                
	                $share_id = intval($video_t_share_id);
	                if($share_id==0){
	                    //分享表中无记录，查分享历史表
	                    $sql = "select id  from ".DB_PREFIX."video_share_history  where user_id =".$user_id." and type = '".$type."' and create_time>".$s_now_time." and create_time<".$e_now_time;
	                    $share_id = intval($GLOBALS['db']->getOne($sql));
	                }
				}
				//补充判断 app 端 上传 video_id 错误问题
				/*if($share_id==0){
                    $sql = "select id,video_id,user_id  from ".DB_PREFIX."video_share  where '".strtotime(date('Y-m-d'))."' - create_time<3600*24 and user_id =".$user_id." and type = '".$type."'";
					$video_share_info = $GLOBALS['db']->getRow($sql);
					if(intval($video_share_info['id'])==0){
						$sql = "select id,video_id,user_id  from ".DB_PREFIX."video_share_history  where '".strtotime(date('Y-m-d'))."'-create_time<3600*24 and user_id =".$user_id." and type = '".$type."'";
						$video_share_info = $GLOBALS['db']->getRow($sql);
					};
					$share_id = intval($video_share_info['id']);
                }*/

               
			}
						
			
			//每个用户每个房间可以分享一次，增加分享次数
           
			fanwe_require(APP_ROOT_PATH.'mapi/lib/redis/VideoRedisService.php');
			$video_redis = new VideoRedisService();
			//$video_redis->incry($this->video_db.$room_id, 'share_count', 1);
			$video_redis->redis->hIncrBy($video_redis->video_db.$room_id,'share_count',1);
			$data = array();
			//写入分享表
			$video_monitor = array();
			$video_monitor['user_id'] = $user_id;
			$video_monitor['video_id'] = $room_id;
			$video_monitor['type'] = $type;
			$video_monitor['create_time'] = NOW_TIME;
			$GLOBALS['db']->autoExecute(DB_PREFIX."video_share", $video_monitor,"INSERT");
			
			$video_share_id = $GLOBALS['db']->insert_id();
			if (defined('OPEN_MISSION') && OPEN_MISSION) {
			    require_once APP_ROOT_PATH . 'mapi/lib/core/Model.class.php';
			    Model::$lib = dirname(__FILE__);
			    Model::build('mission')->incProgress($user_id,3);
			}
			if(defined('OPEN_SHARE_EXPERIENCE')&&OPEN_SHARE_EXPERIENCE==1&&intval($m_config['open_share_ticket'])){
				 //历史记录中也没有，可以增加
				if($share_id==0&&intval($m_config['share_ticket'])>0&&intval($video_user_info['from_user_id'])!=$user_id&&intval($video_share_id)>0){
                    //根据open_share_ticket决定获得印票或钻石 1印票 2钻石
					$ticket_name = $m_config['ticket_name'];
                    if (intval($m_config['open_share_ticket'])==1){
                        $sql = "update ".DB_PREFIX."user set ticket = ticket + ".intval($m_config['share_ticket'])." where id =".$user_id;
                        $data['ticket'] =intval($m_config['share_ticket']);
						if($ticket_name==''){
							$ticket_name = '印票';
						}
                    }else{
                        $sql = "update ".DB_PREFIX."user set diamonds = diamonds + ".intval($m_config['share_ticket'])." where id =".$user_id;
                        $data['diamonds'] =intval($m_config['share_ticket']);
						$ticket_name = '钻石';
                    }
					$GLOBALS['db']->query($sql);//增加用户印票或钻石
					user_deal_to_reids(array($user_id));//同步用户redis

                    //请求返回信息
                    $root['share_award'] =intval($m_config['share_ticket']);
                    $root['share_award_type'] = $ticket_name;
                    $root['share_award_info'] ='分享直播获得'.$ticket_name.intval($m_config['share_ticket']);

                    //写入用户日志
                    $data['log_admin_id'] = 0;
                    $data['video_id'] = $room_id;
                    $param['type'] = 4;//类型 0表示充值 1表示提现 2赠送道具 3 兑换印票 4 分享获得印票
                    $log_msg ="通过".$type.'分享直播间'.$room_id.'，获得'.intval($m_config['share_ticket']).$ticket_name;
                    account_log_com($data,$user_id,$log_msg,$param);
				}
			}
		}
	
		ajax_return($root);
	}
	
	/*
	 * 更新设备号
	 */
	public function apns()
	{
		$root = array();
		$root['status'] = 1;
		//$GLOBALS['user_info']['id'] = 1;
		if(!$GLOBALS['user_info']){
			$root['error'] = "用户未登陆,请先登陆.";
			$root['status'] = 0;
			$root['user_login_status'] = 0;//有这个参数： user_login_status = 0 时，表示服务端未登陆、要求登陆，操作
		}else{
			$user_id = intval($GLOBALS['user_info']['id']);//用户ID
	
			$apns_code = addslashes($_REQUEST['apns_code']);
	
			//apns_code 友盟消息推送服务对设备的唯一标识。Android的device_token是44位字符串, iOS的device-token是64位
			//device_type 1:android; 2:ios
			if (strlen($apns_code) == 44){
				$device_type = 1;
			}else if (strlen($apns_code) == 64){
				$device_type = 2;
			}else{
				$device_type = 0;
			}
			
			$sql = 'update '.DB_PREFIX."user set apns_code ='".$apns_code."',device_type='".$device_type."' where id =".$user_id;
			$GLOBALS['db']->query($sql);
			
			fanwe_require(APP_ROOT_PATH.'mapi/lib/redis/UserRedisService.php');
			$user_redis = new UserRedisService();
			$data['apns_code'] = $apns_code;
			$data['device_type'] = $device_type;
			$user_redis->update_db($user_id,$data);
				
		}
	
		ajax_return($root);
	}
	/*
	 * 个人主页
	 */
	public function user_home(){

		$root = array();
		
		//$GLOBALS['user_info']['id'] = 286;
		if(!$GLOBALS['user_info']){
			$root['error'] = "用户未登陆,请先登陆.";
			$root['status'] = 0;
			$root['user_login_status'] = 0;//有这个参数： user_login_status = 0 时，表示服务端未登陆、要求登陆，操作
		}else{
			$user_id = intval($GLOBALS['user_info']['id']);//用户ID
		
			$to_user_id = intval($_REQUEST['to_user_id']);//被查看的用户id
			if ($to_user_id == 0){
				$to_user_id = $user_id;
			}
			
			$root = getuserinfo($user_id,0,$to_user_id);
			$root['status'] = 1;
		}
		
		ajax_return($root);
		
	}
	
	
	//直播回看
	public function user_review(){
		$root = array();
		
		//$GLOBALS['user_info']['id'] = 1;
		
		
		$to_user_id = intval($_REQUEST['to_user_id']);//被查看的用户id
		if ($to_user_id == 0){
			$to_user_id = intval($GLOBALS['user_info']['id']);
		}

		$sort = intval($_REQUEST['sort']);//排序类型; 0:最新;1:最热
		
		
		$page = intval($_REQUEST['p']);//取第几页数据
		if($page==0)$page = 1;
		$page_size=10;
		
		$limit = (($page-1)*$page_size).",".$page_size;

		$sort_field = "vh.begin_time desc";
		if ($sort == 1){
			$sort_field = "vh.max_watch_number desc";
		}
		
		//video_count
		if($to_user_id ==intval($GLOBALS['user_info']['id'])){
			$sql = "select vh.id,vh.title,vh.begin_time,vh.max_watch_number,vh.video_vid,vh.video_type,vh.channelid,vh.create_time,u.id as user_id,u.head_image,u.nick_name from ".DB_PREFIX."video_history as vh left join ".DB_PREFIX."user as u on u.id= vh.user_id  where vh.group_id!='' and  vh.is_delete = 0 and vh.is_del_vod = 0 and vh.user_id = '".$to_user_id."' order by ".$sort_field." limit ".$limit;
		}else{
			$sql = "select vh.id,vh.title,vh.begin_time,vh.max_watch_number,vh.video_vid,vh.video_type,vh.channelid,vh.create_time,u.id as user_id,u.head_image,u.nick_name from ".DB_PREFIX."video_history as vh left join ".DB_PREFIX."user as u on u.id= vh.user_id  where vh.group_id!='' and  vh.is_delete = 0 and vh.is_del_vod = 0 and vh.is_live_pay = 0 and user_id = '".$to_user_id."' order by ".$sort_field." limit ".$limit;
		}

		$list = array();
		$list_arr = array();
		$list_info = $GLOBALS['db']->getAll($sql);
		foreach ( $list_info as $k => $v )
		{
			
			/*//判断视频存在
			fanwe_require(APP_ROOT_PATH . 'mapi/lib/core/video_factory.php');
            $video_factory = new VideoFactory();

            if($v['video_type'] == 1 && $v['channelid'])
            {
            	if(strlen($v['channelid'])>20){
            		$fileName = $v['channelid'];
               		$ret = $video_factory->DescribeVodPlayInfo($fileName);
            	}else{
            		$ret = $video_factory->GetVodRecordFiles($v['channelid'], $v['create_time']);
            	}
            }else{
                $fileName = $v['id'] . '_' . to_date($v['begin_time'],'Y-m-d-H');
                $ret = $video_factory->DescribeVodPlayInfo($fileName);
            }
            
			if($ret['codeDesc'] == 'Success'){
				$list_arr = $v;
				$list_arr['head_image'] =  get_spec_image($v['head_image'],150,150);
				$list_arr['begin_time_format'] = format_show_date($v['begin_time']);
				if ($v['max_watch_number'] > 10000){
					$list_arr['watch_number_format'] = round($v['max_watch_number']/10000,2)."万";
				}else{
					$list_arr['watch_number_format'] = $v['max_watch_number'];
				}
				
	            $list_arr['max_watch_number'] = $v['max_watch_number'];
	            
				if($v['title'] == '')
					$list_arr['title'] = "....";
				
				$list[] = $list_arr;	
			}else{
				//会误删
				$sql = "update ".DB_PREFIX."video_history set is_del_vod = 1 where id =".$v['id'];
				//$GLOBALS['db']->query($sql);
			}*/
				$list_arr = $v;
				$list_arr['head_image'] =  get_spec_image($v['head_image'],150,150);
				$list_arr['begin_time_format'] = format_show_date($v['begin_time']);
				if ($v['max_watch_number'] > 10000){
					$list_arr['watch_number_format'] = round($v['max_watch_number']/10000,2)."万";
				}else{
					$list_arr['watch_number_format'] = $v['max_watch_number'];
				}
				
	            $list_arr['max_watch_number'] = $v['max_watch_number'];
	            
				if($v['title'] == '')
					$list_arr['title'] = "....";
				
				$list[] = $list_arr;
				
		}		
		$root['list'] = $list;

		if($to_user_id ==intval($GLOBALS['user_info']['id'])){
			$sql = "select count(*)  from ".DB_PREFIX."video_history as vh left join ".DB_PREFIX."user as u on u.id= vh.user_id  where vh.group_id!='' and  vh.is_delete = 0 and vh.is_del_vod = 0 and vh.user_id = '".$to_user_id."' order by ".$sort_field;
		}else{
			$sql = "select count(*)  from ".DB_PREFIX."video_history as vh left join ".DB_PREFIX."user as u on u.id= vh.user_id  where vh.group_id!='' and  vh.is_delete = 0 and vh.is_del_vod = 0 and vh.is_live_pay = 0 and user_id = '".$to_user_id."' order by ".$sort_field;
		}
		
		$count = $GLOBALS['db']->getOne($sql);
		//$count = count($list);
		if ($count >= $page_size)
			$root['has_next'] = 1;
		else
			$root['has_next'] = 0;

        $root['count'] = $count;
        if($to_user_id ==intval($GLOBALS['user_info']['id'])&&$page==1){
        	
			$sql = "update ".DB_PREFIX."user set video_count = ".$root['count']." where id = ".intval($GLOBALS['user_info']['id'])." and video_count!=".$root['count'];
			$GLOBALS['db']->query($sql);
	
	        fanwe_require(APP_ROOT_PATH.'mapi/lib/redis/BaseRedisService.php');
	        fanwe_require(APP_ROOT_PATH.'mapi/lib/redis/UserRedisService.php');
	        $user_redis = new UserRedisService();
	        $user_data = array();
	        $user_data['video_count'] = $root['count'];
	        $user_redis->update_db(intval($GLOBALS['user_info']['id']), $user_data);
        }   
                
		$root['page'] = $page;
		$root['status'] = 1;
	
		ajax_return($root);
	}
	
	/**
	 * 设置黑名单
	 */
	public function set_black(){
		$root = array();
	
		//$GLOBALS['user_info']['id'] = 269;
		if(!$GLOBALS['user_info']){
			$root['error'] = "用户未登陆,请先登陆.";
			$root['status'] = 0;
			$root['user_login_status'] = 0;//有这个参数： user_login_status = 0 时，表示服务端未登陆、要求登陆，操作
		}else{
			//
			$user_id = intval($GLOBALS['user_info']['id']);//当前用户;
				
			$to_user_id = intval($_REQUEST['to_user_id']);//被关注或取消关注的用户
			if($user_id!=$to_user_id){
				$root = set_black($user_id,$to_user_id);
			}else{
				$root['error'] = "不能设置自己！";
				$root['status'] = 0;
			}
		}
		ajax_return($root);
	}
	
	
	/**
	 * 搜索用户列表
	 */
	public function search(){
	
		//$GLOBALS['user_info']['id'] = 269;
		
		$root = array();
		$root['status'] = 1;
		if(!$GLOBALS['user_info']){
			$root['error'] = "用户未登陆,请先登陆.";
			$root['status'] = 0;
			$root['user_login_status'] = 0;//有这个参数： user_login_status = 0 时，表示服务端未登陆、要求登陆，操作
		}else{
			//
			$user_id = intval($GLOBALS['user_info']['id']);//当前用户;
	
			$keyword = strim($_REQUEST['keyword']);//搜索关键字
			if ($keyword != ""){
				$page = intval($_REQUEST['p']);//取第几页数据
					
				if($page==0)$page = 1;
				$page_size=20;
				$limit = (($page-1)*$page_size).",".$page_size;

                $m_config =  load_auto_cache("m_config");//初始化手机端配置
                if($m_config['search_change']==0){
                    if ($page == 1){
                        if (intval($keyword) > 0){//如果搜索关键字为数字
                            //搜索ID，昵称,靓号，不为机器人。
                            $sql = "select u.id as user_id,u.nick_name,u.signature,u.sex,u.head_image,u.user_level,u.v_icon from ".DB_PREFIX."user u where  u.id = ".$keyword." and u.is_robot=0 limit 0,1 
                                union select u.id as user_id,u.nick_name,u.signature,u.sex,u.head_image,u.user_level,u.v_icon from ".DB_PREFIX."user u where u.luck_num = ".$keyword." and u.is_robot=0 limit 0,1 
                                union select u.id as user_id,u.nick_name,u.signature,u.sex,u.head_image,u.user_level,u.v_icon from ".DB_PREFIX."user u where u.nick_name = '".$keyword."' and u.is_robot=0 limit 20";
                        }else{
                            $sql = "select u.id as user_id,u.nick_name,u.signature,u.sex,u.head_image,u.user_level,u.v_icon from ".DB_PREFIX."user u where  u.nick_name = '".$keyword."' and u.is_robot=0 limit ".$limit;
                        }
                    }else{
                        $sql = "select u.id as user_id,u.nick_name,u.signature,u.sex,u.head_image,u.user_level,u.v_icon from ".DB_PREFIX."user u where  u.nick_name = '".$keyword."' and u.is_robot=0 limit ".$limit;
                    }
                }else{
                    if ($page == 1){
                        if (intval($keyword) > 0){//如果搜索关键字为数字
                            //搜索ID，昵称,靓号，不为机器人。
                            $sql = "select u.id as user_id,u.nick_name,u.signature,u.sex,u.head_image,u.user_level,u.v_icon from ".DB_PREFIX."user u where  u.id = ".$keyword." and u.is_robot=0 limit 0,1 
                                union select u.id as user_id,u.nick_name,u.signature,u.sex,u.head_image,u.user_level,u.v_icon from ".DB_PREFIX."user u where u.luck_num = ".$keyword." and u.is_robot=0 limit 0,1 
                                union select u.id as user_id,u.nick_name,u.signature,u.sex,u.head_image,u.user_level,u.v_icon from ".DB_PREFIX."user u where u.nick_name = '".$keyword."' and u.is_robot=0 limit 20";
                        }else{
                            $sql = "select u.id as user_id,u.nick_name,u.signature,u.sex,u.head_image,u.user_level,u.v_icon from ".DB_PREFIX."user u where  u.nick_name like '%".$keyword."%' and u.is_robot=0 limit ".$limit;
                        }
                    }else{
                        $sql = "select u.id as user_id,u.nick_name,u.signature,u.sex,u.head_image,u.user_level,u.v_icon from ".DB_PREFIX."user u where  u.nick_name like '%".$keyword."%' and u.is_robot=0 limit ".$limit;
                    }
                }

//                if (intval($keyword) > 0){//如果搜索关键字为数字
//                    //搜索ID，昵称,靓号，不为机器人。
//                    //$sql = "select u.id as user_id,u.nick_name,u.signature,u.sex,u.head_image,u.user_level,u.v_icon from ".DB_PREFIX."user u where (u.id = '".$keyword. "' or u.luck_num = '".$keyword. "' or u.nick_name = '".$keyword."') and u.is_robot=0 limit ".$limit;
//                    $sql = "select u.id as user_id,u.nick_name,u.signature,u.sex,u.head_image,u.user_level,u.v_icon from ".DB_PREFIX."user u where  u.nick_name = '".$keyword."' and u.is_robot=0 limit ".$limit;
//                }else{
//                   // $sql = "select u.id as user_id,u.nick_name,u.signature,u.sex,u.head_image,u.user_level,u.v_icon from ".DB_PREFIX."user u where (u.id = '".$keyword. "' or u.nick_name = '".$keyword."') and u.is_robot=0 limit ".$limit;
//                    $sql = "select u.id as user_id,u.nick_name,u.signature,u.sex,u.head_image,u.user_level,u.v_icon from ".DB_PREFIX."user u where  u.nick_name = '".$keyword."' and u.is_robot=0 limit ".$limit;
//                }
					
                //$root['sql'] = $sql;
				
				//查询用户列表,修改成 从只读数据库中取,但不是高效做法;主并发时,可以加入阿里云的搜索服务
				//https://www.aliyun.com/product/opensearch?spm=5176.8142029.388261.62.tgDxhe
				$list = $GLOBALS['db']->getAll($sql,true,true);
				$keys =array();

				fanwe_require(APP_ROOT_PATH.'mapi/lib/redis/UserFollwRedisService.php');
				$user_redis = new UserFollwRedisService($user_id);
				$keys = $user_redis->following();
				foreach($list as $k=>$v){
						if(in_array($v['user_id'],$keys)){
							$list[$k]['follow_id'] = $v['user_id'];
						}else{
							$list[$k]['follow_id'] = 0;
						}
						$list[$k]['head_image'] = get_spec_image($v['head_image']);
                        $list[$k]['signature'] = htmlspecialchars_decode($list[$k]['signature']);
                        $list[$k]['nick_name'] = htmlspecialchars_decode($list[$k]['nick_name']);
				}
				$root['list'] = $list;
				if (count($list) == $page_size)
					$root['has_next'] = 1;
				else
					$root['has_next'] = 0;
					
				$root['page'] = $page;//
			}else{
				$root['has_next'] = 0;
				$root['list'] = array();
				$root['page'] = 0;//
			}
		}
		
			
		ajax_return($root);
	
	}
	
	/**
	 * 批量导入未同步的用户到im
	 */
	public function account_import(){
		$root = array();
		$root['status'] = 1;
		
		fanwe_require(APP_ROOT_PATH.'system/tim/TimApi.php');
		$api = createTimAPI();
	
		$sql = "select id,nick_name,head_image from ".DB_PREFIX."user where synchronize = 0 limit 10";
			
		$list = $GLOBALS['db']->getAll($sql);
		
		foreach ( $list as $k => $v )
		{
			$face_url = get_spec_image($v['head_image']);
			$ret = $api->account_import((string)$v['id'], $v['nick_name'], $face_url);
			if ($ret['ActionStatus'] == 'OK'){
				$sql = 'update '.DB_PREFIX."user set synchronize =1 where id =".$v['id'];
				$GLOBALS['db']->query($sql);
			}else{
				$root['ret'][$v['id']] = $ret;
			}
			//print_r($ret);
		}
		ajax_return($root);
	}
	//允许直播的话，未同意状态改为同意
	public function agree(){
		$user_id = intval($GLOBALS['user_info']['id']);
		if($user_id){
			$GLOBALS['db']->query("update ".DB_PREFIX."user set is_agree =1 where   id=".$user_id."  and is_agree = 0  ");
			
			fanwe_require(APP_ROOT_PATH.'mapi/lib/redis/UserRedisService.php');
			$user_redis = new UserRedisService();
			$data['is_agree'] = 1;
			$user_redis->update_db($user_id, $data);
		}
		
		$root['status'] =1;
		$root['error'] ='';
			
		ajax_return($root);
	}

	/**
	 * 一次性取出,我关注的用户列表
	 */
	public function my_follow(){
	
		$root = array();
		$root['status'] = 1;
//		$GLOBALS['user_info']['id'] = 290;
		if(!$GLOBALS['user_info']){
			$root['error'] = "用户未登陆,请先登陆.";
			$root['status'] = 0;
			$root['user_login_status'] = 0;//有这个参数： user_login_status = 0 时，表示服务端未登陆、要求登陆，操作
		}else{
				
			$user_id = intval($GLOBALS['user_info']['id']);//id
	
//			$sql = "select f.podcast_id as user_id from ".DB_PREFIX."focus f where f.user_id = ".$user_id;
//
//			//$root['sql'] = $sql;
//
//			$list = $GLOBALS['db']->getAll($sql);
//			$root['list'] = $list;

			fanwe_require(APP_ROOT_PATH.'mapi/lib/redis/UserFollwRedisService.php');
			$user_redis = new UserFollwRedisService($user_id);
			$root['list']  = $user_redis->get_follonging_user($user_id,1,1000);
	
		}
		ajax_return($root);
	}
	
	/**
	 * 好友，相互关注的用户;用于私密直播时，添加的用户列表
	 */
	public function friends(){
	
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
	
			$page_size=20;

			fanwe_require(APP_ROOT_PATH.'mapi/lib/redis/UserFollwRedisService.php');
			$user_redis = new UserFollwRedisService($user_id);
			$root = $user_redis->get_private_user($page,$page_size);

            fanwe_require(APP_ROOT_PATH.'mapi/lib/redis/VideoRedisService.php');
            $video_redis = new VideoRedisService();
            $video_data = $video_redis->getRow_db($video_id,array('group_id','user_id'));

            fanwe_require(APP_ROOT_PATH.'mapi/lib/redis/VideoViewerRedisService.php');
            $video_viewer_redis = new VideoViewerRedisService();
            $group_id = $video_data['group_id'];//聊天群id

            if($group_id) {
                $users = $video_viewer_redis->get_viewer_list($group_id, $page);
                if($users['list']){
                    $user_ids = array_column($users['list'],'user_id');
                    $friends = $root['list'];
                    foreach($friends as $k=>$v){
                        if(in_array($v['user_id'],$user_ids) || $v['user_id']==$video_data['user_id']){
                            unset($friends[$k]);
                        }
                    }
                    $root['list'] = array_values($friends);
                }
            }
	
		}
		ajax_return($root);
	}
	
	/**
	 * 获得用户基本信息(比如：固化的信息比如：头像，性别，昵称，等级，签名等）
	 */
	public function baseinfo(){
		$root = array();
	
		//$GLOBALS['user_info']['id'] = 292;
	
		if(!$GLOBALS['user_info']){
			$root['error'] = "用户未登陆,请先登陆.";
			$root['status'] = 0;
			$root['user_login_status'] = 0;//有这个参数： user_login_status = 0 时，表示服务端未登陆、要求登陆，操作
		}else{
			$user_id = intval($GLOBALS['user_info']['id']);
	
			$user_ids = strim($_REQUEST['user_ids']);//字符串类型的用户id 23,123,3455 以英文逗号分割的字符串 ,一次不能大于100个用户
	
				
			if ($user_ids == ''){
				$user_ids = $user_id;
			}
			
			//将选中的：私聊 数据添加到数据库中
			$user_list = explode(',',$user_ids);
			
			if (count($user_list) > 100){
				$root['status'] =0;
				$root['error'] ='一次不能大于100个用户';
			
			}else{	
				
				fanwe_require(APP_ROOT_PATH.'mapi/lib/redis/UserRedisService.php');
				$user_redis = new UserRedisService();
				
				$list =   $user_redis->get_m_user($user_list);
				/*
				
				$fields = array('id','is_agree','video_count','is_authentication','nick_name','signature','sex','province','city','head_image','ticket','use_diamonds','user_level','v_type','v_explain','v_icon','is_remind');
				
				
				
				foreach ( $user_list as $k => $v )
				{
					$userinfo = $user_redis->getRow_db($v,$fields);
					$userinfo['user_id'] = $v;
					$list[] = $userinfo;
				}
				
				
				
				$sql = "select id as user_id,is_agree,is_authentication,nick_name,signature,sex,province,city,head_image,user_level,v_type,v_explain,v_icon from ".DB_PREFIX."user where id in (".$user_ids.")";
	
				$list = $GLOBALS['db']->getAll($sql);
				foreach ( $list as $k => $v )
				{
					$list[$k]['head_image'] = get_abs_img_root($v['head_image']);
				}
				*/
				
				$root['list'] = $list;
					
				$root['status'] = 1;
			}
		}
		ajax_return($root);
	}
	
	/**
	 * app呼醒跟进入后台时调用【用来统计用户在线时长】
	 */
	function state_change(){
		$root = array();
		$root['status'] = 1;

		if(!$GLOBALS['user_info']){
			$root['error'] = "用户未登陆,请先登陆.";
			$root['status'] = 0;
			$root['user_login_status'] = 0;//有这个参数： user_login_status = 0 时，表示服务端未登陆、要求登陆，操作
		}else{
			$user_id = intval($GLOBALS['user_info']['id']);
			$action = strim($_REQUEST['action']);
			
			fanwe_require(APP_ROOT_PATH.'mapi/lib/redis/UserRedisService.php');
            $user_redis = new UserRedisService();

			if (strtolower($action) == 'login'){
				$data = array('is_online'=>1,'login_time'=>to_date(NOW_TIME));
                $GLOBALS['db']->autoExecute(DB_PREFIX."user", $data,"UPDATE", 'id='.$user_id);
				$user_redis->update_db($user_id, $data);
			}else{
				$data = array('is_online'=>0,'login_time'=>to_date(NOW_TIME),'logout_time'=>to_date(NOW_TIME));
				
				$user = $user_redis->getRow_db($user_id, array('login_time','logout_time','online_time'));
				
				$login_time = to_timespan($user['login_time']);
				if ($login_time == 0){
					$data['login_time'] = to_date(NOW_TIME);
				}
				
				//计算在线时长
				$online = NOW_TIME - $login_time;
				
				if ($online > 7200){
					//异常数据,一次不可能超过8小时
					$online = 0;
				}
				
				$data['online_time'] = $user['online_time'] + $online;
				
				$GLOBALS['db']->autoExecute(DB_PREFIX."user", $data,"UPDATE", 'id='.$user_id);
				if($GLOBALS['db']->affected_rows()){
					$user_redis->update_db($user_id, $data);
						
					//更新用户等级
					$user_info = $user_redis->getRow_db($user_id,array('id','score','online_time','user_level'));
					user_leverl_syn($user_info);
				}
			}
			
		}

		ajax_return($root);
		
	}
}