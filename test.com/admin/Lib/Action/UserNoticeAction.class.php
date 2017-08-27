<?php
// +----------------------------------------------------------------------
// | Fanwe 方维众筹商业系统
// +----------------------------------------------------------------------
// | Copyright (c) 2011 http://www.fanwe.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: 甘味人生(526130@qq.com)
// +----------------------------------------------------------------------

class UserNoticeAction extends CommonAction
{
    public function index()
    {
        $send_type = array(
            'pai_wait_pay'              => '竞拍未付款',
            'tip_to_pay'                => '提醒买家付款',
            'pay_wait_delivery'         => '买家已付款，等待发货',
            'tip_to_delivery'           => '提醒发货',
            'tip_podcast_to_tryst'      => '提醒主播约会',
            'tip_podcast_to_goods'      =>'提醒卖家发货',
            'tip_podcast_to_over_tryst' => '提醒主播确认完成约会',
            'tip_viewer_to_tryst'       => '提醒买家约会',
            'tip_viewer_to_over_tryst'  => '提醒买家确认完成约会',
            'frozen_margin'             => '冻结保证金',
            'return_margin'             => '退还保证金',
            'pai_close'                 => '竞拍关闭',
            'pai_out'                   => '竞拍出局',
            'pay_margin_success'        => '成功缴纳保证金',
            'viewer_to_over_tryst'      => '买家确认收货（确定完成约会）',
            'podcast_to_over_tryst'     => '主播确认完成约会',
            'sure_delivery'             => '买家确认收货',
            'pai_to_delivery'           => '竞拍已发货',
            'auto_over_pai'             => '自动确认完成竞拍',
            'no_pay'                    => '付款超时',
            'tip_towait'                => '提醒进入等待队列',
            'to_refund'                 => '进入退款',
        );
        $param = array(
            'user_id'    => trim($_GET['user_id']),
            'send_type'  => trim($_GET['send_type']),
            'begin_time' => trim($_GET['begin_time']),
            'end_time'   => trim($_GET['end_time']),
        );
        $map = array();
        if ($param['user_id']) {
            $map['user_id'] = $param['user_id'];
        }
        if ($param['send_type']) {
            $map['type'] = $param['send_type'];
        }
        if ($param['begin_time'] && $param['end_time']) {
            $map['_string'] = "create_time >=" . to_timespan($param['begin_time']) . " and create_time <=" . to_timespan($param['end_time']);
        } else if ($param['begin_time']) {
            $map['_string'] = "create_time >=" . to_timespan($param['begin_time']);
        } else if ($param['end_time']) {
            $map['_string'] = "create_time <=" . to_timespan($param['end_time']);
        }
        $model = D("user_notice");
        if (!empty($model)) {
            $this->_list($model, $map);
        }

        $list = $this->get("list");

        $result = array();
        $row    = 0;
        foreach ($list as $k => &$v) {
            // if ($v['type'] == 0) {
            //     $v['type'] = '仅消息';
            // } elseif ($v['type'] == 1) {
            //     $v['type'] = '推送';
            // } else {
            //     $v['type'] = '消息+推送';
            // }
            $v['type'] = $send_type[$v['type']];

            if ($v['is_read'] == 0) {
                $v['is_read'] = '未读';
            } else {
                $v['is_read'] = '已读';
            }
            $list[$k]['take_name'] = M("user")->where("id=" . $v['user_id'] . " ")->getField("nick_name"); //接收人
            $list[$k]['send_name'] = M("user")->where("id=" . $v['send_id'] . " ")->getField("nick_name"); //发送人
        }

        $this->assign("send_type", $send_type);
        $this->assign("param", $param);
        $this->assign("list", $list);
        $this->display();
        return;
    }
    public function foreverdelete()
    {
        //彻底删除指定记录
        $ajax = intval($_REQUEST['ajax']);
        $id   = $_REQUEST['id'];
        if (isset($id)) {
            $condition = array('id' => array('in', explode(',', $id)));
            $list      = M('user_notice')->where($condition)->delete();
            if ($list !== false) {
                clear_auto_cache("banner_list");
                load_auto_cache("banner_list");
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
    public function view_info()
    {
        $ajax = intval($_REQUEST['ajax']);
        $id   = $_REQUEST['id'];
        if (isset($id)) {
            $list = M('user_notice')->where(array('id' => $id))->find();
            if ($list['type'] == 0) {
                $list['type'] = '仅消息';
            } elseif ($list['type'] == 1) {
                $list['type'] = '推送';
            } else {
                $list['type'] = '消息+推送';
            }

            if ($list['is_read'] == 0) {
                $list['is_read'] = '未读';
            } else {
                $list['is_read'] = '已读';
            }
            $list['take_name'] = M("user")->where("id=" . $list['user_id'] . " ")->getField("nick_name"); //接收人
            $list['send_name'] = M("user")->where("id=" . $list['send_id'] . " ")->getField("nick_name"); //发送人
            $this->assign("list", $list);
        }
        $this->display();
    }

    public function edit()
    {

        //彻底删除指定记录
        $ajax = intval($_REQUEST['ajax']);
        $id   = $_REQUEST['id'];
        if (isset($id)) {
            $list = M('user_notice')->where(array('id' => $id))->save(array('is_read' => 1));
            if ($list !== false) {
                clear_auto_cache("banner_list");
                load_auto_cache("banner_list");
                save_log($info . l("UPDATE_SUCCESS"), 1);
                $this->success(l("UPDATE_SUCCESS"), $ajax);
            } else {
                save_log($info . l("UPDATE_FAILED"), 0);
                $this->error(l("UPDATE_FAILED"), $ajax);
            }
        } else {
            $this->error(l("INVALID_OPERATION"), $ajax);
        }
    }

}
