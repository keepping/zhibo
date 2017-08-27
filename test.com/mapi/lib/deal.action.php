<?php
// +----------------------------------------------------------------------
// | Fanwe 方维p2p借贷系统
// +----------------------------------------------------------------------
// | Copyright (c) 2011 http://www.fanwe.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: 云淡风轻(88522820@qq.com)
// +----------------------------------------------------------------------

class dealModule
{
  

    //测试发送礼物
    public function test_pop_prop()
    {


        /*if(!$GLOBALS['user_info']){
            $user_id = intval($_REQUEST['cstype']);
            $sql = "select * from ".DB_PREFIX."user where id=".$user_id;
            $$GLOBALS['user_info'] = $GLOBALS['db']->getRow($sql);
        }

        $sql = "select id,is_animated from ".DB_PREFIX."prop where is_red_envelope = 0 order by rand() limit 1";
        $prop = $GLOBALS['db']->getRow($sql);

        $prop_id = intval($prop['id']);
        $_REQUEST['prop_id'] = $prop_id;

        //0:普通礼物 1:gif礼物 2:大型动画礼物
        if ($prop['is_animated'] == 0){
            $_REQUEST['is_plus'] = 1;
        }else{
            $_REQUEST['is_plus'] = 0;
        }

        $this->pop_prop();*/

    }

    /**
     * 送礼物
     */
    public function pop_prop(){
        $root = array();

        if(!$GLOBALS['user_info']){
            $root['error'] = "用户未登陆,请先登陆.";
            $root['status'] = 0;
            $root['user_login_status'] = 0;//有这个参数： user_login_status = 0 时，表示服务端未登陆、要求登陆，操作
        }else{
            $user_id = intval($GLOBALS['user_info']['id']);

            $prop_id = intval($_REQUEST['prop_id']);//礼物id
            $num = intval($_REQUEST['num']);//礼物数量
            $is_plus = intval($_REQUEST['is_plus']);//1显示连续;
            $video_id = strim($_REQUEST['room_id']);//直播ID 也是room_id
            $from=strim($_REQUEST['from']);//判断发送来源 pc或者app

            //
            //$sql = "select id,user_id,group_id from ".DB_PREFIX."video where id = ".$video_id;
            //$video = $GLOBALS['db']->getRow($sql);

            fanwe_require(APP_ROOT_PATH.'mapi/lib/redis/VideoRedisService.php');
            $video_redis = new VideoRedisService();
            $video = $video_redis->getRow_db($video_id,array('id','user_id','group_id','prop_table','room_type'));

            $group_id = strim($video['group_id']);//群组ID
            $podcast_id = intval($video['user_id']);//送给谁，有群组ID(group_id)，除了红包外其它的都是送给：群主
            $room_type = intval($video['room_type']);//直播间类型 房间类型 : 1私有群（Private）,0公开群（Public）,2聊天室（ChatRoom）,3互动直播聊天室（AVChatRoom）

            $is_nospeaking = $GLOBALS['db']->getOne("SELECT is_nospeaking FROM ".DB_PREFIX."user WHERE id=".$user_id,true,true);
            if($is_nospeaking){
                $root['status'] = 0;
                $root['error'] = "被im全局禁言，不能发礼物";
                ajax_return($root);
            }

            if($user_id == $podcast_id){
                $root['error'] = "不能发礼物给自己";
                $root['status'] = 0;
                ajax_return($root);
            }

            //检查测试账号不能发礼物给真实主播
            $sql = "select mobile from ".DB_PREFIX."user where id = '".$podcast_id."'";
            $podcast_mobile =  $GLOBALS['db']->getOne($sql);
            if(($GLOBALS['user_info']['mobile'] == '13888888888'&&$podcast_mobile!='13999999999')||$GLOBALS['user_info']['mobile'] == '13999999999'&&$podcast_mobile!='13888888888'){
                $root['error'] = "测试账号不能发礼物给真实主播";
                $root['status'] = 0;
                ajax_return($root);
            }


            //以后需要从缓存中读取
            //$sql = "select id,name,score,diamonds,icon,ticket,is_much,sort,is_red_envelope,is_animated from ".DB_PREFIX."prop where id = '".$prop_id."'";
            //$prop = $GLOBALS['db']->getRow($sql);

            $prop = load_auto_cache("prop_id",array('id'=>$prop_id));

            if ($num <= 0) $num = 1;
            $total_diamonds = $num * $prop['diamonds'];
            $total_score = $num * $prop['score'];
            $total_ticket = intval($num * $prop['ticket']);
            $robot_diamonds = intval($prop['robot_diamonds']);

            fanwe_require(APP_ROOT_PATH.'mapi/lib/redis/UserRedisService.php');
            $user_redis = new UserRedisService();


            $root =  $this->pack_prop($video['prop_table'],$video_redis,$total_diamonds,$total_score,$total_ticket,$num,$prop,$is_plus,$video_id,$user_id,$prop_id,$podcast_id,$group_id,$room_type,$from,$robot_diamonds);
        }
        ajax_return($root);
    }

   private function check_invite($user_id, $total_ticket, $prop_id)
    {
        if (!defined('OPEN_INVITE_CODE') || OPEN_INVITE_CODE != 1) {
            return;
        }
        $m_config = load_auto_cache('m_config');
        if ($m_config['invite_ratio'] <= 0 || $m_config['invite_ratio'] > 1) {
            return;
        }

        $ratio = round($total_ticket * $m_config['invite_ratio'], 2);
        if ($ratio <= 0) {
            return;
        }

        $invite_by_user_id = $GLOBALS['db']->getOne("select invite_by_user_id from fanwe_user_invite where user_id = {$user_id}");
        if ($invite_by_user_id <= 0) {
            return;
        }

        $invite_user = $GLOBALS['db']->getRow("select is_effect,is_authentication from " . DB_PREFIX . "user where id = {$invite_by_user_id}");
        if (!$invite_user['is_effect'] && $invite_user['is_authentication'] == 2) {
            return;
        }

        $GLOBALS['db']->query("update " . DB_PREFIX . "user set ticket = ticket + " . $ratio . " where id = " . $invite_by_user_id);
        $GLOBALS['db']->autoExecute(DB_PREFIX . "invite_distribution_log", array(
            'from_user_id' => $user_id,
            'to_user_id' => $invite_by_user_id,
            'create_date' => to_date(NOW_TIME, 'Y-m-d'),
            'prop_id' => $prop_id,
            'ticket' => $ratio,
            'create_time' => NOW_TIME,
            'create_ym' => to_date(NOW_TIME, 'Ym'),
            'create_d' => to_date(NOW_TIME, 'd'),
            'create_w' => to_date(NOW_TIME, 'W'),
        ));

        user_deal_to_reids(array($invite_by_user_id));
    }


