<?php
// +----------------------------------------------------------------------
// | Fanwe 方维p2p借贷系统
// +----------------------------------------------------------------------
// | Copyright (c) 2011 http://www.fanwe.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: 甘味人生(526130@qq.com)
// +----------------------------------------------------------------------

class DateAction extends CommonAction{
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
        $this->display();
    }
    public function edit() {
        $id = intval($_REQUEST ['id']);
        $condition['id'] = $id;
        $vo = M(MODULE_NAME)->where($condition)->find();
        $this->assign ( 'vo', $vo );
        $this->display ();
    }

    //彻底删除指定记录
    public function foreverdelete() {
        //彻底删除指定记录
        $ajax = intval($_REQUEST['ajax']);
        $id = $_REQUEST ['id'];
        if (isset ( $id )) {
            $condition = array ('id' => array ('in', explode ( ',', $id ) ) );
            $rel_data = M(MODULE_NAME)->where($condition)->findAll();
            foreach($rel_data as $data)
            {
                $info[] = $data['title'];
            }
            if($info) $info = implode(",",$info);
            $list = M(MODULE_NAME)->where ( $condition )->delete();
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

    public function insert() {
        B('FilterString');
        $data = M(MODULE_NAME)->create ();
        //开始验证有效性
        $this->assign("jumpUrl",u(MODULE_NAME."/add"));
        if(!check_empty($data['title']))
        {
            $this->error("请输入项目标题");
        }
        if(!check_empty($data['price'])){
            $data['price'] = 0;
            //$this->error("请输入预约费用");
        }else{
            if(!preg_match('/^[0-9]+(.[0-9]{0,2})?$/', $data['price'])){
                $this->error("预约费用必须是数字且最多两位小数");
            }
        }
        // 更新数据
        $log_info = $data['title'];
        $data['create_time'] = NOW_TIME;
        $list=M(MODULE_NAME)->add($data);
        if (false !== $list) {
            //成功提示
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
        $data = M(MODULE_NAME)->create();
        $log_info = M(MODULE_NAME)->where("id=".intval($data['id']))->getField("title");
        //开始验证有效性
        $this->assign("jumpUrl",u(MODULE_NAME."/edit",array("id"=>$data['id'])));
        if(!check_empty($data['title']))
        {
            $this->error("请输入项目标题");
        }
        if(!check_empty($data['price'])){
            $data['price'] = 0;
            //$this->error("请输入预约费用");
        }else{
            if(!preg_match('/^[0-9]+(.[0-9]{0,2})?$/', $data['price'])){
                $this->error("预约费用必须是数字且最多两位小数");
            }
        }
        // 更新数据
        $list=M(MODULE_NAME)->save ($data);
        if (false !== $list) {
            //成功提示
            save_log($log_info.L("UPDATE_SUCCESS"),1);
            $this->success(L("UPDATE_SUCCESS"));
        } else {
            //错误提示
            save_log($log_info.L("UPDATE_FAILED"),0);
            $this->error(L("UPDATE_FAILED"),0,$log_info.L("UPDATE_FAILED"));
        }
    }

    //用户预约记录
    public function date_list(){
        $id = intval($_REQUEST['id']);
        $date = M(MODULE_NAME)->getById($id);
        $this->assign("date",$date);
        $map['date_id'] = $id;

        if (method_exists ( $this, '_filter' )) {
            $this->_filter ( $map );
        }

        $model = M ("UserDate");
        if (! empty ( $model )) {
            $this->_list ( $model, $map );
        }
        $this->assign ( 'module_name', MODULE_NAME );
        $this->display ();
    }

    //删除预约记录
    public function del_date_list(){
        //彻底删除指定记录
        $date_id = intval($_REQUEST['date_id']);
        $id = $_REQUEST ['id'];
        if (isset ( $id )) {
            $condition = array ('id' => array ('in', explode ( ',', $id ) ) );
            $rel_data = M("UserDate")->where($condition)->findAll();
            foreach($rel_data as $data)
            {
                $info[] = $data['name'];
            }
            if($info) $info = implode(",",$info);
            $list = M("UserDate")->where ( $condition )->delete();

            if ($list!==false) {
                save_log("项目ID".$date_id."预约人".$info.l("FOREVER_DELETE_SUCCESS"),1);
                $result['status'] = 1;
                $result['info'] = '删除成功';
            } else {
                save_log("项目ID".$date_id."预约人".$info.l("FOREVER_DELETE_FAILED"),0);
                $result['status'] = 0;
                $result['info'] = '删除失败';
            }
        } else {
            $result['status'] = 0;
            $result['info'] = '编号错误';
        }
        admin_ajax_return($result);
    }

    //改变预约状态
    public function change_status(){
        $id = intval($_REQUEST['id']);
        $status = intval($_REQUEST['status']);
        $re = M("UserDate")->where("id=".$id)->setField("status",$status);
        if($status==1){
            $status_str = '已约见';
        }elseif($status==2){
            $status_str = '拒绝约见';
        }else{
            $status_str = '取消约见';
        }
        if($re){
            if($status==1){
                $date_id = $GLOBALS['db']->getOne("select date_id from ".DB_PREFIX."user_date where id = ".$id);
                $GLOBALS['db']->query("update ".DB_PREFIX."date set seen_count=seen_count+1 where id = ".$date_id);
                $reservation_config = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."conf where `name`='RESERVATION_CONFIG'");
                $value = unserialize($reservation_config['value']);
                $value['seen_count'] = $value['seen_count'] + 1;
                $value =  serialize($value);
                $GLOBALS['db']->query("update ".DB_PREFIX."conf set value='".$value."' where `name`='RESERVATION_CONFIG'");
            }
            $result['status'] = 1;
            $result['info'] = $status_str.'成功';
            save_log($id.$status_str."成功",1);
        }else{
            $result['status'] = 0;
            $result['info'] = $status_str.'失败';
            save_log($id.$status_str."失败",1);
        }
        admin_ajax_return($result);
    }

}
?>