<?php
// +----------------------------------------------------------------------
// | Fanwe 方维众筹商业系统
// +----------------------------------------------------------------------
// | Copyright (c) 2011 http://www.fanwe.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: 甘味人生(526130@qq.com)
// +----------------------------------------------------------------------

class VideoClassifiedAction extends CommonAction{
	public function index()
	{
        if(strim($_REQUEST['title'])!=''){
            $map['title'] = array('like','%'.strim($_REQUEST['title']).'%');
        }
		if (method_exists ( $this, '_filter' )) {
			$this->_filter ( $map );
		}
		$name=$this->getActionName();
		$model = D ($name);
		if (! empty ( $model )) {
			$this->_list ( $model, $map );
		}
		$this->display ();
	}
	public function add()
	{
		$this->assign("new_sort", M("VideoCate")->max("sort")+1);
		$this->display();
	}
	public function edit() {
		$id = intval($_REQUEST ['id']);
		$condition['id'] = $id;
		$vo = M(MODULE_NAME)->where($condition)->find();
		$this->assign ( 'vo', $vo );
		$this->display ();
	}


	public function foreverdelete() {
		//彻底删除指定记录
		$ajax = intval($_REQUEST['ajax']);
		$id = $_REQUEST ['id'];
		if (isset ( $id )) {
            $id = explode ( ',', $id );
            if(in_array(1,$id)){
                $this->error ("分类为课程的不能删除，请重新选择",$ajax);
            }
            $condition = array ('id' => array ('in', $id  ) );
            $rel_data = M(MODULE_NAME)->where($condition)->findAll();
            foreach($rel_data as $data)
            {
                $info[] = $data['title'];
            }
            if($info) $info = implode(",",$info);
            $list = M(MODULE_NAME)->where ( $condition )->delete();
            if ($list!==false) {
                clear_auto_cache("video_classified");
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

	public function insert() {
		B('FilterString');
		$data = M(MODULE_NAME)->create();
		//开始验证有效性
		$this->assign("jumpUrl",u(MODULE_NAME."/add"));
        $data['title'] = strim($data['title']);
		if(!check_empty($data['title']))
		{
			$this->error("请输入分类名称");
		}
        $cate_id = $GLOBALS['db']->getOne("select id from ".DB_PREFIX."video_classified where title = '".$data['title']."'");
        if($cate_id){
            $this->error("分类名称已存在");
        }
		// 更新数据
		$log_info = $data['title'];
		$list=M(MODULE_NAME)->add($data);
		if (false !== $list) {
			//成功提示
            clear_auto_cache("video_classified");
			save_log($log_info.L("INSERT_SUCCESS"),1);
			$this->success(L("INSERT_SUCCESS"));
		} else {
			//错误提示
			save_log($log_info.L("INSERT_FAILED"),0);
			$this->error(L("INSERT_FAILED"));
		}
	}

	public function update() {
		B('FilterString');
		$data = M(MODULE_NAME)->create ();

		$log_info = M(MODULE_NAME)->where("id=".intval($data['id']))->getField("title");
		//开始验证有效性
		$this->assign("jumpUrl",u(MODULE_NAME."/edit",array("id"=>$data['id'])));
		if(!check_empty($data['title']))
		{
			$this->error("请输入分类名称");
		}
        $cate_id = $GLOBALS['db']->getOne("select id from ".DB_PREFIX."video_classified where title = '".$data['title']."'");
        if($cate_id && $cate_id!=$data['id']){
            $this->error("分类名称已存在，请重新填写！");
        }
		$list=M(MODULE_NAME)->save ($data);
		if (false !== $list) {
            clear_auto_cache("video_classified");
			save_log($log_info.L("UPDATE_SUCCESS"),1);
			$this->success(L("UPDATE_SUCCESS"));
		} else {
			//错误提示
			save_log($log_info.L("UPDATE_FAILED"),0);
			$this->error(L("UPDATE_FAILED"),0,$log_info.L("UPDATE_FAILED"));
		}
	}

    public function upload_video(){
        $region_lv2 = $GLOBALS['db']->getAll("select * from ".DB_PREFIX."region_conf where region_level = 2 order by py asc");  //二级地址
        $this->assign("region_lv2",$region_lv2);

        $classified_id = intval($_REQUEST['classified_id']);
        if(!$classified_id){
            $this->error("分类编号错误");
        }

        $cate_list = M("VideoCate")->where("is_delete = 0 and is_effect = 1")->findAll();
        $this->assign('cate_list', $cate_list);
        $user_list = M("User")->where("is_effect = 1 and is_robot =0")->findAll();
        $this->assign ( 'classified_id', $classified_id);
        $this->assign ( 'user_list', $user_list);
        $m_config = load_auto_cache("m_config");
        $this->assign('secret_id', $m_config['qcloud_secret_id']);
        $this->display();
    }

    public function video_insert(){
        require_once APP_ROOT_PATH."/mapi/lib/core/common.php";
        fanwe_require(APP_ROOT_PATH.'mapi/lib/redis/BaseRedisService.php');
        $m_config = load_auto_cache("m_config");
        $result = array('status'=>1,'error'=>'视频上传成功，视频转码需要几分钟，请耐心等待');

        $user_id = intval($_REQUEST['user_id']);
        $cate_id = intval($_REQUEST['cate_id']);
        $title = trim($_REQUEST['title']);
        $file_id = trim($_REQUEST['file_id']);
        $is_live_pay = intval($_REQUEST['is_live_pay']);
        $live_fee = intval($_REQUEST['live_fee']);
        $live_image = trim($_REQUEST['live_image']);
        $classified_id = intval($_REQUEST['classified_id']);

        if($user_id == 0)
        {
            admin_ajax_return(array('status'=>'0','error'=>'请选择视频所属主播'));
        }
        if(!check_empty($title))
        {
            admin_ajax_return(array('status'=>'0','error'=>'请输入话题'));
        }
        if(!check_empty($file_id))
        {
            admin_ajax_return(array('status'=>'0','error'=>'请上传视频'));
        }
        $province = strim($_REQUEST['province']);//省
        $city = strim($_REQUEST['city']);//市

        //obs 推流延长首次心跳时间
        $obs_monitor_time = intval($m_config['obs_monitor_time']) ? intval($m_config['obs_monitor_time']) : 300;
        $monitor_time = to_date(NOW_TIME + $obs_monitor_time, 'Y-m-d H:i:s');//主播心跳监听
        //添加位置

        if ($province == 'null') {
            $province = '';
        }
        if ($city == 'null') {
            $city = '';
        }
        $province = str_replace("省", "", $province);
        $city = str_replace("市", "", $city);
        if ($province == '' || $city == '') {
            /*
             //服务端则用ip再定位一次
            fanwe_require APP_ROOT_PATH . "system/extend/ip.php";
            $ip = new iplocate ();
            $area = $ip->getaddress ( CLIENT_IP );
            $location = $area ['area1'];
            */
            $ipinfo = get_ip_info();

            $province = $ipinfo['province'];
            $city = $ipinfo['city'];

        }
        //
        $is_private = false;
        $share_type = '';
        $data = $this->create_video($user_id,$classified_id,$live_image, $title, $is_private, $monitor_time, $cate_id, $province, $city, $share_type,1,0,$is_live_pay,$live_fee);
        fanwe_require(APP_ROOT_PATH . 'mapi/lib/core/video_factory.php');
        $video_factory = new VideoFactory();
        $ret = $video_factory->ModifyVodInfo($file_id, $data);
        if (!$ret['status']) {
            admin_ajax_return($ret);
        }

        // 新上传的视频未生成地址
        $data['room_title']=$GLOBALS['user_info']['nick_name']."直播间";
        $data['is_del_vod'] = 1;
        $data['video_vid'] = $file_id;
        $data['end_time'] = NOW_TIME;//'结束时间'
        $GLOBALS['db']->autoExecute(DB_PREFIX . "video", $data, 'INSERT');
        if($GLOBALS['db']->insert_id()){
            save_log($data['id'].L("INSERT_SUCCESS"),1);
            sync_video_to_redis($data['id'], '*', false);
        }else{
            $result['status'] = 0;
            $result['error'] = "视频上传失败";
            save_log($data['id'].L("INSERT_FAILED"),0);
        }
        admin_ajax_return($result);
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

    public function create_video($user_id,$classified_id,$live_image, $title, $is_private, $monitor_time, $cate_id = '', $province = '', $city = '', $share_type = '',$is_upload =0,$is_preparation = 0,$is_live_pay=0,$live_fee=0)
    {
        require_once APP_ROOT_PATH."/mapi/lib/core/common.php";
        //话题
        if ($cate_id) {
            //$cate_title = $GLOBALS['db']->getOne("select title from ".DB_PREFIX."video_cate where id=".$cate_id,true,true);
            $cate = load_auto_cache("cate_id", array('id' => $cate_id));
            $cate_title = $cate['title'];
            if ($cate_title != $title) {
                $cate_id = 0;
            }
        }

        if ($cate_id == 0 && $title != '') {
            $cate_id = $GLOBALS['db']->getOne("select id from " . DB_PREFIX . "video_cate where title='" . $title . "'", true, true);
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
            $GLOBALS['db']->autoExecute(DB_PREFIX . "video_cate", $data_cate, 'INSERT');
            $cate_id = $GLOBALS['db']->insert_id();
        }

        if ($province == '') {
            $province = '火星';
        }

        if ($city == '') {
            $city = '火星';
        }

        $video_id = get_max_room_id(0);
        $data = array();
        $data['id'] = $video_id;
        //room_type 房间类型 : 1私有群（Private）,0公开群（Public）,2聊天室（ChatRoom）,3互动直播聊天室（AVChatRoom）
        if ($is_private == 1) {
            $data['room_type'] = 1;
            $data['private_key'] = md5($video_id . rand(1, 9999999));//私密直播key
        } else {
            $data['room_type'] = 3;
        }
        $data['classified_id'] = 0;
        if($classified_id){
            $data['classified_id'] = $classified_id;
        }
        $data['is_upload'] = 0;
        if($is_upload){
            $data['is_upload'] = 1;
        }
        $data['is_preparation'] = 0;
        if($is_preparation){
            $data['is_preparation'] = 1;
        }
        $data['is_live_pay'] = 0;
        if($is_live_pay){
            $data['is_live_pay'] = $is_live_pay;
            $data['live_pay_type'] = 1;
            $data['live_fee'] = $live_fee;
        }

        $m_config = load_auto_cache("m_config");
        $data['virtual_number'] = intval($m_config['virtual_number']);
        $data['max_robot_num'] = intval($m_config['robot_num']);//允许添加的最大机器人数;

        $sql = "select sex,ticket,refund_ticket,user_level,fans_count,head_image,thumb_head_image from " . DB_PREFIX . "user where id = " . $user_id;
        $user = $GLOBALS['db']->getRow($sql, true, true);

        $info = origin_image_info($user['head_image']);
        $data['head_image'] = get_spec_image($info['file_name']);
        $data['thumb_head_image'] = $user['thumb_head_image'];
        $data['live_image'] = $live_image;

        $data['sex'] = intval($user['sex']);//性别 0:未知, 1-男，2-女
        $data['video_type'] = $m_config['video_type'];//0:腾讯云互动直播;1:腾讯云直播;2:方维云直播

        require_once(APP_ROOT_PATH . 'system/tim/TimApi.php');
        $api = createTimAPI();
        $ret = $api->group_create_group('AVChatRoom', (string)$user_id, (string)$user_id, (string)$video_id);
        if ($ret['ActionStatus'] != 'OK') {
            api_ajax_return(array(
                'status' => 0,
                'error' => $ret['ErrorCode'] . $ret['ErrorInfo']
            ));
        }

        $data['group_id'] = $ret['GroupId'];
        $data['monitor_time'] = $monitor_time;

        $data['create_type'] = 1;// 0:APP端创建的直播;1:PC端创建的直播
        $data['push_url'] = '';//video_type=1;1:腾讯云直播推流地址
        $data['play_url'] = '';//video_type=1;1:腾讯云直播播放地址(rmtp,flv)

        $data['share_type'] = $share_type;
        $data['title'] = $title;
        $data['cate_id'] = $cate_id;
        $data['user_id'] = $user_id;
        $data['live_in'] = 3;//live_in:是否直播中 1-直播中 0-已停止;2:正在创建直播;
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

    public function set_effect()
    {
        $id = intval($_REQUEST['id']);
        $info = M(MODULE_NAME)->where("id=".$id)->getField("title");
        if($info == '课程'){
            $this->error("课程不能设置无效");
        }
        $c_is_effect = M(MODULE_NAME)->where("id=".$id)->getField("is_effect");  //当前状态
        $n_is_effect = $c_is_effect == 0 ? 1 : 0; //需设置的状态
        M(MODULE_NAME)->where("id=".$id)->setField("is_effect",$n_is_effect);
        save_log($info.l("SET_EFFECT_".$n_is_effect),1);
        clear_auto_cache("video_classified");
        $this->ajaxReturn($n_is_effect,l("SET_EFFECT_".$n_is_effect),1)	;
    }

	public function set_sort()
	{
		$id = intval($_REQUEST['id']);
		$sort = intval($_REQUEST['sort']);
		$log_info = M("VideoCate")->where("id=".$id)->getField("title");
		if(!check_sort($sort))
		{
			$this->error(l("SORT_FAILED"),1);
		}
		M(MODULE_NAME)->where("id=".$id)->setField("sort",$sort);
		save_log($log_info.l("SORT_SUCCESS"),1);
        clear_auto_cache("video_classified");
		$this->success(l("SORT_SUCCESS"),1);
	}

}
?>