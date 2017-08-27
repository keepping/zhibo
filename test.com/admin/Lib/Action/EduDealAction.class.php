<?php
// +----------------------------------------------------------------------
// | Fanwe 方维p2p借贷系统
// +----------------------------------------------------------------------
// | Copyright (c) 2011 http://www.fanwe.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: 甘味人生(526130@qq.com)
// +----------------------------------------------------------------------

class EduDealAction extends CommonAction
{
    public function index()
    {
        //把已直播的项目更新为已直播
        $this->update_deal_video_success();


        if (trim($_REQUEST['name']) != '') {
            $map['name'] = array('like', '%' . trim($_REQUEST['name']) . '%');
        }

        $user_id = intval($_REQUEST['user_id']);
        if ($user_id) {
            $map['user_id'] = array('eq', $user_id);
        }

        $create_time_2=empty($_REQUEST['create_time_2'])?to_date(get_gmtime(),'Y-m-d'):strim($_REQUEST['create_time_2']);
        $create_time_2=to_timespan($create_time_2)+24*3600;
        if(trim($_REQUEST['create_time_1'])!='')
        {
            $map["create_time"] = array('between',array(to_timespan($_REQUEST['create_time_1']),$create_time_2));
        }

        $map['deal_status']=1;
        $map['is_delete']=0;
        if (method_exists($this, '_filter')) {
            $this->_filter($map);
        }
        $name = 'EduDeal';
        $model = M($name);
        if (!empty ($model)) {
            $this->_list($model, $map);
        }

        $this->display();
    }

    public function add()
    {

    }

    public function edit()
    {
        $id = intval($_REQUEST ['id']);
        $condition['id'] = $id;
        $vo = M("EduDeal")->where($condition)->find();

        //状态
        if($vo['deal_status'] ==1){
            $vo['deal_status_name']='通过';
        }elseif($vo['deal_status'] ==2){
            $vo['deal_status_name']='未审核';
        }elseif($vo['deal_status'] ==3){
            $vo['deal_status_name']='未通过';
        }

        $vo['begin_time']=to_date(to_timespan($vo['begin_time']),'Y-m-d');
        $vo['end_time']=to_date(to_timespan($vo['end_time']),'Y-m-d');

        $this->assign('vo', $vo);

        //分类
        $cate_list=M("EduCourseCategory")->where("is_effect=1")->findAll();
        $this->assign('cate_list', $cate_list);

        //项目支付成功订单数
        $this->assign('order_count', $this->get_deal_order_count($id));

        //拥金
        $this->assign('default_pay_radio', app_conf("PAY_RADIO"));

        $this->display();
    }

    public function delete() {
        //彻底删除指定记录
        $ajax = intval($_REQUEST['ajax']);
        $id = $_REQUEST ['id'];
        if (isset ( $id )) {
            $condition = array ('id' => array ('in', explode ( ',', $id ) ) );
            $rel_data = M('Deal')->where($condition)->findAll();
            foreach($rel_data as $data)
            {
                $num = $GLOBALS['db']->getOne("select count(*) from ".DB_PREFIX."edu_deal_order where deal_id = ".$data['id']." and order_status=1 and is_refund=0");
                if(intval($num)>0){
                    $this->error ($data['name']."[ID:".$data['id']."]已经有人成功付款,无法删除",$ajax);
                }

                $info[] = $data['name'];
            }
            if($info) $info = implode(",",$info);
            $list = M('EduDeal')->where ( $condition )->setField("is_delete",1);

            if ($list!==false) {
                save_log($info."成功移到回收站",1);
                $this->success ("成功移到回收站",$ajax);
            } else {
                save_log($info."移到回收站出错",0);
                $this->error ("移到回收站出错",$ajax);
            }
        } else {
            $this->error (l("INVALID_OPERATION"),$ajax);
        }
    }




    public function insert()
    {

    }

