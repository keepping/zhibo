<?php

class AdAction extends CommonAction
{
    public function index()
    {
        if(trim($_REQUEST['ad_info'])!='')
        {
            $map['title'] = array('like','%'.trim($_REQUEST['ad_info']).'%');
        }
        if (method_exists ( $this, '_filter' )) {
            $this->_filter ( $map );
        }
        $name=$this->getActionName();
        $model = D ($name);
        if (! empty ( $model )) {
            $this->_list ( $model, $map );
        }
//        $map = array();
//        $this->assign("default_map", $map);
//        parent::index();
        $this->display ();
    }

    public function add()
    {
        $this->assign("place_list", M("AdPlace")->findAll());
        $this->assign("new_sort", 10);
        rm_auto_cache("ad_list");
        $this->display();
    }
    public function delete(){
        //删除指定记录
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
                rm_auto_cache("ad_list");
                save_log($info.l("DELETE_SUCCESS"),1);
                clear_auto_cache("get_help_cache");
                clear_auto_cache("article_notice");
                $this->success (l("DELETE_SUCCESS"),$ajax);
            } else {
                save_log($info.l("DELETE_FAILED"),0);
                $this->error (l("DELETE_FAILED"),$ajax);
            }
        } else {
            $this->error (l("INVALID_OPERATION"),$ajax);
        }
    }
    public function edit()
    {
        $id = intval($_REQUEST ['id']);
        $condition['id'] = $id;
        $vo = M(MODULE_NAME)->where($condition)->find();
        $this->assign('vo', $vo);
        $this->assign("place_list", M("AdPlace")->findAll());
        $this->display();
    }


    public function insert()
    {
        B('FilterString');
        $data = M(MODULE_NAME)->create();

        //开始验证有效性
        $this->assign("jumpUrl", u(MODULE_NAME . "/add"));
        if (!check_empty($data['title'])) {
            $this->error("请输入标题");
        }
        if (!check_empty($data['url'])) {
            $this->error("请输入跳转地址");
        }
        if (!check_empty($data['image'])) {
            $this->error("请上传广告图");
        }
        if (!check_empty($data['place_id'])) {
            $this->error("请选择区域");
        }

        // 更新数据
        $log_info = $data['title'];
        $list = M(MODULE_NAME)->add($data);
        if (false !== $list) {
            rm_auto_cache("ad_list");
            clear_auto_cache("ad_list", $data['place_id']);
            load_auto_cache("ad_list", $data['place_id']);
            //成功提示
            save_log($log_info . L("INSERT_SUCCESS"), 1);
            $this->success(L("INSERT_SUCCESS"));
        } else {
            //错误提示
            save_log($log_info . L("INSERT_FAILED"), 0);
            $this->error(L("INSERT_FAILED"));
        }
    }

    public function update()
    {
        B('FilterString');
        $data = M(MODULE_NAME)->create();
        $log_info = M(MODULE_NAME)->where("id=" . intval($data['id']))->getField("title");
        //开始验证有效性
        $this->assign("jumpUrl", u(MODULE_NAME . "/edit", array("id" => $data['id'])));
        if (!check_empty($data['title'])) {
            $this->error("请输入标题");
        }
        if (!check_empty($data['image'])) {
            $this->error("请上传广告图");
        }
        if (!check_empty($data['title'])) {
            $this->error("请输入标题");
        }
        if (!check_empty($data['place_id'])) {
            $this->error("请选择区域");
        }

        $list = M(MODULE_NAME)->save($data);
        if (false !== $list) {
            rm_auto_cache("ad_list");
            clear_auto_cache("ad_list", $data['place_id']);
            load_auto_cache("ad_list", $data['place_id']);
            //成功提示
            save_log($log_info . L("UPDATE_SUCCESS"), 1);
            $this->success(L("UPDATE_SUCCESS"));
        } else {
            //错误提示
            save_log($log_info . L("UPDATE_FAILED"), 0);
            $this->error(L("UPDATE_FAILED"), 0, $log_info . L("UPDATE_FAILED"));
        }
    }
}