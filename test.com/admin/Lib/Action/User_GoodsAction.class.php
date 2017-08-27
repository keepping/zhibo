<?php
// +----------------------------------------------------------------------
// | Fanwe 方维p2p借贷系统
// +----------------------------------------------------------------------
// | Copyright (c) 2011 http://www.fanwe.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: 甘味人生(526130@qq.com)
// +----------------------------------------------------------------------

class User_GoodsAction extends CommonAction{
    public function index()
    {

        if(strim($_REQUEST['name'])!=''){
            $map['name'] = array('like','%'.strim($_REQUEST['name']).'%');
        }
        if(intval($_REQUEST['user_id'])!=''){
            $map['user_id'] = intval($_REQUEST['user_id']);
        }

        //$name=$this->getActionName();
        $model = D ('user_goods');
        if (! empty ( $model )) {
            $this->_list ( $model, $map );
        }

        $list = $this->get('list');

        foreach($list as $k => $v){
        	$imgs=array();
        	$imgs_details=array();
        	$imgs=json_decode($v['imgs'],1);
        	$imgs_details=json_decode($v['imgs_details'],1);
        	$list[$k]['imgs'] = $imgs[0];
        	$list[$k]['imgs_details'] = $imgs_details[0];
        }

        $this->assign("list",$list);
        $this->display();

    }

//    public function edit() {
//        $id = intval($_REQUEST ['id']);
//        $condition['id'] = $id;
//        $vo = M(MODULE_NAME)->where($condition)->find();
//        $this->assign ('vo',$vo );
//        $this->display ();
//    }


//    public function update() {
//        B('FilterString');
//        $data = M(MODULE_NAME)->create();
//		//clear_auto_cache("prop_list");
//        $log_info = M(MODULE_NAME)->where("id=".intval($data['id']))->getField("name");
//        //开始验证有效性
//        $this->assign("jumpUrl",u(MODULE_NAME."/edit",array("id"=>$data['id'])));
//        if(!check_empty($data['name']))
//        {
//            $this->error("请输入名称");
//        }
//        if(!check_empty($data['imgs']))
//        {
//            $this->error("请输入图标");
//        }
//		if(intval($data['price']) == 0)
//		{
//			$this->error("请输入商品价格(人民币)");
//		}
//        if(intval($data['pai_diamonds']) == 0)
//        {
//            $this->error("请输入商品直播价格(钻石)");
//        }
//        if(intval($data['kd_cost']) == 0)
//        {
//            $data['kd_cost'] = 0;
//        }
//        if(intval($data['score']) == 0)
//        {
//            $data['score'] = 0;
//        }
//        if(intval($data['inventory']) == 0)
//        {
//            $this->error("请输入商品库存");
//        }
//        if(intval($data['sales']) == 0)
//        {
//            $data['sales'] = 0;
//        }
//        if(intval($data['number']) == 0)
//        {
//            $data['number'] = 0;
//        }
//		if(!check_empty($data['description']))
//		{
//			$this->error("请输入商品描述");
//		}
//
//        // 更新数据
//        $list=M(MODULE_NAME)->save ($data);
//        if (false !== $list) {
//            //成功提示
//            save_log($log_info.L("UPDATE_SUCCESS"),1);
//            clear_auto_cache("prop_id",array('id'=>$data['id']));
//            $this->success(L("UPDATE_SUCCESS"));
//        } else {
//            //错误提示
//            save_log($log_info.L("UPDATE_FAILED"),0);
//            $this->error(L("UPDATE_FAILED"),0,$log_info.L("UPDATE_FAILED"));
//        }
//    }
//
//    public function add_goods() {
//        $data = M(MODULE_NAME)->create();
//        if(!check_empty($data['name']))
//        {
//            $this->error("请输入名称");
//        }
//        if(!check_empty($data['imgs']))
//        {
//            $this->error("请输入商品主图片");
//        }
//        if(!check_empty($data['imgs_details']))
//        {
//            $this->error("请输入商品详情图片");
//        }
//        if(intval($data['price']) == 0)
//        {
//            $this->error("请输入商品价格(人民币)");
//        }
//        if(intval($data['pai_diamonds']) == 0)
//        {
//            $this->error("请输入商品直播价格(钻石)");
//        }
//        if(intval($data['kd_cost']) == 0)
//        {
//            $data['kd_cost'] = 0;
//        }
//        if(intval($data['score']) == 0)
//        {
//            $data['score'] = 0;
//        }
//        if(intval($data['inventory']) == 0)
//        {
//            $this->error("请输入商品库存");
//        }
//        if(intval($data['sales']) == 0)
//        {
//            $data['sales'] = 0;
//        }
//        if(intval($data['number']) == 0)
//        {
//            $data['number'] = 0;
//        }
//        if(!check_empty($data['description']))
//        {
//            $this->error("请输入商品描述");
//        }
//
//        // 更新数据
//        $log_info = $data['name'];
//        $list=M(MODULE_NAME)->add($data);
//        if (false !== $list) {
//            //成功提示
//            save_log($log_info.L("INSERT_SUCCESS"),1);
//            $this->success(L("INSERT_SUCCESS"));
//        } else {
//            //错误提示
//            save_log($log_info.L("INSERT_FAILED"),0);
//            $this->error(L("INSERT_FAILED"));
//        }
//
//
//    }


}
?>