    /**
     * 礼物封装
     */
    public function pack_prop($table,$video_redis,$total_diamonds,$total_score,$total_ticket,$num,$prop,$is_plus,$video_id,$user_id,$prop_id,$podcast_id,$group_id,$room_type,$from,$robot_diamonds){
        $pInTrans = $GLOBALS['db']->StartTrans();
        try
        {


            fanwe_require(APP_ROOT_PATH.'mapi/lib/redis/UserRedisService.php');
            $user_redis = new UserRedisService();
            //免费礼物
            if($total_diamonds==0&&$total_score==0&&$total_ticket==0){

                $type = 1;
                //普通会员收到的提示内容;
                $desc = "我送了".$num."个".$prop['name'];
                //礼物接收人（主播）收到的提示内容
                $desc2 = $desc;


                $ext = array();
                $ext['type'] = $type; //0:普通消息;1:礼物;2:弹幕消息;3:主播退出;4:禁言;5:观众进入房间；6：观众退出房间；7:直播结束; 8:红包
                $ext['num'] = $num;
                $ext['is_plus'] = $is_plus;//1：数量连续叠加显示;0:不叠加;这个值是从客户端上传过来的
                $ext['is_much'] = $prop['is_much'];//1:可以连续发送多个;用于小金额礼物
                $ext['room_id'] = $video_id;//直播ID 也是room_id;只有与当前房间相同时，收到消息才响应
                $ext['is_animated'] = $prop['is_animated'];//1:动画；0：未动画

                //消息发送者
                $sender = array();
                $user_info = $user_redis->getRow_db($user_id,array('nick_name','head_image','user_level','v_icon'));
                $sender['user_id'] = $user_id;//发送人昵称
                $sender['nick_name'] = $user_info['nick_name'];//发送人昵称
                $sender['head_image'] = get_spec_image($user_info['head_image']);//发送人头像
                $sender['user_level'] = $user_info['user_level'];//用户等级
                $sender['v_icon'] = $user_info['v_icon'];//认证图标
                $ext['sender'] = $sender;
                if ($type == 1){
                    $ext['prop_id'] = $prop_id; //礼物id
                    $ext['icon'] = get_spec_image($prop['icon']);//图片，是否要: 大中小格式？
                    $ext['total_ticket'] = intval($user_redis->getOne_db($podcast_id,'ticket'));//用户总的：印票数
                    $ext['to_user_id'] = $podcast_id;//礼物接收人（主播）
                    $ext['fonts_color'] = '';//字体颜色
                    $ext['desc'] = $desc;//普通群员收到的提示内容;
                    $ext['desc2'] = $desc2;//礼物接收人（主播）收到的提示内容;
                    $ext['anim_type'] = $prop['anim_type'];//大型道具类型;
                    $ext['top_title'] = $sender['nick_name']."送了,".$prop['name'];//大型道具类型，标题;
                    $ext['anim_cfg'] = $prop['anim_cfg'];
                }
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



                if (isset($_REQUEST['is_debug'])){
                    $root['error'] = '';
                    $root['status'] = 1;
                }else{
                    fanwe_require(APP_ROOT_PATH.'system/tim/TimApi.php');
                    $api = createTimAPI();

                    $ret = $api->group_send_group_msg2($user_id, $group_id, $msg_content);
                    if ($ret['ActionStatus'] == 'FAIL' && $ret['ErrorCode'] == 10002){
                        //10002 系统错误，请再次尝试或联系技术客服。
                        log_err_file(array(__FILE__,__LINE__,__METHOD__,$ret));
                        $ret = $api->group_send_group_msg2($user_id, $group_id, $msg_content);
                    }



                    //$videoGift_redis->update_db($user_prop_id, $ret);

                    if ($ret['ActionStatus'] == 'FAIL'){
                        $root['error'] = $ret['ErrorInfo'].":".$ret['ErrorCode'];
                        $root['status'] = 0;
                    }else{
                        $root['error'] = '';
                        $root['status'] = 1;
                    }
                }
            }else{
                //私密直播间送红包不加经验
                if ($prop['is_red_envelope'] ==1&&$room_type==1){
                    $total_score = 0;
                }
                //减少用户钻石
                $sql = "update ".DB_PREFIX."user set diamonds = diamonds - ".$total_diamonds.", use_diamonds = use_diamonds + ".$total_diamonds.", score = score + ".$total_score." where id = '".$user_id."' and diamonds >= ".$total_diamonds;
                //$sql = "update ".DB_PREFIX."user set diamonds = diamonds - ".$total_diamonds.", use_diamonds = use_diamonds + ".$total_diamonds.", score = score + ".$total_score." where id = ".$user_id;
                //echo $sql;exit;
                $GLOBALS['db']->query($sql);
                if($GLOBALS['db']->affected_rows()){

                    //记录：钻石 减少日志
                    if ($total_ticket > 0){
                        if ($prop['is_red_envelope'] == 1){
                            //主播增加：钻石 数量
                            //$user_redis->lock_diamonds($podcast_id,$total_ticket);
                            $sql = "update ".DB_PREFIX."user set diamonds = diamonds + ".$total_ticket." where id = ".$podcast_id;
                            $GLOBALS['db']->query($sql);

                        }else{
                            if(defined("robot_gifts") && robot_gifts ==1){
                                $roboter = $GLOBALS['db']->getOne("select roboter from ".DB_PREFIX."user where roboter=1 and id=".$user_id);//查询是否特殊权限用户
                                if($roboter){
                                    //增加：不可提现印票
                                    $sql = "update ".DB_PREFIX."user set no_ticket = no_ticket + ".$total_ticket." where id = ".$podcast_id;
                                    $GLOBALS['db']->query($sql);

                                }else{
                                    //增加：用户印票
                                    $sql = "update ".DB_PREFIX."user set ticket = ticket + ".$total_ticket." where id = ".$podcast_id;
                                    $GLOBALS['db']->query($sql);
                                }
                            }else{
                                //增加：用户印票
                                $sql = "update ".DB_PREFIX."user set ticket = ticket + ".$total_ticket." where id = ".$podcast_id;
                                $GLOBALS['db']->query($sql);
                            }
                        }

                        // 邀请码分销
                        $this->check_invite($user_id, $total_ticket, $prop_id);

                        /*
                         * 记录在redis中
                        //当前直播获得印票数
                        $sql = "update ".DB_PREFIX."video set vote_number = vote_number + ".$total_ticket." where id =".$video_id;
                        $GLOBALS['db']->query($sql);

                        */
                    }



                    //=========数据库更新成功后,处理redis数据==========

                    //插入:送礼物表 修改礼物直接写入 mysql @by slf
                    $video_prop = array();
                    $video_prop['prop_id'] = $prop_id;
                    $video_prop['prop_name'] = "'".$prop['name']."'";
                    $video_prop['is_red_envelope'] = $prop['is_red_envelope'];
                    $video_prop['total_score'] = $total_score;
                    $video_prop['total_diamonds'] = $total_diamonds;
                    $video_prop['total_ticket'] = intval($total_ticket);//is_red_envelope=1时,为主播获得的：钻石 数量
                    $video_prop['from_user_id'] = $user_id;
                    $video_prop['to_user_id'] = $podcast_id;
                    $video_prop['create_time'] = NOW_TIME;
                    $video_prop['create_date'] = "'".to_date(NOW_TIME,'Y-m-d')."'";
                    $video_prop['num'] = $num;
                    $video_prop['video_id'] = $video_id;
                    $video_prop['group_id'] = "'".$group_id."'";

                    $video_prop['create_ym'] = to_date($video_prop['create_time'],'Ym');
                    $video_prop['create_d'] = to_date($video_prop['create_time'],'d');
                    $video_prop['create_w'] = to_date($video_prop['create_time'],'W');
                    $video_prop['from_ip'] = "'".get_client_ip()."'";
                    $table_info = $GLOBALS['db']->getRow("Describe ".$table." from_ip",true,true);
                    if(!$table_info){
                        $GLOBALS['db']->query("ALTER TABLE ".$table." ADD COLUMN `from_ip` varchar(255) NOT NULL  COMMENT '送礼物人IP'");
                    }

                    //将礼物写入mysql表中
                    $field_arr = array('prop_id','prop_name','is_red_envelope', 'total_score', 'total_diamonds', 'total_ticket','from_user_id', 'to_user_id', 'create_time','create_date','num','video_id','group_id','create_ym','create_d','create_w','from_ip');
                    $fields = implode(",",$field_arr);
                    $valus = implode(",",$video_prop);
                    fanwe_require (APP_ROOT_PATH.'mapi/lib/core/common.php');
                    //$table = createPropTable();
                    $sql = "insert into ".$table." (".$fields.") VALUES (".$valus.")";

                    $GLOBALS['db']->query($sql);
                    $user_prop_id = $GLOBALS['db']->insert_id();
                    //提交事务,不等 消息推送,防止锁太久
                    $GLOBALS['db']->Commit($pInTrans);
                    $pInTrans = false;//防止，下面异常时，还调用：Rollback


                    if ($prop['is_red_envelope'] == 0 && $total_ticket > 0){
                        //贡献榜
                        fanwe_require(APP_ROOT_PATH.'mapi/lib/redis/VideoContributionRedisService.php');
                        $videoCont_redis = new VideoContributionRedisService();
                        $videoCont_redis->insert_db($user_id, $podcast_id, $video_id, $total_ticket);
                    }
                    //分销功能 计算抽成
                    if(defined('OPEN_DISTRIBUTION')&&OPEN_DISTRIBUTION==1&&$prop['is_red_envelope'] == 0&&$total_ticket>0){
                        $this->distribution_calculate($user_id,$total_ticket);
                    }
                    if ($prop['is_red_envelope'] == 0&&$total_ticket>0) {
                        $this->game_distribution($podcast_id, $video_id, $total_ticket);
                    }

                    user_deal_to_reids(array($user_id,$podcast_id));

                    //更新用户等级
                    $user_info = $user_redis->getRow_db($user_id,array('id','score','online_time','user_level'));
                    user_leverl_syn($user_info);

                    /*
                    $sql = "select diamonds,use_diamonds,score,ticket,user_level,refund_ticket from ".DB_PREFIX."user where id = ".$user_id;
                    $user_data = $GLOBALS['db']->getRow($sql);
                    $user_redis->update_db($user_id, $user_data);

                    $sql = "select diamonds,use_diamonds,score,ticket,user_level,refund_ticket from ".DB_PREFIX."user where id = ".$podcast_id;
                    $user_data = $GLOBALS['db']->getRow($sql);
                    $user_redis->update_db($podcast_id, $user_data);
                    */

                    //=================发送:礼物=================================

                    $type = 1;
                    //普通会员收到的提示内容;
                    if ($prop['is_red_envelope'] == 1){
                        $desc = '我给大家送了一个红包';
                        $type = 8;
                    }else{
                        $desc = "我送了".$num."个".$prop['name'];
                    }

                    //礼物接收人（主播）收到的提示内容
                    $desc2 = $desc;


                    $ext = array();
                    $ext['type'] = $type; //0:普通消息;1:礼物;2:弹幕消息;3:主播退出;4:禁言;5:观众进入房间；6：观众退出房间；7:直播结束; 8:红包
                    $ext['num'] = $num;
                    $ext['is_plus'] = $is_plus;//1：数量连续叠加显示;0:不叠加;这个值是从客户端上传过来的
                    $ext['is_much'] = $prop['is_much'];//1:可以连续发送多个;用于小金额礼物
                    $ext['room_id'] = $video_id;//直播ID 也是room_id;只有与当前房间相同时，收到消息才响应

                    if($prop['is_much']) {
                        // 计算连发次数，兼容 PC 端
                        $key = "user:prop:{$user_id}:{$video_id}:{$prop_id}";
                        $user_prop = $GLOBALS['cache']->get($key, true);
                        if($user_prop && $user_prop['time'] > NOW_TIME){
                            $plus_num = $user_prop['num'] + 1;
                            if($from=='pc' && $plus_num > 1){
                                $is_plus=1;
                            }
                        } else {
                            $plus_num = 1;
                        }
                        $ext['plus_num'] = $plus_num;
                        // app 上传 is_plus 视为连发
                        $GLOBALS['cache']->set($key, array('time' => NOW_TIME + 5, 'num' => $plus_num), 5, true);
                        //APP
                        $key = "user:prop:{'app'}{$user_id}:{$video_id}:{$prop_id}";
                        $app_user_prop = $GLOBALS['cache']->get($key, true);
                        if($app_user_prop && $is_plus==1){
                            $app_plus_num = $app_user_prop['num'] + 1;
                        } else {
                            $app_plus_num = 1;
                        }
                        $ext['app_plus_num'] = $app_plus_num;
                        // app 上传 is_plus 视为连发
                        $GLOBALS['cache']->set($key, array('num' => $app_plus_num), 86400, true);

                    }else{
                        $ext['app_plus_num'] = 1;
                    }




                    $ext['is_animated'] = $prop['is_animated'];//1:动画；0：未动画

                    //消息发送者
                    $sender = array();
                    $user_info = $user_redis->getRow_db($user_id,array('nick_name','head_image','user_level','v_icon'));
                    $sender['user_id'] = $user_id;//发送人昵称
                    $sender['nick_name'] = $user_info['nick_name'];//发送人昵称
                    $sender['head_image'] = get_spec_image($user_info['head_image']);//发送人头像
                    $sender['user_level'] = $user_info['user_level'];//用户等级
                    $sender['v_icon'] = $user_info['v_icon'];//认证图标

                    $ext['sender'] = $sender;


                    if ($type == 1){
                        $ext['prop_id'] = $prop_id; //礼物id
                        //$ext['animated_url'] = $prop['animated_url'];//动画播放url
                        $ext['icon'] = get_spec_image($prop['icon']);//图片，是否要: 大中小格式？
                        //$ext['is_red_envelope'] = $prop['is_red_envelope'];//是否是：红包；1:红包
                        $ext['user_prop_id'] = $user_prop_id; //红包时用到，抢红包的id
                        //$ext['show_num'] = $show_num;//显示连续送的礼物数量;

                        $fields = array('ticket','no_ticket');
                        $user_info = $user_redis->getRow_db($podcast_id,$fields);//用户总的：印票数
                        $ext['total_ticket'] =$user_info['ticket']+$user_info['no_ticket'];//用户总的：印票数
                        $ext['to_user_id'] = $podcast_id;//礼物接收人（主播）
                        $ext['fonts_color'] = '';//字体颜色
                        $ext['desc'] = $desc;//普通群员收到的提示内容;
                        $ext['desc2'] = $desc2;//礼物接收人（主播）收到的提示内容;
                        $ext['anim_type'] = $prop['anim_type'];//大型道具类型;

                        $ext['top_title'] = $sender['nick_name']."送了,".$prop['name'];//大型道具类型，标题;

                        /*
                        if ($ext['is_animated'] == 1){
                            //要缓存getAllCached
                            $sql = "select id,url,play_count,delay_time,duration,show_user,type from ".DB_PREFIX."prop_animated where prop_id = ".$prop_id." order by sort desc";
                            $anim_list = $GLOBALS['db']->getAll($sql);
                            $ext['anim_cfg'] = $anim_list;
                            //$ext['sql'] = $sql;
                        }else{
                            $ext['anim_cfg'] = array();
                        }
                        */

                        $ext['anim_cfg'] = $prop['anim_cfg'];
                    }else{
                        $ext['prop_id'] = $prop_id; //礼物id
                        //$ext['animated_url'] = $prop['animated_url'];//动画播放url
                        $ext['icon'] = get_spec_image($prop['icon']);//图片，是否要: 大中小格式？
                        //$ext['is_red_envelope'] = $prop['is_red_envelope'];//是否是：红包；1:红包
                        $ext['user_prop_id'] = $user_prop_id; //红包时用到，抢红包的id
                        //$ext['show_num'] = $show_num;//显示连续送的礼物数量;
                        $ext['total_ticket'] = intval($user_redis->getOne_db($podcast_id,'ticket'));//用户总的：印票数
                        $ext['to_user_id'] = $podcast_id;//礼物接收人（主播）
                        $ext['to_diamonds'] = $total_ticket;//礼物接收人（主播）,获得的：钻石 数量
                        $ext['fonts_color'] = '';//字体颜色
                        $ext['desc'] = $desc;//普通群员收到的提示内容;
                        $ext['desc2'] = $desc2;//礼物接收人（主播）收到的提示内容;


                        $allot_diamonds = 0;
                        if ($prop['is_red_envelope'] ==1&&$room_type==1){

                        }else{
                            if ($robot_diamonds > 0){
                                //优先分配给：观众列表中的机器人
                                $robot_list = $video_redis->get_robot($video_id);

                                $robot_num = count($robot_list);
                                if ($robot_num > 0){
                                    //给一半以上的机器 人分配
                                    $robot_num = mt_rand(ceil($robot_num/2), $robot_num);
                                    //可分配的钻石小于机器人数1.3倍时,减少分配人数
                                    if ($robot_num * 1.3 > $robot_diamonds) $robot_num = ceil($robot_diamonds/2);

                                    $diamonds_list = $this->red_rand_list2($robot_diamonds,$robot_num);

                                    while(count($diamonds_list) > 0){
                                        $money = $diamonds_list[0];
                                        array_splice($diamonds_list,0,1);

                                        $robot_num = count($robot_list) - 1;
                                        $r = mt_rand(0, $robot_num);
                                        $robot_userid = $robot_list[$r];

                                        array_splice($robot_list,$r,1);

                                        //实际分配的
                                        $allot_diamonds = $allot_diamonds + $money;

                                        allot_red_to_user($user_prop_id,$robot_userid,$money);
                                    };
                                }
                            }
                        }

                        //生成一个随机红包队列（观众可抢钻石=diamonds-ticket-robot_diamods)
                        $money_list = $this->red_rand_list($total_diamonds - $total_ticket-$allot_diamonds);
                        fanwe_require(APP_ROOT_PATH.'mapi/lib/redis/VideoRedRedisService.php');
                        $videoRed_redis = new VideoRedRedisService();
                        $videoRed_redis->push_red($user_prop_id, $money_list);

                        //记录直播间发的：红包 记录; 主要用于,直播结束后,处理还未被领取的红包
                        $video_redis->add_red($video_id, $user_prop_id);
                    }





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



                    if (isset($_REQUEST['is_debug'])){
                        $root['error'] = '';
                        $root['status'] = 1;
                    }else{
                        fanwe_require(APP_ROOT_PATH.'system/tim/TimApi.php');
                        $api = createTimAPI();

                        $ret = $api->group_send_group_msg2($user_id, $group_id, $msg_content);
                        if ($ret['ActionStatus'] == 'FAIL' && $ret['ErrorCode'] == 10002){
                            //10002 系统错误，请再次尝试或联系技术客服。
                            log_err_file(array(__FILE__,__LINE__,__METHOD__,$ret));
                            $ret = $api->group_send_group_msg2($user_id, $group_id, $msg_content);
                        }

                        $GLOBALS['db']->autoExecute($table, $ret,'UPDATE','id='.$user_prop_id);

                        //$videoGift_redis->update_db($user_prop_id, $ret);

                        if ($ret['ActionStatus'] == 'FAIL'){
                            $root['error'] = $ret['ErrorInfo'].":".$ret['ErrorCode'];
                            $root['status'] = 0;
                        }else{
                            $root['error'] = '';
                            $root['status'] = 1;
                        }
                    }

                }else{
                    $GLOBALS['db']->Rollback($pInTrans);
                    $root['error'] = "用户钻石不足";
                    $root['status'] = 0;
                }
            }


        }catch(Exception $e){
            //异常回滚
            $root['error'] = $e->getMessage();
            $root['status'] = 0;

            $GLOBALS['db']->Rollback($pInTrans);
        }
        if (defined('OPEN_MISSION') && OPEN_MISSION) {
            require_once APP_ROOT_PATH . 'mapi/lib/core/Model.class.php';
            Model::$lib = dirname(__FILE__);
            Model::build('mission')->incProgress($user_id,2);
        }
        return $root;
    }

