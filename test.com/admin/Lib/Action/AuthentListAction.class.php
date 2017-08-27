<?php
// +----------------------------------------------------------------------
// | Fanwe 方维p2p借贷系统
// +----------------------------------------------------------------------
// | Copyright (c) 2011 http://www.fanwe.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: 甘味人生(526130@qq.com)
// +----------------------------------------------------------------------

class AuthentListAction extends CommonAction{
    public function index()
    {
        parent::index();
    }

    public function add()
    {
        $this->assign("new_sort", M("AuthentList")->max("sort")+1);
        $this->display();
    }
    public function edit() {
        $id = intval($_REQUEST ['id']);
        $condition['id'] = $id;
        $vo = M(MODULE_NAME)->where($condition)->find();
        $this->assign ( 'vo', $vo );
        $this->display ();
    }

    //彻底删除指定记录
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
            //删除相关预览图
//				foreach($rel_data as $data)
//				{
//					@unlink(get_real_path().$data['preview']);
//				}			
            if ($list!==false) {
                save_log($info.l("FOREVER_DELETE_SUCCESS"),1);
                clear_auto_cache("get_help_cache");
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
        if(!check_empty($data['name']))
        {
            $this->error("请输入名称");
        }
        if(!check_empty($data['icon']))
        {
            $this->error("请输入图标");
        }
        // 更新数据
        $log_info = $data['name'];
        $list=M(MODULE_NAME)->add($data);
        if (false !== $list) {
            //成功提示
            save_log($log_info.L("INSERT_SUCCESS"),1);
            clear_auto_cache("get_help_cache");
            $this->success(L("INSERT_SUCCESS"));
        } else {
            //错误提示
            save_log($log_info.L("INSERT_FAILED"),0);
            $this->error(L("INSERT_FAILED"));
        }
    }

    public function update() {
        B('FilterString');
        $data = M(MODULE_NAME)->create();

//		if($_FILES['preview']['name']!='')
//		{
//			$result = $this->uploadImage();
//			if($result['status']==0)
//			{
//				$this->error($result['info'],$ajax);
//			}
//			//删除图片
//			@unlink(get_real_path().M("Article")->where("id=".$data['id'])->getField("preview"));
//			$data['preview'] = $result['data'][0]['bigrecpath'].$result['data'][0]['savename'];
//		}

        $log_info = M(MODULE_NAME)->where("id=".intval($data['id']))->getField("name");
        //开始验证有效性
        $this->assign("jumpUrl",u(MODULE_NAME."/edit",array("id"=>$data['id'])));
        if(!check_empty($data['name']))
        {
            $this->error("请输入名称");
        }
        if(!check_empty($data['icon']))
        {
            $this->error("请输入图标");
        }
        // 更新数据
        $list=M(MODULE_NAME)->save ($data);
        if (false !== $list) {
            //成功提示
            save_log($log_info.L("UPDATE_SUCCESS"),1);
            clear_auto_cache("get_help_cache");

            $users = M("User")->where("authent_list_id = ".intval($data['id']))->findAll();
            if(sizeof($users)>0){
                $user_ids = array_column($users,"id");
                $condition['id'] = array ('in', $user_ids );
                $condition['is_authentication'] = 2;
                M("User")->where($condition)->setField("v_icon",get_spec_image($data['icon']));
                user_deal_to_reids($user_ids);
            }

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
        $log_info = M("AuthentList")->where("id=".$id)->getField("name");
        if(!check_sort($sort))
        {
            $this->error(l("SORT_FAILED"),1);
        }
        M("AuthentList")->where("id=".$id)->setField("sort",$sort);
        save_log($log_info.l("SORT_SUCCESS"),1);
        clear_auto_cache("get_help_cache");
        $this->success(l("SORT_SUCCESS"),1);
    }
}
?>