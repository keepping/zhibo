<?php
// +----------------------------------------------------------------------
// | Fanwe 方维p2p借贷系统
// +----------------------------------------------------------------------
// | Copyright (c) 2011 http://www.fanwe.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: 云淡风轻(88522820@qq.com)
// +----------------------------------------------------------------------

class synModule  extends baseModule
{
	//登录 test
	public function login()
	{
		if(IS_DEBUG){
			$mobile = intval($_REQUEST['mobile']);
			$uid = intval($_REQUEST['id']);
			if($mobile){
				$user_data = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."user where mobile =".$mobile);
			}else{
				if($uid){
					$user_data = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."user where id =".$uid);
				}else{
					print_r("请填写会员ID");exit;
				}
			}
	
			es_session::set("user_info",$user_data);
			$GLOBALS['user_info'] = $user_data;
			es_cookie::set("client_ip",CLIENT_IP,3600*24*30);
			es_cookie::set("nick_name",$user_data['nick_name'],3600*24*30);
			es_cookie::set("user_id",$user_data['id'],3600*24*30);
			es_cookie::set("user_pwd",md5($user_data['user_pwd']."_EASE_COOKIE"),3600*24*30);
			es_cookie::set("is_agree",$user_data['is_agree'],3600*24*30);
			es_cookie::set("PHPSESSID2",es_session::id(),3600*24*30);
			print_r($user_data);
		}
		
	}
	//循环同步到IM
	function  synchronization_im(){
		if(IS_DEBUG){
			fanwe_require(APP_ROOT_PATH."system/libs/user.php");
			$user_id = intval($_REQUEST['id']);
			if($user_id){
				$user = $GLOBALS['db']->getAll("SELECT id,nick_name,head_image FROM ".DB_PREFIX."user where id = ".$user_id);
			}else{
				$user = $GLOBALS['db']->getAll("SELECT id,nick_name,head_image FROM ".DB_PREFIX."user where synchronize=0 ");
			}
			if($user){
				foreach($user as $k=>$user_data){
					accountimport($user_data);
				}
				echo $user_id."用户已同步";
			}else{
				echo "用户已同步";
			}
		}
	}
	//循环同步到redis
	function  synchronization_redis(){
		if(IS_DEBUG){
			fanwe_require(APP_ROOT_PATH.'mapi/lib/redis/UserRedisService.php');
			$user_redis = new UserRedisService();
			$user_id = intval($_REQUEST['id']);
			$type = intval($_REQUEST['type']);
			if($user_id){
				if($type==1&&$_REQUEST['c']!=''){
					fanwe_require(APP_ROOT_PATH."system/libs/user.php");
					$user_info =array();
					if($_REQUEST['c']=='mobile'||$_REQUEST['c']=='all')
						$user_info['mobile'] ='';
	
					if($_REQUEST['c']=='qq'||$_REQUEST['c']=='all')
						$user_info['qq_openid'] ='';
	
					if($_REQUEST['c']=='wx'||$_REQUEST['c']=='all'){
						$user_info['wx_openid'] ='';
						$user_info['wx_unionid'] ='';
					}
					if($_REQUEST['c']=='sina'||$_REQUEST['c']=='all'){
						$user_info['sina_id'] ='';
					}
	
					if($_REQUEST['c']=='wx_gz'||$_REQUEST['c']=='all'){
						$user_info['gz_openid'] ='';
						$user_info['subscribe'] ='';
					}
	
					$where = "id=".intval($user_id);
					$GLOBALS['db']->autoExecute(DB_PREFIX."user",$user_info,'UPDATE',$where);
				}
	
				$user = $GLOBALS['db']->getRow("SELECT * FROM ".DB_PREFIX."user where id = ".$user_id);
				if($user){
					$user_redis->update_db($user_id,$user);
					echo "用户redis同步完成";
				}else{
					echo "用户redis已同步";
				}
			}
		}
	}
	//图片同步
	function  synchronization_image(){
		if(IS_DEBUG){
			$user_id = intval($_REQUEST['id']);
			if($user_id){
				$user_data = $GLOBALS['db']->getRow("select id,head_image from ".DB_PREFIX."user where id =".$user_id);
	
				if($GLOBALS['distribution_cfg']['OSS_TYPE']&&$GLOBALS['distribution_cfg']['OSS_TYPE']!='NONE')
				{
					syn_to_remote_image_server($user_data['head_image']);
				}
	
				echo "执行结束";exit;
			}else{
				echo "ID空";exit;
			}
		}
	}
	//测试读取缓存 load_auto_cache
	public function m_configs(){
		if(IS_DEBUG){
			$cache_name = $_REQUEST['cache_name'];
			$rm_cache = intval($_REQUEST['rm_cache']);
			if($rm_cache){
				rm_auto_cache($cache_name);
			}
			$cache =  load_auto_cache($cache_name);
			print_r($cache);exit;
		}
	}
	function get_ip(){
		print_r(get_info());
	}

    //修改用户id到指定位数
    public function update_user_id(){
        $sql = "update ".DB_PREFIX."user set id=id+100000";
        $GLOBALS['db']->query($sql);
        $sql = "select id from ".DB_PREFIX."user order by id desc";
        $max_id = $GLOBALS['db']->getOne($sql,true,true);
        $max_id = intval($max_id)+1;
        $sql = "alter table ".DB_PREFIX."user_id AUTO_INCREMENT=".$max_id;
        $GLOBALS['db']->query($sql);

        fanwe_require(APP_ROOT_PATH.'mapi/lib/redis/BaseRedisService.php');
        fanwe_require(APP_ROOT_PATH.'mapi/lib/redis/UserRedisService.php');
        //删除定时器加入直播的机器人列表
        $user_redis = new UserRedisService();
        $video_con_keys = $user_redis->redis->keys($GLOBALS['distribution_cfg']['REDIS_PREFIX'].'user_robot');
        $video_con_count = $user_redis->redis->delete($video_con_keys);
        print_r("删除加入直播间的机器人：".$video_con_count);
        print_r("<br/>");

        $user_data = $GLOBALS['db']->getAll("select * from ".DB_PREFIX."user");
        if(count($user_data)>0){
            require_once(APP_ROOT_PATH.'system/tim/TimApi.php');
            $api = createTimAPI();

            $user_redis = new UserRedisService();
            foreach($user_data as $k=>$v){
                //同步到im
                $ret = $api->account_import((string)$v['id'], $v['nick_name'], $v['head_image']);
                if($ret['ErrorCode']==0){
                    $GLOBALS['db']->query("update ".DB_PREFIX."user set synchronize = 1 where id =".$v['id']);
                    $v['synchronize'] = 1;
                    $ret_im[] = $v['id'];
                }
                //同步用户到redis
                $user_redis->insert_db($v['id'],$v);
                $ret_redis[] = $v['id'];
            }
        }
        print_r("同步到redis用户：");
        print_r($ret_redis);
        print_r("<br/>");
        print_r("同步到im用户：");
        print_r($ret_im);exit;
    }

    //删除定时器加入直播的机器人列表
    public function del_user_robot(){
        if(IS_DEBUG){
            fanwe_require(APP_ROOT_PATH.'mapi/lib/redis/BaseRedisService.php');
            fanwe_require(APP_ROOT_PATH.'mapi/lib/redis/UserRedisService.php');
            $user_redis = new UserRedisService();
            $video_con_keys = $user_redis->redis->keys($GLOBALS['distribution_cfg']['REDIS_PREFIX'].'user_robot');
            $video_con_count = $user_redis->redis->delete($video_con_keys);
            print_r($video_con_count);exit;
        }
    }

    //同步机器人到redis
    public function robot($json = 0){
        //if(IS_DEBUG){
            $user_data = $GLOBALS['db']->getAll("select * from ".DB_PREFIX."user where is_robot = 1");
            if(count($user_data)>0){
                fanwe_require(APP_ROOT_PATH.'mapi/lib/redis/BaseRedisService.php');
                fanwe_require(APP_ROOT_PATH.'mapi/lib/redis/UserRedisService.php');
                $user_redis = new UserRedisService();
                foreach($user_data as $k=>$v){
                    $user_redis->insert_db($v['id'],$v);
                    $ret[] = $v['id'];
                }
            }
            if($json){
                $root = array('status'=>1,'error'=>'实际数量：'.count($user_data).'   同步数量：'.count($ret));
                ajax_return($root);
            }
            print_r($ret);exit;
        //}

    }

    //同步机器人到im
    public function robot_im1($json = 0){
        //if(IS_DEBUG){
            $user_data = $GLOBALS['db']->getAll("select * from ".DB_PREFIX."user where is_robot = 1 limit 0,100");
            require_once(APP_ROOT_PATH.'system/tim/TimApi.php');
            fanwe_require(APP_ROOT_PATH.'mapi/lib/redis/BaseRedisService.php');
            fanwe_require(APP_ROOT_PATH.'mapi/lib/redis/UserRedisService.php');
            $user_redis = new UserRedisService();
            $api = createTimAPI();
            if(is_array($api)){
                if($json){
                    ajax_return($api);
                }
                print_r($api);exit;
            }
            if(count($user_data)){
                foreach($user_data as $k=>$v){

                    //添加成功，同步信息

                    $ret = $api->account_import((string)$v['id'], $v['nick_name'], $v['head_image']);
                    if($ret['ErrorCode']==0){
                        $GLOBALS['db']->query("update ".DB_PREFIX."user set synchronize = 1 where id =".$v['id']);
                        $data['synchronize'] = 1;
                        $user_redis->update_db($v['id'],$data);
                        $ret_im[] = $v['id'];
                    }else{
						print_r($ret);echo "<hr/>";exit;
					}

                }
            }
            if($json){
                $root = array('status'=>1,'error'=>'实际数量：'.count($user_data).'   同步数量：'.count($ret));
                ajax_return($root);
            }
            print_r($ret_im);exit;
        //}
    }
