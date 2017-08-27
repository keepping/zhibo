<?php

class AdPlaceAction extends CommonAction
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
        $this->display ();
    }

    public function add()
    {
        $this->display();
    }

    public function edit()
    {
        $id = intval($_REQUEST ['id']);
        $condition['id'] = $id;
        $vo = M(MODULE_NAME)->where($condition)->find();
        $this->assign('vo', $vo);
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

        // 更新数据
        $log_info = $data['title'];
        $list = M(MODULE_NAME)->add($data);
        if (false !== $list) {
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
        $list = M(MODULE_NAME)->save($data);
        if (false !== $list) {
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