    /**
     * 弹幕消息接口
     */
    public function pop_msg()
    {
        $root = array();

        if (!$GLOBALS['user_info']) {
            $root['error'] = "用户未登陆,请先登陆.";
            $root['status'] = 0;
            $root['user_login_status'] = 0;//有这个参数： user_login_status = 0 时，表示服务端未登陆、要求登陆，操作
        } else {
            $user_id = intval($GLOBALS['user_info']['id']);
            $room_id = strim($_REQUEST['room_id']);//直播ID 也是room_id
            $msg = strim($_REQUEST['msg']);//消息内容8J+UkQ==
            //$to_user_id = intval($_REQUEST['to_user_id']);//群主ID

            $is_nospeaking = $GLOBALS['db']->getOne("SELECT is_nospeaking FROM " . DB_PREFIX . "user WHERE id=" . $user_id,
                true, true);

            if ($is_nospeaking) {
                $root['status'] = 0;
                $root['error'] = "被im全局禁言，不能发消息";
                ajax_return($root);
            }

            //$sql = "select id,user_id,group_id from ".DB_PREFIX."video where id = ".$room_id;
            //$video = $GLOBALS['db']->getRow($sql);

            fanwe_require(APP_ROOT_PATH . 'mapi/lib/redis/VideoRedisService.php');
            $video_redis = new VideoRedisService();
            $video = $video_redis->getRow_db($room_id, array('id', 'user_id', 'group_id', 'prop_table'));

            fanwe_require(APP_ROOT_PATH . 'mapi/lib/redis/UserRedisService.php');
            $user_redis = new UserRedisService();

            $group_id = strim($video['group_id']);//群组ID
            $podcast_id = intval($video['user_id']);//送给谁，有群组ID(group_id)，除了红包外其它的都是送给：群主

            $m_config = load_auto_cache("m_config");//初始化手机端配置

            $is_podcast = false;
            //主播,自己发送 弹幕 消息,不扣钻石
            if ($podcast_id == $user_id) {
                $ext = array();
                $ext['type'] = 2; //0:普通消息;1:礼物;2:弹幕消息;3:主播退出;4:禁言;5:观众进入房间；6：观众退出房间；7:直播结束
                $ext['room_id'] = $room_id;//直播ID 也是room_id;只有与当前房间相同时，收到消息才响应
                $ext['num'] = 1;
                $ext['prop_id'] = 0; //礼物id
                //s$ext['animated_url'] = '';//动画播放url
                $ext['icon'] = '';//图片，是否要: 大中小格式？
                //$ext['is_red_envelope'] = 0;//是否是：红包；1:红包
                $ext['user_prop_id'] = 0; //红包时用到，抢红包的id
                //$ext['show_num'] = 1;//显示连续送的礼物数量;
                $ext['total_ticket'] = intval($user_redis->getOne_db($podcast_id, 'ticket'));//用户总的：印票数
                $ext['to_user_id'] = 0;//礼物接收人（主播）
                $ext['fonts_color'] = '';//字体颜色
                $ext['desc'] = $msg;//弹幕消息;
                $ext['desc2'] = $msg;//弹幕消息;

                //消息发送者
                $sender = array();
                $user_info = $user_redis->getRow_db($user_id, array('nick_name', 'head_image', 'user_level', 'v_icon'));
                $sender['user_id'] = $user_id;//发送人昵称
                $sender['nick_name'] = $user_info['nick_name'];//发送人昵称
                $sender['head_image'] = get_spec_image($user_info['head_image']);//发送人头像
                $sender['user_level'] = $user_info['user_level'];//用户等级
                $sender['v_icon'] = $user_info['v_icon'];//认证图标

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


                if (intval($m_config['has_dirty_words']) == 1) {
                    //文档内容,用来过滤脏字
                    $msg_text_elem = array(
                        'MsgType' => 'TIMTextElem', //
                        'MsgContent' => array(
                            'Text' => $msg,
                        )
                    );
                    array_push($msg_content, $msg_text_elem, $msg_content_elem);
                } else {
                    //将创建的元素$msg_content_elem, 加入array $msg_content
                    array_push($msg_content, $msg_content_elem);
                }


                if (isset($_REQUEST['is_debug'])) {
                    $root['error'] = '';
                    $root['status'] = 1;
                } else {
                    fanwe_require(APP_ROOT_PATH . 'system/tim/TimApi.php');
                    $api = createTimAPI();
                    $ret = $api->group_send_group_msg2($user_id, $group_id, $msg_content);
                }

                if ($ret['ActionStatus'] == 'FAIL') {
                    log_err_file(array(__FILE__, __LINE__, __METHOD__, $ret));
                    if ($ret['ErrorCode'] == 80001) {
                        $root['error'] = '该词已被禁用';
                    } else {
                        $root['error'] = $ret['ErrorInfo'] . ":" . $ret['ErrorCode'];
                    }
                    $root['status'] = 0;
                } else {
                    $root['error'] = '';
                    $root['status'] = 0;//app端通过这个判断是否扣除钻石，1为扣除
                }
            } else {


                //$sql = "select id from ".DB_PREFIX."video_forbid_send_msg where group_id='".$group_id."' and user_id = ".$user_id;
                //$has_forbid = $GLOBALS['db']->getOne($sql,true,true) > 0;

                $has_forbid = $video_redis->has_forbid_msg($group_id, $user_id);
                if ($has_forbid) {
                    $root['error'] = "被禁言,不能发送消息";
                    $root['status'] = 0;
                } else {

                    //file_put_contents(APP_ROOT_PATH.'mapi/lib/msg.txt', $msg);

                    //$msg2 = unserialize(file_get_contents(APP_ROOT_PATH.'mapi/lib/msg2.txt'));
                    //$msg =$msg .'【'.base64_decode("8J+UkQ==").'】';


                    $total_diamonds = 1;
                    $total_score = 1;
                    $total_ticket = 1;

                    $pInTrans = $GLOBALS['db']->StartTrans();
                    try {
                        $sql = "update " . DB_PREFIX . "user set diamonds = diamonds - " . $total_diamonds . ",use_diamonds = use_diamonds + " . $total_diamonds . ", score = score + " . $total_score . " where id = '" . $user_id . "' and diamonds >= " . $total_diamonds;
                        $GLOBALS['db']->query($sql);
                        if ($GLOBALS['db']->affected_rows()) {
                            if ($total_ticket > 0) {
                                if(defined("robot_gifts") && robot_gifts ==1){
                                    $roboter = $GLOBALS['db']->getOne("select roboter from ".DB_PREFIX."user where roboter=1 and id=".$user_id);//查询是否特殊权限用户
                                    if($roboter){
                                        //增加：不可提现印票
                                        $sql = "update ".DB_PREFIX."user set no_ticket = no_ticket + ".$total_ticket." where id = ".$podcast_id;
                                        $GLOBALS['db']->query($sql);

                                    }else{
                                        //增加：用户印票
                                        $sql = "update ".DB_PREFIX."user set ticket = ticket + ".$total_ticket." where id = ".$podcast_id;
                                        $GLOBALS['db']->query($sql);
                                    }
                                }else{
                                    //增加：用户印票
                                    $sql = "update " . DB_PREFIX . "user set ticket = ticket + " . $total_ticket . " where id = " . $podcast_id;
                                    $GLOBALS['db']->query($sql);
                                }
                                /*
                                //当前直播获得印票数
                                $sql = "update ".DB_PREFIX."video set vote_number = vote_number + ".$total_ticket." where id =".$room_id;
                                $GLOBALS['db']->query($sql);
                                */
                                //记录：用户印票增加日志
                            }


                            $video_prop = array();
                            $video_prop['prop_id'] = 0;
                            $video_prop['prop_name'] = "'" . '弹幕' . "'";
                            $video_prop['is_red_envelope'] = 0;
                            $video_prop['total_score'] = $total_score;
                            $video_prop['total_diamonds'] = $total_diamonds;
                            $video_prop['total_ticket'] = intval($total_ticket);
                            $video_prop['from_user_id'] = $user_id;
                            $video_prop['to_user_id'] = $podcast_id;
                            $video_prop['create_time'] = NOW_TIME;
                            $video_prop['create_date'] = "'" . to_date(NOW_TIME, 'Y-m-d') . "'";
                            $video_prop['num'] = 1;
                            $video_prop['video_id'] = $room_id;
                            $video_prop['group_id'] = "'" . $group_id . "'";
                            $video_prop['msg'] = "'" . $msg . "'";

                            $video_prop['create_ym'] = to_date($video_prop['create_time'], 'Ym');
                            $video_prop['create_d'] = to_date($video_prop['create_time'], 'd');
                            $video_prop['create_w'] = to_date($video_prop['create_time'], 'W');
                            $video_prop['from_ip'] = "'".get_client_ip()."'";


                            //将礼物写入mysql表中
                            $field_arr = array(
                                'prop_id',
                                'prop_name',
                                'is_red_envelope',
                                'total_score',
                                'total_diamonds',
                                'total_ticket',
                                'from_user_id',
                                'to_user_id',
                                'create_time',
                                'create_date',
                                'num',
                                'video_id',
                                'group_id',
                                'msg',
                                'create_ym',
                                'create_d',
                                'create_w',
                                'from_ip'
                            );
                            $fields = implode(",", $field_arr);
                            $valus = implode(",", $video_prop);

                            $table = $video['prop_table'];
                            $table_info = $GLOBALS['db']->getRow("Describe ".$table." from_ip",true,true);
                            if(!$table_info){
                                $GLOBALS['db']->query("ALTER TABLE ".$table." ADD COLUMN `from_ip` varchar(255) NOT NULL  COMMENT '送礼物人IP'");
                            }
                            $sql = "insert into " . $table . "(" . $fields . ") VALUES (" . $valus . ")";
                            $GLOBALS['db']->query($sql);
                            $user_prop_id = $GLOBALS['db']->insert_id();

                            //提交事务,不等 消息推送,防止锁太久
                            $GLOBALS['db']->Commit($pInTrans);
                            $pInTrans = false;//防止，下面异常时，还调用：Rollback


                            if ($total_ticket > 0) {
                                fanwe_require(APP_ROOT_PATH . 'mapi/lib/redis/VideoContributionRedisService.php');
                                $videoCont_redis = new VideoContributionRedisService();
                                $videoCont_redis->insert_db($user_id, $podcast_id, $room_id, $total_ticket);
                            }

                            user_deal_to_reids(array($user_id, $podcast_id));

                            //更新用户等级
                            $user_info = $user_redis->getRow_db($user_id,
                                array('id', 'score', 'online_time', 'user_level'));
                            user_leverl_syn($user_info);

                            //分销功能 计算抽成
                            if (defined('OPEN_DISTRIBUTION') && OPEN_DISTRIBUTION == 1 && $total_ticket > 0) {
                                $this->distribution_calculate($user_id, $total_ticket);
                            }
                            $this->game_distribution($podcast_id, $room_id, $total_ticket);
                            //发送:礼物


                            $ext = array();
                            $ext['type'] = 2; //0:普通消息;1:礼物;2:弹幕消息;3:主播退出;4:禁言;5:观众进入房间；6：观众退出房间；7:直播结束
                            $ext['room_id'] = $room_id;//直播ID 也是room_id;只有与当前房间相同时，收到消息才响应
                            $ext['num'] = 1;
                            $ext['prop_id'] = 0; //礼物id
                            //s$ext['animated_url'] = '';//动画播放url
                            $ext['icon'] = '';//图片，是否要: 大中小格式？
                            //$ext['is_red_envelope'] = 0;//是否是：红包；1:红包
                            $ext['user_prop_id'] = $user_prop_id; //红包时用到，抢红包的id
                            //$ext['show_num'] = 1;//显示连续送的礼物数量;
                            $fields = array('ticket','no_ticket');
                            $user_info = $user_redis->getRow_db($podcast_id,$fields);//用户总的：印票数
                            $ext['total_ticket'] =$user_info['ticket']+$user_info['no_ticket'];//用户总的：印票数
                            $ext['to_user_id'] = 0;//礼物接收人（主播）
                            $ext['fonts_color'] = '';//字体颜色
                            $ext['desc'] = $msg;//弹幕消息;
                            $ext['desc2'] = $msg;//弹幕消息;

                            //消息发送者
                            $sender = array();
                            $user_info = $user_redis->getRow_db($user_id,
                                array('nick_name', 'head_image', 'user_level'));
                            $sender['user_id'] = $user_id;//发送人昵称
                            $sender['nick_name'] = $user_info['nick_name'];//发送人昵称
                            $sender['head_image'] = get_spec_image($user_info['head_image']);//发送人头像
                            $sender['user_level'] = $user_info['user_level'];//用户等级

                            $ext['sender'] = $sender;


                            #构造高级接口所需参数
                            $msg_content = array();
                            //创建array 所需元素
                            $msg_content_elem = array(
                                'MsgType' => 'TIMCustomElem',       //自定义类型
                                'MsgContent' => array(
                                    'Data' => json_encode($ext),
                                    'Desc' => '',
                                )
                            );


                            if (intval($m_config['has_dirty_words']) == 1) {
                                //文档内容,用来过滤脏字
                                $msg_text_elem = array(
                                    'MsgType' => 'TIMTextElem', //
                                    'MsgContent' => array(
                                        'Text' => $msg,
                                    )
                                );
                                array_push($msg_content, $msg_text_elem, $msg_content_elem);
                            } else {
                                //将创建的元素$msg_content_elem, 加入array $msg_content
                                array_push($msg_content, $msg_content_elem);
                            }


                            if (isset($_REQUEST['is_debug'])) {
                                $root['error'] = '';
                                $root['status'] = 1;
                            } else {
                                fanwe_require(APP_ROOT_PATH . 'system/tim/TimApi.php');
                                $api = createTimAPI();


                                $ret = $api->group_send_group_msg2($user_id, $group_id, $msg_content);
                                if ($ret['ActionStatus'] == 'FAIL' && $ret['ErrorCode'] == 10002) {
                                    //10002 系统错误，请再次尝试或联系技术客服。
                                    log_err_file(array(__FILE__, __LINE__, __METHOD__, $ret));
                                    $ret = $api->group_send_group_msg2($user_id, $group_id, $msg_content);
                                }

                                $GLOBALS['db']->autoExecute($table, $ret, 'UPDATE', 'id=' . $user_prop_id);


                                //$videoGift_redis->update_db($user_prop_id, $ret);


                                if ($ret['ActionStatus'] == 'FAIL') {
                                    if ($ret['ErrorCode'] == 80001) {
                                        $root['error'] = '该词已被禁用';
                                    } else {
                                        $root['error'] = $ret['ErrorInfo'] . ":" . $ret['ErrorCode'];
                                    }
                                    $root['status'] = 0;
                                } else {
                                    $root['error'] = '';
                                    $root['status'] = 1;
                                }
                            }
                        } else {
                            $GLOBALS['db']->Rollback($pInTrans);
                            $root['error'] = "用户钻石不足";
                            $root['status'] = 0;
                        }


                    } catch (Exception $e) {
                        //异常回滚
                        $root['error'] = $e->getMessage();
                        $root['status'] = 0;

                        $GLOBALS['db']->Rollback($pInTrans);
                    }
                }
            }
        }

        ajax_return($root);
    }

