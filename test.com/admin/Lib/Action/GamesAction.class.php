<?php
// +----------------------------------------------------------------------
// | Fanwe 方维p2p借贷系统
// +----------------------------------------------------------------------
// | Copyright (c) 2011 http://www.fanwe.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: 甘味人生(526130@qq.com)
// +----------------------------------------------------------------------

class GamesAction extends CommonAction
{
    public function index()
    {

        if (strim($_REQUEST['name']) != '') {
            $map['name'] = array('like', '%' . strim($_REQUEST['name']) . '%');
        }

        $name  = $this->getActionName();
        $model = D($name);
        if (!empty($model)) {
            $this->_list($model, $map);
        }
        $list = $this->get("list");
        foreach ($list as $key => $value) {
            $list[$key]['image'] = get_spec_image($value['image']);
        }
        $this->assign('list', $list);
        $this->display();
    }

    public function edit()
    {
        $id              = intval($_REQUEST['id']);
        $condition['id'] = $id;
        $vo              = M(MODULE_NAME)->where($condition)->find();
        $vo['image']     = get_spec_image($vo['image']);
        $this->assign('vo', $vo);
        $this->assign('op', json_decode($vo['option'], 1));
        $this->assign('banker', OPEN_BANKER_MODULE);
        $this->assign('diamond_game', OPEN_DIAMOND_GAME_MODULE);
        $this->display();
    }

    public function update()
    {
        B('FilterString');
        $data   = M(MODULE_NAME)->create();
        $option = [];
        foreach ($data['option'] as $key => $value) {
            $option[$key + 1] = floatval($value);
        }
        $data['option'] = json_encode($option);
        //clear_auto_cache("prop_list");
        $log_info = M(MODULE_NAME)->where("id=" . intval($data['id']))->getField("name");
        //开始验证有效性
        $this->assign("jumpUrl", u(MODULE_NAME . "/edit", array("id" => $data['id'])));
        if (!check_empty($data['name'])) {
            $this->error("请输入名称");
        }
        if (!check_empty($data['image'])) {
            $this->error("请输入图标");
        }
//        if(intval($data['principal']) == 0)
        //        {
        //            $this->error("请输入游戏底金");
        //        }
        if (intval($data['commission_rate']) < 0) {
            $this->error("请输入佣金比率，0-100");
        }
        if (intval($data['long_time']) == 0) {
            $this->error("请输入游戏时长");
        }
        if (intval($data['rate']) == '') {
            $data['rate'] = 0;
        }
        if (!check_empty($data['description'])) {
            $this->error("请输入游戏描述");
        }

        if (intval($data['long_time']) < 20 || intval($data['long_time']) > 99) {
            $this->error("请输入游戏时长，20-99");
        }
        // 更新数据
        $list = M(MODULE_NAME)->save($data);
        if (false !== $list) {
            //成功提示
            save_log($log_info . L("UPDATE_SUCCESS"), 1);
            clear_auto_cache("prop_id", array('id' => $data['id']));
            $this->success(L("UPDATE_SUCCESS"));
        } else {
            //错误提示
            save_log($log_info . L("UPDATE_FAILED"), 0);
            $this->error(L("UPDATE_FAILED"), 0, $log_info . L("UPDATE_FAILED"));
        }
    }
    public function betLog()
    {
        $user_id     = intval($_REQUEST['user_id']);
        $game_log_id = intval($_REQUEST['game_log_id']);
        if ($user_id) {
            $map['user_id'] = $user_id;
        }
        if ($game_log_id) {
            $map['game_log_id'] = $game_log_id;
        }
        $model = D('coin_log');
        if (!empty($model)) {
            $this->_list($model, $map);
        }
        $this->assign("is_diamond", defined('OPEN_DIAMOND_GAME_MODULE') && OPEN_DIAMOND_GAME_MODULE == 1);
        $this->display();
    }
    public function addCoin()
    {
        if ($_POST) {
            $user_id = intval($_REQUEST['user_id']);
            $coin    = intval($_REQUEST['coin']);
            if (!($user_id && $coin)) {
                $this->ajax_return(array(
                    'status' => 0,
                    'error'  => '参数错误',
                ));
            }
            $where            = array('id' => $user_id);
            $user_model       = M('user');
            $res              = $user_model->setInc('coin', $where, $coin);
            $account_diamonds = $user_model->getField('coin', $where);
            if ($res) {
                fanwe_require(APP_ROOT_PATH . 'mapi/lib/redis/BaseRedisService.php');
                fanwe_require(APP_ROOT_PATH . 'mapi/lib/redis/UserRedisService.php');
                $user_redis = new UserRedisService();
                $user_redis->inc_field($user_id, 'coin', $coin);
                $data = array(
                    'user_id'          => $user_id,
                    'game_log_id'      => -1,
                    'diamonds'         => $coin,
                    'account_diamonds' => $account_diamonds,
                    'memo'             => '后台修改',
                    'create_time'      => NOW_TIME,
                );
                M('coin_log')->add($data);
                $this->ajax_return(array(
                    'status' => 1,
                    'error'  => '更新成功',
                ));
            }
            $this->ajax_return(array(
                'status' => 0,
                'error'  => '更新失败',
            ));
        } else {
            $user_id    = intval($_REQUEST['user_id']);
            $user_model = M('user');
            $user_info  = $user_model->field(array('id', 'nick_name', 'coin'))->find($user_id);
            $this->assign("user_info", $user_info);
            $this->display();
        }
    }
    protected function ajax_return($data)
    {
        header("Content-Type:text/html; charset=utf-8");
        echo (json_encode($data));
        exit;
    }
    public function bankerLog()
    {
        $sort  = $_REQUEST['_sort'] ? 'asc' : 'desc';
        $order = str_replace('`', '', trim($_REQUEST['_order']));
        $p     = intval($_REQUEST['p']);

        $user_id    = intval($_REQUEST['user_id']);
        $video_id   = intval($_REQUEST['video_id']);
        $is_history = intval($_REQUEST['is_history']);

        $where = "where status in ('3','4')";
        if ($user_id) {
            $where .= " and `user_id`= $user_id";
        }
        if ($video_id) {
            $where .= " and `video_id`= $video_id";
        }
        $table1  = DB_PREFIX . 'banker_log';
        $table2  = DB_PREFIX . 'banker_log_history';
        $select1 = "SELECT * FROM $table1 $where";
        $select2 = "SELECT * FROM $table2 $where";

        $str_order = '';
        if ($order) {
            $str_order = "order by `$order` $sort";
        } else {
            $str_order = "order by `id` $sort";
        }
        $count   = "SELECT count(1) as count FROM ($select1 UNION $select2) AS a";
        $count   = M()->query($count);
        $count   = intval($count[0]['count']);
        $p       = new Page($count, '');
        $limit   = ' limit ' . $p->firstRow . ',' . $p->listRows;
        $page    = $p->show();
        $select  = "SELECT * FROM ($select1 UNION $select2) AS a $str_order $limit";
        $voList  = M()->query($select);
        $sortAlt = $sort == 'desc' ? l("ASC_SORT") : l("DESC_SORT");
        $this->assign('list', $voList);
        $this->assign('sort', $sort);
        $this->assign('order', $order);
        $this->assign('sortImg', $sort);
        $this->assign('sortType', $sortAlt);
        $this->assign("page", $page);
        $this->assign("nowPage", $p->nowPage);
        $this->display();
    }
}