    public function update()
    {
        B('FilterString');
        $data = M("EduDeal")->create();

        $this->assign("jumpUrl", u(MODULE_NAME . "/edit", array("id" => $data['id'])));
        $deal_info = M("EduDeal")->where("id=" . intval($data['id']))->find();
        if(!$deal_info) {
            $this->error("发起人不存在");
        }
        $log_info = $red_info['title'] . "(ID：" . $red_info['id'] . ")";


        //项目已有支付成功的订单时，不可以修改的字段用项目原数据覆盖
        $order_count=$this->get_deal_order_count($data['id']);
        if($order_count>0){
            $data['begin_time']=$deal_info['begin_time'];
            $data['limit_num']=$deal_info['limit_num'];
            $data['price']=$deal_info['price'];
            $data['pay_radio']=$deal_info['pay_radio'];
        }

        if(intval(M("User")->where("id=" . intval($deal_info['user_id'])." and is_effect=1")->count()) <=0){
            $this->error("发起人不存在");
        }

        if (!$deal_info) {
            $this->error("请选择编辑的项目");
        }

        if ($data['name'] == '') {
            $this->error("请输入项目名称");
        }

        if (msubstr($data['name'], 0, 30, "utf-8", false) != $data['name']) {
            $this->error("项目名称不能超过30个字");
        }
        if (empty($data['image'])) {
            $this->error("项目名称不能超过30个字");
            $root['error'] = '请上传图片';
            return api_ajax_return($root);
        }
        if (empty($data['description'])) {
            $this->error("请输入详情描述");
        }
        $day_second=24*60*60;
        $begin_time_num = to_timespan(to_date(to_timespan($data['begin_time']),'Y-m-d'));
        $end_time_num = to_timespan(to_date(to_timespan($data['end_time']),'Y-m-d'))+$day_second;
        $video_begin_time_num = to_timespan($data['video_begin_time']);

        if (!$begin_time_num) {
            $this->error("请输入项目开始时间");
        }
        if (!$end_time_num) {

            $this->error("请输入项目结束时间");
        }

        if (!$video_begin_time_num) {
            $this->error("请输入直播开始时间");
        }

        if ($end_time_num <= $begin_time_num) {
            $this->error("项目结束时间小于开始时间");;
        }

        if ($video_begin_time_num <= $end_time_num) {
            $this->error("直播开始时间要大于项目结束时间");
        }

        if ($data['limit_num'] <= 0) {
            $this->error("请输入目标数量");
        }

        if ($data['price'] <= 0) {
            $this->error("请输入支持价格");
        }


        // 更新数据
        $data['begin_time'] = to_date($begin_time_num);
        $data['end_time'] = to_date($end_time_num-$day_second);
        $data['video_begin_time'] = to_date($video_begin_time_num-$video_begin_time_num%60);
        $list = M("EduDeal")->save($data);
        if (false !== $list) {
            //待完成

            //成功提示
            $this->assign("jumpUrl", u(MODULE_NAME . "/index", array("id" => $data['id'])));
            save_log($log_info . L("UPDATE_SUCCESS"), 1);
            $this->success(L("UPDATE_SUCCESS"));
        } else {
            //错误提示
            save_log($log_info . L("UPDATE_FAILED"), 0);
            $this->error(L("UPDATE_FAILED"), 0, $log_info . L("UPDATE_FAILED"));
        }
    }

    public function toogle_status()
    {

        $id = intval($_REQUEST['id']);
        $ajax = intval($_REQUEST['ajax']);
        $field = $_REQUEST['field'];
        $info = $id."_".$field;

        $c_is_effect = M(MODULE_NAME)->where("id=".$id)->getField($field);  //当前状态

        $n_is_effect = $c_is_effect == 0 ? 1 : 0; //需设置的状态
        M(MODULE_NAME)->where("id=".$id)->setField($field,$n_is_effect);

        //删除缓存

        save_log($info.l("SET_EFFECT_".$n_is_effect),1);
        $this->ajaxReturn($n_is_effect,l("SET_EFFECT_".$n_is_effect),1)	;
    }

    public function get_deal_order_count($id)
    {
        $order_count=M("EduDealOrder")->where("deal_id=".intval($id)." and order_status=1")->count();
        return intval($order_count);
    }

    /**
     * 支持列表
     */
    public function deal_support()
    {

        $deal_id=$_REQUEST['deal_id'];
        $deal_info=M("EduDeal")->where("id=".intval($deal_id)."")->find();
        if(!$deal_info)
        {
            $this->errors("众筹项目不存在");
        }
        $this->assign ( "deal_info", $deal_info );
        //列表过滤器，生成查询Map对象
        $map = $this->_search ();
        $map['deal_id'] = $deal_id;
        $map['order_status']=1;
        //追加默认参数
        if($this->get("default_map")){
            $map = array_merge($map,$this->get("default_map"));
        }

        if(isset($_REQUEST['is_refund']))
        {
            if( $_REQUEST['is_refund']!='NULL'){
                $map['is_refund']=intval($_REQUEST['is_refund']);
            }
        }

        if (method_exists ( $this, '_filter' )) {
            $this->_filter ( $map );
        }

        $model = M ('EduDealOrder');
        if (! empty ( $model )) {
            $this->_list ( $model, $map );
        }

        $this->display ();
    }

