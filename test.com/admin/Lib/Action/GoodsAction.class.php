<?php
// +----------------------------------------------------------------------
// | Fanwe 方维p2p借贷系统
// +----------------------------------------------------------------------
// | Copyright (c) 2011 http://www.fanwe.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: 甘味人生(526130@qq.com)
// +----------------------------------------------------------------------

class GoodsAction extends CommonAction{
    public function index()
    {

        if(strim($_REQUEST['name'])!=''){
            $map['name'] = array('like','%'.strim($_REQUEST['name']).'%');
        }

        $name=$this->getActionName();
        $model = D ($name);
        if (! empty ( $model )) {
            $this->_list ( $model, $map );
        }
        $list = $this->get('list');

        foreach($list as $k => $v){
            $list[$k]['imgs'] = json_decode($v['imgs'],1)[0];
            $list[$k]['imgs_details'] = json_decode($v['imgs_details'],1)[0];
        }

        $this->assign("list",$list);
        $this->display();

    }

    public function edit() {
        $id = intval($_REQUEST ['id']);
        $condition['id'] = $id;
        $vo = M(MODULE_NAME)->where($condition)->find();
        $vo['imgs'] = json_decode($vo['imgs'],1)[0];
        $vo['imgs_details'] = json_decode($vo['imgs_details'],1)[0];
        $vo['tags_id'] = json_decode($vo['tags_id']);
        $this->assign ('vo',$vo );
        $cate = $GLOBALS['db']->getAll("select * from ".DB_PREFIX."goods_cate where is_effect=1");
        $tags = $GLOBALS['db']->getAll("select * from ".DB_PREFIX."goods_tags");
        $this->assign ('cate_info',$cate );
        $this->assign ('tags_info',$tags );
        $this->display ();
    }


    public function update() {
        B('FilterString');
        $data = M(MODULE_NAME)->create();
		//clear_auto_cache("prop_list");
        $log_info = M(MODULE_NAME)->where("id=".intval($data['id']))->getField("name");
        //开始验证有效性
        $this->assign("jumpUrl",u(MODULE_NAME."/edit",array("id"=>$data['id'])));

        $data['tags_id'] = json_encode($_REQUEST['tags_id']);

        if(!check_empty($data['name']))
        {
            $this->error("请输入名称");
        }
        if(!check_empty($data['imgs']))
        {
            $this->error("请输入商品主图片");
        }
        $data['imgs'] = json_encode(array($data['imgs']),JSON_UNESCAPED_SLASHES);
        if(!check_empty($data['imgs_details']))
        {
            $this->error("请输入商品详情图片");
        }
        $data['imgs_details'] = json_encode(array($data['imgs_details']),JSON_UNESCAPED_SLASHES);
        if(SHOPPING_GOODS == 1){
            if(floatval($data['price']) == 0)
            {
                $this->error("请输入商品价格(人民币)");
            }
        }
        if(intval($data['kd_cost']) == 0)
        {
            $data['kd_cost'] = 0;
        }
        if(floatval($data['podcast_ticket']) == 0)
        {
            $data['podcast_ticket'] = 0;
        }
        if(intval($data['score']) == 0)
        {
            $data['score'] = 0;
        }
        if(intval($data['inventory']) == 0)
        {
            $this->error("请输入商品库存");
        }
        if(intval($data['sales']) == 0)
        {
            $data['sales'] = 0;
        }
        if(intval($data['number']) == 0)
        {
            $data['number'] = 0;
        }
        if(PAI_REAL_BTN == 1){
            if(intval($data['pai_diamonds']) == 0)
            {
                $this->error("请输入商品直播价格(钻石)");
            }
            if(intval($data['bz_diamonds']) == 0)
            {
                $this->error("请输入竞拍保证金");
            }
            if(intval($data['jj_diamonds']) == 0)
            {
                $this->error("请输入竞拍加价金额");
            }
            if(floatval($data['pai_time']) == 0)
            {
                $data['pai_time'] = 0;
            }
        }
		if(!check_empty($data['description']))
		{
			$this->error("请输入商品描述");
		}

        // 更新数据
        $list=M(MODULE_NAME)->save ($data);
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

    public function add(){
        $vo = $GLOBALS['db']->getAll("select * from ".DB_PREFIX."goods_cate where is_effect=1");
        $tags = $GLOBALS['db']->getAll("select * from ".DB_PREFIX."goods_tags");
        $this->assign ('cate_info',$vo );
        $this->assign ('tags_info',$tags );
        $this->display ();
    }

    public function add_goods() {
        $data = M(MODULE_NAME)->create();
        $data['tags_id'] = json_encode($_REQUEST['tags_id']);
        if(!check_empty($data['name']))
        {
            $this->error("请输入名称");
        }
        if(!check_empty($data['imgs']))
        {
            $this->error("请输入商品主图片");
        }
        $data['imgs'] = json_encode(array($data['imgs']),JSON_UNESCAPED_SLASHES);
        if(!check_empty($data['imgs_details']))
        {
            $this->error("请输入商品详情图片");
        }
        $data['imgs_details'] = json_encode(array($data['imgs_details']),JSON_UNESCAPED_SLASHES);
        if(SHOPPING_GOODS == 1){
            if(floatval($data['price']) == 0)
            {
                $this->error("请输入商品价格(人民币)");
            }
        }
        if(intval($data['kd_cost']) == 0)
        {
            $data['kd_cost'] = 0;
        }
        if(floatval($data['podcast_ticket']) == 0)
        {
            $data['podcast_ticket'] = 0;
        }
        if(intval($data['score']) == 0)
        {
            $data['score'] = 0;
        }
        if(intval($data['inventory']) == 0)
        {
            $this->error("请输入商品库存");
        }
        if(intval($data['sales']) == 0)
        {
            $data['sales'] = 0;
        }
        if(intval($data['number']) == 0)
        {
            $data['number'] = 0;
        }
        if(PAI_REAL_BTN == 1){
            if(intval($data['pai_diamonds']) == 0)
            {
                $this->error("请输入商品直播价格(钻石)");
            }
            if(intval($data['bz_diamonds']) == 0)
            {
                $this->error("请输入竞拍保证金");
            }
            if(intval($data['jj_diamonds']) == 0)
            {
                $this->error("请输入竞拍加价金额");
            }
            if(floatval($data['pai_time']) == 0)
            {
                $data['pai_time'] = 0;
            }
        }
        if(!check_empty($data['description']))
        {
            $this->error("请输入商品描述");
        }

        // 更新数据
        $log_info = $data['name'];
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


}
?>