<?php
// +----------------------------------------------------------------------
// | Fanwe 方维众筹商业系统
// +----------------------------------------------------------------------
// | Copyright (c) 2011 http://www.fanwe.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: 甘味人生(526130@qq.com)
// +----------------------------------------------------------------------

class VideoMonitorAction extends CommonAction{
    public function __construct()
    {
        parent::__construct();
        require_once APP_ROOT_PATH."/admin/Lib/Action/VideoCommonAction.class.php";
    }
    //监控
    public function monitor(){
        $map['live_in'] = 1;

        $m_config =  load_auto_cache("m_config");//初始化手机端配置
        $app_id = $m_config['vodset_app_id'];
        $this->assign('app_id',$app_id);

        if (method_exists ( $this, '_filter' )) {
            $this->_filter ( $map );
        }
        if(intval($_REQUEST ['listRows'])==0&&intval($m_config['live_page_size'])>10){
            $_REQUEST ['listRows'] = intval($m_config['live_page_size']);
        }
        $name=$this->getActionName();
        $model = D ('Video');
        if (! empty ( $model )) {
            $this->_list ( $model, $map );
        }
        $list = $this->get("list");
        foreach($list as $k=>$v){
            $list[$k]['watch_number'] = $v['watch_number']+$v['virtual_watch_number']+$v['robot_num'];
        }
        $this->assign ( 'url_name', get_manage_url_name());
        $this->assign ( 'list', $list );
        $this->display();
    }
	//关闭房间
	function close_live(){
        $common = new VideoCommon();
        $data = $_REQUEST;
        $common->close_live($data);
	}

    //发出警告
    public function send_warning(){
        $room_id = intval($_REQUEST['room_id']);
        $sql = "select id from ".DB_PREFIX."video where id = ".$room_id." and live_in = 1";
        $video = $GLOBALS['db']->getRow($sql,true,true);

        $warning_list = M("WarningMsg")->where("is_effect = 1")->findAll();
        $this->assign("warning_list",$warning_list);
        $this->assign("video",$video);
        $this->display();
    }

    public function send_warning_msg(){
        $room_id =  intval($_REQUEST['id']);
        $msg = strim($_REQUEST['warning_msg']);
        if($msg==''){
            $this->error("警告内容不能为空");
        }
        fanwe_require(APP_ROOT_PATH.'mapi/lib/redis/BaseRedisService.php');
        fanwe_require(APP_ROOT_PATH.'mapi/lib/redis/VideoRedisService.php');
        $video_redis = new VideoRedisService();
        $video = $video_redis->getRow_db($room_id,array('id','user_id','group_id'));
        $m_config =  load_auto_cache("m_config");
        $system_user_id =$m_config['tim_identifier'];//系统消息
        $ext = array();
        $ext['type'] = 41;
        $ext['desc'] = $msg;
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
        $ret = $api->openim_send_msg2($system_user_id, $video['user_id'], $msg_content);
        if($ret['ActionStatus'] != 'OK'){
            $ret = $api->openim_send_msg2($system_user_id, $video['user_id'], $msg_content);
        }
        if($ret['ActionStatus']=='OK'){
            $this->success("发送成功");
        }else{
            $this->success("发送失败");
        }

    }

    //设置永久禁播状态
    public function set_ban()
    {
        $id = intval($_REQUEST['id']);
        $ajax = intval($_REQUEST['ajax']);
        $user_info = M("User")->getById($id);
        $c_is_effect = M("User")->where("id=".$id)->getField("is_ban");  //当前状态
        $n_is_effect = $c_is_effect == 0 ? 1 : 0; //需设置的状态
        $result=M("User")->where("id=".$id)->setField("is_ban",$n_is_effect);
        save_log($user_info['nick_name'].l("SET_BAN_".$n_is_effect),1);
        if ($n_is_effect!==false) {
            //$this->success (l("操作成功"),$ajax);
        } else {
            $this->error (l("操作失败"),$ajax);
        }
        return $n_is_effect;
    }
}
?>