<?php
// +----------------------------------------------------------------------
// | Fanwe 方维众筹商业系统
// +----------------------------------------------------------------------
// | Copyright (c) 2011 http://www.fanwe.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: 甘味人生(526130@qq.com)
// +----------------------------------------------------------------------

class StationMessageAction extends CommonAction{

    public function index()
    {
        parent::index();
    }
	
	public function add()
	{
        $send_user = M("User")->where("is_admin=1")->findAll();
        $this->assign ( 'send_user', $send_user );
		$this->display();
	}
	
	public function insert()
	{
        B('FilterString');
        $ajax = intval($_REQUEST['ajax']);
        $data = M(MODULE_NAME)->create();
        //开始验证有效性
        $this->assign("jumpUrl",u(MODULE_NAME."/add"));
        if($data['content']=='')
        {
            $this->error(l("MESSAGE_CONTENT_EMPTY_TIP"));
        }
        $account_id = $data['send_user_id'];
        if(!$account_id){
            $this->error(l("请选择发送人，若没有请到机器人头像列表添加系统管理员！"));
        }
        $data['send_type'] = intval($data['send_type']);
        if($data['send_type']==1)
        {
            if($data['send_define_data']=='')
            {
                $this->error(l("SEND_DEFINE_DATE_EMPTY_TIP"));
            }
        }
        $data['send_time'] = NOW_TIME;
        $data['send_status'] = 0;
        $data['send_define_data'] = strim($data['send_define_data']);
        // 更新数据
        $log_info = $data['content'];
        $message_id = M(MODULE_NAME)->add($data);
        if ($message_id) {
            fanwe_require(APP_ROOT_PATH.'system/tim/TimApi.php');
            $api = createTimAPI();

            $content = $data['content'];
            $ext = array();
            $ext['type'] = 20;
            $ext['text'] = $content;
            //$ext['desc2'] = $content;

            $sender = array();
            $user_info =  M("User")->where("id=".$account_id)->find();
            $sender['user_id'] = $user_info['id'];//发送人昵称
            $sender['nick_name'] = $user_info['nick_name'];//发送人昵称
            $sender['head_image'] = get_spec_image($user_info['head_image']);//发送人头像
            $sender['user_level'] = $user_info['user_level'];//用户等级
            $sender['v_icon'] = $user_info['v_icon'];//认证图标

            $ext['sender'] = $sender;


            $msg_content = array();


            //18：直播结束（全体推送的，用于更新用户列表状态）
            $to = array();
            if($data['send_type']==1){
                $to = preg_split("/[\s]+/",$data['send_define_data']);
            }else if($data['send_type']==2){
                $all = preg_split("/[\s]+/",$data['send_define_data']);
                foreach($all as $k=>$v){
                    $group_id = M("Video")->where("id=".$v)->getField("group_id");
                    if(!$group_id){
                        $group_id = M("VideoHistory")->where("id=".$v)->getField("group_id");
                    }
                    if($group_id){
                        $ext['group_id']=$group_id;
                        $group_info = $api->group_get_group_member_info($group_id,0,0);
                        if($group_info['MemberList']){
                            $members = array_column($group_info['MemberList'],"Member_Account");
                            $to = array_merge($to,$members);
                        }
                        //$ret = $api->group_send_group_msg2($account_id,$group_id, $msg_content);
                    }
                }
            }else{

            }
            //创建array 所需元素
            $msg_content_elem = array(
                'MsgType' => 'TIMCustomElem',       //自定义类型
                'MsgContent' => array(
                    'Data' => json_encode($ext),
                    'Text' => $content
                )
            );
            array_push($msg_content, $msg_content_elem);
            if($data['send_type']==0){
                $ret = $api->openim_push($account_id,$msg_content);
            }else{
                $exceed = 500; //一次最多发送500条
                if(sizeof($to)>$exceed){
                    $num = ceil(sizeof($to)/$exceed);
                    for($i=0;$i<$num;$i++){
                        $to_account = array_slice($to,$i*$exceed,$exceed);
                        $ret = $api->openim_batchsendmsg($account_id, $msg_content,$to_account);
                    }
                }else{
                    $ret = $api->openim_batchsendmsg($account_id, $msg_content,$to);
                }
            }
            if ($ret['ActionStatus'] == 'FAIL'){
                $GLOBALS['db']->query("update ".DB_PREFIX."station_message set send_status = 1,ret_data='".$ret['ErrorCode']."' where id=".$message_id);
            }else{
                $GLOBALS['db']->query("update ".DB_PREFIX."station_message set send_status = 2 where id=".$message_id);
            }
            //成功提示
            save_log($log_info.L("INSERT_SUCCESS"),1);
            $this->success(L("INSERT_SUCCESS"));
        } else {
            //错误提示
            save_log($log_info.L("INSERT_FAILED"),0);
            $this->error(L("INSERT_FAILED"));
        }
	}
	
