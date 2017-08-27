<?php
// +----------------------------------------------------------------------
// | Fanwe 方维众筹商业系统
// +----------------------------------------------------------------------
// | Copyright (c) 2011 http://www.fanwe.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: 甘味人生(526130@qq.com)
// +----------------------------------------------------------------------

class VideoEndAction extends CommonAction{
	//直播结束列表
	public function endline_index()
	{
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
		
		//$map['live_in'] = 0;
				
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
        }

        $this->assign ( 'list', $list );
		//print_r($list);exit;
        if(defined('OPEN_LIVE_PAY')&&OPEN_LIVE_PAY==1){
            $this->assign ( 'is_pay_live', 1 );
        }else{
            $this->assign ( 'is_pay_live', 0 );
        }
		$cate_list = M("VideoCate")->findAll();
		$this->assign("cate_list",$cate_list);
		$this->display ();
	}
/**
 * 印票贡献榜
 */
    public function contribution_list()
    {

        $video_id = intval($_REQUEST['id']);
        $video = M("VideoHistory")->getById($video_id);
        $this->assign("video",$video);
        $user="select prop_table from ".DB_PREFIX."video_history where id=$video_id";
        $userlist=$GLOBALS['db']->getOne($user,true,true);
        if(defined('OPEN_LIVE_PAY')&&OPEN_LIVE_PAY==1){
            $prop = "select u.id as user_id ,u.nick_name,u.head_image,sum(v.total_diamonds) as num,sum(v.total_ticket) as total_ticket
											from $userlist as v LEFT JOIN  ".DB_PREFIX."user as u  on u.id = v.from_user_id 
											where v.video_id=".$video_id." and prop_id<>12 GROUP BY v.from_user_id order BY sum(v.total_diamonds)";
            $proplist=$GLOBALS['db']->getAll($prop,true,true);
            $pay = "select u.id as user_id ,u.nick_name,u.head_image,sum(v.total_diamonds) as num,sum(v.total_ticket) as total_ticket
                                            from ".DB_PREFIX."live_pay_log_history as v 
                                            LEFT JOIN ".DB_PREFIX."user as u 
                                            on u.id = v.from_user_id 
                                            where v.video_id=".$video_id."
                                            GROUP BY u.nick_name order BY sum(v.total_diamonds)";
            $paylist=$GLOBALS['db']->getAll($pay,true,true);


            $list_arr=array_merge($proplist,$paylist);

            $list=array();

            foreach($list_arr as $k=>$v){
                if(!isset($list[$v['user_id']])){
                    $list[$v['user_id']]=$v;
                }else{
                    $list[$v['user_id']]['num']+=$v['num'];
                    $list[$v['user_id']]['total_ticket']+=$v['total_ticket'];

                }
            }
        }else{
            $prop = "select u.id as user_id ,u.nick_name,u.head_image,sum(v.total_diamonds) as num,sum(v.total_ticket) as total_ticket
											from $userlist as v LEFT JOIN  ".DB_PREFIX."user as u  on u.id = v.from_user_id 
											where v.video_id=".$video_id." and prop_id<>12 GROUP BY v.from_user_id order BY sum(v.total_diamonds)";
            $list=$GLOBALS['db']->getAll($prop,true,true);
        }

        foreach($list as $k=>$v){
            $list[$k]['total_ticket'] =floatval($list[$k]['total_ticket']);
            $list[$k]['head_image']= get_spec_image($v['head_image']);
        }


        $count=count($list);
        $p     = new Page($count, $listRows = 20);
        $this->assign("list",$list);
        $this->assign("page", $p->show());
        $this->display();
//		if($video)
//		{
//            $page = intval($_REQUEST['p']);
//            if($page<=0)
//                $page = 1;
//            $map['video_id'] = $video['id'];
//            $map['user_id'] = $video['user_id'];
//            require_once APP_ROOT_PATH."/admin/Lib/Action/RedisCommon.class.php";
//            $redisCommon = new Ridescommon();
//            $redis = $redisCommon->video_contribute_list($map['user_id'],$map['video_id'],0,$page);
//            $model = D ("VideoContributionHistory");
//            if (! empty ( $model )) {
//                $this->_list ( $model, $map,'','',1,$redis);
//            }
//
//			/*$map['video_id'] = $video['id'];
//			$model = D ("VideoContributionHistory");
//			if (! empty ( $model )) {
//				$this->_list ( $model, $map );
//			}*/
//		}


    }
