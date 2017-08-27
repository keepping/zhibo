<?php
// +----------------------------------------------------------------------
// | Fanwe 方维众筹商业系统
// +----------------------------------------------------------------------
// | Copyright (c) 2011 http://www.fanwe.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: 甘味人生(526130@qq.com)
// +----------------------------------------------------------------------

class PodcastOrderAction extends CommonAction
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
            $map['_string'] = "create_time >=" . $begin_time_span . " and is_p=0";
        } elseif ($begin_time != '' && $end_time != '') {
            $map['_string'] = "create_time >=" . $begin_time_span . " and create_time <=" . $end_time_span . " and is_p=0";
        } elseif ($begin_time == '' && $end_time != '') {
            $map['_string'] = "create_time <=" . $end_time_span . " and is_p=0";
        }else{
            $map['_string'] = "is_p=0";
        }

        if ($order_status == -1) {
            unset($map['order_status']);
        }

        if (method_exists($this, '_filter')) {
            $this->_filter($map);
        }

        $model = D("goods_order");
        $map['order_type'] = array('neq','pai');
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
                $list[$k]['goods_diamonds'] = 0;
            }elseif($v['order_type'] == 'pai_goods'){
                $list[$k]['order_type'] = '实物竞拍单';
            }else{
                $list[$k]['order_type'] = '虚拟竞拍单';
            }
            $list[$k]['no_refund_format'] = $v['no_refund'] ? "否" : "是";

            switch ($v['order_status']) {
                case '1':
                    $list[$k]['order_status_format'] = "买家待付款";
                    break;
                case '2':
                    $list[$k]['order_status_format'] = "卖家待发货";
                    break;
                case '3':
                    $list[$k]['order_status_format'] = "买家待收货";
                    break;
                case '4':
                    $list[$k]['order_status_format'] = "已结单";
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
        }elseif($vo['order_type'] == 'pai_goods'){
            $vo['order_type'] = '实物竞拍单';
        }else {
            $vo['order_type'] = '虚拟竞拍单';
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
                $vo['order_status_format'] = '买家待付款';
                break;
            case '2':
                $vo['order_status_format'] = '卖家待发货';
                break;
            case '3':
                $vo['order_status_format'] = '买家待收货';
                break;
            case '4':
                $vo['order_status_format'] = '已结单';
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


    public function update()
    {

        $id     = intval($_REQUEST['id']);
        $order_status = intval($_REQUEST['order_status']);
        $courier_offic = strim($_REQUEST['courier_offic']);
        $courier_number = strim($_REQUEST['courier_number']);
        $this->assign("jumpUrl", u(MODULE_NAME . "/edit", array("id" => $id)));
        if (!$id) {
            $this->error("参数错误");
        }
        $mod             = M('goods_order');
        $order           = $mod->field('order_sn,order_status,viewer_id,pai_id')->find($id);
        $log_info        = $order['order_sn'];
        $delivery_time = date("Y-m-d H:i:s");
        if($order['order_status'] == 3){
            $this->error(L("店家已经发货"));
            return;
        }elseif($order['order_status'] == 4){
            $this->error(L("订单已完成"));
            return;
        }elseif($order['order_status'] == 1){
            $this->error(L("等待买家付款"));
            return;
        }elseif($order['order_status'] > 4){
            $this->error(L("订单已关闭"));
            return;
        }
        if($courier_number == 0){
            $this->error(L("请输入物流单号"));
            return;
        }

        $res = $mod->save(array('id' => $id, 'order_status' => $order_status,'delivery_time'=>$delivery_time,'courier_offic'=>$courier_offic,'courier_number'=>$courier_number));
        $courier = M('courier')->add(array('id'=>'','order_sn'=>$log_info,'courier_number' => $courier_number,"courier_offic"=>$courier_offic));
        if(intval($order['pai_id']) !=0){
            $pai_goods = M('pai_goods')->save(array('id'=>$order['pai_id'],'order_status' => $order_status));
            $pai_join = M('pai_join')->where('order_id='.$id)->save(array('order_status' => $order_status));
        }


        if (false === $res) {
            //错误提示
            save_log($log_info . L("UPDATE_FAILED"), 0);
            $this->error(L("UPDATE_FAILED"), 0, $log_info . L("UPDATE_FAILED"));
        } else {
            //成功提示
            clear_auto_cache("banner_list");
            load_auto_cache("banner_list");
            save_log($log_info . L("UPDATE_SUCCESS"), 1);
            $this->success(L("UPDATE_SUCCESS"));
        }
    }

    //导出订单
    public function export_dd($page=1){

        $pagesize = 10;
        set_time_limit(0);
        $limit = (($page - 1)*intval($pagesize)).",".(intval($pagesize));


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
            $map['_string'] = "create_time >=" . $begin_time_span . " and is_p=0";
        } elseif ($begin_time != '' && $end_time != '') {
            $map['_string'] = "create_time >=" . $begin_time_span . " and create_time <=" . $end_time_span . " and is_p=0";
        } elseif ($begin_time == '' && $end_time != '') {
            $map['_string'] = "create_time <=" . $end_time_span . " and is_p=0";
        }else{
            $map['_string'] = "is_p=0";
        }

        if ($order_status == -1) {
            unset($map['order_status']);
        }

        if (method_exists($this, '_filter')) {
            $this->_filter($map);
        }

//        $model = D("goods_order");
        $map['order_type'] = array('neq','pai');
        $list = M("goods_order")->where($map)->order('id desc')->select();
//        if (!empty($model)) {
//            $this->_list($model, $map);
//        }
//
//        $list = $this->get("list");

        if($list)
        {
//            register_shutdown_function(array(&$this, 'export_dd'), $page+1);
            $refund_value = array(
                'id' => '""',
                'order_source' => '""',
                'order_type' => '""',
                'order_sn' => '""',
                'order_status' => '""',
                'no_refund' => '""',
                'refund_buyer_status' => '""',
                'refund_buyer_delivery' => '""',
                'refund_seller_status' => '""',
                'refund_platform' => '""',
                'refund_over_time' => '""',
                'refund_reason' => '""',
                'number' => '""',
                'total_diamonds' => '""',
                'goods_diamonds' => '""',
                'podcast_ticket' => '""',
                'refund_diamonds' => '""',
                'freight_diamonds' => '""',
                'memo' => '""',
                'consignee' => '""',
                'consignee_mobile' => '""',
                'consignee_district' => '""',
                'consignee_address' => '""',
                'create_date' => '""',
                'podcast_id' => '""',
                'viewer_id' => '""',
                'goods_id' => '""',
                'pay_time' => '""',
                'delivery_time' => '""',
                'courier_number' => '""',
                'courier_offic' => '""',
                'buy_type' => '""'
            );
            if($page == 1)
            {
                $content = iconv("utf-8","gbk","购物订单ID,订单来源,订单类型,订单编号,订单状态,是否允许退款,退款买方状态,退款买方配货状态,退款卖方状态,退款平台申诉,退款完成时间,退款原因,商品数量,订单总金额(含运费),商品的单价,主播抽取佣金(印票),退款金额,运费,订单备注,收货人,收货人手机号,收货人所在地区信息,详细地址,下单时间,主播人id,购买人id,商品id,付款时间,发货时间,物流单号,物流公司,订单拥有者");
                $content = $content . "\n";
            }

            foreach($list as $key => $value){
                if($value['order_source'] == 'local'){
                    $list[$key]['order_source'] = '本地';
                }else{
                    $list[$key]['order_source'] = '远程';
                }
                if($value['order_type'] == 'shop'){
                    $list[$key]['order_type'] = '购物单';
                }elseif($value['order_type'] == 'pai_goods'){
                    $list[$key]['order_type'] = '实物竞拍单';
                }
                if($value['order_status'] == 1){
                    $list[$key]['order_status'] = '待付款';
                }elseif($value['order_status'] == 2){
                    $list[$key]['order_status'] = '待发货';
                }elseif($value['order_status'] == 3){
                    $list[$key]['order_status'] = '待收货';
                }elseif($value['order_status'] == 4){
                    $list[$key]['order_status'] = '已收货';
                }elseif($value['order_status'] == 5){
                    $list[$key]['order_status'] = '已退货';
                }elseif($value['order_status'] == 6){
                    $list[$key]['order_status'] = '未付款';
                }elseif($value['order_status'] == 7){
                    $list[$key]['order_status'] = '结单';
                }
                if($value['no_refund'] == 0){
                    $list[$key]['no_refund'] = '是';
                }else{
                    $list[$key]['no_refund'] = '否';
                }
                if($value['refund_buyer_status'] == 0){
                    $list[$key]['refund_buyer_status'] = '无';
                }elseif($value['refund_buyer_status'] == 1){
                    $list[$key]['refund_buyer_status'] = '退款中';
                }elseif($value['refund_buyer_status'] == 2){
                    $list[$key]['refund_buyer_status'] = '退货中';
                }elseif($value['refund_buyer_status'] == 3){
                    $list[$key]['refund_buyer_status'] = '退款成功';
                }elseif($value['refund_buyer_status'] == 4){
                    $list[$key]['refund_buyer_status'] = '主动撤销退款';
                }elseif($value['refund_buyer_status'] == 5){
                    $list[$key]['refund_buyer_status'] = '被动关闭';
                }
                if($value['refund_buyer_delivery'] == 0){
                    $list[$key]['refund_buyer_delivery'] = '无';
                }elseif($value['refund_buyer_delivery'] == 1){
                    $list[$key]['refund_buyer_delivery'] = '未发货';
                }elseif($value['refund_buyer_delivery'] == 2){
                    $list[$key]['refund_buyer_delivery'] = '已发货';
                }
                if($value['refund_seller_status'] == 0){
                    $list[$key]['refund_seller_status'] = '无';
                }else{
                    $list[$key]['refund_seller_status'] = '退款成功';
                }
                if($value['refund_platform'] == 0){
                    $list[$key]['refund_platform'] = '无';
                }elseif($value['refund_platform'] == 1){
                    $list[$key]['refund_platform'] = '申诉中(卖方)';
                }elseif($value['refund_platform'] == 2){
                    $list[$key]['refund_platform'] = '(卖方)申诉完成';
                }elseif($value['refund_platform'] == 3){
                    $list[$key]['refund_platform'] = '买方申诉';
                }elseif($value['refund_platform'] == 4){
                    $list[$key]['refund_platform'] = '买方申诉完成';
                }
                if($value['consignee_district']){
                    $value['consignee_district'] = json_decode(htmlspecialchars_decode($value['consignee_district']), true);
                    $list[$key]['consignee_district'] =$value['consignee_district']['province'].$value['consignee_district']['city'].$value['consignee_district']['area'];
                }
                if($value['buy_type'] == 0){
                    $list[$key]['buy_type'] = '买给自己';
                }else{
                    $list[$key]['buy_type'] = '买给主播';
                }
                $refund_value['id'] = '"' . iconv('utf-8','gbk',$list[$key]['id']) . '"';
                $refund_value['order_source'] = '"' . iconv('utf-8','gbk',$list[$key]['order_source']) . '"';
                $refund_value['order_type'] = '"' . iconv('utf-8','gbk',$list[$key]['order_type']) . '"';
                $refund_value['order_sn'] = '"' . iconv('utf-8','gbk','\''.+$list[$key]['order_sn']) . '"';
                $refund_value['order_status'] = '"' . iconv('utf-8','gbk',$list[$key]['order_status']) . '"';
                $refund_value['no_refund'] = '"' . iconv('utf-8','gbk',$list[$key]['no_refund']) . '"';
                $refund_value['refund_buyer_delivery'] = '"' . iconv('utf-8','gbk',$list[$key]['refund_buyer_status']) . '"';
                $refund_value['refund_buyer_delivery'] = '"' . iconv('utf-8','gbk',$list[$key]['refund_buyer_delivery']) . '"';
                $refund_value['refund_seller_status'] = '"' . iconv('utf-8','gbk',$list[$key]['refund_seller_status']) . '"';
                $refund_value['refund_platform'] = '"' . iconv('utf-8','gbk',$list[$key]['refund_platform']) . '"';
                $refund_value['refund_over_time'] = '"' . iconv('utf-8','gbk',$list[$key]['refund_over_time']) . '"';
                $refund_value['refund_reason'] = '"' . iconv('utf-8','gbk',$list[$key]['refund_reason']) . '"';
                $refund_value['number'] = '"' . iconv('utf-8','gbk',$list[$key]['number']) . '"';
                $refund_value['total_diamonds'] = '"' . iconv('utf-8','gbk',$list[$key]['total_diamonds']) . '"';
                $refund_value['goods_diamonds'] = '"' . iconv('utf-8','gbk',$list[$key]['goods_diamonds']) . '"';
                $refund_value['podcast_ticket'] = '"' . iconv('utf-8','gbk',$list[$key]['podcast_ticket']) . '"';
                $refund_value['refund_diamonds'] = '"' . iconv('utf-8','gbk',$list[$key]['refund_diamonds']) . '"';
                $refund_value['freight_diamonds'] = '"' . iconv('utf-8','gbk',$list[$key]['freight_diamonds']) . '"';
                $refund_value['memo'] = '"' . iconv('utf-8','gbk',$list[$key]['memo']) . '"';
                $refund_value['consignee'] = '"' . iconv('utf-8','gbk',$list[$key]['consignee']) . '"';
                $refund_value['consignee_mobile'] = '"' . iconv('utf-8','gbk','\''.+$list[$key]['consignee_mobile']) . '"';
                $refund_value['consignee_district'] = '"' . iconv('utf-8','gbk',$list[$key]['consignee_district']) . '"';
                $refund_value['consignee_address'] = '"' . iconv('utf-8','gbk',$list[$key]['consignee_address']) . '"';
                $refund_value['create_date'] = '"' . iconv('utf-8','gbk',$list[$key]['create_date']) . '"';
                $refund_value['podcast_id'] = '"' . iconv('utf-8','gbk',$list[$key]['podcast_id']) . '"';
                $refund_value['viewer_id'] = '"' . iconv('utf-8','gbk',$list[$key]['viewer_id']) . '"';
                $refund_value['goods_id'] = '"' . iconv('utf-8','gbk',$list[$key]['goods_id']) . '"';
                $refund_value['pay_time'] = '"' . iconv('utf-8','gbk',$list[$key]['pay_time']) . '"';
                $refund_value['delivery_time'] = '"' . iconv('utf-8','gbk',$list[$key]['delivery_time']) . '"';
                $refund_value['courier_number'] = '"' . iconv('utf-8','gbk',"\t".$list[$key]['courier_number']) . '"';
                $refund_value['courier_offic'] = '"' . iconv('utf-8','gbk',$list[$key]['courier_offic']) . '"';
                $refund_value['buy_type'] = '"' . iconv('utf-8','gbk',$list[$key]['buy_type']) . '"';
                $content .= implode(",", $refund_value) . "\n";
            }

            header("Content-Disposition: attachment; filename=shop_order.csv");
            echo $content ;
        }
        else
        {
            if($page==1)
                $this->error(L("NO_RESULT"));
        }

    }

}