//同步机器人到im
    public function robot_im2($json = 0){
        //if(IS_DEBUG){
            $user_data = $GLOBALS['db']->getAll("select * from ".DB_PREFIX."user where is_robot = 1 limit 200,100");
            require_once(APP_ROOT_PATH.'system/tim/TimApi.php');
            fanwe_require(APP_ROOT_PATH.'mapi/lib/redis/BaseRedisService.php');
            fanwe_require(APP_ROOT_PATH.'mapi/lib/redis/UserRedisService.php');
            $user_redis = new UserRedisService();
            $api = createTimAPI();
            
            if(is_array($api)){
                if($json){
                    ajax_return($api);
                }
                print_r($api);exit;
            }
            if(count($user_data)){
				
                foreach($user_data as $k=>$v){

                    //添加成功，同步信息

                    $ret = $api->account_import((string)$v['id'], $v['nick_name'], $v['head_image']);

                    if($ret['ErrorCode']==0){
                        $GLOBALS['db']->query("update ".DB_PREFIX."user set synchronize = 1 where id =".$v['id']);
                        $data['synchronize'] = 1;
                        $user_redis->update_db($v['id'],$data);
                        $ret_im[] = $v['id'];
                    
                    }else{
						print_r($ret);echo "<hr/>";exit;
					}

                }
            }
            if($json){
                $root = array('status'=>1,'error'=>'实际数量：'.count($user_data).'   同步数量：'.count($ret));
                ajax_return($root);
            }
            print_r($ret_im);exit;
        //}
    }
	//同步机器人到im
    public function robot_im3($json = 0){
        //if(IS_DEBUG){
            $user_data = $GLOBALS['db']->getAll("select * from ".DB_PREFIX."user where is_robot = 1 limit 300,100");
            require_once(APP_ROOT_PATH.'system/tim/TimApi.php');
            fanwe_require(APP_ROOT_PATH.'mapi/lib/redis/BaseRedisService.php');
            fanwe_require(APP_ROOT_PATH.'mapi/lib/redis/UserRedisService.php');
            $user_redis = new UserRedisService();
            $api = createTimAPI();
            
            if(is_array($api)){
                if($json){
                    ajax_return($api);
                }
                print_r($api);exit;
            }
            if(count($user_data)){
				
                foreach($user_data as $k=>$v){

                    //添加成功，同步信息

                    $ret = $api->account_import((string)$v['id'], $v['nick_name'], $v['head_image']);

                    if($ret['ErrorCode']==0){
                        $GLOBALS['db']->query("update ".DB_PREFIX."user set synchronize = 1 where id =".$v['id']);
                        $data['synchronize'] = 1;
                        $user_redis->update_db($v['id'],$data);
                        $ret_im[] = $v['id'];
                    
                    }else{
						print_r($ret);echo "<hr/>";exit;
					}

                }
            }
            if($json){
                $root = array('status'=>1,'error'=>'实际数量：'.count($user_data).'   同步数量：'.count($ret));
                ajax_return($root);
            }
            print_r($ret_im);exit;
        //}
    }

    //同步某个用户信息到redis
    public function update_user(){
        if(IS_DEBUG){
            $id = $_REQUEST['id'];
            $user_data = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."user where id = ".$id);
            fanwe_require(APP_ROOT_PATH.'mapi/lib/redis/BaseRedisService.php');
            fanwe_require(APP_ROOT_PATH.'mapi/lib/redis/UserRedisService.php');
            $user_redis = new UserRedisService();
            $user_redis->update_db($user_data['id'],$user_data);
        }
    }
    
    public function pay_live(){
    	if(IS_DEBUG){
	    	//扣费开始
	 		$m_config =  load_auto_cache("m_config");//初始化手机端配置 
	 		$uesddiamonds_to_score = $m_config['uesddiamonds_to_score'];
	 		$ticket_to_rate = $m_config['ticket_to_rate'];
	 		
	 		$sql = "select id,from_user_id,to_user_id,total_ticket from ".DB_PREFIX."live_pay_log  where create_date = '2017-01-18' order by id desc";
			$live_pay_log_info = $GLOBALS['db']->getAll($sql);
	 		
	 		foreach($live_pay_log_info as $k=>$v){
	 			//$total_ticket = round($v['live_fee']*floatval($ticket_to_rate),2);
	 			//echo $v['id']."--".$v['to_user_id']."--".$v['total_ticket'];echo "<hr/>";
	 			//echo $v['to_user_id'].",";
	 			//$sql = "update ".DB_PREFIX."live_pay_log set total_ticket = ".$total_ticket." where total_ticket=0 and id = ".$v['id'];
				//$GLOBALS['db']->query($sql);
	 			//if($GLOBALS['db']->affected_rows()){
	 				//$sql = "update ".DB_PREFIX."user set score = score + ".$v['total_score']." where id = ".$v['from_user_id'];
					//$GLOBALS['db']->query($sql);
	 				//$sql = "update ".DB_PREFIX."user set ticket = ticket + ".$v['total_ticket']." where id = ".$v['to_user_id'];
					//$GLOBALS['db']->query($sql);
					//user_deal_to_reids(array($v['from_user_id']));//同步user信息到redis
					//更新主播直播间获得印票
					fanwe_require(APP_ROOT_PATH.'mapi/lib/redis/VideoContributionRedisService.php');
	                $videoCont_redis = new VideoContributionRedisService();
	                $videoCont_redis->insert_db($v['from_user_id'], $v['to_user_id'],$v['id'], $v['total_ticket']);
	 			//}
	 			
	 		}
	 		
	 		print_r(count($live_pay_log_info));exit;
	 		/**/
    	}
    	
    }

    //所有用户等级改为1,其他与等级相关字段清零
    public function syn_user_level($json =0,$user_id=0){
        if(IS_DEBUG){
            $sql = "";
            if($user_id>0){
                $sql = " and id=".$user_id;
            }
            $GLOBALS['db']->query("update ".DB_PREFIX."user set score=0,online_time=0,user_level=1 where is_robot = 0 ".$sql);
            $user_data = $GLOBALS['db']->getAll("select id,score,online_time,user_level from ".DB_PREFIX."user where is_robot = 0 ".$sql,true,true);
            if(count($user_data)>0){
                fanwe_require(APP_ROOT_PATH.'mapi/lib/redis/BaseRedisService.php');
                fanwe_require(APP_ROOT_PATH.'mapi/lib/redis/UserRedisService.php');
                $user_redis = new UserRedisService();
                foreach($user_data as $k=>$v){
                    $user_redis->update_db($v['id'],$v);
                    $ret[] = $v['id'];
                }
            }
            if($json){
                $root = array('status'=>0,'error'=>'修改失败！');
                if(count($user_data) == count($ret)){
                    $root['status'] = 1;
                    $root['error'] = '修改成功！';
                }
                ajax_return($root);
            }
            print_r($ret);exit;
        }

    }

    //根据用户id,等级，积分修改用户等级
    public function update_user_level(){
        if(IS_DEBUG){
            $id = $_REQUEST['id'];
            $user_level = $_REQUEST['user_level'];
            $score = $_REQUEST['score'];
            $GLOBALS['db']->query("update ".DB_PREFIX."user set user_level=".$user_level.",score=".$score.",online_time = 0 where id in(".$id.")");
            $user_data = $GLOBALS['db']->getAll("select id,user_level,score,online_time from ".DB_PREFIX."user where id in(".$id.")");
            if(count($user_data)>0){
                fanwe_require(APP_ROOT_PATH.'mapi/lib/redis/BaseRedisService.php');
                fanwe_require(APP_ROOT_PATH.'mapi/lib/redis/UserRedisService.php');
                $user_redis = new UserRedisService();
                foreach($user_data as $k=>$v){
                    $user_redis->update_db($v['id'],$v);
                    $ret[] = $v['id'];
                }
            }
            print_r($ret);exit;
        }

    }

    public function clear_data(){
        if(IS_DEBUG){
            $html = '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
    <title>数据操作</title>
</head>
<body>
<script type="text/javascript" src="'.SITE_DOMAIN.'/admin/Tpl/default/Common/js/jquery.js"></script>
<div align="center" style="padding-top: 50px;">
<form action="'.SITE_DOMAIN.'/mapi/index.php?ctl=syn&act=clear" method="get" id="form_type">
    清除信息：<select name="type" id="type" onchange="change_type();">
    <option value="0">选择数据的操作方式</option>
    <option value="1">机器人同步到im</option>
    <option value="2">机器人同步到redis</option>
    <option value="3">修改等级为1（积分清零）</option>
    <option value="4">用户的钻石、消费钻石、印票、可用印票清零</option>
    <option value="5">用户的粉丝、关注数量清零</option>
    <option value="6">清空所有记录（包含用户、直播、提现、充值等除配置外所有数据，不含机器人）</option>
</select>
    <br/>
    <div style="display: none;padding-top: 20px;" id="user_module">
        主播ID：<input type = "text" value="" name="user_id" id="user_id"/>(不填则操作所有主播)
    </div>
    <br/>
    <input type="button" class="submit button" value="提交" onclick="submit_type();"/>
</form>
</div>
<script type="text/javascript">
    $(function(){
        var type = $("#type option:selected") .val();
        if(type==3 || type==4 || type==5){
            $("#user_module").show();
        }else{
            $("#user_module").hide();
            $("#user_id").val("");
        }
    });
        function change_type(){
            var type = $("#type option:selected") .val();
            if(type==3 || type==4 || type==5){
                $("#user_module").show();
            }else{
                $("#user_module").hide();
                $("#user_id").val("");
            }
        }

        function submit_type(){
            var type = $("#type option:selected") .val();
            if(type>0){
                var confirm_str = "确定将";
                var user_id = $("#user_id").val();
                var user_str = "所有";
                if(user_id){
                    user_str = user_id;
                }
                confirm_str = confirm_str+user_str+$("#type option:selected") .text() +"吗？";
                if(confirm(confirm_str)){
                    var url = $("#form_type").attr("action");
                    var query = $("#form_type").serialize();
                    $.ajax({
                        url:url,
                        data:query,
                        dataType:"json",
                        type:"post",
                        success:function(result){
                            alert(result.error);
                            func();
                            function func(){
                                if(result.status==1){
                                    location.href=location.href;
                                }
                            }
                        }
                    });
                }
            }else{
                alert("请选择数据的操作方式！！");
            }
        }
        </script>
</body>
</html>';
            echo $html;
        }else{
            print_r("请开启debug模式");exit;
        }
    }

    public function clear(){
        $root = array('status'=>0,'error'=>'');
        if(IS_DEBUG){
            $type = $_REQUEST['type'];
            $root['error'] = '请选择数据的操作方式！！';
            if($type){
                $user_id = intval($_REQUEST['user_id']);
                if($type == 3 || $type==4 || $type==5){
                    $user_id = $GLOBALS['db']->getOne("select id from " . DB_PREFIX . "user where is_robot=0 and id = " . intval($user_id),true,true);
                    if(intval($user_id)<=0){
                        $root['error'] = '主播ID不存在';
                        ajax_return($root);
                    }
                }
                if($type == 1){
                    $this->robot_im(1);
                }elseif($type == 2){
                    $this->robot(1);
                }elseif($type == 3){
                    $this->syn_user_level(1,$user_id);
                }elseif($type==4){
                    $sql = "";
                    if($user_id>0){
                        $sql = " and id=".$user_id;
                    }
                    $GLOBALS['db']->query("update ".DB_PREFIX."user set diamonds=0,use_diamonds=0,ticket=0,refund_ticket=0 where is_robot = 0 ".$sql);
                    $user_data = $GLOBALS['db']->getAll("select id,diamonds,use_diamonds,ticket,refund_ticket from ".DB_PREFIX."user where is_robot = 0 ".$sql,true,true);
                    if(count($user_data)>0){
                        fanwe_require(APP_ROOT_PATH.'mapi/lib/redis/BaseRedisService.php');
                        fanwe_require(APP_ROOT_PATH.'mapi/lib/redis/UserRedisService.php');
                        $user_redis = new UserRedisService();
                        foreach($user_data as $k=>$v){
                            $user_redis->update_db($v['id'],$v);
                            $ret[] = $v['id'];
                        }
                    }
                    $root = array('status'=>0,'error'=>'清除失败！');
                    if(count($user_data) == count($ret)){
                        $root['status'] = 1;
                        $root['error'] = '清除成功！';
                    }
                    ajax_return($root);
                }elseif($type==5){
                    $sql = "";
                    if($user_id>0){
                        $sql = " and id=".$user_id;
                    }
                    $GLOBALS['db']->query("update ".DB_PREFIX."user set fans_count=0,focus_count=0 where is_robot = 0 ".$sql);
                    $user_data = $GLOBALS['db']->getAll("select id,fans_count,focus_count from ".DB_PREFIX."user where is_robot = 0 ".$sql,true,true);
                    if(count($user_data)>0){
                        fanwe_require(APP_ROOT_PATH.'mapi/lib/redis/BaseRedisService.php');
                        fanwe_require(APP_ROOT_PATH.'mapi/lib/redis/UserRedisService.php');
                        fanwe_require(APP_ROOT_PATH.'mapi/lib/redis/UserFollwRedisService.php');
                        $user_redis = new UserRedisService();
                        foreach($user_data as $k=>$v){
                            $user_redis->update_db($v['id'],$v);
                            $ret[] = $v['id'];
                            $user_follow_redis = new UserFollwRedisService($user_id);
                            $user_follow_redis->redis->delete($user_follow_redis->user_follow_db.$v['id']);
                            $user_follow_redis->redis->delete($user_follow_redis->user_followed_by_db.$v['id']);
                        }
                    }
                    $root = array('status'=>0,'error'=>'清除失败！');
                    if(count($user_data) == count($ret)){
                        $root['status'] = 1;
                        $root['error'] = '清除成功！';
                    }
                    ajax_return($root);
                }elseif($type==6){
                    $this->clear_all();
                }
            }
        }else{
            $root['error'] = '请开启debug模式';
        }
        print_r($root);
    }

    //清空用户数据
    public function clear_all(){
        $result = array();
        $result['api_log'] = $GLOBALS['db']->query("delete from ".DB_PREFIX."api_log");
        $result['black'] = $GLOBALS['db']->query("delete from ".DB_PREFIX."black");
        $result['deal_msg_list'] = $GLOBALS['db']->query("delete from ".DB_PREFIX."deal_msg_list");
        $result['mobile_verify_code'] = $GLOBALS['db']->query("delete from ".DB_PREFIX."mobile_verify_code");
        $result['exchange_log'] = $GLOBALS['db']->query("delete from ".DB_PREFIX."exchange_log");
        $result['flow_statistics'] = $GLOBALS['db']->query("delete from ".DB_PREFIX."flow_statistics");

        $result['log'] = $GLOBALS['db']->query("delete from ".DB_PREFIX."log");
        $result['payment_notice'] = $GLOBALS['db']->query("delete from ".DB_PREFIX."payment_notice");

        $result['push_anchor'] = $GLOBALS['db']->query("delete from ".DB_PREFIX."push_anchor");
        $result['slb_group'] = $GLOBALS['db']->query("delete from ".DB_PREFIX."slb_group");
        $result['tipoff'] = $GLOBALS['db']->query("delete from ".DB_PREFIX."tipoff");

        //清空主播
        $result['user'] = $GLOBALS['db']->query("delete from ".DB_PREFIX."user where is_robot = 0");
        $result['user_admin'] = $GLOBALS['db']->query("delete from ".DB_PREFIX."user_admin");
        $result['user_id'] = $GLOBALS['db']->query("delete from ".DB_PREFIX."user_id");
        $result['user_log'] = $GLOBALS['db']->query("delete from ".DB_PREFIX."user_log");
        $result['user_music'] = $GLOBALS['db']->query("delete from ".DB_PREFIX."user_music");
        $result['user_refund'] = $GLOBALS['db']->query("delete from ".DB_PREFIX."user_refund");
        $result['login_log'] = $GLOBALS['db']->query("delete from ".DB_PREFIX."login_log");

        //清空直播记录
        $result['room_id'] = $GLOBALS['db']->query("delete from ".DB_PREFIX."room_id");
        $result['video'] = $GLOBALS['db']->query("delete from ".DB_PREFIX."video");
        $result['video_history'] = $GLOBALS['db']->query("delete from ".DB_PREFIX."video_history");
        $result['video_cate'] = $GLOBALS['db']->query("delete from ".DB_PREFIX."video_cate");
        $result['video_lianmai'] = $GLOBALS['db']->query("delete from ".DB_PREFIX."video_lianmai");
        $result['video_lianmai_history'] = $GLOBALS['db']->query("delete from ".DB_PREFIX."video_lianmai_history");
        $result['video_monitor'] = $GLOBALS['db']->query("delete from ".DB_PREFIX."video_monitor");
        $result['video_monitor_history'] = $GLOBALS['db']->query("delete from ".DB_PREFIX."video_monitor_history");
        $result['video_red'] = $GLOBALS['db']->query("delete from ".DB_PREFIX."video_red");
        $result['video_share'] = $GLOBALS['db']->query("delete from ".DB_PREFIX."video_share");
        $result['video_share_history'] = $GLOBALS['db']->query("delete from ".DB_PREFIX."video_share_history");
        $result['video_prop'] = $GLOBALS['db']->query("delete from ".DB_PREFIX."video_prop");

        if(defined('OPEN_LIVE_PAY')&&OPEN_LIVE_PAY){
            $result['live_pay_log'] = $GLOBALS['db']->query("delete from ".DB_PREFIX."live_pay_log");
            $result['live_pay_log_history'] = $GLOBALS['db']->query("delete from ".DB_PREFIX."live_pay_log_history");
        }

        //家族
        if(defined('OPEN_FAMILY_MODULE')&&OPEN_FAMILY_MODULE==1){
            $result['family'] = $GLOBALS['db']->query("delete from ".DB_PREFIX."family");
            $result['family_join'] = $GLOBALS['db']->query("delete from ".DB_PREFIX."family_join");
            $result['family_level'] = $GLOBALS['db']->query("delete from ".DB_PREFIX."family_level");
        }
        if(defined('OPEN_GAME_MODULE')&&OPEN_GAME_MODULE==1){
            $result = array_merge($result,$this->clear_game_data());
        }
        if(defined('OPEN_GAME_MODULE')&&OPEN_PAI_MODULE==1){
            $result = array_merge($result,$this->pai_delete_data());
        }
        if(defined('OPEN_GAME_MODULE')&&SHOPPING_GOODS==1){
            $result = array_merge($result,$this->shop_delete_data());
        }
        if(defined('OPEN_GAME_MODULE')&&OPEN_PODCAST_GOODS==1){
            $result = array_merge($result,$this->podcast_goods_delete_data());
        }

        $root = array();
        $root['status'] = 1;
        $root['error'] = json_encode($result);
        print_r($root);
    }
    public function clear_game_data()
    {
        require_once APP_ROOT_PATH . 'mapi/lib/core/Model.class.php';
        Model::$lib = dirname(__FILE__);
        $result     = array();
        $variable   = [
            'coin_log',
            'game_log',
            'game_log_history',
            'user_game_log',
            'user_game_log_history',
            'banker_log',
            'banker_log_history',
            'game_distribution',
        ];
        foreach ($variable as $value) {
            $result[$value] = Model::build($value)->delete(['id' => ['>', 0]]);
        }
        return $result;
    }

    //清空竞拍数据
    public function pai_delete_data(){
        require_once APP_ROOT_PATH . 'mapi/lib/core/Model.class.php';
        Model::$lib = dirname(__FILE__);
        $result = array();
        $variable   = [
            'pai_goods',
            'pai_join',
            'goods_order',
            'user_address',
            'user_notice',
            'pai_tags',
            'user_diamonds_log',
            'pai_log',
            'pai_violations',
            'goods',
            'user_goods',
            'courier',
            'goods_cate',
            'goods_tags',
        ];
        foreach($variable as $key => $value){
            $result[$value] = Model::build($value)->delete(['id'=>['>',0]]);
        }
        return $result;
    }

    //清空购物数据
    public function shop_delete_data(){
        require_once APP_ROOT_PATH . 'mapi/lib/core/Model.class.php';
        Model::$lib = dirname(__FILE__);
        $result = array();
        $variable   = [
            'goods_order',
            'user_address',
            'user_notice',
            'pai_tags',
            'user_diamonds_log',
            'goods',
            'user_goods',
            'courier',
            'shopping_cart',
            'goods_cate',
            'goods_tags',
        ];
        foreach($variable as $key => $value){
            $result[$value] = Model::build($value)->delete(['id'=>['>',0]]);
        }
        return $result;
    }

    //清空小店数据
    public function podcast_goods_delete_data(){
        require_once APP_ROOT_PATH . 'mapi/lib/core/Model.class.php';
        Model::$lib = dirname(__FILE__);
        $result = array();
        $result['podcast_goods'] = Model::build('podcast_goods')->delete(['id'=>['>',0]]);

        return $result;
    }
   //获取腾讯aeskey
	public function get_aes_key(){
        if(IS_DEBUG) {
            $m_config = load_auto_cache("m_config");//初始化手机端配置
            require_once(APP_ROOT_PATH . 'system/tim/TimApi.php');
            $api = createTimAPI();
            $group_id = strim($m_config['full_group_id']);
            $base_info_filter = array("Introduction");
            $ret = $api->group_get_group_info2(array('0' => $group_id), $base_info_filter);
            print_r($ret);
        }
	}
    //获取服务端key
    public function get_privatekey(){
        $key_list = get_privatekey();
        print_r($key_list);
    }

}


?>
