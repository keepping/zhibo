<?php
// +----------------------------------------------------------------------
// | Fanwe 方维众筹商业系统
// +----------------------------------------------------------------------
// | Copyright (c) 2011 http://www.fanwe.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: 甘味人生(526130@qq.com)
// +----------------------------------------------------------------------

class RechargeRuleAction extends CommonAction{
    public function index()
    {
        parent::index();
    }
    public function add()
    {
        //赠送游戏币显示or隐藏
        if(defined('OPEN_SEND_COINS_MODULE')&&OPEN_SEND_COINS_MODULE==1){
            $this->assign('is_coins_module',1);
        }else{
            $this->assign('is_coins_module',0);
        }
        $this->display();
    }
    public function edit() {
        $id = intval($_REQUEST ['id']);
        $condition['id'] = $id;
        $vo = M(MODULE_NAME)->where($condition)->find();
        //赠送游戏币显示or隐藏
        if(defined('OPEN_SEND_COINS_MODULE')&&OPEN_SEND_COINS_MODULE==1){
            $this->assign('is_coins_module',1);
        }else{
            $this->assign('is_coins_module',0);
        }
        $this->assign ( 'vo', $vo );
        $this->display ();
    }


    public function foreverdelete() {
        //彻底删除指定记录
        $ajax = intval($_REQUEST['ajax']);
        $id = $_REQUEST ['id'];
        if (isset ( $id )) {
            $condition = array ('id' => array ('in', explode ( ',', $id ) ) );
            $list = M(MODULE_NAME)->where ( $condition )->delete();
            //更新缓存
            clear_auto_cache("rule_list");
            load_auto_cache("rule_list");
            clear_auto_cache("iparule_list");
            load_auto_cache("iparule_list");
            clear_auto_cache("aliprule_list");
            load_auto_cache("aliprule_list");
            clear_auto_cache("wxrule_list");
            load_auto_cache("wxrule_list");
            $info = "规则".$id;
            if ($list!==false) {
                save_log($info.l("FOREVER_DELETE_SUCCESS"),1);
                $this->success (l("FOREVER_DELETE_SUCCESS"),$ajax);
            } else {
                save_log($info.l("FOREVER_DELETE_FAILED"),0);
                $this->error (l("FOREVER_DELETE_FAILED"),$ajax);
            }
        } else {
            $this->error (l("INVALID_OPERATION"),$ajax);
        }
    }

    public function insert() {
        B('FilterString');
        $ajax = intval($_REQUEST['ajax']);
        $data = M(MODULE_NAME)->create ();

        //开始验证有效性
        $this->assign("jumpUrl",u(MODULE_NAME."/add"));
        if(!check_empty($data['name']))
        {
            $this->error("请输入名称");
        }
        if(!check_empty($data['diamonds']))
        {
            $this->error("请输入钻石数量");
        }
        if(!(intval($data['diamonds'])>0))
        {
            $this->error("钻石数量必须大于0");
        }
        if(!check_empty($data['money']))
        {
            $this->error("请输入价格");
        }
        if(!(intval($data['money']*100)>0))
        {
            $this->error("价格必须大于0");
        }
        
//		if(!check_empty($data['iap_money']))
//        {
//            $this->error("请输入苹果支付价格");
//        }
//        if(!(intval($data['iap_money']*100)>0))
//        {
//            $this->error("价格必须大于0");
//        }
        
        if(check_empty($data['gift_diamonds'])&&intval($data['gift_diamonds'])!=0&&!(intval($data['gift_diamonds'])>0))
        {
            $this->error("赠送钻石必须大于等于0");
        }
        if(defined('OPEN_SEND_COINS_MODULE')&&OPEN_SEND_COINS_MODULE==1)
        {
            if (OPEN_GAME_MODULE && check_empty($data['gift_coins']) && intval($data['gift_coins']) != 0 && !(intval($data['gift_coins']) > 0)) {
                $this->error("赠送游戏币必须大于等于0");
            }
        }

        if($data['product_id']!='' && M(MODULE_NAME)->where("product_id = '".$data['product_id']."'")->count()>0){
            $this->error("苹果应用内支付项目ID已存在");
        }
        // 更新数据
        $list = M(MODULE_NAME)->add($data);
        $info = "规则".$list;
        if (false !== $list) {
            //更新缓存
            clear_auto_cache("rule_list");
            load_auto_cache("rule_list");
            clear_auto_cache("iparule_list");
            load_auto_cache("iparule_list");
            clear_auto_cache("aliprule_list");
            load_auto_cache("aliprule_list");
            clear_auto_cache("wxrule_list");
            load_auto_cache("wxrule_list");
            //成功提示
            save_log($info.L("INSERT_SUCCESS"),1);
            $this->success(L("INSERT_SUCCESS"));
        } else {
            //错误提示
            save_log($info.L("INSERT_FAILED"),0);
            $this->error(L("INSERT_FAILED"));
        }
    }

