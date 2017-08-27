<?php

class EduCommentAction extends CommonAction
{
    public function course()
    {
        $map = array('type' => 1, 'reply_id' => intval($_REQUEST['reply_id']));
        $title = strim($_REQUEST['title']);
        if (!empty($title)) {
            $map['content'] = array('like', '%' . $title . '%');
        }
        $this->assign("default_map", $map);

        parent::index();
    }

    public function booking()
    {
        $map = array('type' => 2, 'reply_id' => intval($_REQUEST['reply_id']));
        $title = strim($_REQUEST['title']);
        if (!empty($title)) {
            $map['content'] = array('like', '%' . $title . '%');
        }
        $this->assign("default_map", $map);

        parent::index();
    }

    public function org()
    {
        $map = array('type' => 3, 'reply_id' => intval($_REQUEST['reply_id']));
        $title = strim($_REQUEST['title']);
        if (!empty($title)) {
            $map['content'] = array('like', '%' . $title . '%');
        }
        $this->assign("default_map", $map);

        parent::index();
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
                $info[] = $data['content'];
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