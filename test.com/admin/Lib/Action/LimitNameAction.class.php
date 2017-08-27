<?php
// +----------------------------------------------------------------------
// | Fanwe 方维众筹商业系统
// +----------------------------------------------------------------------
// | Copyright (c) 2011 http://www.fanwe.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: 甘味人生(526130@qq.com)
// +----------------------------------------------------------------------

class LimitNameAction extends CommonAction{

    //检查模块开关是否开启
    public function  check_Module(){
        $m_config =  load_auto_cache("m_config");
        if($m_config['name_limit']==0){
            $this->redirect('APP_ROOT+'.get_manage_url_name().'?m=Conf&a=mobile&');
        }
    }

    //昵称限制
    public function index(){
        $this->check_Module();
        if (trim($_REQUEST['name'])) {
            $where = ' name like \'%' . trim($_REQUEST['name']) . '%\'';
        }
        $model = M('limit_name');
        $count = $model->where($where)->count();
        $p     = new Page($count);
        $page = $p->show();
        $vo = $model->where($where)->limit($p->firstRow.','.$p->listRows)->select();
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
        $this->check_Module();
        B('FilterString');
        $data = M(MODULE_NAME)->create();

        //开始验证有效性
        $this->assign("jumpUrl", u(MODULE_NAME . "/add"));
        if (!check_empty($data['name'])) {
            $this->error("请输入昵称");
        }
        $condition['name'] = $data['name'];
        $count = M(MODULE_NAME)->where($condition)->count();
        if ($count > 0){
            $this->error("该昵称已存在");
        }



        // 更新数据
        $log_info = $data['name'];
        $list = M(MODULE_NAME)->add($data);
        if ($list!==false) {
            //成功提示
            save_log($log_info . L("INSERT_SUCCESS"), 1);
            $this->success(L("INSERT_SUCCESS"));
        } else {
            //错误提示
            save_log($log_info . L("INSERT_FAILED"), 0);
            $this->error(L("INSERT_FAILED"));
        }
    }
    //彻底删除指定记录
    public function foreverdelete() {
        $this->check_Module();
        //彻底删除指定记录
        $ajax = intval($_REQUEST['ajax']);
        $id = intval($_REQUEST ['id']);
        if (isset ( $id )) {
            $condition = array ('id' => array ('in', explode ( ',', $id ) ) );
            $rel_data = M(MODULE_NAME)->where($condition)->findAll();
            foreach($rel_data as $data)
            {
                $info[] = $data['name'];
            }
            if($info) $info = implode(",",$info);
            $list = M(MODULE_NAME)->where ( $condition )->delete();
            //删除相关预览图
//				foreach($rel_data as $data)
//				{
//					@unlink(get_real_path().$data['preview']);
//				}
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
	
}
?>