    public function delete_order() {
        //彻底删除指定记录
        $ajax = intval($_REQUEST['ajax']);
        $id = $_REQUEST ['id'];
        if (isset ( $id )) {
            $condition = array ('id' => array ('in', explode ( ',', $id ) ) );
            $rel_data = M("EduDealOrder")->where($condition)->findAll();
            foreach($rel_data as $data)
            {
                $info[] = "[项目ID".$data['deal_id']."支持人ID:".$data['user_id']."状态:".$data['order_status']."]";
                $deal_ids[$data['deal_id']]=$data['deal_id'];
            }
            if($info) $info = implode(",",$info);
            $list = M("EduDealOrder")->where ( $condition )->delete();

            if ($list!==false) {
                foreach($deal_ids as $d_id)
                {
                    FanweServiceCall('edu_deal', 'syn_deal', $d_id);
                }

                save_log($info."成功删除",1);
                $this->success ("成功删除",$ajax);
            } else {
                save_log($info."删除出错",0);
                $this->error ("删除出错",$ajax);
            }
        } else {
            $this->error (l("INVALID_OPERATION"),$ajax);
        }
    }

    public function order_refund()
    {
        $id = intval($_REQUEST['id']);
        $order_info = M("EduDealOrder")->getById($id);
        if($order_info)
        {
            $count_pay_log = M("EduDealPayLog")->where("deal_id=".intval($order_info['deal_id']))->count();
            if($count_pay_log >0){
                $this->error("筹款已发，不能退款");
            }

            if($order_info['is_refund']==0 && $order_info['order_status'] ==1)
            {
                $GLOBALS['db']->query("update ".DB_PREFIX."edu_deal_order set is_refund = 1 where id = ".$id." and is_refund = 0 and order_status=1");
                if($GLOBALS['db']->affected_rows()>0)
                {
                    $deal_name=M("EduDeal")->where("id=".intval($order_info['deal_id'])."")->getField("name");
                    $msg = "众筹直播(".$deal_name.")退款";

                    require_once APP_ROOT_PATH."system/libs/user.php";
                    //会员钻石增加
                    modify_account(array("diamonds"=>($order_info['pay'])),$order_info['user_id'],$msg,array('type'=>20));

                    //会员账户 钻石变更日志表
                    fanwe_require(APP_ROOT_PATH . 'mapi/lib/core/common_edu.php');
                    insert_user_diamonds_log(array('diamonds'=>$order_info['pay']),$order_info['user_id'],$msg,6,$order_info['id']);

                    //同步项目记录
                    FanweServiceCall('edu_deal', 'syn_deal', $order_info['deal_id']);
                }
                $this->success("成功退款到会员余额");
            }elseif($order_info['is_refund']==0 && $order_info['order_status'] ==0)
            {
                $this->error("订单未付款");
            }
            else
            {
                $this->error("已经退款");
            }
        }
        else
        {
            $this->error("没有该项目的支持");
        }
    }

