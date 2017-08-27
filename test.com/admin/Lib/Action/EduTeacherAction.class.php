<?php

class EduTeacherAction extends CommonAction
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
        $teacher = M(MODULE_NAME)->where("id=" . $id)->find();  //当前状态

        $user = M('User')->where("id=" . $teacher['user_id'])->find();  //当前状态
        // 审核未通过
        if ($user['is_authentication'] != 2) {
            $this->ajaxReturn(0, l("RECOMMEND_FAILED"), 0);
        } else {
            $n_is_effect = $teacher['is_recommend'] == 0 ? 1 : 0; //需设置的状态
            M(MODULE_NAME)->where("id=" . $id)->setField("is_recommend", $n_is_effect);
            save_log($user['authentication_name'] . l("SET_RECOMMEND_" . $n_is_effect), 1);
            $this->ajaxReturn($n_is_effect, l("RECOMMEND_FAILED" . $n_is_effect), 1);
        }
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

    public function edit()
    {
        $id = intval($_REQUEST ['id']);
        $condition['id'] = $id;
        $vo = M(MODULE_NAME)->where($condition)->find();
        $vo['tags'] = empty($vo['tags']) ? array() : explode(',', $vo['tags']);

        $tags = M("EduTags")->where('type=0')->findAll();
        foreach ($tags as &$tag) {
            if (in_array($tag['title'], $vo['tags'])) {
                $tag['is_checked'] = true;
            }
        }
        unset($tag);

        $this->assign('vo', $vo);
        $this->assign("tags", $tags);

        $m_config = load_auto_cache("m_config");
        $this->assign('secret_id', $m_config['qcloud_secret_id']);
        $this->display();
    }

    public function update()
    {
        B('FilterString');
        $data = M(MODULE_NAME)->create();
        //开始验证有效性
        $this->assign("jumpUrl", u(MODULE_NAME . "/edit", array("id" => $data['id'])));
        if (!check_empty($data['desc_image'])) {
            $this->error("请上传宣传图片");
        }
        $teacher = M(MODULE_NAME)->where("id=" . intval($data['id']))->find();
        if (empty($data['tags'])) {
            $data['tags'] = array();
        }
        $data['tags'] = implode(',', array_slice($data['tags'], 0, 3));

        $log_info = $teacher['title'];
        $list = M(MODULE_NAME)->save($data);
        if (false !== $list) {
            if (!empty($data['file_id']) && $teacher['file_id'] != $data['file_id']) {
                fanwe_require(APP_ROOT_PATH . 'mapi/lib/core/common_edu.php');
                upload_edu_video($data['file_id'], 'edu_teacher', 'desc_video', $data['id']);
            }

            //更新众筹直播标签
//            if($teacher['tags'] != $data['tags']){
//                fanwe_require(APP_ROOT_PATH . 'mapi/lib/core/common_edu.php');
//                update_deal_tags($teacher['user_id'],$data['tags']);
//            }

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