    /**
     * 预生成好，红包随机队列
     * @param unknown_type $total_diamonds
     *
     * 0    1
     * 1    20
     * 2    3
     *
     */
    function red_rand_list($total_diamonds)
    {

        $list = array();
        while ($total_diamonds > 0) {
            $diamonds = mt_rand(1, 20);//随机取：1至20中的一个数字

            if ($total_diamonds >= $diamonds) {
                $total_diamonds = $total_diamonds - $diamonds;
                $list[] = $diamonds;
            } else {
                if ($total_diamonds >= 1) {
                    $diamonds = 1;
                    $total_diamonds = $total_diamonds - $diamonds;
                    $list[] = $diamonds;
                }
            }
        }

        return $list;
    }

    /**
     * 把$total_diamonds 生成指定数量$num的，随机列表数
     * @param unknown_type $total_diamonds
     * @param unknown_type $num
     * @return multitype:number
     */
    function red_rand_list2($total_diamonds, $num)
    {
        $list = array();
        if ($num > $total_diamonds) {
            $num = $total_diamonds;
        }

        //先生成一批为：1 的
        for ($x = 0; $x < $num; $x++) {
            $list[] = 1;
            $total_diamonds = $total_diamonds - 1;
        }

        while ($total_diamonds > 0) {
            foreach ($list as $k => $v) {
                $diamonds = mt_rand(1, 19);//随机取：1至20中的一个数字

                if ($total_diamonds >= $diamonds) {
                    $total_diamonds = $total_diamonds - $diamonds;
                } else {
                    if ($total_diamonds >= 1) {
                        $diamonds = 1;
                        $total_diamonds = $total_diamonds - $diamonds;
                    }
                }

                $list[$k] = $v + $diamonds;

                if ($total_diamonds == 0) {
                    break;
                }
            }
        };

        return $list;
    }