/**
 * 删除印票贡献榜
 */	
	public function del_contribution_list()
	{
		$ajax = intval($_REQUEST['ajax']);
		$id = $_REQUEST ['id'];
		if (isset ( $id )) {
				$condition = array ('id' => array ('in', explode ( ',', $id ) ) );		
				$rel_data = M("VideoContributionHistory")->where($condition)->findAll();
				foreach($rel_data as $data)
				{
					$deal_id = $data['video_id'];
					$info[] = $data['id'];	
				}
				if($info) $info = implode(",",$info);
				$info = "视频ID".$deal_id."的印票贡献榜:".$info;
				$list = M("VideoContributionHistory")->where ( $condition )->delete();
							
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
    //检查视频
    public function check_video(){
        $id = $_REQUEST['id'];
        $condition['id'] = $id;
        $video = M('VideoHistory')->where($condition)->find();
        if(!empty($video)){
            fanwe_require(APP_ROOT_PATH.'mapi/lib/redis/BaseRedisService.php');
            fanwe_require(APP_ROOT_PATH.'mapi/lib/redis/VideoRedisService.php');
            require_once APP_ROOT_PATH."/mapi/lib/core/common.php";

            $ret = get_vodset_by_video_id($id);
            if ($ret['total_count'] > 0){
                //视频存在
                $sql = "update ".DB_PREFIX."video_history set is_del_vod = 0 where id = ".$video['id'];
                $GLOBALS['db']->query($sql);

                $video_redis = new VideoRedisService();
                $data = array();
                $data['is_del_vod'] = 0;
                $video_redis->update_db($video['id'], $data);
                $result['status'] = 1;
                $result['info'] = '视频存在';
            }else{
                $result['status'] = 0;
                $result['info'] = '视频不存在';
            }
        }else{
            $result['status'] = 0;
            $result['info'] = '直播记录不存在';
        }
        admin_ajax_return($result);
    }

    //删除直播记录
    public function delete(){
        $ajax = intval($_REQUEST['ajax']);
        $id = $_REQUEST['id'];
        $result['status'] = 0;
        if (isset ( $id )) {
            $condition = array ('id' => array ('in', explode ( ',', $id ) ) );
            $rel_data = M('VideoHistory')->where($condition)->findAll();
            $url_str = '';
            foreach($rel_data as $data)
            {
                if($data['video_vid']!='' && $data['is_del_vod']==0 && $data['is_delete']==0){//判断是否存在视频文件
                    if($url_str!=''){
                        $url_str.=',';
                    }
                    $url_str.=$data['id'];
                }
                $info[] = $data['id'];
                $forbid_del[] = $data['group_id'];
            }
            if($url_str!=''){
                $result['info'] = $url_str."存在视频文件，请先到回播列表删除视频文件！";
            }else{
                if($info) $info = implode(",",$info);
                $list = M('VideoHistory')->where ( $condition )->delete();
                if ($list!==false) {
                    $del_condition = array ('video_id' => array ('in', explode ( ',', $id ) ) );
                    //删除对应印票贡献
                    M('VideoContributionHistory')->where ( $del_condition )->delete();
                    //删除redis印票贡献
                    //删除禁言记录
                    $forbid_condition = array ('group_id' => array ('in', $forbid_del ) );
                    M('VideoForbidSendMsg')->where ( $forbid_condition )->delete();
                    //删除举报记录
                    M('Tipoff')->where ( $del_condition )->delete();
                    //删除连麦记录
                    M('VideoLianmaiHistory')->where ( $del_condition )->delete();
                    //删除主播心跳监听
                    M('VideoMonitorHistory')->where ( $del_condition )->delete();
                    //删除私聊邀请好友
                    M('VideoPrivateHistory')->where ( $del_condition )->delete();
                    //删除送礼记录
                    M('VideoPropHistory')->where ( $del_condition )->delete();
                    //删除用户抢到红包记录
                    M('VideoRedEnvelopeHistory')->where ( $del_condition )->delete();
                    //删除会员分享记录
                    M('VideoShareHistory')->where ( $del_condition )->delete();
                    //删除直播观众记录
                    M('VideoViewerHistory')->where ( $del_condition )->delete();
                    //删除虚拟人数计划
                    $vir_condition = array ('room_id' => array ('in', explode ( ',', $id ) ) );
                    M('VideoVirtual')->where ( $vir_condition )->delete();

                    save_log($info.l("FOREVER_DELETE_SUCCESS"),1);
                    clear_auto_cache("get_help_cache");
                    $result['info'] = $url_str."删除成功！";
                    $result['status'] = 1;
                } else {
                    save_log($info.l("FOREVER_DELETE_FAILED"),0);
                    $result['info'] = $url_str."删除失败！";
                }
            }
        } else {
            $result['info'] = '编号错误';
        }
        admin_ajax_return($result);
    }
}
?>