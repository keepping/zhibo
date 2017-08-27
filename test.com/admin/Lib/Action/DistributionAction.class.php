<?php
// +----------------------------------------------------------------------
// | Fanwe 方维众筹商业系统
// +----------------------------------------------------------------------
// | Copyright (c) 2011 http://www.fanwe.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: 甘味人生(526130@qq.com)
// +----------------------------------------------------------------------

class DistributionAction extends AuthAction{
	//首页
    public function index(){
        if (trim($_REQUEST['nick_name'])!='') {
            $where = " and u2.nick_name like '%".trim($_REQUEST['nick_name'])."%'";
        }
        if(trim($_REQUEST['mobile'])!='')
        {

            $where .= "and u2.mobile like '%".trim($_REQUEST['mobile'])."%'";
        }

        if(intval($_REQUEST['id'])!='')
        {

            $where .= "and u2.id =".intval($_REQUEST['id'])."";
        }
        $sql = "select u1.id,u1.diamonds,u1.use_diamonds,u1.ticket,u1.mobile,u1.p_user_id,u1.nick_name,u2.id as p_id,u2.nick_name as p_user_name from ".DB_PREFIX."user as u1 
                left join ".DB_PREFIX."user as u2 on u2.id=u1.p_user_id where u1.p_user_id>0 $where";
        $model = M('user');
        $user_list  = $model->query($sql);
        $user = array();

        foreach($user_list as $k=>$v){
            $payment_sql = "select sum(money) as total_payment from ".DB_PREFIX."payment_notice where is_paid=1 and user_id=".$v['id'];
            $total_payment = $GLOBALS['db']->getOne($payment_sql);

            if(empty($user[$v['p_user_id']]['id'])){
                $user[$v['p_user_id']]['id'] = $v['p_user_id'];
                $user[$v['p_user_id']]['nick_name'] = $v['p_user_name'];
                $user[$v['p_user_id']]['total_payment'] = 0;
            }
            $user[$v['p_user_id']]['list'][] = array(
                    'id'           => $v['id'],
                    'nick_name'    => $v['nick_name'],
                    'diamonds'     => $v['diamonds'],
                    'use_diamonds' => $v['use_diamonds'],
                    'ticket'       => $v['ticket'],
                    'mobile'       => $v['mobile']
                );
            $user[$v['p_user_id']]['total_payment'] += floatval($total_payment);
        }

        $this->assign('user_list',$user);
        $this->display();
    }
}
?>