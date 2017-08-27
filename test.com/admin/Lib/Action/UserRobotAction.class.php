<?php
// +----------------------------------------------------------------------
// | Fanwe 方维众筹商业系统
// +----------------------------------------------------------------------
// | Copyright (c) 2011 http://www.fanwe.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: 甘味人生(526130@qq.com)
// +----------------------------------------------------------------------

class UserRobotAction extends CommonAction{
    public function __construct()
    {
        require_once APP_ROOT_PATH."/admin/Lib/Action/UserCommonAction.class.php";
        parent::__construct();
    }
    //机器人
    public function index()
    {
        $common = new UserCommon();
        filter_request($_REQUEST);   
        $data = $_REQUEST;
        $data['is_authentication'] = array('in',array(0,1,2,3));
        $data['is_robot'] = 1;
        $common->index($data);
    }

    public function add(){
        $common = new UserCommon();
        $common->add();
    }

    public function insert(){
        $common = new UserCommon();
        filter_request($_REQUEST);
        $data = $_REQUEST;
        $common->insert($data);
    }

    public function edit() {
        $common = new UserCommon();
        filter_request($_REQUEST);
        $data = $_REQUEST;
        $common->edit($data);
    }


    public function delete() {
        $common = new UserCommon();
         filter_request($_REQUEST);
        $data = $_REQUEST;
        $common->delete($data);
    }

    public function update() {
        $common = new UserCommon();
        filter_request($_REQUEST);
        $data = $_REQUEST;
        $common->update($data);

    }

    public function set_effect()
    {
        $common = new UserCommon();
         filter_request($_REQUEST);
        $data = $_REQUEST;
        $n_is_effect = $common->set_effect($data);
        $this->ajaxReturn($n_is_effect,l("SET_EFFECT_".$n_is_effect),1);
    }

    public function set_ban()
    {
        $common = new UserCommon();
        filter_request($_REQUEST);
        $data = $_REQUEST;
        $n_is_effect = $common->set_ban($data);
        $this->ajaxReturn($n_is_effect,l("SET_BAN_".$n_is_effect),1);
    }

    //新增关注
    public function add_focus(){
        $common = new UserCommon();
        filter_request($_REQUEST);
        $data = $_REQUEST;
        $common->add_focus($data);
    }

    //新增关注
    public function set_follow(){
        $common = new UserCommon();
         filter_request($_REQUEST);
        $data = $_REQUEST;
        $common->set_follow($data);
    }
    //关注列表
    public function focus_list(){
        $common = new UserCommon();
         filter_request($_REQUEST);
        $data = $_REQUEST;
        $common->focus_list($data);
    }

    //新增粉丝
    public function add_fans(){
        $common = new UserCommon();
         filter_request($_REQUEST);
        $data = $_REQUEST;
        $common->add_fans($data);
    }

    //新增粉丝
    public function set_follower(){
        $common = new UserCommon();
         filter_request($_REQUEST);
        $data = $_REQUEST;
        $common->set_follower($data);
    }

    //粉丝列表
    public function fans_list(){
        $common = new UserCommon();
         filter_request($_REQUEST);
        $data = $_REQUEST;
        $common->fans_list($data);
    }

    //删除关注
    public function del_focus_list(){
        $common = new UserCommon();
         filter_request($_REQUEST);
        $data = $_REQUEST;
        $common->del_focus_list($data);
    }

    //删除粉丝
    public function del_fans_list(){
        $common = new UserCommon();
         filter_request($_REQUEST);
        $data = $_REQUEST;
        $common->del_fans_list($data);
    }

    //印票贡献榜
    public function contribution_list(){
        $common = new UserCommon();
         filter_request($_REQUEST);
        $data = $_REQUEST;
        $common->contribution_list($data);
    }

    /**
     * 删除印票贡献榜
     */
    public function del_contribution_list()
    {
    	 filter_request($_REQUEST);
        $ajax = intval($_REQUEST['ajax']);
        $id = $_REQUEST ['id'];
        if (isset ( $id )) {
            $common = new UserCommon();
            $data = $_REQUEST;
            $status = $common->del_contribution_list($data);

            if ($status!==false) {
                $this->success (l("FOREVER_DELETE_SUCCESS"),$ajax);
            } else {
                $this->error (l("FOREVER_DELETE_FAILED"),$ajax);
            }
        } else {
            $this->error (l("INVALID_OPERATION"),$ajax);
        }
    }

    //消息推送
    public function push(){
        $common = new UserCommon();
         filter_request($_REQUEST);
        $data = $_REQUEST;
        $common->push($data);
    }

