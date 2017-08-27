<?php
// +----------------------------------------------------------------------
// | Fanwe 方维p2p借贷系统
// +----------------------------------------------------------------------
// | Copyright (c) 2011 http://www.fanwe.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: 甘味人生(526130@qq.com)
// +----------------------------------------------------------------------

class GoodsCateAction extends CommonAction{
	public function index()
	{

		$name=$this->getActionName();
		$model = D ($name);
		if (! empty ( $model )) {
			$this->_list ( $model);
		}
//		$list = $this->get('list');
//		$this->assign("list",$list);
		$this->display ();
	}

	public function add()
	{

		$this->assign("new_sort", M(MODULE_NAME)->where("is_delete=0"));
		$this->display();
	}

	public function edit() {		
		$id = intval($_REQUEST ['id']);
		$condition['id'] = $id;		
		$vo = M(MODULE_NAME)->where($condition)->find();
		$this->assign ( 'vo', $vo );
		
		$this->display ();
	}

	public function insert() {
		B('FilterString');
		$data = M(MODULE_NAME)->create();

		//开始验证有效性
		$this->assign("jumpUrl",u(MODULE_NAME."/index"));
		if(!check_empty($data['name']))
		{
			$this->error(L("ARTICLECATE_TITLE_EMPTY_TIP"));
		}	

		// 更新数据
		$log_info = $data['name'];

		$list=M(MODULE_NAME)->add($data);
		if (false !== $list) {
			//$this->create_httpd();
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

		$data = M(MODULE_NAME)->create ();
		$log_info = M(MODULE_NAME)->where("id=".intval($data['id']))->getField("name");
		//开始验证有效性
		$this->assign("jumpUrl",u(MODULE_NAME."/edit",array("id"=>$data['id'])));

		if(!check_empty($data['name']))
		{
			$this->error(L("ARTICLECATE_TITLE_EMPTY_TIP"));
		}

		$data['name'] = strim($data['name']);
		// 更新数据
		$list=M(MODULE_NAME)->save ($data);
		if (false !== $list) {
			//成功提示
			save_log($log_info.L("UPDATE_SUCCESS"),1);
			$this->success(L("UPDATE_SUCCESS"));
		} else {
			//错误提示
			save_log($log_info.L("UPDATE_FAILED"),0);
			$this->error(L("UPDATE_FAILED"),0,$log_info.L("UPDATE_FAILED"));
		}
	}

	public function delete() {
		//删除指定记录
		$ajax = intval($_REQUEST['ajax']);
		$id = $_REQUEST ['id'];
		if (isset ( $id )) {
			$condition['id'] = $id;
			$list = M(MODULE_NAME)->where($condition)->setField ( 'is_effect', 0 );
			if ($list!==false) {
				$result['info'] = "删除成功！";
				$result['status'] = 1;
				admin_ajax_return($result);
			} else {
				$result['info'] = "删除失败！";
				$result['status'] = 0;
				admin_ajax_return($result);
			}
		} else {
			$this->error (l("INVALID_OPERATION"),$ajax);
		}
	}

//	public function restore() {
//		//删除指定记录
//		$ajax = intval($_REQUEST['ajax']);
//		$id = $_REQUEST ['id'];
//		if (isset ( $id )) {
//				$condition = array ('id' => array ('in', explode ( ',', $id ) ) );
//				$rel_data = M(MODULE_NAME)->where($condition)->findAll();
//				foreach($rel_data as $data)
//				{
//					$info[] = $data['title'];
//				}
//				if($info) $info = implode(",",$info);
//				$list = M(MODULE_NAME)->where ( $condition )->setField ( 'is_delete', 0 );
//				if ($list!==false) {
//					save_log($info.l("RESTORE_SUCCESS"),1);
//					clear_auto_cache("cache_shop_acate_tree");
//					clear_auto_cache("deal_shop_acate_belone_ids");
//					clear_auto_cache("get_help_cache");
//					$this->success (l("RESTORE_SUCCESS"),$ajax);
//				} else {
//					save_log($info.l("RESTORE_FAILED"),0);
//					$this->error (l("RESTORE_FAILED"),$ajax);
//				}
//			} else {
//				$this->error (l("INVALID_OPERATION"),$ajax);
//		}
//	}

//	public function foreverdelete() {
//		//彻底删除指定记录
//		$ajax = intval($_REQUEST['ajax']);
//		$id = $_REQUEST ['id'];
//		if (isset ( $id )) {
//				$condition = array ('id' => array ('in', explode ( ',', $id ) ) );
//				if(M("Article")->where(array ('cate_id' => array ('in', explode ( ',', $id ) ) ))->count()>0)
//				{
//					$this->error (l("SUB_ARTICLE_EXIST"),$ajax);
//				}
//
//				$rel_data = M(MODULE_NAME)->where($condition)->findAll();
//				foreach($rel_data as $data)
//				{
//					$info[] = $data['title'];
//				}
//				if($info) $info = implode(",",$info);
//				$list = M(MODULE_NAME)->where ( $condition )->delete();
//
//				if ($list!==false) {
//					save_log($info.l("FOREVER_DELETE_SUCCESS"),1);
//					clear_auto_cache("cache_shop_acate_tree");
//					clear_auto_cache("deal_shop_acate_belone_ids");
//					clear_auto_cache("get_help_cache");
//					$this->success (l("FOREVER_DELETE_SUCCESS"),$ajax);
//				} else {
//					save_log($info.l("FOREVER_DELETE_FAILED"),0);
//					$this->error (l("FOREVER_DELETE_FAILED"),$ajax);
//				}
//			} else {
//				$this->error (l("INVALID_OPERATION"),$ajax);
//		}
//	}

}
?>