    /**
     * 抢红包
     */
    public function red_envelope()
    {

        $root = array();
        $root['status'] = 1;

        if (!$GLOBALS['user_info']) {
            $root['error'] = "用户未登陆,请先登陆.";
            $root['status'] = 0;
            $root['user_login_status'] = 0;//有这个参数： user_login_status = 0 时，表示服务端未登陆、要求登陆，操作
        } else {
            $user_id = intval($GLOBALS['user_info']['id']);

            $user_prop_id = intval($_REQUEST['user_prop_id']);//红包id


            //============================redis================================================
            fanwe_require(APP_ROOT_PATH . 'mapi/lib/redis/VideoRedRedisService.php');
            $videoRed_redis = new VideoRedRedisService();

            //判断该用户没有抢过;
            $ret = $videoRed_redis->get_user_winning($user_prop_id, $user_id);
            if ($ret == false) {
                //判断是否还有可以抢的红包
                if ($videoRed_redis->red_exists($user_prop_id)) {
                    $money = $videoRed_redis->pop_red($user_prop_id);
                    if ($money > 0) {
                        allot_red_to_user($user_prop_id, $user_id, $money);
                        $root['diamonds'] = $money;

                        $root['error'] = "恭喜您抢到" . $money . "个钻石";
                    } else {
                        $root['status'] = 0;
                        $root['error'] = "手慢了，未捡到";
                    }
                } else {
                    $root['status'] = 0;
                    $root['error'] = "手慢了，未捡到！";
                }
            } else {
                $root['diamonds'] = $ret;
                $root['error'] = "恭喜您抢到" . $ret . "个钻石";
            }
        }

        ajax_return($root);
    }

