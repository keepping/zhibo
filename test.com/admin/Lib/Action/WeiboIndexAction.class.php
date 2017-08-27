<?php
// +----------------------------------------------------------------------
// | Fanwe 方维众筹商业系统
// +----------------------------------------------------------------------
// | Copyright (c) 2011 http://www.fanwe.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: 甘味人生(526130@qq.com)
// +----------------------------------------------------------------------

class WeiboIndexAction extends CommonAction{
    private $type_list;
    private $position;
    public function __construct(){
        parent::__construct();
        $position = array(
            '4'=>'启动广告',
            '10'=>'首页-美女轮播图',
            '11'=>'首页-写真轮播图',
        );
        $type_list =array('0'=>'网页url链接');
        //教育直播
        if( defined("OPEN_EDU_MODULE") && OPEN_EDU_MODULE){

            $type_list[10] = '会员动态跳转';
            $type_list[11] = '动态详情跳转';


        }

        $this->type_list = $type_list;
        $this->position = $position;
    }

    public function index()
    {
        $condition = ' show_position in(4,10,11)';
        $count = M('index_image')->where($condition)->count();
        $p     = new Page($count, $listRows = 20);
        //举报类型

        $weibo  =  M('index_image')->where($condition)->order('id desc')->limit($p->firstRow . ',' . $p->listRows)->findAll();
        $page = $p->show();
        $this->assign("page", $page);
        $this->assign ( 'list', $weibo );

        $this->display ();
    }
    public function add()
    {
        $family = M("Family")->where("status = 1")->findAll();
        $this->assign("family",$family);
        $this->assign("position",$this->position);
        $this->assign("type_list",$this->type_list);
        $this->assign("new_sort", M("IndexImage")->max("sort")+1);
        $this->display();
    }
    public function edit() {
        $family = M("Family")->where("status = 1")->findAll();
        $this->assign("family",$family);
        $this->assign("position",$this->position);
        $this->assign("type_list",$this->type_list);
        $id = intval($_REQUEST ['id']);
        $condition['id'] = $id;
        $vo = M('index_image')->where($condition)->find();
        $this->assign ( 'vo', $vo );
        $this->display ();
    }


    public function foreverdelete() {
        //彻底删除指定记录
        $ajax = intval($_REQUEST['ajax']);
        $id = $_REQUEST ['id'];
        if (isset ( $id )) {
            $condition = array ('id' => array ('in', explode ( ',', $id ) ) );
            $rel_data = M('index_image')->where($condition)->findAll();
            foreach($rel_data as $data)
            {
                $info[] = $data['title'];
            }
            if($info) $info = implode(",",$info);
            $list = M('index_image')->where ( $condition )->delete();
            if ($list!==false) {
                clear_auto_cache("banner_list_xr");
                load_auto_cache("banner_list_xr");
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
        $ajax = intval($_REQUEST['ajax']);
        $data = M('index_image')->create ();

        //开始验证有效性
        $this->assign("jumpUrl",u('WeiboIndex'."/add"));
        if($data['show_position']==4){
            $index_image = M('index_image')->where("show_position = 4")->find();
            if($index_image){
                $this->error("启动广告只能添加一个，已添加可编辑！");
            }
        }
        if(!check_empty($data['image']))
        {
            $this->error("请上传广告图");
        }
        if(!check_empty($data['title']))
        {
            $this->error("请输入标题");
        }

        if($data['type']==1|| $data['type']==6 || $data['type']==7 || $data['type']==8 || $data['type']==10 || $data['type']==11){
            if($data['type']==8)
                $error_title="请输入房间号";
            elseif($data['type']==6)
                $error_title="请输入机构id";
            elseif($data['type']==7)
                $error_title="请输入会员id";
            elseif($data['type']==1)
                $error_title="请选择家族";
            elseif($data['type']==10)
                $error_title="请输入会员ID";
            elseif($data['type']==11)
                $error_title="请输入动态";

            if(!$data['show_id']){
                $this->error($error_title);
            }
        }else{
            $data['show_id'] = '';
        }

        // 更新数据
        $log_info = $data['title'];
        $list=M('index_image')->add($data);
        if (false !== $list) {
            //load_auto_cache("index_image",'',false);
            //redis缓存
            clear_auto_cache("banner_list_xr");
            load_auto_cache("banner_list_xr");
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
        $data = M('index_image')->create ();

        $log_info = M('index_image')->where("id=".intval($data['id']))->getField("title");
        //开始验证有效性
        $this->assign("jumpUrl",u('WeiboIndex'."/edit",array("id"=>$data['id'])));
        if($data['show_position']==4){
            $index_image = M('index_image')->where("show_position = 4")->find();
            if(isset($index_image['id']) && $index_image['id']!=$data['id']){
                $this->error("启动广告只能添加一个，已添加可编辑！");
            }
        }
        if(!check_empty($data['image']))
        {
            $this->error("请上传广告图");
        }
        if(!check_empty($data['title']))
        {
            $this->error("请输入标题");
        }


        if($data['type']==1|| $data['type']==6 || $data['type']==7 || $data['type']==8 || $data['type']==10 || $data['type']==11){
            if($data['type']==8)
                $error_title="请输入房间号";
            elseif($data['type']==6)
                $error_title="请输入机构id";
            elseif($data['type']==7)
                $error_title="请输入会员id";
            elseif($data['type']==1)
                $error_title="请选择家族";
            elseif($data['type']==10)
                $error_title="请输入会员ID";
            elseif($data['type']==11)
                $error_title="请输入动态";

            if(!$data['show_id']){
                $this->error($error_title);
            }
        }else{
            $data['show_id'] = '';
        }

        $list=M('index_image')->save ($data);
        if (false !== $list) {
            //成功提示
            //load_auto_cache("index_image",'',false);
            //redis缓存
            clear_auto_cache("banner_list_xr");
            load_auto_cache("banner_list_xr");
            save_log($log_info.L("UPDATE_SUCCESS"),1);
            $this->success(L("UPDATE_SUCCESS"));
        } else {
            //错误提示
            save_log($log_info.L("UPDATE_FAILED"),0);
            $this->error(L("UPDATE_FAILED"),0,$log_info.L("UPDATE_FAILED"));
        }
    }

    public function set_sort()
    {
        $id = intval($_REQUEST['id']);
        $sort = intval($_REQUEST['sort']);
        $log_info = M("IndexImage")->where("id=".$id)->getField("title");
        if(!check_sort($sort))
        {
            $this->error(l("SORT_FAILED"),1);
        }
        M("IndexImage")->where("id=".$id)->setField("sort",$sort);
        save_log($log_info.l("SORT_SUCCESS"),1);
        clear_auto_cache("banner_list_xr");
        $this->success(l("SORT_SUCCESS"),1);
    }
}
?>