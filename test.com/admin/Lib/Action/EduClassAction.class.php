<?php

class EduClassAction extends CommonAction
{
    public function index()
    {
        $course_id = intval($_REQUEST['course_id']);
        $group_id = intval($_REQUEST['group_id']);
        $this->assign("course_id", $course_id);
        $this->assign("group_id", $group_id);

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
        $course_id = intval($_REQUEST['course_id']);
        $group_id = intval($_REQUEST['group_id']);
        if ($course_id > 0) {
            $price = M("EduCourses")->where(array('id' => $course_id))->getField('price');
        } elseif ($group_id > 0) {
            $group = M("EduClassGroup")->where(array('id' => $group_id))->find();
            if (empty($group)) {
                throw new Exception;
            }
            $course_id = $group['course_id'];
            $price = M("EduCourses")->where(array('id' => $group['course_id']))->getField('price');
        }

        if (!$course_id) {
            throw new Exception;
        }

        $group_list = M("EduClassGroup")->where(array('course_id' => $course_id))->findAll();
        if (empty($group_list)) {
            $this->assign("jumpUrl", u("EduClassGroup/add", array('course_id' => $course_id)));
            $this->error("请先添加目录");
        }

        $this->assign("course_id", $course_id);
        $this->assign("group_id", $group_id);
        $this->assign("show_price", $price > 0 ? false : true);
        $this->assign("group_list", $group_list);
        $m_config = load_auto_cache("m_config");
        $this->assign('secret_id', $m_config['qcloud_secret_id']);
        $this->assign('max_size', conf('MAX_IMAGE_SIZE') / 1000);
        $this->display();
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

    public function delete()
    {
        //删除指定记录
        $ajax = intval($_REQUEST['ajax']);
        $id = $_REQUEST ['id'];
        if (isset ($id)) {
            $info = M(MODULE_NAME)->where(array('id' => $id))->getField('title');
            $list = M(MODULE_NAME)->save(array('id' => $id, 'is_delete' => 1));

            if ($list !== false) {
                $course_id = M(MODULE_NAME)->where(array('id' => $id))->getField('course_id');
                $class_info = $GLOBALS['db']->getRow("select count(*) as num,sum(long_time) as long_time from fanwe_edu_class where course_id = {$course_id} and is_delete = 0");
                M('EduCourses')->save(array(
                    'id' => $course_id,
                    'courses_count' => $class_info['num'],
                    'long_time' => $class_info['long_time'],
                ));
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
        $condition['id'] = $id;
        $vo = M(MODULE_NAME)->where($condition)->find();
        $price = M("EduCourses")->where(array('id' => $vo['course_id']))->getField('price');
        $this->assign('vo', $vo);
        $this->assign("group_id", $vo['group_id']);
        $this->assign("show_price", $price > 0 ? false : true);
        $this->assign("group_list", M("EduClassGroup")->where(array('course_id' => $vo['course_id']))->findAll());
        $m_config = load_auto_cache("m_config");
        $this->assign('secret_id', $m_config['qcloud_secret_id']);
        $this->assign('max_size', conf('MAX_IMAGE_SIZE') / 1000);
        $this->display();
    }


    public function insert()
    {
        B('FilterString');
        $data = M(MODULE_NAME)->create();

        //开始验证有效性
        $this->assign("jumpUrl", u("EduClass/index", array('group_id' => $data['group_id'])));
        if (!check_empty($data['title'])) {
            $this->error("请输入标题");
        }

        if (!check_empty($data['group_id'])) {
            $this->error("请先添加目录");
        }

        if ($data['type'] == 0 && !check_empty($data['file_id'])) {
            $this->error("请上传视频");
        } elseif ($data['type'] == 1 && !check_empty($data['play_url'])) {
            $this->error("请上传音频");
        }

        if (!$data['long_time'] > 0) {
            $this->error("请输入时长");
        }

        // 更新数据
        $log_info = $data['title'];
        $list = M(MODULE_NAME)->add($data);
        if (false !== $list) {
            if (!empty($data['file_id'])) {
                fanwe_require(APP_ROOT_PATH . 'mapi/lib/core/common_edu.php');
                upload_edu_video($data['file_id'], 'edu_class', 'play_url', $list);
            }
            $class_info = $GLOBALS['db']->getRow("select count(*) as num,sum(long_time) as long_time from fanwe_edu_class where course_id = {$data['course_id']} and is_delete = 0");
            $course_data = array(
                'id' => $data['course_id'],
                'courses_count' => $class_info['num'],
                'long_time' => $class_info['long_time'],
            );

            if ($class_info['num'] == 1) {
                $course_data['is_effect'] = true;
            }
            M('EduCourses')->save($course_data);
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
        $class = M(MODULE_NAME)->where("id=" . intval($data['id']))->find();
        //开始验证有效性
        $this->assign("jumpUrl", u(MODULE_NAME . "/edit", array("id" => $data['id'])));
        if (!check_empty($data['title'])) {
            $this->error("请输入标题");
        }

        if ($data['type'] == 0 && !check_empty($data['file_id'])) {
            $this->error("请上传视频");
        } elseif ($data['type'] == 1 && !check_empty($data['play_url'])) {
            $this->error("请上传音频");
        }

        if (!$data['long_time'] > 0) {
            $this->error("请输入时长");
        }

        $log_info = $class['title'];
        $list = M(MODULE_NAME)->save($data);
        if (false !== $list) {
            if (!empty($data['file_id']) && $class['file_id'] != $data['file_id']) {
                fanwe_require(APP_ROOT_PATH . 'mapi/lib/core/common_edu.php');
                upload_edu_video($data['file_id'], 'edu_class', 'play_url', $data['id']);
            }
            $class_info = $GLOBALS['db']->getRow("select count(*) as num,sum(long_time) as long_time from fanwe_edu_class where course_id = {$class['course_id']} and is_delete = 0");
            M('EduCourses')->save(array(
                'id' => $class['course_id'],
                'courses_count' => $class_info['num'],
                'long_time' => $class_info['long_time'],
            ));
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