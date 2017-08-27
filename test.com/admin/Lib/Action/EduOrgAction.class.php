<?php

class EduOrgAction extends CommonAction
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
        $org = M(MODULE_NAME)->where("id=" . $id)->find();  //当前状态
        $user = M('User')->where("id=" . $org['user_id'])->find();  //当前状态
        if ($user['is_authentication'] != 2) {
            $this->ajaxReturn(0, l("RECOMMEND_FAILED"), 0);
        } else {
            $n_is_effect = $org['is_recommend'] == 0 ? 1 : 0; //需设置的状态
            M(MODULE_NAME)->where("id=" . $id)->setField("is_recommend", $n_is_effect);
            save_log($org['title'] . l("SET_RECOMMEND_" . $n_is_effect), 1);
            $this->ajaxReturn($n_is_effect, l("SET_RECOMMEND_" . $n_is_effect), 1);
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
        $images = json_decode($vo['images'], true);
        $members = json_decode($vo['members'], true);
        $vo['tags'] = empty($vo['tags']) ? array() : explode(',', $vo['tags']);

        $tags = M("EduTags")->where('type=1')->findAll();
        foreach ($tags as &$tag) {
            if (in_array($tag['title'], $vo['tags'])) {
                $tag['is_checked'] = true;
            }
        }
        unset($tag);

        $this->assign('vo', $vo);
        $this->assign('images', $images);
        $this->assign('image_num', count($images));
        $this->assign('members', $members);
        $this->assign('member_num', count($members));
        $this->assign("tags", $tags);

        $m_config = load_auto_cache("m_config");
        $this->assign('secret_id', $m_config['qcloud_secret_id']);
        $this->display();
    }

    public function update()
    {
        B('FilterString');
        $data = M(MODULE_NAME)->create();
        $org = M(MODULE_NAME)->where("id=" . intval($data['id']))->find();
        //开始验证有效性
        $this->assign("jumpUrl", u(MODULE_NAME . "/edit", array("id" => $data['id'])));
        if (!check_empty($data['logo'])) {
            $this->error("请上传 Logo 图片");
        }

        $data['images'] = json_encode(array_values(array_filter($data['images'])));
        $data['members'] = json_encode(array_values(array_filter($data['members'], function ($m) {
            return !empty($m['name']) && !empty($m['avatar']);
        })));

        if (empty($data['tags'])) {
            $data['tags'] = array();
        }
        $data['tags'] = implode(',', array_slice($data['tags'], 0, 3));

        $log_info = $org['title'];
        $list = M(MODULE_NAME)->save($data);
        if (false !== $list) {
            if (!empty($data['file_id']) && $org['file_id'] != $data['file_id']) {
                fanwe_require(APP_ROOT_PATH . 'mapi/lib/core/common_edu.php');
                upload_edu_video($data['file_id'], 'edu_org', 'desc_video', $data['id']);
            }

            //更新众筹直播标签
//            if($org['tags'] != $data['tags']){
//                fanwe_require(APP_ROOT_PATH . 'mapi/lib/core/common_edu.php');
//                update_deal_tags($org['user_id'],$data['tags']);
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