<?php
// +----------------------------------------------------------------------
// | Fanwe 方维众筹商业系统
// +----------------------------------------------------------------------
// | Copyright (c) 2011 http://www.fanwe.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: 甘味人生(526130@qq.com)
// +----------------------------------------------------------------------

class UserDiamondsLogAction extends CommonAction
{

    public function index()
    {
        $table = 'fanwe_user_diamonds_log log, fanwe_pai_goods goods, fanwe_user';
        $where = 'log.user_id = fanwe_user.id and log.pai_id = goods.id and log.type=1';
        if (isset($_REQUEST['name'])) {
            $where .= ' and goods.name like \'%' . addslashes(trim($_REQUEST['name'])) . '%\'';
        }
        if (isset($_REQUEST['user_name'])) {
            $where .= ' and fanwe_user.nick_name like \'%' . addslashes(trim($_REQUEST['user_name'])) . '%\'';
        }
        $begin_time = trim($_REQUEST['begin_time']) == '' ? 0 : to_timespan($_REQUEST['begin_time']);
        $end_time   = trim($_REQUEST['end_time']) == '' ? 0 : to_timespan($_REQUEST['end_time']);
        if ($begin_time != 0) {
            $where .= ' and log.create_time >= \'' . addslashes($begin_time) . '\'';
        } elseif ($end_time != 0) {
            $where .= ' and log.create_time <= \'' . addslashes($end_time) . '\'';
        }

        $mod   = M('user_diamonds_log');
        $count = $mod->table($table)->where($where)->count();
        $p     = new Page($count, $listRows = 20);
        if ($count > 0) {
            $info = $mod->table($table)->where($where)->field('log.*,goods.name as goods_name,fanwe_user.nick_name')->order('log.id desc')->limit($p->firstRow . ',' . $p->listRows)->select();
        }
        $page = $p->show();
        $this->assign("page", $page);
        $this->assign("list", $info);
        $this->display();
    }
    public function edit()
    {
        header('location:http://' . $_SERVER['HTTP_HOST'] . '/'.get_manage_url_name().'?m=PaiGoods&a=edit&id=' . intval($_REQUEST['id']));
        die;
    }

}
