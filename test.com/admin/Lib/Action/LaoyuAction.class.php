<?php
// +----------------------------------------------------------------------
// | Fanwe 方维p2p借贷系统
// +----------------------------------------------------------------------
// | Copyright (c) 2011 http://www.fanwe.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: 甘味人生(526130@qq.com)
// +----------------------------------------------------------------------

class LaoyuAction extends CommonAction{

    //老余个人信息
    public function index()
    {
        $region_tree = M("RegionConf")->where("region_level<3")->findAll();
        $region_tree = D("RegionConf")->toTree($region_tree);
        $this->assign("region_tree",$region_tree);
        $laoyu_info = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."conf where `name`='LAOYU_INFO'");
        if(!$laoyu_info){
            $value['name'] = '';
            $value['wanna_count'] = 0;
            $value['seen_count'] = 0;
            $value['head_image'] = '';
            $value['desc'] = '';
            $value['content'] = '';
            $value['region'] = '';
            $value = serialize($value);
            $GLOBALS['db']->query("insert into ".DB_PREFIX."conf VALUES('','LAOYU_INFO','".$value."',0,0,'',0,0,0)");
            $laoyu_info = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."conf where `name`='LAOYU_INFO'");
        }
        $laoyu_info = unserialize($laoyu_info['value']);
        //$laoyu_info['region'] = implode(',',$laoyu_info['region']);
        $this->assign("laoyu_info",$laoyu_info);
        $this->display ();
    }
    //老余个人信息保存
    public function update() {
        $laoyu_info = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."conf where `name`='LAOYU_INFO'");
        $value = unserialize($laoyu_info['value']);
        $value['name'] = $_REQUEST['name'];
        $value['head_image'] = $_REQUEST['head_image'];
        $value['desc'] = $_REQUEST['desc'];
        $value['content'] = $_REQUEST['content'];
        $value['region'] = $_REQUEST['region'];
        $laoyu_info['value'] = serialize($value);
        $list = M("Conf")->save ($laoyu_info);
        if (false !== $list) {
            //成功提示
            //save_log("老余个人信息".L("UPDATE_SUCCESS"),1);
            $this->success(L("UPDATE_SUCCESS"));
        } else {
            //错误提示
            $this->error(L("UPDATE_FAILED"),0,"老余个人信息".L("UPDATE_FAILED"));
        }
    }

    //老余个人信息
    public function security()
    {
        $security = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."conf where `name`='DATE_SECURITY'");
        if(!$security){
            $GLOBALS['db']->query("insert into ".DB_PREFIX."conf VALUES('','DATE_SECURITY','',0,0,'',0,0,0)");
            $security = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."conf where `name`='DATE_SECURITY'");
        }
        $this->assign("security",$security);
        $this->display ();
    }
    //约见保障保存
    public function security_update(){
        $security = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."conf where `name`='DATE_SECURITY'");
        $security['value'] = $_REQUEST['value'];
        $list = M("Conf")->save ($security);
        if (false !== $list) {
            //成功提示
            //save_log("约见保障".L("UPDATE_SUCCESS"),1);
            $this->success(L("UPDATE_SUCCESS"));
        } else {
            //错误提示
            $this->error(L("UPDATE_FAILED"),0,"老余个人信息".L("UPDATE_FAILED"));
        }
    }
}
?>