<?php

class EduClassBookingAction extends CommonAction
{
    public function index()
    {
        $map = array();
        $title = strim($_REQUEST['title']);
        if (!empty($title)) {
            $map['title'] = array('like', '%' . $title . '%');
        }
        $this->assign("default_map", $map);
        parent::index();
    }

    public function add()
    {
        $org_id = intval($_REQUEST['org_id']);
        $user_id = M('EduOrg')->where("id=" . $org_id)->getField("user_id");
        $this->assign('user_id', $user_id);

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
            $this->error("请输入课程标题");
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
            $this->error("请输入课程标题");
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

    public function delete()
    {
        //删除指定记录
        $ajax = intval($_REQUEST['ajax']);
        $id = $_REQUEST ['id'];
        if (isset ($id)) {
            $condition = array('id' => array('in', explode(',', $id)));
            $rel_data = M(MODULE_NAME)->where($condition)->findAll();
            foreach ($rel_data as $data) {
                $info[] = $data['title'];
            }
            if ($info) {
                $info = implode(",", $info);
            }
            $list = M(MODULE_NAME)->where($condition)->delete();
            if ($list !== false) {
                save_log($info . l("DELETE_SUCCESS"), 1);
                $this->success(l("DELETE_SUCCESS"), $ajax);
            } else {
                save_log($info . l("DELETE_FAILED"), 0);
                $this->error(l("DELETE_FAILED"), $ajax);
            }
        } else {
            $this->error(l("INVALID_OPERATION"), $ajax);
        }
    }
}