    /**
     * 抢红包---》看看大家的手气
     */
    public function user_red_envelope()
    {

        $root = array();
        $root['status'] = 1;
        //$GLOBALS['user_info']['id'] = 278;
        if (!$GLOBALS['user_info']) {
            $root['error'] = "用户未登陆,请先登陆.";
            $root['status'] = 0;
            $root['user_login_status'] = 0;//有这个参数： user_login_status = 0 时，表示服务端未登陆、要求登陆，操作
        } else {
            $user_prop_id = intval($_REQUEST['user_prop_id']);//红包id

            fanwe_require(APP_ROOT_PATH . 'mapi/lib/redis/VideoRedRedisService.php');
            $videoRed_redis = new VideoRedRedisService();

            $list = $videoRed_redis->get_winnings($user_prop_id);


            $root['status'] = 1;

            $root['list'] = $list;

        }
        ajax_return($root);
    }

    /**
     * 送礼物给某人
     */
    public function send_prop()
    {
        $root = array();

        if (!$GLOBALS['user_info']) {
            $root['error'] = "用户未登陆,请先登陆." . print_r($_COOKIE, 1);
            $root['status'] = 0;
            $root['user_login_status'] = 0;//有这个参数： user_login_status = 0 时，表示服务端未登陆、要求登陆，操作
        } else {
            $user_id = intval($GLOBALS['user_info']['id']);

            $prop_id = intval($_REQUEST['prop_id']);//礼物id
            $num = intval($_REQUEST['num']);//礼物数量
            $to_user_id = strim($_REQUEST['to_user_id']);//送给谁

            $is_nospeaking = $GLOBALS['db']->getOne("SELECT is_nospeaking FROM " . DB_PREFIX . "user WHERE id=" . $user_id,
                true, true);
            if ($is_nospeaking) {
                $root['status'] = 0;
                $root['error'] = "被im全局禁言，不能发礼物";
                ajax_return($root);
            }

            if ($user_id == $to_user_id) {
                $root['error'] = "不能发礼物给自己";
                $root['status'] = 0;
                ajax_return($root);
            }

            //检查测试账号不能发礼物给真实主播
            $sql = "select mobile from " . DB_PREFIX . "user where id = '" . $to_user_id . "'";
            $podcast_mobile = $GLOBALS['db']->getOne($sql);
            if (($GLOBALS['user_info']['mobile'] == '13888888888' && $podcast_mobile != '13999999999') || $GLOBALS['user_info']['mobile'] == '13999999999' && $podcast_mobile != '13888888888') {
                $root['error'] = "测试账号不能发礼物给真实主播";
                $root['status'] = 0;
                ajax_return($root);
            }

            $prop = load_auto_cache("prop_id", array('id' => $prop_id));

            if ($num <= 0) {
                $num = 1;
            }
            $total_diamonds = $num * $prop['diamonds'];
            $total_score = $num * $prop['score'];
            $total_ticket = intval($num * $prop['ticket']);

            fanwe_require(APP_ROOT_PATH . 'mapi/lib/redis/UserRedisService.php');
            $user_redis = new UserRedisService();

            $pInTrans = $GLOBALS['db']->StartTrans();
            try {
                //免费礼物
                if ($total_diamonds == 0 && $total_score == 0 && $total_ticket == 0) {
                    $m_config = load_auto_cache("m_config");//初始化手机端配置
                    $root['to_msg'] = "收到一个" . $prop['name'] . ",获得" . $total_ticket . $m_config['ticket_name'] . ",可以去个人主页>我的收益 查看哦";
                    $to_diamonds = 0;
                    $to_ticket = intval($total_ticket);
                    $root['from_msg'] = "送给你一个" . $prop['name'];
                    $root['from_score'] = "你的经验值+" . $total_score;
                    $root['to_ticket'] = intval($to_ticket);
                    $root['to_diamonds'] = $to_diamonds;//可获得的：钻石数；只有红包时，才有
                    $root['to_user_id'] = $to_user_id;
                    $root['prop_icon'] = $prop['icon'];
                    $root['status'] = 1;
                    $root['prop_id'] = $prop_id;
                    $root['total_ticket'] = intval($user_redis->getOne_db($to_user_id, 'ticket'));//用户总的：印票数
                } else {
                    //私聊送红包礼物没有经验
                    if ($prop['is_red_envelope'] == 1) {
                        $total_score = 0;
                    }
                    $sql = "update " . DB_PREFIX . "user set diamonds = diamonds - " . $total_diamonds . ", use_diamonds = use_diamonds + " . $total_diamonds . ", score = score + " . $total_score . " where id = '" . $user_id . "' and diamonds >= " . $total_diamonds;
                    $GLOBALS['db']->query($sql);
                    if ($GLOBALS['db']->affected_rows()) {
                        $m_config = load_auto_cache("m_config");//初始化手机端配置
                        //将红包的钻石，直接加给被送用户
                        if ($prop['is_red_envelope'] == 1) {
                            //$desc = '我给大家送了一个红包';
                            //$user_redis->lock_diamonds($to_user_id,$total_diamonds);

                            $sql = "update " . DB_PREFIX . "user set diamonds = diamonds + " . $total_diamonds . " where id = " . $to_user_id;
                            $GLOBALS['db']->query($sql);

                            $root['to_msg'] = "收到一个" . $prop['name'] . ",获得" . $total_diamonds . "钻石,可以去个人主页 查看哦";

                            $to_diamonds = $total_diamonds;//用户添加的：钻石 数;
                            $to_ticket = 0;
                        } else {

                            $sql = "update " . DB_PREFIX . "user set ticket = ticket + " . $total_ticket . " where id = " . $to_user_id;
                            $GLOBALS['db']->query($sql);

                            $root['to_msg'] = "收到一个" . $prop['name'] . ",获得" . $total_ticket . $m_config['ticket_name'] . ",可以去个人主页>我的收益 查看哦";

                            $to_diamonds = 0;
                            $to_ticket = intval($total_ticket);
                        }

                        $this->check_invite($user_id, $total_ticket, $prop_id);

                        //插入:送礼物表
                        $video_prop = array();
                        $video_prop['prop_id'] = $prop_id;
                        $video_prop['prop_name'] = "'" . $prop['name'] . "'";
                        $video_prop['is_red_envelope'] = $prop['is_red_envelope'];
                        $video_prop['total_score'] = $total_score;
                        $video_prop['total_diamonds'] = $total_diamonds;
                        if ($prop['is_red_envelope'] == 1) {
                            $video_prop['total_ticket'] = intval($total_diamonds);
                        } else {
                            $video_prop['total_ticket'] = intval($total_ticket);
                        }
                        $video_prop['from_user_id'] = $user_id;
                        $video_prop['to_user_id'] = $to_user_id;
                        $video_prop['create_time'] = NOW_TIME;
                        $video_prop['create_date'] = to_date(NOW_TIME, 'Y-m-d');
                        $video_prop['num'] = $num;

                        $video_prop['create_ym'] = to_date($video_prop['create_time'], 'Ym');
                        $video_prop['create_d'] = to_date($video_prop['create_time'], 'd');
                        $video_prop['create_w'] = to_date($video_prop['create_time'], 'W');
                        $video_prop['from_ip'] = "'".get_client_ip()."'";

                        //将礼物写入mysql表中
                        $field_arr = array(
                            'prop_id',
                            'prop_name',
                            'is_red_envelope',
                            'total_score',
                            'total_diamonds',
                            'total_ticket',
                            'from_user_id',
                            'to_user_id',
                            'create_time',
                            'create_date',
                            'num',
                            'create_ym',
                            'create_d',
                            'create_w',
                            'from_ip'
                        );
                        $fields = implode(",", $field_arr);
                        $valus = implode(",", $video_prop);

                        $table = createPropTable();
                        $table_info = $GLOBALS['db']->getRow("Describe ".$table." from_ip",true,true);
                        if(!$table_info){
                            $GLOBALS['db']->query("ALTER TABLE ".$table." ADD COLUMN `from_ip` varchar(255) NOT NULL  COMMENT '送礼物人IP'");
                        }
                        $sql = "insert into " . $table . "(" . $fields . ") VALUES (" . $valus . ")";
                        $GLOBALS['db']->query($sql);
                        $user_prop_id = $GLOBALS['db']->insert_id();

                        //提交事务
                        $GLOBALS['db']->Commit($pInTrans);
                        $pInTrans = false;

                        if ($prop['is_red_envelope'] == 0) {
                            fanwe_require(APP_ROOT_PATH . 'mapi/lib/redis/VideoContributionRedisService.php');
                            $videoCont_redis = new VideoContributionRedisService();
                            $videoCont_redis->insert_db($user_id, $to_user_id, 0, $total_ticket);
                        }

                        //分销功能 计算抽成
                        if (defined('OPEN_DISTRIBUTION') && OPEN_DISTRIBUTION == 1 && $prop['is_red_envelope'] == 0 && $total_ticket > 0) {
                            $this->distribution_calculate($user_id, $total_ticket);
                        }
                        $this->game_distribution($to_user_id, 0, $total_ticket);

                        user_deal_to_reids(array($user_id, $to_user_id));

                        //更新用户等级
                        $user_info = $user_redis->getRow_db($user_id,
                            array('id', 'score', 'online_time', 'user_level'));
                        user_leverl_syn($user_info);


                        $root['from_msg'] = "送给你一个" . $prop['name'];
                        $root['from_score'] = "你的经验值+" . $total_score;
                        $root['to_ticket'] = intval($to_ticket);
                        $root['to_diamonds'] = $to_diamonds;//可获得的：钻石数；只有红包时，才有
                        $root['to_user_id'] = $to_user_id;
                        $root['prop_icon'] = $prop['icon'];
                        $root['status'] = 1;
                        $root['prop_id'] = $prop_id;
                        $root['total_ticket'] = intval($user_redis->getOne_db($to_user_id, 'ticket'));//用户总的：印票数


                    } else {
                        $GLOBALS['db']->Rollback($pInTrans);
                        $root['error'] = "用户钻石不足";
                        $root['status'] = 0;
                    }
                }
                //减少用户钻石


            } catch (Exception $e) {
                //异常回滚
                $root['error'] = $e->getMessage();
                $root['status'] = 0;

                $GLOBALS['db']->Rollback($pInTrans);
            }

        }
        ajax_return($root);
    }
    private function game_distribution($podcast_id, $video_id, $total_ticket)
    {
        if (defined('GAME_DISTRIBUTION') && GAME_DISTRIBUTION) {
            require_once APP_ROOT_PATH . 'mapi/lib/core/Model.class.php';
            Model::$lib = dirname(__FILE__);
            Model::build('game_distribution')->addLog($podcast_id, $video_id, 0, $total_ticket, '直播礼物分销',1);
        }
    }
    /*
     * 分销抽成
     */
    private function distribution_calculate($user_id, $total_ticket)
    {
        $root = array();
        $m_config = load_auto_cache("m_config");//初始化手机端配置
        $table = DB_PREFIX . 'distribution_log';
        fanwe_require(APP_ROOT_PATH . 'mapi/lib/redis/UserRedisService.php');
        $user_redis = new UserRedisService();
        $to_user_id = $user_redis->getOne_db($user_id, 'p_user_id');//用户总的：印票数
        $ticket = 0;
        $result = 0;
        if (intval($to_user_id) > 0 && intval($m_config['distribution']) == 1 && $user_id > 0 && $total_ticket > 0) {
            $ticket = round($m_config['distribution_rate'] * 0.01 * $total_ticket, 2);
            $sql = "select id from " . $table . " where to_user_id = " . $to_user_id . " and from_user_id = " . $user_id;
            $distribution_id = $GLOBALS['db']->getOne($sql);
            if (intval($distribution_id) > 0) {
                $sql = "update " . $table . " set ticket = ticket + " . $ticket . " where id = " . $distribution_id;
                $GLOBALS['db']->query($sql);
                $result = 1;
            } else {
                //插入:分销日志
                $video_prop = array();
                $video_prop['from_user_id'] = $user_id;
                $video_prop['to_user_id'] = $to_user_id;
                $video_prop['create_date'] = "'" . to_date(NOW_TIME, 'Y-m-d') . "'";
                $video_prop['ticket'] = $ticket;
                $video_prop['create_time'] = NOW_TIME;
                $video_prop['create_ym'] = to_date($video_prop['create_time'], 'Ym');
                $video_prop['create_d'] = to_date($video_prop['create_time'], 'd');
                $video_prop['create_w'] = to_date($video_prop['create_time'], 'W');

                //将日志写入mysql表中
                $field_arr = array(
                    'from_user_id',
                    'to_user_id',
                    'create_date',
                    'ticket',
                    'create_time',
                    'create_ym',
                    'create_d',
                    'create_w'
                );
                $fields = implode(",", $field_arr);
                $valus = implode(",", $video_prop);

                $sql = "insert into " . $table . "(" . $fields . ") VALUES (" . $valus . ")";
                $GLOBALS['db']->query($sql);
                $result = $GLOBALS['db']->insert_id();
            }
            if (intval($result) > 0) {
                $sql = "update " . DB_PREFIX . "user set ticket = ticket + " . $ticket . " where id = " . $to_user_id;
                $GLOBALS['db']->query($sql);
            }

        }
    }

}