    public function update() {
        B('FilterString');
        $data = M(MODULE_NAME)->create ();


        //开始验证有效性
        $this->assign("jumpUrl",u(MODULE_NAME."/edit",array("id"=>$data['id'])));
        if(!check_empty($data['name']))
        {
            $this->error("请输入名称");
        }
        if(!check_empty($data['diamonds']))
        {
            $this->error("请输入钻石数量");
        }
        if(!(intval($data['diamonds'])>0))
        {
            $this->error("钻石数量必须大于0");
        }
        if(!check_empty($data['money']))
        {
            $this->error("请输入价格");
        }
        if(!(intval($data['money']*100)>0))
        {
            $this->error("价格必须大于0");
        }
        
//        if(!check_empty($data['iap_money']))
//        {
//            $this->error("请输入苹果支付价格");
//        }
//        if(!(intval($data['iap_money']*100)>0))
//        {
//            $this->error("价格必须大于0");
//        }
        
        if(check_empty($data['gift_diamonds'])&&intval($data['gift_diamonds'])!=0&&!(intval($data['gift_diamonds'])>0))
        {
            $this->error("赠送钻石必须大于等于0");
        }

        $rule_id =$GLOBALS['db']->getOne("select id from ".DB_PREFIX."recharge_rule where product_id = '".$data['product_id']."'");
        if($data['product_id']!='' && $rule_id && $rule_id!=$data['id']){
            $this->error("苹果应用内支付项目ID已存在");
        }

        $list = M(MODULE_NAME)->save ($data);
        $info = "规则".$data['id'];
        if (false !== $list) {
            //更新缓存
            clear_auto_cache("rule_list");
            load_auto_cache("rule_list");
            clear_auto_cache("iparule_list");
            load_auto_cache("iparule_list");
            clear_auto_cache("aliprule_list");
            load_auto_cache("aliprule_list");
            clear_auto_cache("wxrule_list");
            load_auto_cache("wxrule_list");
            //成功提示
            save_log($info.L("UPDATE_SUCCESS"),1);
            $this->success(L("UPDATE_SUCCESS"));
        } else {
            //错误提示
            save_log($info.L("UPDATE_FAILED"),0);
            $this->error(L("UPDATE_FAILED"),0,L("UPDATE_FAILED"));
        }
    }

    public function set_sort()
    {
        $id = intval($_REQUEST['id']);
        $sort = intval($_REQUEST['sort']);
        if(!check_sort($sort))
        {
            $this->error(l("SORT_FAILED"),1);
        }
        M(MODULE_NAME)->where("id=".$id)->setField("sort",$sort);
        //更新缓存
        clear_auto_cache("rule_list");
        load_auto_cache("rule_list");
        clear_auto_cache("iparule_list");
        load_auto_cache("iparule_list");
        clear_auto_cache("aliprule_list");
        load_auto_cache("aliprule_list");
        clear_auto_cache("wxrule_list");
        load_auto_cache("wxrule_list");
        save_log(l("SORT_SUCCESS"),1);
        $this->success(l("SORT_SUCCESS"),1);
    }

    public function set_effect()
    {
        $id = intval($_REQUEST['id']);
        $ajax = intval($_REQUEST['ajax']);
        $info = M(MODULE_NAME)->where("id=".$id)->getField("title");
        $c_is_effect = M(MODULE_NAME)->where("id=".$id)->getField("is_effect");  //当前状态
        $n_is_effect = $c_is_effect == 0 ? 1 : 0; //需设置的状态
        M(MODULE_NAME)->where("id=".$id)->setField("is_effect",$n_is_effect);
        //更新缓存
        clear_auto_cache("rule_list");
        load_auto_cache("rule_list");
        clear_auto_cache("iparule_list");
        load_auto_cache("iparule_list");
        clear_auto_cache("aliprule_list");
        load_auto_cache("aliprule_list");
        clear_auto_cache("wxrule_list");
        load_auto_cache("wxrule_list");
        save_log($info.l("SET_EFFECT_".$n_is_effect),1);
        clear_auto_cache("get_help_cache");
        $this->ajaxReturn($n_is_effect,l("SET_EFFECT_".$n_is_effect),1)	;
    }

}
?>