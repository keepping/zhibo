<?php
// +----------------------------------------------------------------------
// | Fanwe 方维众筹商业系统
// +----------------------------------------------------------------------
// | Copyright (c) 2011 http://www.fanwe.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: 甘味人生(526130@qq.com)
// +----------------------------------------------------------------------

class PaiJoinAction extends CommonAction
{

    public function index()
    {
        $table = 'fanwe_pai_join pai_join, fanwe_pai_goods goods, fanwe_user';
        $where = 'pai_join.pai_id = goods.id and pai_join.user_id = fanwe_user.id';
        if (isset($_REQUEST['name'])) {
            $where .= ' and goods.name like \'%' . addslashes(trim($_REQUEST['name'])) . '%\'';
        }
        if (isset($_REQUEST['user_name'])) {
            $where .= ' and fanwe_user.nick_name like \'%' . addslashes(trim($_REQUEST['user_name'])) . '%\'';
        }
        $begin_time = trim($_REQUEST['begin_time']) == '' ? 0 : to_timespan($_REQUEST['begin_time']);
        $end_time   = trim($_REQUEST['end_time']) == '' ? 0 : to_timespan($_REQUEST['end_time']);
        if ($begin_time != 0) {
            $where .= ' and pai_join.create_time >= \'' . addslashes($begin_time) . '\'';
        } elseif ($end_time != 0) {
            $where .= ' and pai_join.create_time <= \'' . addslashes($end_time) . '\'';
        }

        $count = M('pai_join')->table($table)->where($where)->count();
        $p     = new Page($count, $listRows = 20);
        if ($count > 0) {
            $field = 'pai_join.*,goods.name,goods.podcast_name as podcast_name,fanwe_user.nick_name as user_name';
            $order = 'pai_join.id desc';
            $list  = M('pai_join')->table($table)->where($where)->field($field)->order($order)->limit($p->firstRow . ',' . $p->listRows)->select();
            $max   = array();
            foreach ($list as &$value) {
                $value['create_time'] = to_date($value['create_time']);
                if ($value['pai_status'] == 0) {
                    if (!isset($max[$value['pai_id']])) {

                        $map = array('pai_id' => $value['pai_id']);

                        $max[$value['pai_id']] = M('pai_join')->where($map)->max('pai_diamonds');
                    }
                    $value['max'] = $max[$value['pai_id']];
                }
            }
        }
        $page = $p->show();
        $this->assign("page", $page);
        $this->assign("list", $list);
        $this->display();
    }
    public function edit()
    {
        header('location:http://' . $_SERVER['HTTP_HOST'] . '/'.get_manage_url_name().'?m=PaiGoods&a=edit&id=' . intval($_REQUEST['id']));
        die;
    }

}
