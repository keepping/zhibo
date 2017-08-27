<?php
// +----------------------------------------------------------------------
// | Fanwe 方维众筹商业系统
// +----------------------------------------------------------------------
// | Copyright (c) 2011 http://www.fanwe.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: 甘味人生(526130@qq.com)
// +----------------------------------------------------------------------

class PaiTagsAction extends CommonAction
{
    public function index()
    {
        $map   = array();
        $mod   = M(MODULE_NAME);
        $count = $mod->where($map)->count();
        $p     = new Page($count, $listRows = 20);
        if ($count > 0) {
            $list = $mod->where($map)->order('sort')->limit($p->firstRow . ',' . $p->listRows)->select();
        }
        $page = $p->show();
        $this->assign("page", $page);
        $this->assign("list", $list);
        $this->display('index');
    }
    public function edit()
    {
        $id = intval($_REQUEST['id']);
        $vo = M(MODULE_NAME)->where(array('id' => $id))->find();
        $this->assign('vo', $vo);
        $this->display();
    }
    public function add()
    {
        $this->display();
    }
    public function update()
    {
        $data = M(MODULE_NAME)->create();
        if (empty($data['name'])) {
            $this->error('标签名称不能为空');
        }
        if (intval($data['id'])) {
            // 更新数据
            $res = M(MODULE_NAME)->save($data);
            $this->assign("jumpUrl", u(MODULE_NAME . "/edit", array("id" => $data['id'])));
        } else {
            unset($data['id']);
            $res = $data['id'] = M(MODULE_NAME)->add($data);
            $this->assign("jumpUrl", u(MODULE_NAME . "/add"));
        }
        $log_info = M(MODULE_NAME)->where("id=" . intval($data['id']))->getField("name");

        if (false !== $res) {
            clear_auto_cache("pay_list");
            load_auto_cache("pay_list");
            save_log($log_info . L("UPDATE_SUCCESS"), 1);
            $this->success(L("UPDATE_SUCCESS"));
        } else {
            //错误提示
            save_log($log_info . L("UPDATE_FAILED"), 0);
            $this->error(L("UPDATE_FAILED"), 0, $log_info . L("UPDATE_FAILED"));
        }
    }
    public function foreverdelete()
    {
        //彻底删除指定记录
        $ajax = intval($_REQUEST['ajax']);
        $id   = $_REQUEST['id'];
        if (isset($id)) {
            $condition = array('id' => array('in', explode(',', $id)));
            $rel_data  = M(MODULE_NAME)->where($condition)->findAll();
            foreach ($rel_data as $data) {
                $info[] = $data['adm_name'];
                if (conf("DEFAULT_ADMIN") == $data['adm_name']) {
                    $this->error($data['adm_name'] . l("DEFAULT_ADMIN_CANNOT_DELETE"), $ajax);
                }
            }
            if ($info) {
                $info = implode(",", $info);
            }

            $list = M(MODULE_NAME)->where($condition)->delete();
            if ($list !== false) {
                save_log($info . l("FOREVER_DELETE_SUCCESS"), 1);
                $this->success(l("FOREVER_DELETE_SUCCESS"), $ajax);
            } else {
                save_log($info . l("FOREVER_DELETE_FAILED"), 0);
                $this->error(l("FOREVER_DELETE_FAILED"), $ajax);
            }
        } else {
            $this->error(l("INVALID_OPERATION"), $ajax);
        }
    }
}
