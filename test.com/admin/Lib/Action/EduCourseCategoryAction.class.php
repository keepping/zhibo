<?php

class EduCourseCategoryAction extends CommonAction
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

    //设置推荐
    public function set_recommend()
    {
        $id = intval($_REQUEST['id']);
        $category = M(MODULE_NAME)->where("id=" . $id)->find();  //当前状态
        $n_is_effect = $category['is_recommend'] == 0 ? 1 : 0; //需设置的状态
        M(MODULE_NAME)->where("id=" . $id)->setField("is_recommend", $n_is_effect);
        load_auto_cache("edu_course_category", array('act' => 'index', 'is_recommend' => 1), true);
        save_log($category['title'] . l("SET_RECOMMEND_" . $n_is_effect), 1);
        $this->ajaxReturn($n_is_effect, l("SET_BAN_" . $n_is_effect), 1);
    }

    public function set_sort()
    {
        $id = intval($_REQUEST['id']);
        $sort = intval($_REQUEST['sort']);
        $category = M(MODULE_NAME)->where("id=" . $id)->find();
        if (!check_sort($sort)) {
            $this->error(l("SORT_FAILED"), 1);
        }
        M(MODULE_NAME)->where("id=" . $id)->setField("sort", $sort);
        save_log($category['title'] . l("SORT_SUCCESS"), 1);
        $this->success(l("SORT_SUCCESS"), 1);
    }

    public function insert()
    {
        B('FilterString');
        $data = M(MODULE_NAME)->create();

        //开始验证有效性
        $this->assign("jumpUrl", u(MODULE_NAME . "/index"));
        if (!check_empty($data['title'])) {
            $this->error("请输入标题");
        }
        if (!check_empty($data['image'])) {
            $this->error("请上传图片");
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

    public function edit()
    {
        $id = intval($_REQUEST ['id']);
        $vo = M(MODULE_NAME)->where(array('id' => $id))->find();

        $this->assign('vo', $vo);
        $this->display();
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
            $this->error("请上传图片");
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