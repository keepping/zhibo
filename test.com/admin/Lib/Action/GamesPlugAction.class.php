<?php
// +----------------------------------------------------------------------
// | Fanwe 方维p2p借贷系统
// +----------------------------------------------------------------------
// | Copyright (c) 2011 http://www.fanwe.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: 甘味人生(526130@qq.com)
// +----------------------------------------------------------------------

class GamesPlugAction extends CommonAction{
    public function index()
    {

        if(strim($_REQUEST['name'])!=''){
            $map['name'] = array('like','%'.strim($_REQUEST['name']).'%');
        }
        $model = D ("plugin");
        //付费直播
        if(!defined('OPEN_LIVE_PAY') || OPEN_LIVE_PAY != 1) {
        	 $map['class'] = array('neq','live_pay');
        }
        if (! empty ( $model )) {
            $this->_list ( $model, $map );
        }
        $this->display();
    }

    public function edit() {
        $id = intval($_REQUEST ['id']);
        $condition['id'] = $id;
        $vo = M("plugin")->where($condition)->find();
        $this->assign ('vo',$vo );
        $this->display ();
    }


    public function update() {
        B('FilterString');
        $data = M("plugin")->create();
		//clear_auto_cache("prop_list");
        $log_info = M("plugin")->where("id=".intval($data['id']))->getField("name");
        //开始验证有效性
        $this->assign("jumpUrl",u("GamesPlug"."/edit",array("id"=>$data['id'])));
        if(!check_empty($data['name']))
        {
            $this->error("请输入名称");
        }
        if(!check_empty($data['image']))
        {
            $this->error("请输入图标");
        }

        // 更新数据
        $list=M("plugin")->save ($data);
        if (false !== $list) {
            //成功提示
            save_log($log_info.L("UPDATE_SUCCESS"),1);
            clear_auto_cache("prop_id",array('id'=>$data['id']));
            $this->success(L("UPDATE_SUCCESS"));
        } else {
            //错误提示
            save_log($log_info.L("UPDATE_FAILED"),0);
            $this->error(L("UPDATE_FAILED"),0,$log_info.L("UPDATE_FAILED"));
        }
    }


}
?>