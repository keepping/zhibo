<?php
// +----------------------------------------------------------------------
// | Fanwe 方维众筹商业系统
// +----------------------------------------------------------------------
// | Copyright (c) 2011 http://www.fanwe.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: 甘味人生(526130@qq.com)
// +----------------------------------------------------------------------

class UserAddrAction extends CommonAction{
	public function index()	{
		$map=array();
		$model = D ("user_address");
		if (! empty ( $model )) {
			$this->_list ( $model, $map );
		}
		
		$list = $this->get("list");
		
		$result = array();
		$row = 0;
		foreach($list as $kk=>&$vv)
		{
			$list[$kk]['user_name'] 	  = M("user")->where("id=".$vv['user_id']." ")->getField("nick_name");
			if (!empty($v['consignee_district'])) {
				$list[$kk]['consignee_district']	=json_decode($vv['consignee_district']);
				//按数组方式调用里面的数据 
				echo iconv('gb2312', 'utf-8',$list[$kk]['consignee_district']['province']).'+'; 
			}
			$list[$kk]['consignee_district'] = json_decode($vv['consignee_district'],true);
			$list[$kk]['consignee_district'] = $vv['consignee_district']['province'].$vv['consignee_district']['city'].$vv['consignee_district']['area'];
		}
		
		$this->assign("list",$list);
		$this->display ();
		return;
	}
	
	
	
	
	public function edit() {		
 		$this->assign("type_list",$this->type_list);
		$id = intval($_REQUEST ['id']);
		$condition['id'] = $id;		
		$vo = M('user_address')->where($condition)->find();
		$vo['user_name'] 	  = M("user")->where("id=".$vo['user_id']." ")->getField("nick_name");
		$this->assign ( 'vo', $vo );
		
		$region_pid = 0;
		$region_lv2 = $GLOBALS['db']->getAll("select * from ".DB_PREFIX."region_conf where region_level = 2 order by py asc");  //二级地址
		foreach($region_lv2 as $k=>$v)
		{
			if($v['name'] == $vo['province'])
			{
				$region_lv2[$k]['selected'] = 1;
				$region_pid = $region_lv2[$k]['id'];
				break;
			}
		}
		$this->assign("region_lv2",$region_lv2);
		
		
		if($region_pid>0)
		{
			$region_lv3 = $GLOBALS['db']->getAll("select * from ".DB_PREFIX."region_conf where pid = ".$region_pid." order by py asc");  //三级地址
			foreach($region_lv3 as $k=>$v)
			{
				if($v['name'] == $vo['city'])
				{
					$region_lv3[$k]['selected'] = 1;
					break;
				}
			}
			$this->assign("region_lv3",$region_lv3);
		}
		
		$this->display ();
	}
	
	
	public function update() {
		B('FilterString');
		$data =$_POST;
 		$log_info = M('user_address')->where("id=".intval($data['id']))->getField("user_id	");
		//开始验证有效性
		$this->assign("jumpUrl",u(MODULE_NAME."/edit",array("id"=>$data['id'])));
		if(!check_empty($data['consignee']))
		{
			$this->error("请填写收货人姓名");
		}	
		if(!check_empty($data['consignee_address']))
		{
			$this->error("请输入收货详细地址");
		}
		$data['create_time']=to_date(get_gmtime());
		$list=M('user_address')->save ($data);
		if (false !== $list) {
			//成功提示
			clear_auto_cache("banner_list");
			load_auto_cache("banner_list");
			save_log($log_info.L("UPDATE_SUCCESS"),1);
			$this->success(L("UPDATE_SUCCESS"));
		} else {
			//错误提示
			save_log($log_info.L("UPDATE_FAILED"),0);
			$this->error(L("UPDATE_FAILED"),0,$log_info.L("UPDATE_FAILED"));
		}
	}
	
	public function foreverdelete() {
		//彻底删除指定记录
		$ajax = intval($_REQUEST['ajax']);
		$id = $_REQUEST ['id'];
		if (isset ( $id )) {
				$condition = array ('id' => array ('in', explode ( ',', $id ) ) );
                $log_info = M('user_address')->where("id=".intval($id))->getField("user_id	");
				$list = M('user_address')->where ( $condition )->delete();				
				if ($list!==false) {
                    clear_auto_cache("banner_list");
                    load_auto_cache("banner_list");
					save_log($log_info.l("FOREVER_DELETE_SUCCESS"),1);
					$this->success (l("FOREVER_DELETE_SUCCESS"),$ajax);
				} else {
					save_log($log_info.l("FOREVER_DELETE_FAILED"),0);
					$this->error (l("FOREVER_DELETE_FAILED"),$ajax);
				}
			} else {
				$this->error (l("INVALID_OPERATION"),$ajax);
		}
	}
	
}