<?php
// +----------------------------------------------------------------------
// | Fanwe 方维p2p借贷系统
// +----------------------------------------------------------------------
// | Copyright (c) 2011 http://www.fanwe.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: 甘味人生(526130@qq.com)
// +----------------------------------------------------------------------

class EduRedAction extends CommonAction
{
    public function index()
    {
        if (trim($_REQUEST['title']) != '') {
            $map['title'] = array('like', '%' . trim($_REQUEST['title']) . '%');
        }

        $sn = trim($_REQUEST['sn']);
        if ($sn != '') {
            $map['sn'] = array('eq', $sn);
        }

        $nick_name = trim($_REQUEST['nick_name']);
        if ($nick_name != '') {

            $user_ids_array = M("User")->field("id,nick_name")->where("nick_name like '%" . $nick_name . "%'")->findAll();
            $user_ids = implode(',', array_map('array_shift', $user_ids_array));
            $map['user_id'] = array('in', $user_ids);
        }

        $user_id = intval($_REQUEST['user_id']);
        if ($user_id) {
            $map['user_id'] = array('eq', $user_id);
        }

        if (method_exists($this, '_filter')) {
            $this->_filter($map);
        }
        $name = $this->getActionName();
        $model = M($name);
        if (!empty ($model)) {
            $this->_list($model, $map);
        }

        $this->display();
    }

    public function add()
    {
        $this->display();
    }

    public function edit()
    {
        $id = intval($_REQUEST ['id']);
        $condition['id'] = $id;
        $vo = M(MODULE_NAME)->where($condition)->find();
        $this->assign('vo', $vo);

        $this->display();
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
                $info[] = $data['title'];
            }
            if ($info) {
                $info = implode(",", $info);
            }
            $list = M(MODULE_NAME)->where($condition)->setField('is_delete', 1);
            if ($list !== false) {
                save_log($info . l("DELETE_SUCCESS"), 1);
                clear_auto_cache("get_help_cache");
                clear_auto_cache("article_notice");
                $result['status'] = 1;
                $result['info'] = '删除成功';
            } else {
                save_log($info . l("DELETE_FAILED"), 0);
                $result['status'] = 0;
                $result['info'] = '删除失败';
            }
        } else {
            $result['status'] = 0;
            $result['info'] = '请选择要删除的选项';
        }
        admin_ajax_return($result);
    }

    public function restore()
    {
        //删除指定记录
        $ajax = intval($_REQUEST['ajax']);
        $id = $_REQUEST ['id'];
        if (isset ($id)) {
            $condition = array('id' => array('in', explode(',', $id)));
            $rel_data = M(MODULE_NAME)->where($condition)->findAll();
            foreach ($rel_data as $data) {
                $info[] = $data['title'];
            }
            if ($info) {
                $info = implode(",", $info);
            }
            $list = M(MODULE_NAME)->where($condition)->setField('is_delete', 0);
            if ($list !== false) {
                save_log($info . l("RESTORE_SUCCESS"), 1);
                clear_auto_cache("get_help_cache");
                clear_auto_cache("article_notice");
                $this->success(l("RESTORE_SUCCESS"), $ajax);
            } else {
                save_log($info . l("RESTORE_FAILED"), 0);
                $this->error(l("RESTORE_FAILED"), $ajax);
            }
        } else {
            $this->error(l("INVALID_OPERATION"), $ajax);
        }
    }

    public function foreverdelete()
    {
        //彻底删除指定记录
        $ajax = intval($_REQUEST['ajax']);
        $id = $_REQUEST ['id'];
        if (isset ($id)) {
            $condition = array('id' => array('in', explode(',', $id)));
            $rel_data = M(MODULE_NAME)->where($condition)->findAll();
            foreach ($rel_data as $data) {
                $info[] = $data['title'];
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

    public function insert()
    {
        B('FilterString');
        $ajax = intval($_REQUEST['ajax']);
        $data = M(MODULE_NAME)->create();
        //开始验证有效性
        $this->assign("jumpUrl", u(MODULE_NAME . "/add"));
        if (!check_empty($data['title'])) {
            $this->error("红包名称不能为空");
        }
        if ($data['diamonds'] <= 0) {
            $this->error("钻石数要大于零 ");
        }
        $red_num = intval($_REQUEST['red_num']);

        if ($red_num <= 0) {
            $this->error("红包数量要大于零 ");
        }

        $end_time_num=to_timespan($data['end_time']);
        if( $end_time_num >0 && $end_time_num < NOW_TIME)
        {
           $this->error("过期时间不能小于当前时间");
        }

        // 更新数据
        $log_info = $data['title'] . "红包";
        $data['end_time'] = to_date(to_timespan($data['end_time']));
        $data['create_time'] = get_gmtime();
        $ok_num=0;
        for ($i = 0; $i < $red_num; $i++) {
            $data['sn'] = $this->get_red_sn();
            $list = M(MODULE_NAME)->add($data);
            if(false !== $list){
                ++$ok_num;
            }
        }

        if ($ok_num>0) {
            //成功提示
            save_log($log_info ."(".$ok_num."个)". L("INSERT_SUCCESS"), 1);
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

        $red_info = M(MODULE_NAME)->where("id=" . intval($data['id']))->find();
        $log_info = $red_info['title'] . "(ID：" . $red_info['id'] . ")";
        if (!$red_info) {
            $this->error("请选择编辑的红包");
        }
        if (to_timespan($red_info['exchange_time']) > 0) {
            $this->error("该红包已使用，不能编辑");
        }
        $end_time_num=to_timespan($data['end_time']);
        if( $end_time_num >0 && $end_time_num < NOW_TIME)
        {
            $this->error("过期时间不能小于当前时间");
        }
        //开始验证有效性
        $this->assign("jumpUrl", u(MODULE_NAME . "/edit", array("id" => $data['id'])));
        if (!check_empty($data['title'])) {
            $this->error("红包名称不能为空");
        }
        if ($data['diamonds'] <= 0) {
            $this->error("钻石数要大于零 ");
        }

        // 更新数据
        $data['end_time'] = to_date(to_timespan($data['end_time']));
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

    //得到随机数
    public function get_red_sn()
    {
        $chars = "ABCDEFGHJKMNPQRSTWXYZ2345678";//去除了I L O U V 0 1 9
        $chars = str_shuffle($chars);
        $rand_str = substr($chars, 0, 6);
        return $rand_str;
    }
}

?>