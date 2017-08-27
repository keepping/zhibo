<?php
// +----------------------------------------------------------------------
// | Fanwe 方维众筹商业系统
// +----------------------------------------------------------------------
// | Copyright (c) 2011 http://www.fanwe.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: 甘味人生(526130@qq.com)
// +----------------------------------------------------------------------

class IosRechargeRuleAction extends CommonAction{
    public function index()
    {
        parent::index();
    }
    public function add()
    {
        $this->display();
    }
    public function edit() {
        $id = intval($_REQUEST ['id']);
        $condition['id'] = $id;
        $vo = M(MODULE_NAME)->where($condition)->find();
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
            if ($list!==false) {
                save_log(l("FOREVER_DELETE_SUCCESS"),1);
                $this->success (l("FOREVER_DELETE_SUCCESS"),$ajax);
            } else {
                save_log(l("FOREVER_DELETE_FAILED"),0);
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

        if(check_empty($data['gift_diamonds'])&&intval($data['gift_diamonds'])!=0&&!(intval($data['gift_diamonds'])>0))
        {
            $this->error("价格必须大于0");
        }
        // 更新数据
        $list=M(MODULE_NAME)->add($data);
        if (false !== $list) {
            //成功提示
            save_log(L("INSERT_SUCCESS"),1);
            $this->success(L("INSERT_SUCCESS"));
        } else {
            //错误提示
            save_log(L("INSERT_FAILED"),0);
            $this->error(L("INSERT_FAILED"));
        }
    }

    public function update() {
        B('FilterString');
        $data = M(MODULE_NAME)->create ();


        //开始验证有效性
        $this->assign("jumpUrl",u(MODULE_NAME."/edit",array("id"=>$data['id'])));
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

        if(check_empty($data['gift_diamonds'])&&intval($data['gift_diamonds'])!=0&&!(intval($data['gift_diamonds'])>0))
        {
            $this->error("赠送钻石必须大于0");
        }

        $list=M(MODULE_NAME)->save ($data);
        if (false !== $list) {
            //成功提示
            save_log(L("UPDATE_SUCCESS"),1);
            $this->success(L("UPDATE_SUCCESS"));
        } else {
            //错误提示
            save_log(L("UPDATE_FAILED"),0);
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
        save_log(l("SORT_SUCCESS"),1);
        $this->success(l("SORT_SUCCESS"),1);
    }

    public function set_effect()
    {
        $id = intval($_REQUEST['id']);
        $ajax = intval($_REQUEST['ajax']);
        $info = M(MODULE_NAME)->where("id=".$id)->getField("name");
        $c_is_effect = M(MODULE_NAME)->where("id=".$id)->getField("is_effect");  //当前状态
        $n_is_effect = $c_is_effect == 0 ? 1 : 0; //需设置的状态
        M(MODULE_NAME)->where("id=".$id)->setField("is_effect",$n_is_effect);
        save_log($info.l("SET_EFFECT_".$n_is_effect),1);
        clear_auto_cache("get_help_cache");
        $this->ajaxReturn($n_is_effect,l("SET_EFFECT_".$n_is_effect),1)	;
    }

}
?>