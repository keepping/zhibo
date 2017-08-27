<?php
// +----------------------------------------------------------------------
// | Fanwe 方维p2p借贷系统
// +----------------------------------------------------------------------
// | Copyright (c) 2011 http://www.fanwe.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: 甘味人生(526130@qq.com)
// +----------------------------------------------------------------------

class PlugInAction extends CommonAction{
    public function index()
    {

        if(strim($_REQUEST['name'])!=''){
            $map['name'] = array('like','%'.strim($_REQUEST['name']).'%');
        }
        $model = D ("plugin");

        $in =array();
        //付费直播
        if((defined('OPEN_LIVE_PAY') && OPEN_LIVE_PAY == 1)&&(defined('LIVE_PAY_TIME')&&LIVE_PAY_TIME==1)) {
            $in[]="'live_pay'";
        }
        if((defined('OPEN_LIVE_PAY') && OPEN_LIVE_PAY == 1)&&(defined('LIVE_PAY_SCENE')&&LIVE_PAY_SCENE==1)) {
            $in[]="'live_pay_scene'";
        }
        if(defined('OPEN_PAI_MODULE') && OPEN_PAI_MODULE == 1) {
            $in[]="'pai'";
        }
        if(defined('SHOPPING_GOODS') && SHOPPING_GOODS == 1) {
            $in[]="'shop'";
        }
        if(defined('OPEN_GAME_MODULE') && OPEN_GAME_MODULE == 1) {
            $in[]="'game'";
        }
        if(defined('OPEN_PODCAST_GOODS') && OPEN_PODCAST_GOODS == 1) {
            $in[]="'podcast_goods'";
        }
        if($in){
            $map['class'] = array('in',implode(',',$in));
            if (! empty ( $model )) {
           	 	$this->_list ( $model, $map );
        	}
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