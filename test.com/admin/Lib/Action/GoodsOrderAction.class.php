<?php
// +----------------------------------------------------------------------
// | Fanwe 方维众筹商业系统
// +----------------------------------------------------------------------
// | Copyright (c) 2011 http://www.fanwe.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: 甘味人生(526130@qq.com)
// +----------------------------------------------------------------------

class GoodsOrderAction extends CommonAction
{
    public function index()
    {
        //列表过滤器，生成查询Map对象
        $map = $this->_search();

        if (!isset($_REQUEST['order_status'])) {
            $_REQUEST['order_status'] = -1;
        }

        $order_sn     = $param['order_sn']     = strim($_REQUEST['order_sn']);
        $order_status = $param['order_status'] = intval($_REQUEST['order_status']);
        $begin_time   = $param['begin_time']   = strim($_REQUEST['begin_time']);
        $end_time     = $param['end_time']     = strim($_REQUEST['end_time']);

        $this->assign('param', $param);

        //追加默认参数
        if ($this->get("default_map")) {
            $map = array_merge($map, $this->get("default_map"));
        }

        if ($order_sn != '') {
            $map['order_sn'] = $order_sn;
        }
        if (isset($_REQUEST['order_status']) && $order_status >= 0) {
            $map['order_status'] = $order_status;
        }

        $begin_time_span = to_timespan($begin_time);
        $end_time_span   = to_timespan($end_time);
        if ($begin_time != '' && $end_time == '') {
            $map['_string'] = "create_time >=" . $begin_time_span . " and order_type != 'shop'";
        } elseif ($begin_time != '' && $end_time != '') {
            $map['_string'] = "create_time >=" . $begin_time_span . " and create_time <=" . $end_time_span . " and order_type != 'shop'";
        } elseif ($begin_time == '' && $end_time != '') {
            $map['_string'] = "create_time <=" . $end_time_span . " and order_type != 'shop'";
        }else{
            $map['_string'].="order_type != 'shop'";
        }

        if ($order_status == -1) {
            unset($map['order_status']);
        }

        if (method_exists($this, '_filter')) {
            $this->_filter($map);
        }

//        $map['_string'].="order_type != 'shop'";

        $model = D("goods_order");
        if (!empty($model)) {
            $this->_list($model, $map);
        }

        $list = $this->get("list");

        $result = array();
        $row    = 0;
        foreach ($list as $k => $v) {

            $list[$k]['order_source']     = $v['order_source'] == 'local' ? "本地" : "远程";
            if($v['order_type'] == 'shop'){
                $list[$k]['order_type'] = '购物单';
            }elseif($v['order_type'] == 'pai_goods'){
                $list[$k]['order_type'] = '实物竞拍';
            }else{
                $list[$k]['order_type'] = '虚拟竞拍';
            }
            //$list[$k]['order_type']       = $v['order_type'] == 'shop' ? "购物单" : "竞拍单";
            $list[$k]['no_refund_format'] = $v['no_refund'] ? "否" : "是";

            switch ($v['order_status']) {
                case '1':
                    $list[$k]['order_status_format'] = "待付款";
                    break;
                case '2':
                    $list[$k]['order_status_format'] = "待发货";
                    break;
                case '3':
                    $list[$k]['order_status_format'] = "待收货";
                    break;
                case '4':
                    $list[$k]['order_status_format'] = "已收货";
                    break;
                case '5':
                    $list[$k]['order_status_format'] = "已退货";
                    break;
                case '6':
                    $list[$k]['order_status_format'] = "超时关闭";
                    break;
                case '7':
                    $list[$k]['order_status_format'] = "结单";
                    break;
            }

            $list[$k]['podcast_name'] = M("user")->where("id=" . $v['podcast_id'] . " ")->getField("nick_name");
            $list[$k]['pai_name']     = M("user")->where("id=" . $v['viewer_id'] . " ")->getField("nick_name");

        }

        $this->assign("list", $list);
        $this->display();
        return;
    }
    /**
     *
     * 订单详情
     */
    public function edit()
    {
        $this->assign("type_list", $this->type_list);
        $id = intval($_REQUEST['id']);
        if (!isset($id) || empty($id)) {
            $this->error('系统繁忙，参数不存在！');
        }
        $vo = M('goods_order')->where(array('id' => $id))->find();

        if ($vo['order_source'] == 'local') {
            $vo['order_source'] = '本地';
        } else {
            $vo['order_source'] = '远程';
        }
        if ($vo['order_type'] == 'shop') {
            $vo['order_type'] = '购物单';
        } else {
            $vo['order_type'] = '竞拍单';
        }

        $vo['no_refund_format'] = $vo['no_refund'] ? '否' : '是';

        if ($vo['order_status'] == 1) {
            $vo['order_status_format'] = "待付款";
        } elseif ($vo['order_status'] == 2) {
            $vo['order_status_format'] = "待发货";
        } elseif ($vo['order_status'] == 3) {
            $vo['order_status_format'] = "待收货";
        } elseif ($vo['order_status'] == 4) {
            $vo['order_status_format'] = "已收货";
        } elseif ($vo['order_status'] == 5) {
            $vo['order_status_format'] = "已退货";
        }

        if ($vo['refund_buyer_delivery'] == 0) {
            $vo['delivery_status_format'] = "无";
        } elseif ($vo['refund_buyer_delivery'] == 1) {
            $vo['delivery_status_format'] = "未发货";
        } else {
            $vo['delivery_status_format'] = "已发货";
        }

        if ($vo['refund_buyer_status'] == 0) {
            $vo['refund_buyer_status'] = "无";
        } elseif ($vo['refund_buyer_status'] == 1) {
            $vo['refund_buyer_status'] = "退款中";
        } elseif ($vo['refund_buyer_status'] == 2) {
            $vo['refund_buyer_status'] = "退货中";
        } elseif ($vo['refund_buyer_status'] == 3) {
            $vo['refund_buyer_status'] = "退款成功";
        } elseif ($vo['refund_buyer_status'] == 4) {
            $vo['refund_buyer_status'] = "主动撤销退款";
        } else {
            $vo['refund_buyer_status'] = "被动关闭";
        }

        if ($vo['refund_seller_status'] == 0) {
            $vo['refund_seller_status'] = "无";
        } else {
            $vo['refund_seller_status'] = "退款成功";
        }

        if ($vo['refund_platform'] == 0) {
            $vo['refund_platform'] = "无";
        } elseif ($vo['refund_platform'] == 1) {
            $vo['refund_platform'] = "申诉中";
        } else {
            $vo['refund_platform'] = "申诉完成";
        }

        $vo['podcast_name'] = M("user")->where("id=" . $vo['podcast_id'] . " ")->getField("nick_name");
        $vo['pai_name']     = M("user")->where("id=" . $vo['viewer_id'] . " ")->getField("nick_name");
        $vo['goods_name']   = M("pai_goods")->where("id=" . $vo['pai_id'] . " ")->getField("name");
        if($vo['order_type'] == '购物单'){
            $vo['goods_name']   = M("goods")->where("id=" . $vo['goods_id'] . " ")->getField("name");
        }

        if (!empty($vo['consignee_district'])) {
            $arr = json_decode(htmlspecialchars_decode($vo['consignee_district']), true);
            //按数组方式调用里面的数据
            $vo['consignee_district'] = $arr['province'].$arr['city'].$arr['area'];
        }
        $vo['pay_time'] = $vo['pay_time'] == '0000-00-00 00:00:00' ? '未付款' : $vo['pay_time'];
        switch ($vo['order_status']) {
            case '1':
                $vo['order_status_format'] = '待付款';
                break;
            case '2':
                $vo['order_status_format'] = '待发货';
                break;
            case '3':
                $vo['order_status_format'] = '待收货';
                break;
            case '4':
                $vo['order_status_format'] = '已收货';
                break;
            case '5':
                $vo['order_status_format'] = '退款成功';
                break;
            case '6':
                $vo['order_status_format'] = '未付款';
                break;
            case '7':
                $vo['order_status_format'] = '结单';
                break;
        }

        $this->assign('vo', $vo);
        $this->display();
    }

}
