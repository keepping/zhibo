<?php
	class FamilyLevelAction extends CommonAction{
		public function index(){
			parent::index();
		}
		
		public function add(){
			$this->assign("new_level", M("FamilyLevel")->max("level")+1);
            $this->assign("old_score", M("FamilyLevel")->max("score"));
			$this->display();
		}
		
		public function edit(){
			$id = intval($_REQUEST ['id']);
			$condition['id'] = $id;
			$vo = M(MODULE_NAME)->where($condition)->find();
            $old_level = M(MODULE_NAME)->where('level='.($vo['level']-1))->find();
            $this->assign("old_score",$old_level['score']);
			$this->assign ( 'vo', $vo );
			$this->display ();
		}
		
		public function update(){
			B('FilterString');
			$data = M(MODULE_NAME)->create ();
			rm_auto_cache("family_level");
			$log_info = M(MODULE_NAME)->where("id=".intval($data['id']))->getField("name");
			//开始验证有效性
			$this->assign("jumpUrl",u(MODULE_NAME."/edit",array("id"=>$data['id'])));
            if(!check_empty($data['level']))
            {
                $this->error("请输入等级");
            }
			if(!check_empty($data['name']))
			{
				$this->error("请输入等级名称");
			}
            if(!(intval($data['level'])>0))
            {
                $this->error("等级必须大于0");
            }
            $family_level_list = M(MODULE_NAME)->where("level=".intval($data['level']-1))->find();
			if($family_level_list)
			{
                if(intval($data['score'])<=$family_level_list['score']){
                    $this->error('所需积分值必须大于上一级所需积分值');
                }
            }else{
                if(intval($data['score'])<0)
                {
                    $this->error("积分值必须大于等于0");
                }
            }
            $data['point'] = $data['score'];
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
		
		public function insert() {
			B('FilterString');
			$ajax = intval($_REQUEST['ajax']);
			$data = M(MODULE_NAME)->create ();
			rm_auto_cache("family_level");
			//开始验证有效性
			$this->assign("jumpUrl",u(MODULE_NAME."/add"));
            if(!check_empty($data['level']))
            {
                $this->error("请输入等级");
            }
			if(!check_empty($data['name']))
			{
				$this->error("请输入等级名称");
			}
            if(!(intval($data['level'])>0))
            {
                $this->error("等级必须大于0");
            }
			$family_level_list = M(MODULE_NAME)->where("level=".intval($data['level']-1))->find();
			if($family_level_list)
			{
                if(intval($data['score'])<=$family_level_list['score']){
                    $this->error('所需积分值必须大于上一级所需积分值');
                }
			}else{
                if(intval($data['score'])<0)
                {
                    $this->error("积分值必须大于等于0");
                }
            }
			
			// 更新数据
			$log_info = $data['name'];
            $data['point'] = $data['score'];
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
		
		public function delete(){
			//彻底删除指定记录
			$ajax = intval($_REQUEST['ajax']);
			$id = $_REQUEST ['id'];
			rm_auto_cache("family_level");
			if (isset ( $id )) {
					$condition = array ('id' => array ('in', explode ( ',', $id ) ) );			
					$rel_data = M(MODULE_NAME)->where($condition)->findAll();				
					foreach($rel_data as $data)
					{
						$info[] = $data['name'];	
						if(conf("DEFAULT_ADMIN")==$data['name'])
						{
							$this->error ($data['name'].l("DEFAULT_ADMIN_CANNOT_DELETE"),$ajax);
						}	
					}
					if($info) $info = implode(",",$info);
					$list = M(MODULE_NAME)->where ( $condition )->delete();
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