<?php
// +----------------------------------------------------------------------
// | Fanwe 方维众筹商业系统
// +----------------------------------------------------------------------
// | Copyright (c) 2011 http://www.fanwe.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: 甘味人生(526130@qq.com)
// +----------------------------------------------------------------------

class SlbGroupAction extends CommonAction{
	public function index()
	{
		parent::index();
	}

	public function add()
	{
		$this->display();
	}
	public function add_api()
	{
		$id = intval($_REQUEST ['id']);
		$condition['id'] = $id;		
		$vo = M(MODULE_NAME)->where($condition)->find();
		$this->assign ('vo', $vo);
		//输出API接口列表
		$this->assign("apilist",M("ApiList")->findAll());
		$this->display();
	}
	public function insert_api()
	{
		$slb_group_id = intval($_REQUEST ['slb_group_id']);
		$api_id = $_REQUEST ['api_id'];
		if (isset ( $api_id )) {
			$condition = array ('id' => array ('in', $api_id) );
			$rel_data = M("ApiList")->where($condition)->findAll();		
			foreach($rel_data as $data)
			{
				$info[] = $data['name'];
			}
			if($info) $info = implode(",",$info);
			$ApiList = M("ApiList")->where("slb_group_id=".$slb_group_id)->findAll();
			foreach($ApiList as $date){
				if($date&&!in_array($date['id'],$api_id)){
					$ApiList_condition[$date['id']] = $date['id'];
				}
				$Api_condition = array ('id' => array ('in', $ApiList_condition));
				$list = M("ApiList")->where ( $Api_condition )->setField ( 'slb_group_id', 0);  
			}
			$list = M("ApiList")->where ( $condition )->setField ( 'slb_group_id', $slb_group_id);
			clear_auto_cache("api_list");
			if ($list!==false) {
				save_log($info.l("UPDATE_SUCCESS"),0);
				$this->success (l("UPDATE_SUCCESS"),0);
			} else {
				save_log($info.l("UPDATE_FAILED"),0);
				$this->error (l("UPDATE_FAILED"),0);
			}
		} else {
			$this->error (l("INVALID_OPERATION"),0);
		}
	}
	public function edit() {		
		$id = intval($_REQUEST ['id']);
		$condition['is_effect'] = 1;
		$condition['id'] = $id;		
		$vo = M(MODULE_NAME)->where($condition)->find();
		$this->assign ( 'vo', $vo );
		$this->display ();
	}
	//相关操作
	public function set_effect()
	{
		$id = intval($_REQUEST['id']);
		$ajax = intval($_REQUEST['ajax']);
		$info = M(MODULE_NAME)->where("id=".$id)->getField("name");		
		$c_is_effect = M(MODULE_NAME)->where("id=".$id)->getField("is_effect");  //当前状态
		if(conf("DEFAULT_ADMIN")==$info)
		{
			$this->ajaxReturn($c_is_effect,l("DEFAULT_ADMIN_CANNOT_EFFECT"),1)	;	
		}	
		$n_is_effect = $c_is_effect == 0 ? 1 : 0; //需设置的状态
		M(MODULE_NAME)->where("id=".$id)->setField("is_effect",$n_is_effect);
		clear_auto_cache("api_list");	
		save_log($info.l("SET_EFFECT_".$n_is_effect),1);
		$this->ajaxReturn($n_is_effect,l("SET_EFFECT_".$n_is_effect),1)	;	
	}
	public function insert() {
		B('FilterString');
		$data = M(MODULE_NAME)->create ();
		//开始验证有效性
		$this->assign("jumpUrl",u(MODULE_NAME."/add"));
		if(!check_empty($data['name']))
		{
			$this->error('服务器名称不能为空');
		}	
		
	
		if(M(MODULE_NAME)->where("name='".$data['name']."'")->count()>0)
		{
			$this->error(L('服务器名称已存在'));
		}
		// 更新数据
		$log_info = $data['name'];

		$list=M(MODULE_NAME)->add($data);
		clear_auto_cache("api_list");
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
		$data = M(MODULE_NAME)->create ();
		$slb_group_info = M(MODULE_NAME)->where("id=".intval($data['id']))->getField("name");
		//开始验证有效性
		$this->assign("jumpUrl",u(MODULE_NAME."/edit",array("id"=>$data['id'])));
		
		if(!check_empty($data['name']))
		{
			$this->error('服务器名称不能为空');
		}
        $id = M(MODULE_NAME)->where("name='".$data['name']."'")->getField('id');
		if($id && $id!=$data['id'])
		{
			$this->error(L('服务器名称已存在'));
		}
		// 更新数据
		$list=M(MODULE_NAME)->save ($data);
		clear_auto_cache("api_list");
		if (false !== $list) {
			//成功提示
			save_log($slb_group_info.L("UPDATE_SUCCESS"),1);
			$this->success(L("UPDATE_SUCCESS"));
		} else {
			//错误提示
			save_log($slb_group_info.L("UPDATE_FAILED"),0);
			$this->error(L("UPDATE_FAILED"),0,$slb_group_info.L("UPDATE_FAILED"));
		}
	}

	public function delete() {
		//删除指定记录
		$ajax = intval($_REQUEST['ajax']);
		$id = $_REQUEST ['id'];
		if (isset ( $id )) {
			$condition = array ('id' => array ('in', explode ( ',', $id ) ) );
			$rel_data = M(MODULE_NAME)->where($condition)->findAll();				
			foreach($rel_data as $data)
			{
				$info[] = $data['name'];
			}
			if($info) $info = implode(",",$info);
			$list = M(MODULE_NAME)->where ( $condition )->setField ( 'is_effect', 1 );
			clear_auto_cache("api_list");
			if ($list!==false) {
				save_log($info.l("DELETE_SUCCESS"),1);
				$this->success (l("DELETE_SUCCESS"),$ajax);
			} else {
				save_log($info.l("DELETE_FAILED"),0);
				$this->error (l("DELETE_FAILED"),$ajax);
			}
		} else {
			$this->error (l("INVALID_OPERATION"),$ajax);
		}		
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
					$info[] = $data['name'];	
				}
				if($info) $info = implode(",",$info);
				$list = M(MODULE_NAME)->where ( $condition )->delete();
				clear_auto_cache("api_list");
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

}
?>