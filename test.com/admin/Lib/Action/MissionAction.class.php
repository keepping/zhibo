<?php
// +----------------------------------------------------------------------
// | Fanwe 方维p2p借贷系统
// +----------------------------------------------------------------------
// | Copyright (c) 2011 http://www.fanwe.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: 甘味人生(526130@qq.com)
// +----------------------------------------------------------------------

class MissionAction extends CommonAction
{
    protected static $title = [
        0 => '在线任务',
        1 => '玩游戏任务',
        2 => '打赏主播任务',
        3 => '分享主播任务',
        4 => '关注主播任务',
    ];
    public function index()
    {
        // $this->__set('default_map',[]);
        $_REQUEST['_order'] = $_REQUEST['_order'] ? $_REQUEST['_order'] : 'type`,`sort';
        $_REQUEST['_sort']  = $_REQUEST['_sort'] ? $_REQUEST['_sort'] : 'desc';
        $this->assign("default_map", 'is_effect = 1');
        parent::index();
    }

    public function add()
    {
        $this->assign("title", self::$title);
        $this->assign("new_sort", M(MODULE_NAME)->max("sort") + 1);
        $this->display();
    }
    public function edit()
    {
        $this->assign("title", self::$title);
        $id = intval($_REQUEST['id']);

        $condition['id'] = $id;

        $vo = M(MODULE_NAME)->where($condition)->find();
        $this->assign('vo', $vo);
        $this->display();
    }
    //彻底删除指定记录
    public function foreverdelete()
    {
        //彻底删除指定记录
        $ajax = intval($_REQUEST['ajax']);
        $id   = $_REQUEST['id'];
        if (isset($id)) {
            $types     = [];
            $condition = array('id' => array('in', explode(',', $id)));
            $rel_data  = M(MODULE_NAME)->where($condition)->findAll();
            foreach ($rel_data as $data) {
                $info[] = $data['name'];
                if (!in_array($data['type'], $types)) {
                    $types[] = $data['type'];
                }
            }
            if ($info) {
                $info = implode(",", $info);
            }

            $list = M(MODULE_NAME)->where($condition)->delete();
            if ($list !== false) {
                foreach ($types as $type) {
                    self::orderSort($type);
                }
                save_log($info . l("FOREVER_DELETE_SUCCESS"), 1);
                clear_auto_cache("get_help_cache");
                $this->success(l("FOREVER_DELETE_SUCCESS"), $ajax);
            } else {
                save_log($info . l("FOREVER_DELETE_FAILED"), 0);
                $this->error(l("FOREVER_DELETE_FAILED"), $ajax);
            }
        } else {
            $this->error(l("INVALID_OPERATION"), $ajax);
        }
    }

    public function insert()
    {
        B('FilterString');
        $ajax = intval($_REQUEST['ajax']);
        $data = M(MODULE_NAME)->create();
        //开始验证有效性
        $this->assign("jumpUrl", u(MODULE_NAME . "/add"));
        if (!check_empty($data['name'])) {
            $this->error("请输入名称");
        }
        // 更新数据
        $log_info = $data['name'];
        $list     = M(MODULE_NAME)->add($data);
        if (false !== $list) {
            self::orderSort($data['type']);
            //成功提示
            save_log($log_info . L("INSERT_SUCCESS"), 1);
            clear_auto_cache("get_help_cache");
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

        $data['is_order'] = 0;

        $type     = M(MODULE_NAME)->where("id=" . intval($data['id']))->getField("type");
        $log_info = M(MODULE_NAME)->where("id=" . intval($data['id']))->getField("name");
        //开始验证有效性
        $this->assign("jumpUrl", u(MODULE_NAME . "/edit", array("id" => $data['id'])));
        if (!check_empty($data['name'])) {
            $this->error("请输入名称");
        }
        // 更新数据
        $list = M(MODULE_NAME)->save($data);
        if (false !== $list) {
            self::orderSort($type);
            self::orderSort($data['type']);
            //成功提示
            save_log($log_info . L("UPDATE_SUCCESS"), 1);
            clear_auto_cache("get_help_cache");

            $users = M("User")->where("authent_list_id = " . intval($data['id']))->findAll();
            if (sizeof($users) > 0) {
                $user_ids                       = array_column($users, "id");
                $condition['id']                = array('in', $user_ids);
                $condition['is_authentication'] = 2;
                M("User")->where($condition)->setField("v_icon", get_spec_image($data['icon']));
                user_deal_to_reids($user_ids);
            }

            $this->success(L("UPDATE_SUCCESS"));
        } else {
            //错误提示
            save_log($log_info . L("UPDATE_FAILED"), 0);
            $this->error(L("UPDATE_FAILED"), 0, $log_info . L("UPDATE_FAILED"));
        }
    }

    public function set_sort()
    {
        $id       = intval($_REQUEST['id']);
        $sort     = intval($_REQUEST['sort']);
        $log_info = M(MODULE_NAME)->where("id=" . $id)->getField("name");
        $type     = M(MODULE_NAME)->where("id=" . $id)->getField("type");
        if (!check_sort($sort)) {
            $this->error(l("SORT_FAILED"), 1);
        }
        M(MODULE_NAME)->save(["sort" => $sort, 'id' => $id, 'is_sort' => 0]);
        self::orderSort($type);
        save_log($log_info . l("SORT_SUCCESS"), 1);
        clear_auto_cache("get_help_cache");
        $this->success(l("SORT_SUCCESS"), 1);
    }
    public function set_effect()
    {
        $id   = intval($_REQUEST['id']);
        $ajax = intval($_REQUEST['ajax']);

        $info        = M(MODULE_NAME)->where("id=" . $id)->getField("adm_name");
        $c_is_effect = M(MODULE_NAME)->where("id=" . $id)->getField("is_effect"); //当前状态
        $type        = M(MODULE_NAME)->where("id=" . $id)->getField("type"); //当前状态
        if (conf("DEFAULT_ADMIN") == $info) {
            $this->ajaxReturn($c_is_effect, l("DEFAULT_ADMIN_CANNOT_EFFECT"), 1);
        }
        $n_is_effect = $c_is_effect == 0 ? 1 : 0; //需设置的状态
        M(MODULE_NAME)->save(["is_effect" => $n_is_effect, 'id' => $id]);
        self::orderSort($type);
        save_log($info . l("SET_EFFECT_" . $n_is_effect), 1);
        $this->ajaxReturn($n_is_effect, l("SET_EFFECT_" . $n_is_effect), 1);
    }
    protected static function orderSort($type = 0)
    {
        $mod  = M(MODULE_NAME);
        $list = $mod->where(array('is_effect' => 1, 'type' => $type))->order('sort,is_order')->select();
        if ($list) {
            $order_list = array();
            foreach ($list as $value) {
                if ($value['sort'] > 0) {
                    $order_list[] = $value;
                }
            }
            foreach ($list as $value) {
                if ($value['sort'] <= 0) {
                    $order_list[] = $value;
                }
            }
            foreach ($order_list as $key => $value) {
                if (!($key + 1 == $value['sort'] && $value['is_order'])) {
                    $mod->where(array(
                        'id' => $value['id'],
                    ))->save(array(
                        'sort'     => $key + 1,
                        'is_order' => 1,
                    ));
                }
            }
        }
    }
}
