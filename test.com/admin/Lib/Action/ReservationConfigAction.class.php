<?php
// +----------------------------------------------------------------------
// | Fanwe 方维p2p借贷系统
// +----------------------------------------------------------------------
// | Copyright (c) 2011 http://www.fanwe.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: 甘味人生(526130@qq.com)
// +----------------------------------------------------------------------

class ReservationConfigAction extends CommonAction{

    //预约配置信息
    public function index()
    {
        $region_tree = M("RegionConf")->where("region_level<3")->findAll();
        $region_tree = D("RegionConf")->toTree($region_tree);
        $this->assign("region_tree",$region_tree);
        $reservation_config = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."conf where `name`='RESERVATION_CONFIG'");
        if(!$reservation_config){
            $value['name'] = '';//人物姓名
            $value['wanna_count'] = 0;//想见人数
            $value['seen_count'] = 0;//见过人数
            $value['head_image'] = '';//人物头像
            $value['desc'] = '';//人物简介
            $value['content'] = '';//人物详细信息
            $value['region'] = '';//约见区域
            $value['security'] = '';//预约保障
            $value = serialize($value);
            $GLOBALS['db']->query("insert into ".DB_PREFIX."conf VALUES('','RESERVATION_CONFIG','".$value."',0,0,'',0,0,0)");
            $reservation_config = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."conf where `name`='RESERVATION_CONFIG'");
        }
        $reservation_config = unserialize($reservation_config['value']);
        $this->assign("reservation_config",$reservation_config);
        $this->display ();
    }
    //预约配置信息保存
    public function update() {
        $reservation_config = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."conf where `name`='RESERVATION_CONFIG'");
        $value = unserialize($reservation_config['value']);
        $value['name'] = $_REQUEST['name'];
        $value['head_image'] = $_REQUEST['head_image'];
        $value['desc'] = $_REQUEST['desc'];
        $value['content'] = $_REQUEST['content'];
        $value['region'] = $_REQUEST['region'];
        $value['security'] = $_REQUEST['security'];
        $reservation_config['value'] = serialize($value);
        $list = M("Conf")->save ($reservation_config);
        if (false !== $list) {
            //成功提示
            //save_log("预约配置信息".L("UPDATE_SUCCESS"),1);
            $this->success(L("UPDATE_SUCCESS"));
        } else {
            //错误提示
            $this->error(L("UPDATE_FAILED"),0,"预约配置信息".L("UPDATE_FAILED"));
        }
    }
}
?>