<?php

class EduCoursesAction extends CommonAction
{
    public function index()
    {
        $map = array('is_delete' => 0);
        $title = strim($_REQUEST['title']);
        if (!empty($title)) {
            $map['title'] = array('like', '%' . $title . '%');
        }
        $this->assign("default_map", $map);
        $this->assign("category_list", M("EduCourseCategory")->findAll());
        parent::index();
    }

    public function add()
    {
        $user_id = intval($_REQUEST['user_id']);
        $authentication_type = M('User')->where(array('id' => $user_id))->getField('authentication_type');
        if ($authentication_type == '教师') {
            $teacher = M("EduTeacher")->where(array('user_id' => $user_id))->find();
            $image = $teacher['desc_image'];
            $this->assign("jumpUrl", u("EduTeacher/edit", array('id' => $teacher['id'])));
        } elseif ($authentication_type == '机构') {
            $org = M("EduOrg")->where(array('user_id' => $user_id))->find();
            $image = $org['desc_video_image'];
            $this->assign("jumpUrl", u("EduOrg/edit", array('id' => $org['id'])));
        }

        if (empty($image)) {
            $this->error("请先上传封面");
        }

        $this->assign("category_list", M("EduCourseCategory")->findAll());
        $this->assign("tags", M("EduTags")->where('type=2')->findAll());
        $this->assign("user_id", $user_id);
        $this->assign("image", $image);
        $this->display();
    }

    //设置推荐
    public function set_recommend()
    {
        $id = intval($_REQUEST['id']);
        $course = M(MODULE_NAME)->where("id=" . $id)->find();  //当前状态
        $user = M('User')->where("id=" . $course['user_id'])->find();  //当前状态
        if ($user['is_authentication'] != 2) {
            $this->ajaxReturn(0, "未通过认证，推荐失败", 0);
        } elseif ($course['courses_count'] <= 0) {
            $this->ajaxReturn(0, "请先添加课时，推荐失败", 0);
        } else {
            $n_is_effect = $course['is_recommend'] == 0 ? 1 : 0; //需设置的状态
            M(MODULE_NAME)->where("id=" . $id)->setField("is_recommend", $n_is_effect);
            save_log($course['title'] . l("SET_RECOMMEND_" . $n_is_effect), 1);
            load_auto_cache("edu_index_courses", array(
                "list_type" => 'recommend',
                "is_recommend" => '1',
                "limit" => '4'
            ), true);
            $this->ajaxReturn($n_is_effect, l("SET_RECOMMEND_" . $n_is_effect), 1);
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

    public function edit()
    {
        $id = intval($_REQUEST ['id']);
        $vo = M(MODULE_NAME)->where(array('id' => $id))->find();
        $vo['tags'] = empty($vo['tags']) ? array() : explode(',', $vo['tags']);

        $tags = M("EduTags")->where('type=2')->findAll();
        foreach ($tags as &$tag) {
            if (in_array($tag['title'], $vo['tags'])) {
                $tag['is_checked'] = true;
            }
        }
        unset($tag);

        $this->assign('vo', $vo);
        $this->assign("tags", $tags);
        $this->assign("category_list", M("EduCourseCategory")->findAll());
        $this->display();
    }


    public function insert()
    {
        B('FilterString');
        $data = M(MODULE_NAME)->create();

        //开始验证有效性
        $this->assign("jumpUrl", u(MODULE_NAME . "/add", array('user_id' => $data['user_id'])));
        if (!check_empty($data['title'])) {
            $this->error("请输入标题");
        }

        if (!check_empty($data['image'])) {
            $this->error("请上传封面");
        }

        if (empty($data['tags'])) {
            $data['tags'] = array();
        }

        $data['tags'] = implode(',', array_slice($data['tags'], 0, 3));

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

        if (!check_empty($data['image'])) {
            $this->error("请上传封面");
        }

        if (empty($data['tags'])) {
            $data['tags'] = array();
        }

        $data['tags'] = implode(',', array_slice($data['tags'], 0, 3));

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