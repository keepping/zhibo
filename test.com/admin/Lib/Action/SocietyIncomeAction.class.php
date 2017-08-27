<?php

class SocietyIncomeAction extends CommonAction
{
    public function index(){
        $m_config = load_auto_cache('m_config');
        $rate = floatval($m_config['society_public_rate']);

        $id = intval($_REQUEST['id']);
        $name = $_REQUEST['name'];

        $model = M('society');
        $field = 's.id,s.name,s.logo,s.memo,s.status,(u.ticket-u.refund_ticket) as ticket,((u.ticket-u.refund_ticket)*s.refund_rate) as money';
        $table = DB_PREFIX."society as s,".DB_PREFIX."user as u";
        $where = 's.user_id=u.id and s.status<>0 and s.status<>4 ';
        $parameter = 's.user_id=u.id';
        if ($id > 0){
            $where .= ' and s.id='.$id;
            $parameter .= '&s.id='.$id;
        }
        if ($name != ''){
            $where .= ' and s.name like \'%' . addslashes($name) . '%\'';
            $parameter .= '&s.name like \'%' . addslashes($name) . '%\'';
        }
        $count = $model->table($table)->where($where)->count();
        if ($count){
            $sql = "select $field from $table where $where order by s.id";
            $list = $this->_Sql_list($model,$sql,'&'.$parameter,'',0);

            foreach ($list as $key => $value){
                $list[$key]['logo']  = get_spec_image($value['logo']);
            }
        }
        $this->assign('list', $list);

        $this->display();
    }

    public function submit_refund(){
        $id = intval($_REQUEST['id']);
        if ($id == 0){
            $this->error("id不能为空");
        }

        $society_info = M('society')->where("id=".$id)->field('user_id,name,refund_rate')->find();
        $refund_exist = M('user_refund')->where('user_id='.$society_info['user_id']." and (is_pay =0 or is_pay=1)")->getField('id');
        if ($refund_exist){
            $this->error("这个公会还有未处理的提现");
        }
        //若公会有设置提现比例，则使用设置的提现比例；若没有，使用平台统一的公会提现比例。
        if(floatval($society_info['refund_rate']) > 0){
            $rate = floatval($society_info['refund_rate']);
        }else{
            $m_config = load_auto_cache("m_config");//初始化手机端配置
            $rate = floatval($m_config['society_public_rate']);
        }

        $user_info = M('user')->where('id='.$society_info['user_id'])->field('ticket,refund_ticket')->find();
        $refund_data = array();
        $refund_data['ticket'] = $user_info['ticket'] - $user_info['refund_ticket'];
        if ($refund_data['ticket'] > 0){
            $refund_data['money'] = floatval($refund_data['ticket'] * $rate);
            $refund_data['user_bank_id'] = -1;
            $refund_data['user_id'] = $society_info['user_id'];
            $refund_data['create_time'] = NOW_TIME;
            $refund_data['memo'] = $society_info['name']."公会提现";
            $refund_data['withdrawals_type'] = 2;
            $refund_data['is_pay'] = 0;
            $res = M('user_refund')->add($refund_data);
            if ($res){
                save_log($society_info['name'] . "公会提现" . $refund_data['money'] . "成功", 1);
                $this->success("提现成功");
            }else{
                save_log($society_info['name'] . "公会提现" . $refund_data['money'] . "失败", 0);
                $this->success("提现失败");
            }
        }else{
            $this->error("此公会可提现余额为0");
        }

    }

}