    /**
     * 批量退款
     */
    public function batch_refund()
    {
        $page = intval($_REQUEST['page']);

        $page=($page<=0)?1:$page;

        $page_size = 100;
        $deal_id = intval($_REQUEST['id']);

        $limit = (($page-1)*$page_size).",".$page_size;
        $now_time=to_date(to_timespan(to_date(get_gmtime(),'Y-m-d')));
        $deal_info = M("EduDeal")->where("id=".$deal_id." and is_delete = 0 and is_effect = 1 and is_success = 0 and end_time <'".$now_time."'")->find();
        if(!$deal_info)
        {
            $this->error("该项目不能批量退款");
        }
        else
        {
            require_once APP_ROOT_PATH."system/libs/user.php";
            require_once APP_ROOT_PATH . "mapi/lib/core/common_edu.php";
            $refund_order_list = M("EduDealOrder")->where("deal_id=".$deal_id." and is_refund = 0 and order_status = 1")->limit($limit)->findAll();
            foreach($refund_order_list as $k=>$v)
            {
                $GLOBALS['db']->query("update ".DB_PREFIX."edu_deal_order set is_refund = 1 where id = ".$v['id']);
                if($GLOBALS['db']->affected_rows()>0)
                {
                    $msg = "众筹直播(".$deal_info['name'].")退款";

                    //会员钻石增加
                    modify_account(array("diamonds"=>($v['pay'])),$v['user_id'],$msg,array('type'=>20));

                    //会员账户 钻石变更日志表
                    insert_user_diamonds_log(array('diamonds'=>$v['pay']),$v['user_id'],$msg,6,$v['id']);

                }
            }

            //同步项目记录
            FanweServiceCall('edu_deal', 'syn_deal', $deal_info['id']);

            $remain = M("EduDealOrder")->where("deal_id=".$deal_id." and is_refund = 0 and order_status = 1")->count();
            if($remain==0)
            {
                $jump_url = u("EduDeal/index");
                $this->assign("jumpUrl",$jump_url);
                $this->success("批量退款成功");
            }
            else
            {
                $jump_url = u("EduDeal/batch_refund",array("id"=>$deal_id,"page"=>$page+1));
                $this->assign("jumpUrl",$jump_url);
                $this->success("批量退款中，请勿刷新页面，剩余".$remain."条订单未退款");
            }

        }

    }
    /**
     * 结算
     */
    public function pay_log()
    {
        $deal_id = intval($_REQUEST['id']);
        $deal_info = M("EduDeal")->getById($deal_id);

        //拥金
        $deal_info['commission'] = $deal_info['support_amount']  - ($deal_info['pay_amount']) ;
        $this->assign("deal_info",$deal_info);

        if($deal_info)
        {
            $map['deal_id'] = $deal_info['id'];

            $model = D ("EduDealPayLog");
            $paid_money = intval($model->where($map)->sum("money"));
            $remain_money =intval( $deal_info['pay_amount'] - $paid_money);
            $this->assign("remain_money",$remain_money);
            $this->assign("paid_money",$paid_money);
            if (! empty ( $model )) {
                $this->_list ( $model, $map );
            }
        }
        $this->display();
    }
    /**
     * 发款
     */
    public function add_pay_log()
    {
        $deal_id = intval($_REQUEST['id']);
        $deal_info = M("EduDeal")->where("is_success=1 and is_video=1 and id=".$deal_id."")->find();
        if(!$deal_info)
        {
            echo '<div style="padding:20px 0px;text-align:center;">项目不能结算</div>';
            return false;
        }

        //拥金
        $deal_info['commission'] = $deal_info['support_amount']  - ($deal_info['pay_amount']) ;
        $this->assign("deal_info",$deal_info);

        $map['deal_id'] = $deal_info['id'];
        $model = D ("EduDealPayLog");
        $paid_money = intval($model->where($map)->sum("money"));
        $remain_money =intval( $deal_info['pay_amount'] - $paid_money);
        $this->assign("paid_money",$paid_money);
        $this->assign("remain_money",$remain_money);

        $this->display();
    }
    /**
     * 确认发款
     */
    public function save_pay_log()
    {
        $deal_id = intval($_REQUEST['id']);
        $deal_info = M("EduDeal")->where("is_success=1 and is_video=1 and id=".$deal_id."")->find();

        $log_info = strim($_REQUEST['log_info']);
        if(!$deal_info)
        {
            $this->error("项目不能结算");
        }

        $map['deal_id'] = $deal_info['id'];
        $model = D ("EduDealPayLog");
        $paid_money = $model->where($map)->sum("money");
        $remain_money = $deal_info['pay_amount'] - $paid_money;
        $money = $remain_money;
        if($remain_money<=0)
        {
            $this->error("已结算完了");
        }

        $user_info = M("user")->where("id=".$deal_info['user_id']." and is_effect=1 ")->find();
        if(!$user_info)
        {
            $this->error("无效的项目发起人");
        }

        $log_info = $log_info==""?$deal_info['name']."项目结算":$log_info;
        //增加发起人印票
        $ticket = $money;
        $GLOBALS['db']->query("update " . DB_PREFIX . "user set ticket = ticket + " . $ticket . "  where id = " . $deal_info['user_id'] . " ");
        $re=$GLOBALS['db']-> affected_rows();
        if($re)
        {
            //更年新reids
            user_deal_to_reids(array($deal_info['user_id']));
            //印票变更日志表
            fanwe_require(APP_ROOT_PATH . 'mapi/lib/core/common_edu.php');
            insert_user_diamonds_log(array('ticket'=>$money),$deal_info['user_id'],$log_info."获得印票",7,$deal_info['id']);

            //写入用户日志
            $data = array();
            $data['ticket'] = $ticket;
            $param['type'] = 20;//类型 0表示充值 1表示提现 2赠送道具 3 兑换印票 4表示保证金操作 5表示竞拍模块消费 6表示竞拍模块收益 20教育
            $log_msg = $log_info;//备注
            account_log_com($data, $deal_info['user_id'], $log_msg, $param);

            //写入发放日志
            $log['deal_id'] = $deal_info['id'];
            $log['money'] = $money;
            $log['create_time'] = get_gmtime();
            $log['log_info'] = $log_info;
            $model->add($log);
            save_log($log_info.$money,1);

            FanweServiceCall('edu_deal', 'syn_deal', $deal_id);
            //通知发起人

            $this->success("结算成功");
        }else{
            $this->success("结算失败");
        }

    }


