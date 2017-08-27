<?php
// +----------------------------------------------------------------------
// | Fanwe 方维众筹商业系统
// +----------------------------------------------------------------------
// | Copyright (c) 2011 http://www.fanwe.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: 甘味人生(526130@qq.com)
// +----------------------------------------------------------------------

class VideoCateAction extends CommonAction{
	public function index()
	{
        $now=get_gmtime();
        $map['is_delete'] = 0;
        if(strim($_REQUEST['title'])!=''){
            $map['title'] = array('like','%'.strim($_REQUEST['title']).'%');
        }
        $create_time_2=empty($_REQUEST['create_time_2'])?to_date($now,'Y-m-d'):strim($_REQUEST['create_time_2']);
        $create_time_2=to_timespan($create_time_2)+24*3600;
        if(trim($_REQUEST['create_time_1'])!='')
        {
            $map['create_time'] = array('between',array(to_timespan($_REQUEST['create_time_1']),$create_time_2));
        }

		if (method_exists ( $this, '_filter' )) {
			$this->_filter ( $map );
		}
		$name=$this->getActionName();
		$model = D ($name);
		if (! empty ( $model )) {
			$this->_list ( $model, $map );
		}
		$list = $this->get("list");
		$result = array();
		$row = 0;
		foreach($list as $k=>$v)
		{
			$v['level'] = -1;
			$v['title'] = $v['title'];
			$video_count = M('Video')->where('cate_id ='.$v['id'])->count();
			$v['video_num'] = $video_count;
			$result[$row] = $v;
			$row++;

		}
		$this->assign("list",$result);
		$this->display ();
		return;
	}
	public function add()
	{
		$cate_tree = M(MODULE_NAME)->where('is_delete = 0')->findAll();
		$this->assign("cate_tree",$cate_tree);
		$this->assign("new_sort", M("VideoCate")->max("sort")+1);
		$this->display();
	}
	public function edit() {
		$id = intval($_REQUEST ['id']);
		$cate_tree = M(MODULE_NAME)->where('is_delete = 0')->findAll();
		$this->assign("cate_tree",$cate_tree);
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
				foreach($rel_data as $data)
				{
					$info[] = $data['title'];
				}
				if($info) $info = implode(",",$info);
				$list = M(MODULE_NAME)->where ( $condition )->delete();
				if ($list!==false) {
                    //redis同步
                    require_once APP_ROOT_PATH."/admin/Lib/Action/RedisCommon.class.php";
                    $redisCommon = new Ridescommon();
                    foreach($rel_data as $data){
                        $redisCommon->video_cate_list($data['title'],$data,'delete');
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
		$data = M(MODULE_NAME)->create();
		//开始验证有效性
		$this->assign("jumpUrl",u(MODULE_NAME."/add"));
        $data['title'] = strim($data['title']);
		if(!check_empty($data['title']))
		{
			$this->error("请输入话题名称");
		}
        $cate_id = $GLOBALS['db']->getOne("select id from ".DB_PREFIX."video_cate where title = '".$data['title']."'");
        if($cate_id){
            $this->error("话题名称已存在");
        }
        $data['create_time'] = get_gmtime();
        $data['is_delete'] = 0;
        $user_count = $GLOBALS['db']->getOne("select count(*) from ".DB_PREFIX."user where id = ".intval($data['user_id']));
        if(strim($data['user_id'])!='' && $user_count<=0){
            $this->error("关联用户ID不存在");
        }
		// 更新数据
		$log_info = $data['title'];
		$list=M(MODULE_NAME)->add($data);
		if (false !== $list) {
            //redis同步
            require_once APP_ROOT_PATH."/admin/Lib/Action/RedisCommon.class.php";
            $redisCommon = new Ridescommon();
            $redisData = $data;
            $redisData['id'] = intval(M(MODULE_NAME)->getLastInsID());
            $redisCommon->video_cate_list($data['title'],$redisData,'insert');
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

		$log_info = M(MODULE_NAME)->where("id=".intval($data['id']))->getField("title");
		//开始验证有效性
		$this->assign("jumpUrl",u(MODULE_NAME."/edit",array("id"=>$data['id'])));
		if(!check_empty($data['title']))
		{
			$this->error("请输入分类名称");
		}
        $user_count = $GLOBALS['db']->getOne("select count(*) from ".DB_PREFIX."user where id = ".intval($data['user_id']));
        if(strim($data['user_id'])!='' && $user_count<=0){
            $this->error("关联用户ID不存在");
        }
		$list=M(MODULE_NAME)->save ($data);
		if (false !== $list) {
            //redis同步
            require_once APP_ROOT_PATH."/admin/Lib/Action/RedisCommon.class.php";
            $redisCommon = new Ridescommon();
            $redisCommon->video_cate_list($data['title'],$data,'update');
			//成功提示
			save_log($log_info.L("UPDATE_SUCCESS"),1);
			$this->success(L("UPDATE_SUCCESS"));
		} else {
			//错误提示
			save_log($log_info.L("UPDATE_FAILED"),0);
			$this->error(L("UPDATE_FAILED"),0,$log_info.L("UPDATE_FAILED"));
		}
	}
    /*public function set_effect()
    {
        $id = intval($_REQUEST['id']);
        $ajax = intval($_REQUEST['ajax']);
        $info = M(MODULE_NAME)->where("id=".$id)->getField("title");
        $c_is_effect = M(MODULE_NAME)->where("id=".$id)->getField("is_effect");  //当前状态
        $n_is_effect = $c_is_effect == 0 ? 1 : 0; //需设置的状态
        M(MODULE_NAME)->where("id=".$id)->setField("is_effect",$n_is_effect);
        save_log($info.l("SET_EFFECT_".$n_is_effect),1);
        //redis同步
        require_once APP_ROOT_PATH."/admin/Lib/Action/RedisCommon.class.php";
        $data = M(MODULE_NAME)->where("id=".$id)->find();
        $redisCommon = new Ridescommon();
        $redisCommon->video_cate_list($data['title'],$data,'update');
        clear_auto_cache("get_help_cache");
        $this->ajaxReturn($n_is_effect,l("SET_EFFECT_".$n_is_effect),1)	;
    }*/

	public function set_sort()
	{
		$id = intval($_REQUEST['id']);
		$sort = intval($_REQUEST['sort']);
		$log_info = M("VideoCate")->where("id=".$id)->getField("title");
		if(!check_sort($sort))
		{
			$this->error(l("SORT_FAILED"),1);
		}
		M("VideoCate")->where("id=".$id)->setField("sort",$sort);
        //redis同步
        require_once APP_ROOT_PATH."/admin/Lib/Action/RedisCommon.class.php";
        $data = M(MODULE_NAME)->where("id=".$id)->find();
        $redisCommon = new Ridescommon();
        $redisCommon->video_cate_list($data['title'],$data,'update');
		save_log($log_info.l("SORT_SUCCESS"),1);
		$this->success(l("SORT_SUCCESS"),1);
	}

}
?>