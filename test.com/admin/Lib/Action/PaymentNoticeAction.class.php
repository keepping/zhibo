<?php
// +----------------------------------------------------------------------
// | Fanwe 方维众筹商业系统
// +----------------------------------------------------------------------
// | Copyright (c) 2011 http://www.fanwe.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: 甘味人生(526130@qq.com)
// +----------------------------------------------------------------------

class PaymentNoticeAction extends CommonAction{
	public function index()
	{
        if(strim($_REQUEST['nick_name'])!=''){//name
            $user=M("User")->where("nick_name like '%".strim($_REQUEST['nick_name'])."%' ")->findAll();
            $user_arr_id = array();
            foreach($user as $k=>$v){
                $user_arr_id[$k] =intval($v['id']);
            }
            //$user_str_id = implode(',',$user_arr_id);
            $map['user_id'] = array('in',$user_arr_id);
        }

        $name=$this->getActionName();
        $model = D ($name);
        if($_REQUEST['payment_id']>0)
        {
            $payment_name = M("Payment")->where(" id = ".intval($_REQUEST['payment_id']))->getField("class_name");
            if($payment_name=="Wwxjspay"){
                $model = M ('UserRefund');
                $map['is_pay'] = 3;
                $map['withdrawals_type'] = 0;
                $is_cash = 1;
            }else{
                $map['payment_id'] = intval($_REQUEST['payment_id']);
                $map['is_paid'] = 1;
                $is_cash = 0;
            }
        }
		if (method_exists ( $this, '_filter' )) {
			$this->_filter ( $map );
		}

		if (! empty ( $model )) {
			$this->_list ( $model, $map );
		}
        $this->assign("is_cash",$is_cash);
		$this->display ();
		return;
	}
	
	public function delete() {
		//彻底删除指定记录
		$ajax = intval($_REQUEST['ajax']);
		$id = $_REQUEST ['id'];
		if (isset ( $id )) {
				$condition = array ('id' => array ('in', explode ( ',', $id ) ) );			
				$rel_data = M(MODULE_NAME)->where($condition)->findAll();				
				$list = M(MODULE_NAME)->where ( $condition )->delete();		
				
				foreach($rel_data as $data)
				{
					$info[] = "[单号:".$data['notice_sn']."]";						
				}
				if($info) $info = implode(",",$info);
				
				if ($list!==false) {
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
	

}
?>