    /*
     *众筹项目直播列表
     * */
    public function deal_videos()
    {
        $deal_id=$_REQUEST['deal_id'];
        $deal=M("Deal")->where("id=".$deal_id."")->find();
        if($deal)
        {
            $sql = "SELECT v.*,watch_number+virtual_watch_number+robot_num as all_watch_number,u.nick_name FROM ".DB_PREFIX."video v LEFT JOIN ".DB_PREFIX."user u ON u.id = v.user_id  LEFT JOIN ".DB_PREFIX."edu_video_info as ev ON ev.video_id = v.id 
				where v.live_in in (1,3) and ev.deal_id=".$deal_id."  order by v.id desc";
            $list = $GLOBALS['db']->getAll($sql,true,true);

            $sql2 = "SELECT v.*,watch_number+virtual_watch_number+robot_num as all_watch_number,u.nick_name FROM ".DB_PREFIX."video_history v LEFT JOIN ".DB_PREFIX."user u ON u.id = v.user_id  LEFT JOIN ".DB_PREFIX."edu_video_info as ev ON ev.video_id = v.id 
				where v.live_in in (1,3) and ev.deal_id=".$deal_id."  order by v.id desc";
            $list2 = $GLOBALS['db']->getAll($sql2,true,true);
            $list = array_merge($list,$list2);
        }else{
            $list=array();
        }

        $this->assign('deal',$deal);
        $this->assign('list',$list);
        $this->display();
    }


