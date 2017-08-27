<?php

class EduClassGroupAction extends CommonAction
{
    public function index()
    {
        $course_id = strim($_REQUEST['course_id']);
        if (empty($course_id)) {
            throw new Exception;
        }

        $map = array('is_delete' => 0);
        $this->assign("default_map", $map);
        $this->assign("course_id", $course_id);
        parent::index();
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

    public function add()
    {
        $course_id = strim($_REQUEST['course_id']);
        if (empty($course_id)) {
            throw new Exception;
        }
        $price = M("EduCourses")->where(array('id' => $course_id))->getField('price');
        $this->assign("show_price", $price > 0 ? false : true);
        $this->assign("course_id", $course_id);
        $m_config = load_auto_cache("m_config");
        $this->assign('secret_id', $m_config['qcloud_secret_id']);
        $this->display();
    }

    public function delete()
    {
        //删除指定记录
        $ajax = intval($_REQUEST['ajax']);
        $id = $_REQUEST ['id'];
        if (isset ($id)) {
            $group = M(MODULE_NAME)->where(array('id' => $id))->field('course_id,title')->find();
            $list = M(MODULE_NAME)->save(array('id' => $id, 'is_delete' => 1));

            if ($list !== false) {
                $GLOBALS['db']->query("update " . DB_PREFIX . "edu_class set is_delete = 1 where course_id = {$group['course_id']} and group_id = {$id}");
                $class_info = $GLOBALS['db']->getRow("select count(*) as num,sum(long_time) as long_time from fanwe_edu_class where course_id = {$group['course_id']} and is_delete = 0");
                M('EduCourses')->save(array(
                    'id' => $group['course_id'],
                    'courses_count' => $class_info['num'],
                    'long_time' => $class_info['long_time'],
                ));
                save_log($group['title'] . l("DELETE_SUCCESS"), 1);
                $this->success(l("DELETE_SUCCESS"), $ajax);
            } else {
                save_log($group['title'] . l("DELETE_FAILED"), 0);
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
        $price = M("EduCourses")->where(array('id' => $vo['course_id']))->getField('price');
        $this->assign("show_price", $price > 0 ? false : true);
        $this->assign('vo', $vo);
        $this->assign("course_id", $vo['course_id']);
        $m_config = load_auto_cache("m_config");
        $this->assign('secret_id', $m_config['qcloud_secret_id']);
        $this->display();
    }


    public function insert()
    {
        B('FilterString');
        $data = M(MODULE_NAME)->create();
        if (!check_empty($data['course_id'])) {
            throw new Exception;
        }

        //开始验证有效性
        $this->assign("jumpUrl", u(MODULE_NAME . "/index", array('course_id' => $data['course_id'])));
        if (!check_empty($data['title'])) {
            $this->error("请输入标题");
        }

        // 更新数据
        $log_info = $data['title'];
        $list = M(MODULE_NAME)->add($data);
        if (false !== $list) {
            if (!empty($data['file_id'])) {
                fanwe_require(APP_ROOT_PATH . 'mapi/lib/core/common_edu.php');
                upload_edu_video($data['file_id'], 'edu_class_group', 'play_url', $list);
            }
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
        $group = M(MODULE_NAME)->where("id=" . intval($data['id']))->find();
        //开始验证有效性
        $this->assign("jumpUrl", u(MODULE_NAME . "/edit", array("id" => $data['id'])));
        if (!check_empty($data['title'])) {
            $this->error("请输入标题");
        }

        $log_info = $group['title'];
        $list = M(MODULE_NAME)->save($data);
        if (false !== $list) {
            if (!empty($data['file_id']) && $group['file_id'] != $data['file_id']) {
                fanwe_require(APP_ROOT_PATH . 'mapi/lib/core/common_edu.php');
                upload_edu_video($data['file_id'], 'edu_class_group', 'play_url', $data['id']);
            }
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