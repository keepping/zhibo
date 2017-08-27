<?php
// +----------------------------------------------------------------------
// | Fanwe 方维众筹商业系统
// +----------------------------------------------------------------------
// | Copyright (c) 2011 http://www.fanwe.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: 甘味人生(526130@qq.com)
// +----------------------------------------------------------------------

class GoodsTagsAction extends CommonAction
{
    public function index()
    {
        $map   = array();
        $mod   = M('goods_tags');
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
        $vo = M('goods_tags')->where(array('id' => $id))->find();
        $this->assign('vo', $vo);
        $this->display();
    }
//    public function add()
//    {
//        $this->display();
//    }
    public function update()
    {
        $data = array();
        $data['id'] = intval($_REQUEST['id']);
        $data['image']=$_REQUEST['image'];
        $data['name']=$_REQUEST['name'];
        $data['sort']=$_REQUEST['sort'];

        if(empty($data['image'])){
            $this->error('标签图片不能为空');
        }
        if (empty($data['name'])) {
            $this->error('标签名称不能为空');
        }
        if (intval($data['id'])) {
            // 更新数据
            $res = M('goods_tags')->save($data);
            $this->assign("jumpUrl", u(MODULE_NAME . "/edit", array("id" => $data['id'])));
        } else {
            unset($data['id']);
            $res = $data['id'] = M('goods_tags')->add($data);
            $this->assign("jumpUrl", u(MODULE_NAME . "/add"));
        }
        $log_info = M('goods_tags')->where("id=".intval($data['id']))->getField("name");

        if (false !== $res) {
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
            $rel_data  = M('goods_tags')->where($condition)->findAll();
            foreach ($rel_data as $data) {
                $info[] = $data['adm_name'];
                if (conf("DEFAULT_ADMIN") == $data['adm_name']) {
                    $this->error($data['adm_name'] . l("DEFAULT_ADMIN_CANNOT_DELETE"), $ajax);
                }
            }
            if ($info) {
                $info = implode(",", $info);
            }

            $list = M('goods_tags')->where($condition)->delete();
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