    //查看直播（web2.1播放器）
    public function play(){
        $id = $_REQUEST['id'];
        $condition['id'] = $id;

        $video = M('Video')->where($condition)->find();
        if($video){
            $video = M('VideoHistory')->where($condition)->find();
        }
        $m_config =  load_auto_cache("m_config");//初始化手机端配置
        $app_id = $m_config['vodset_app_id'];
        $this->assign('app_id',$app_id);
        if($video){
            require_once APP_ROOT_PATH."/mapi/lib/core/common.php";
            fanwe_require(APP_ROOT_PATH.'mapi/lib/redis/BaseRedisService.php');
            $root = get_vodset_by_video_id($id);
            if(isset($root['vodset'])){
                $play_list = array();
                $vodset = $root['vodset'];
                foreach($vodset as $k=>$v){
                    $playSet = $v['fileSet'];
                    for($i=sizeof($playSet)-1;$i>=0;$i--){
                        $play_list[] = $playSet[$i]['fileId'];
                        $play_url_list[] = $playSet[$i]['playSet'];
                    }
                }
                foreach($play_url_list as $k2=>$v2){
                    foreach($v2 as $kk=>$vv) {
                        //mp4
                        if ($vv['definition'] == 0&&strpos($vv['url'], '.mp4')) {//原画mp4 播放URL
                            $video['mp4_url'] = $vv['url'];
                        }
                        if ($vv['definition'] == 10) {//手机mp4 播放URL
                            $video['mp4_sj_url'] = $vv['url'];
                        }
                        if ($vv['definition'] == 20) {//标清mp4 播放URL
                            $video['mp4_sd_url'] = $vv['url'];
                        }
                        if ($vv['definition'] == 40 || $vv['definition'] == 30) {//高清mp4 播放URL
                            $video['mp4_hd_url'] = $vv['url'];
                        }
                        //m3u8
                        if ($vv['definition'] == 0 &&strpos($vv['url'], '.m3u8')) {//原画m3u8 播放URL
                            $video['m3u8_url'] = $vv['url'];
                        }
                        if (($vv['definition'] == 210)&&strpos($vv['url'], '.m3u8')) {//手机m3u8 播放URL
                            $video['m3u8_url'] = $vv['url'];
                        }
                        if (($vv['definition'] == 220)&&strpos($vv['url'], '.m3u8')) {//标清m3u8 播放URL
                            $video['m3u8_sd_url'] = $vv['url'];
                        }
                        if (($vv['definition'] == 230)&&strpos($vv['url'], '.m3u8')) {//高清m3u8 播放URL
                            $video['m3u8_hd_url'] = $vv['url'];
                        }
                        //flv
                        if (strpos($vv['url'], '.flv')&&$vv['definition'] == 0) {//原画flv 播放URL
                            $video['flv_url'] = $vv['url'];
                        }
                        if (strpos($vv['url'], '.flv')&&$vv['definition'] == 0) {//手机flv 播放URL
                            $video['flv_sj_url'] = $vv['url'];
                        }
                        if (strpos($vv['url'], '.flv')&&$vv['definition'] == 0) {//标清flv 播放URL
                            $video['flv_sd_url'] = $vv['url'];
                        }
                        if (strpos($vv['url'], '.flv')&&$vv['definition'] == 0) {//高清flv 播放URL
                            $video['flv_hd_url'] = $vv['url'];
                        }
                        //rtmp
                        if (!strpos($vv['url'], '.flv')&&!strpos($vv['url'], '.mp4')&&!strpos($vv['url'], '.m3u8')&&$vv['definition'] == 0) {//原画rtmp 播放URL
                            $video['rtmp_url'] = $vv['url'];
                        }
                        if (!strpos($vv['url'], '.flv')&&!strpos($vv['url'], '.mp4')&&!strpos($vv['url'], '.m3u8')&&$vv['definition'] == 0) {//手机rtmp 播放URL
                            $video['rtmp_sj_url'] = $vv['url'];
                        }
                        if (!strpos($vv['url'], '.flv')&&!strpos($vv['url'], '.mp4')&&!strpos($vv['url'], '.m3u8')&&$vv['definition'] == 0) {//标清rtmp 播放URL
                            $video['rtmp_sd_url'] = $vv['url'];
                        }
                        if (!strpos($vv['url'], '.flv')&&!strpos($vv['url'], '.mp4')&&!strpos($vv['url'], '.m3u8')&&$vv['definition'] == 0) {//高清rtmp 播放URL
                            $video['rtmp_hd_url'] = $vv['url'];
                        }
                    }
                }
                $this->assign("poster",$vodset[0]['fileSet'][sizeof($vodset[0]['fileSet'])-1]['image_url']);
            }else{
                $this->assign("error",$root['error']);
            }
        }else{
            //直播
            $video['mp4_url'] = $video['play_mp4'];
            $video['m3u8_url'] = $video['play_hls'];
            $video['flv_url'] = $video['play_flv'];
            $video['rtmp_url'] = $video['play_rtmp'];
        }
        $this->assign("video",$video);
        $this->display();
    }

    /*
     * 把已直播的项目更新为已直播
     * */
    public function update_deal_video_success()
    {
        $deal_list=$GLOBALS['db']->getAll("select * from ".DB_PREFIX."edu_deal where is_effect =1 and is_success=1 and is_video=0");
        foreach($deal_list as $k=>$v){
            $sql="select v.end_time,v.begin_time from ".DB_PREFIX."edu_video_info as ev left join ".DB_PREFIX."video as v on v.id=ev.video_id
                 where ev.deal_id=".intval($v['id'])."";
            $video_list=$GLOBALS['db']->getAll($sql);
            foreach($video_list as $k=>$v){
                if(($v['end_time']-$v['begin_time']) >= (15*60)){
                    $GLOBALS['db']->query("update ".DB_PREFIX."edu_deal set is_video=1,real_video_time=".$v['begin_time']."  where is_effect =1 and is_success=1 and is_video=0");
                    break;
                }
            }
        }

    }
}

?>