	public function foreverdelete() {
		//彻底删除指定记录
		$ajax = intval($_REQUEST['ajax']);
		$id = $_REQUEST ['id'];
		if (isset ( $id )) {
				//$MODULE_NAME='PromoteMsg';
				$condition = array ('id' => array ('in', explode ( ',', $id ) ) );
				$rel_data = M(MODULE_NAME)->where($condition)->findAll();
				foreach($rel_data as $data)
				{
					$info[] = $data['id'];	
				}
				if($info) $info = implode(",",$info);
				$list = M(MODULE_NAME)->where ( $condition )->delete();
			
				if ($list!==false) {
					save_log(l("FOREVER_DELETE_SUCCESS"),1);
					$this->success (l("FOREVER_DELETE_SUCCESS"),$ajax);
				} else {
					save_log(l("FOREVER_DELETE_FAILED"),0);
					$this->error (l("FOREVER_DELETE_FAILED"),$ajax);
				}
			} else {
				$this->error (l("INVALID_OPERATION"),$ajax);
		}
	}
	
	public function edit() {
		$id = intval($_REQUEST ['id']);
		$condition['id'] = $id;
		$vo = M(MODULE_NAME)->where($condition)->find();
		$this->assign ( 'vo', $vo );
		$this->display ();
	}
	
	public function update()
	{
        B('FilterString');
        $data = M(MODULE_NAME)->create();
        $this->assign("jumpUrl",u(MODULE_NAME."/edit",array("id"=>$data['id'])));
		//开始验证
        if($data['content']=='')
        {
            $this->error(l("MESSAGE_CONTENT_EMPTY_TIP"));
        }
        $data['send_type'] = intval($data['send_type']);
        if($data['send_type']==1)
        {
            if($data['send_define_data']=='')
            {
                $this->error(l("SEND_DEFINE_DATE_EMPTY_TIP"));
            }
        }
		$rs = M(MODULE_NAME)->save($data);
		if($rs)
		{
            if($data['send_status']!=2){
                fanwe_require(APP_ROOT_PATH.'system/tim/TimApi.php');
                $api = createTimAPI();
                if($data['send_type']==1){
                    $to = explode(',',$data['send_define_data']);
                }else{
                    $to = $GLOBALS['db']->getAll("select id from ".DB_PREFIX."user where is_robot = 0 and is_effect =1",true,true);
                    $to = array_column($to,"id");
                }
                $content = $data['content'];
                $ret = $api->openim_batchsendmsg($to,$content);
                if ($ret['ActionStatus'] == 'FAIL'){
                    $GLOBALS['db']->query("update ".DB_PREFIX."station_message set send_status = 1 where id=".$data['id']);
                }else{
                    $GLOBALS['db']->query("update ".DB_PREFIX."station_message set send_status = 2 where id=".$data['id']);
                }
            }

			save_log($data['content'].L("UPDATE_SUCCESS"),1);
			$this->success(L("UPDATE_SUCCESS"));
		}
		else
		{
			$this->error(L("UPDATE_FAILED"));
		}
	
	}
		
}
?>