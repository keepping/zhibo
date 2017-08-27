<?php
// +----------------------------------------------------------------------
// | Fanwe 方维众筹商业系统
// +----------------------------------------------------------------------
// | Copyright (c) 2011 http://www.fanwe.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: 甘味人生(526130@qq.com)
// +----------------------------------------------------------------------

class KeyListAction extends CommonAction{

    //KEYlist
    public function index(){
        $model = M('KeyList');
        $count = $model->count();
        $p  = new Page($count);
        $page = $p->show();
        $vo = $model->where(array('is_delete'=>0))->limit($p->firstRow.','.$p->listRows)->select();
        $this->assign("page", $page);
        $this->assign("list", $vo);
        $this->display ();
    }
    //添加页面显示
    public function add()
    {
        $this->display();
    }
    //添加方法
    public function insert()
    {
        B('FilterString');
        filter_request($_REQUEST);
        $data =array();
        //$data['type'] = trim($_REQUEST['type']);
        $data['aes_key'] = trim($_REQUEST['aes_key']);
        $data['version'] = trim($_REQUEST['version']);
        $data['is_init'] = trim($_REQUEST['is_init']);
        $data['is_effect'] = trim($_REQUEST['is_effect']);
        //开始验证有效性
        $this->assign("jumpUrl", u(MODULE_NAME . "/add"));
        /*if (!in_array($data['type'],array('ios','android'))) {
            $this->error("手机端类型请输入ios或android");
        }
        if ($data['version']=='') {
            $this->error("请输入yyyymmddnn版本格式");
        }*/
        // 更新数据
        $log_info = 'aes_key';
        $list = $GLOBALS['db']->autoExecute(DB_PREFIX."key_list", $data,"INSERT");
        if ($list!==false) {
            //更新动态秘钥
            $this->edit_full_group_info();
            //成功提示
            save_log($log_info . L("INSERT_SUCCESS"), 1);
            $this->success(L("INSERT_SUCCESS"));
        } else {
            //错误提示
            save_log($log_info . L("INSERT_FAILED"), 0);
            $this->error(L("INSERT_FAILED"));
        }
    }
    //编辑
    public function edit(){
        $id = intval($_REQUEST['id']);
        $list = M(MODULE_NAME)->where('id='.$id)->find();//根据ID相关数据
        $this->assign('vo',$list);
        $this->display();
    }
    //删除
    public function delete() {
        //彻底删除指定记录
        $ajax = intval($_REQUEST['ajax']);
        $id = intval($_REQUEST ['id']);
        if (isset ( $id )) {
            $condition = array ('id' => array ('in', explode ( ',', $id ) ) );
            $rel_data = M(MODULE_NAME)->where($condition)->findAll();

            foreach($rel_data as $data)
            {
                if($data['is_init']){
                    if(is_array($id)){
                        $key = array_search($data['id'],$id);
                        if($key){
                            unset($id[$key]);
                            save_log($data['aes_key'].'是打包填写KEY不能删除!',1);
                        }
                    }else{
                        $this->error($data['aes_key'].'是打包填写KEY不能删除!!',1);
                    }
                }
                $info[] = $data['aes_key'];
            }
            if($info) $info = implode(",",$info);
            $ids = explode ( ',', $id );
            foreach($ids as $uid)
            {
                $sql = "update ".DB_PREFIX."key_list set is_delete = 1 where is_delete = 0 and id =".$uid;
                $GLOBALS['db']->query($sql); //删除
            }
            //更新动态秘钥
            $this->edit_full_group_info();
            save_log($info.l("DELETE_SUCCESS"),1);
            $this->success (l("DELETE_SUCCESS"),$ajax);
        } else {
            $this->error (l("INVALID_OPERATION"),$ajax);
        }
    }
    //更新
    public function update(){
        B('FilterString');
        filter_request($_REQUEST);
        $data =array();
        $id = trim($_REQUEST['id']);
        $data['aes_key'] = trim($_REQUEST['aes_key']);
        $data['is_init'] = trim($_REQUEST['is_init']);
        $data['is_effect'] = trim($_REQUEST['is_effect']);
        //开始验证有效性
        $this->assign("jumpUrl", u(MODULE_NAME . "/edit"));
        /*if (!in_array($data['type'],array('ios','android'))) {
            $this->error("手机端类型请输入ios或android");
        }
        if ($data['version']=='') {
            $this->error("请输入yyyymmddnn版本格式");
        }*/
        // 更新数据
        $log_info = 'aes_key';
        foreach($data as $k=>$v){
            if($v==''){
                unset($data[$k]);
            }
        }
        $list = $GLOBALS['db']->autoExecute(DB_PREFIX . "key_list", $data, $mode = 'UPDATE', 'id=' . $id);
        if ($list!==false) {
            //更新动态秘钥
            $this->edit_full_group_info();
            //成功提示
            save_log($log_info . L("UPDATE_SUCCESS"), 1);
            $this->success(L("UPDATE_SUCCESS"));
        } else {
            //错误提示
            save_log($log_info . L("UPDATE_FAILED"), 0);
            $this->error(L("UPDATE_FAILED"));
        }

    }
    //彻底删除指定记录
    public function foreverdelete() {
        //彻底删除指定记录
        $ajax = intval($_REQUEST['ajax']);
        $id = intval($_REQUEST ['id']);
        if (isset ( $id )) {
            $condition = array ('id' => array ('in', explode ( ',', $id ) ) );
            $rel_data = M(MODULE_NAME)->where($condition)->findAll();
            foreach($rel_data as $data)
            {
                $info[] = $data['aes_key'];
            }
            if($info) $info = implode(",",$info);
            $list = M(MODULE_NAME)->where ( $condition )->delete();
            if ($list!==false) {
                save_log($info.l("FOREVER_DELETE_SUCCESS"),1);
                clear_auto_cache("get_help_cache");
                $this->success (l("FOREVER_DELETE_SUCCESS"),$ajax);
            } else {
                save_log($info.l("FOREVER_DELETE_FAILED"),0);
                $this->error (l("FOREVER_DELETE_FAILED"),$ajax);
            }
        } else {
            $this->error (l("INVALID_OPERATION"),$ajax);
        }
    }
//更新动态秘钥
	public  function edit_full_group_info(){
        $m_config =  load_auto_cache("m_config");//初始化手机端配置
        $group_id = strim($m_config['full_group_id']);
        if($group_id){
            require_once(APP_ROOT_PATH . 'system/tim/TimApi.php');
            $api = createTimAPI();
            $aes_key_info = get_privatekey();
            if(is_array($aes_key_info[0]['aes_key'])){
                $aes_key = $aes_key_info[0]['aes_key'][0];
            }else{
                $aes_key = $aes_key_info[0]['aes_key'];
            }
            $base_info_filter = array("Introduction");
            $ret = $api->group_get_group_info2(array('0'=>$group_id),$base_info_filter);
            if($ret['GroupInfo'][0]['ErrorCode']){
                $ret = $api->full_group_create($group_id,$aes_key);
            }else{
                if($ret['GroupInfo'][0]['Introduction'] != $aes_key){
                    $info_set['introduction'] = $aes_key;
                    $rets = $api->group_modify_group_base_info2($ret['GroupInfo'][0]['GroupId'], 'FullGroup', $info_set);
                }
            }
        }
    }
}
?>