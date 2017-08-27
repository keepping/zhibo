<?php
// +----------------------------------------------------------------------
// | Fanwe 方维众筹商业系统
// +----------------------------------------------------------------------
// | Copyright (c) 2011 http://www.fanwe.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: 甘味人生(526130@qq.com)
// +----------------------------------------------------------------------

class IndexImageAction extends CommonAction{
    private $type_list;
    private $position;
    public function __construct(){
        parent::__construct();
        $type_list =array('0'=>'网页url链接');
        $position = array('0'=>'轮播图');

        if(defined("OPEN_RANKING_LIST")&&OPEN_RANKING_LIST){
            $type_list[2] = '排行榜APP跳转';
        }
        
        if(defined("OPEN_PC")&&OPEN_PC){
            $position[3] = 'PC首页';
        }
        
        //教育直播
        if( defined("OPEN_EDU_MODULE") && OPEN_EDU_MODULE){
            $position[5] = '首页直播推荐';
            $position[9] = '首页预约课程推荐';
            $position[6] = '课堂首页轮播图';
            $position[7] = '约课首页轮播图';
            $position[8] = '线下约课轮播图';
            
           $type_list[8] = '跳转到直播间';
           $type_list[6] = '跳转线下约课详情';
           $type_list[7] = '跳转一对一约课';
           $type_list[9] = '跳转课程详情';
            
        }
        /*$type_list[3] = 'PC首页';
        $type_list[4] = '启动广告';*/
        $position[4] = '启动广告';
        
       
        $this->type_list = $type_list;
        $this->position = $position;
    }

	public function index()
	{
		parent::index();
	}
	public function add()
	{
        $this->assign("position",$this->position);
 		$this->assign("type_list",$this->type_list);
		$this->assign("new_sort", M("IndexImage")->max("sort")+1);
		$this->display();
	}
	public function edit() {
        $this->assign("position",$this->position);
 		$this->assign("type_list",$this->type_list);
		$id = intval($_REQUEST ['id']);
		$condition['id'] = $id;		
		$vo = M(MODULE_NAME)->where($condition)->find();
		$this->assign ( 'vo', $vo );
		$this->display ();
	}
	
	
	public function foreverdelete() {
		//彻底删除指定记录
		$ajax = intval($_REQUEST['ajax']);
		$id = $_REQUEST ['id'];
		if (isset ( $id )) {
				$condition = array ('id' => array ('in', explode ( ',', $id ) ) );
				$rel_data = M(MODULE_NAME)->where($condition)->findAll();
                $delete_show_position=array();
				foreach($rel_data as $data)
				{
					$info[] = $data['title'];
                    $delete_show_position[$data['show_position']]=$data['show_position'];
				}
				if($info) $info = implode(",",$info);
				$list = M(MODULE_NAME)->where ( $condition )->delete();				
				if ($list!==false) {
                    clear_auto_cache("banner_list");
                    load_auto_cache("banner_list");
                    foreach($delete_show_position as $v){
                        load_auto_cache("banner_edu_list",array('show_position'=>$v),false);
                    }
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
		$data = M(MODULE_NAME)->create ();

		//开始验证有效性
		$this->assign("jumpUrl",u(MODULE_NAME."/add"));
        if($data['show_position']==4){
            $index_image = M(MODULE_NAME)->where("show_position = 4")->find();
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
		
        if($data['type']==1|| $data['type']==6 || $data['type']==7 || $data['type']==8 || $data['type']==9){
        	if($data['type']==8)
        		$error_title="请输入房间号";
        	elseif($data['type']==6)
        		$error_title="请输入机构id";
        	elseif($data['type']==7)
        		$error_title="请输入会员id";
            elseif($data['type']==9)
                $error_title="请输入课程id";

            if(!$data['show_id']){
                $this->error($error_title);
            }
        }else{
            $data['show_id'] = '';
        }
        
		// 更新数据
		$log_info = $data['title'];
		$list=M(MODULE_NAME)->add($data);
		if (false !== $list) {
			//load_auto_cache("index_image",'',false);
			//redis缓存
			clear_auto_cache("banner_list");
			load_auto_cache("banner_list");

            load_auto_cache("banner_edu_list",array('show_position'=>$data['show_position']),false);
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
		$data = M(MODULE_NAME)->create ();

        $before_info = M("IndexImage")->where("id=" . $data['id'])->find();
 		$log_info = $before_info['title'];
		//开始验证有效性
		$this->assign("jumpUrl",u(MODULE_NAME."/edit",array("id"=>$data['id'])));
        if($data['show_position']==4){
            $index_image = M(MODULE_NAME)->where("show_position = 4")->find();
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
       
        
        if($data['type']==1|| $data['type']==6 || $data['type']==7 || $data['type']==8 || $data['type']==9){
        	if($data['type']==8)
        		$error_title="请输入房间号";
        	elseif($data['type']==6)
        		$error_title="请输入机构id";
        	elseif($data['type']==7)
        		$error_title="请输入会员id";
            elseif($data['type']==9)
                $error_title="请输入课程id";

        	
            if(!$data['show_id']){
                $this->error($error_title);
            }
        }else{
            $data['show_id'] = '';
        }
        
		$list=M(MODULE_NAME)->save ($data);
		if (false !== $list) {
			//成功提示
			//load_auto_cache("index_image",'',false);
			//redis缓存
			clear_auto_cache("banner_list");
			load_auto_cache("banner_list");

            if($before_info['show_position'] != $data['show_position']){
                load_auto_cache("banner_edu_list",array('show_position'=>$before_info['show_position']),false);
            }
            load_auto_cache("banner_edu_list",array('show_position'=>$data['show_position']),false);
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
    $image_info = M("IndexImage")->where("id=" . $id)->find();
    $log_info = $image_info['title'];
    if(!check_sort($sort))
    {
        $this->error(l("SORT_FAILED"),1);
    }
    M("IndexImage")->where("id=".$id)->setField("sort",$sort);
    save_log($log_info.l("SORT_SUCCESS"),1);
    clear_auto_cache("banner_list");
    load_auto_cache("banner_edu_list",array('show_position'=>$image_info['show_position']),false);
    $this->success(l("SORT_SUCCESS"),1);
}
}
?>