    //删除推送消息
    public function del_push(){
    	 filter_request($_REQUEST);
        $ajax = intval($_REQUEST['ajax']);
        $id = $_REQUEST ['id'];
        if (isset ( $id )) {
            $common = new UserCommon();
            $data = $_REQUEST;
            $status = $common->del_push($data);

            if ($status!==false) {
                $this->success (l("FOREVER_DELETE_SUCCESS"),$ajax);
            } else {
                $this->error (l("FOREVER_DELETE_FAILED"),$ajax);
            }
        } else {
            $this->error (l("INVALID_OPERATION"),$ajax);
        }
    }


    public function account()
    {
        $common = new UserCommon();
         filter_request($_REQUEST);
        $data = $_REQUEST;
        $status = $common->account($data);
    }
    public function modify_account()
    {
        $common = new UserCommon();
         filter_request($_REQUEST);
        $data = $_REQUEST;
        $status = $common->modify_account($data);
        $this->success(L("UPDATE_SUCCESS"));
    }

    public function account_detail()
    {
        $common = new UserCommon();
         filter_request($_REQUEST);
        $data = $_REQUEST;
        $common->account_detail($data);
    }

    //兑换日志
    public function exchange_log()
    {
        $common = new UserCommon();
         filter_request($_REQUEST);
        $data = $_REQUEST;
        $common->exchange_log($data);
    }

    public function foreverdelete_account_detail()
    {
		 filter_request($_REQUEST);
        //彻底删除指定记录
        $ajax = intval($_REQUEST['ajax']);
        $id = $_REQUEST ['id'];
        $data = $_REQUEST;
        if (isset ( $id )) {
            $common = new UserCommon();
            $status = $common->foreverdelete_account_detail($data);
            if ($status!==false) {
                $this->success (l("FOREVER_DELETE_SUCCESS"),$ajax);
            } else {
                $this->error (l("FOREVER_DELETE_FAILED"),$ajax);
            }
        } else {
            $this->error (l("INVALID_OPERATION"),$ajax);
        }
    }

    //删除兑换日志
    public function foreverdelete_exchange_log()
    {
	 filter_request($_REQUEST);
        //彻底删除指定记录
        $ajax = intval($_REQUEST['ajax']);
        $id = $_REQUEST ['id'];
        $data = $_REQUEST;
        if (isset ( $id )) {
            $common = new UserCommon();
            $status = $common->foreverdelete_exchange_log($data);
            if ($status!==false) {
                $this->success (l("FOREVER_DELETE_SUCCESS"),$ajax);
            } else {
                $this->error (l("FOREVER_DELETE_FAILED"),$ajax);
            }
        } else {
            $this->error (l("INVALID_OPERATION"),$ajax);
        }
    }
    public function check_user(){
        $common = new UserCommon();
        filter_request($_REQUEST);
        $user_id = $_REQUEST['id'];
        admin_ajax_return($common->check_user($user_id));
    }
    //礼物日志
    public function prop()
    {
        $common = new UserCommon();
        $data = $_REQUEST;
        $common->prop($data);
    }
    //删除礼物日志
    public function delete_prop()
    {

        //彻底删除指定记录
        $ajax = intval($_REQUEST['ajax']);
        $id = $_REQUEST ['id'];
        $data = $_REQUEST;
        if (isset ( $id )) {
            $common = new UserCommon();
            $status = $common->del_prop($data);
            if ($status!==false) {
                $this->success (l("FOREVER_DELETE_SUCCESS"),$ajax);
            } else {
                $this->error (l("FOREVER_DELETE_FAILED"),$ajax);
            }
        } else {
            $this->error (l("INVALID_OPERATION"),$ajax);
        }
    }

    //设置机器人送礼物特权
    public function roboter()
    {
        $id = intval($_REQUEST['id']);
        $c_is_roboter = M("User")->where("id=".$id)->getField("roboter");  //当前状态
        $n_is_roboter = $c_is_roboter == 0 ? 1 : 0; //需设置的状态
        $result=M("User")->where("id=".$id)->setField("roboter",$n_is_roboter);
        $user_data = array();
        if($result){
            fanwe_require(APP_ROOT_PATH.'mapi/lib/redis/BaseRedisService.php');
            fanwe_require(APP_ROOT_PATH.'mapi/lib/redis/UserRedisService.php');
            $user_redis = new UserRedisService();
            $user_data['roboter'] = $n_is_roboter;
            $user_redis->update_db($id,$user_data);
        }
        $this->ajaxReturn($n_is_roboter,l("roboter_".$n_is_roboter),1);
    }

}
?>