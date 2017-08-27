<?php

class EduClassOfflineAction extends CommonAction
{
    public function index()
    {
        $user_id = intval($_REQUEST['user_id']);
        if ($user_id > 0) {
            $this->assign("user_id", $user_id);
        }

        $map = array('is_delete' => 0);
        $title = strim($_REQUEST['title']);
        if (!empty($title)) {
            $map['title'] = array('like', '%' . $title . '%');
        }
        $this->assign("default_map", $map);
        parent::index();
    }

    public function add()
    {
        $user_id = intval($_REQUEST['user_id']);

        $org = M('EduOrg')->where(array('user_id' => $user_id))->find();
        if (empty($org)) {
            throw new Exception;
        }

        if (empty($org['logo']) || empty($org['description']) || empty($org['members'])) {
            $this->assign("jumpUrl", u("EduOrg/edit", array('id' => $org['id'])));
            $this->error("请完善机构信息");
        }

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
        $this->assign("jumpUrl", u(MODULE_NAME . "/add", array('user_id' => $data['user_id'])));
        if (!check_empty($data['title'])) {
            $this->error("请输入课程标题");
        }

        if (!check_empty($data['image'])) {
            $this->error("请上传图片");
        }

        $org_id = M('EduOrg')->where(array('user_id' => $data['user_id']))->getField('id');
        if (!$org_id) {
            $this->error("操作不正确");
        }

        if ($data['price'] <= 0) {
            $this->error("请输入价格");
        }

        if ($data['class_num'] < 1) {
            $this->error("请输入课时数量");
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

        if ($data['price'] <= 0) {
            $this->error("请输入价格");
        }

        if ($data['class_num'] < 1) {
            $this->error("请输入课时数量");
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
            $info = M(MODULE_NAME)->where(array('id' => $id))->getField('title');
            $list = M(MODULE_NAME)->save(array('id' => $id, 'is_delete' => 1));

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