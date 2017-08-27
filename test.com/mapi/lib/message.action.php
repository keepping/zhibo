<?php

class messageModule  extends baseModule
{

    /**
     * 消息列表
     * $data = array("user_id"=>$user_id,"page"=>$page,"page_size"=>$page_size);
     * return array("rs_count"=>$rs_count,"list"=>$list,"page"=>$page);
     */
    public function getlist(){

        $root = array('status' => 1,'error'=>'','data'=>array());

        $page = intval($_REQUEST['p']);//当前页

        $user_id = intval($GLOBALS['user_info']['id']);
        if($user_id == 0){
            $root['status']=10007;
            $root['error']="请先登录";
            api_ajax_return($root);
        }

        if($page==0)$page = 1;
        $page_size=PAI_PAGE_SIZE;

        $rs = FanweServiceCall("message","getlist",array("user_id"=>$user_id,"page"=>$page,"page_size"=>$page_size));

        $data = array();
        $data['rs_count'] = intval($rs['rs_count']);
        $data['list'] = $rs['list'];
        $data['page'] = $rs['page'];

        $root['data'] = $data;
        api_ajax_return($root);
    }

    /**
     * 消息推送
     * $data = array("send_type"=>$send_type,"user_ids"=>$user_ids,"send_user_id"=>$send_user_id,"send_status"=>$send_status,"content"=>$content);
     * return array("status"=>$status);
     */
    public function send(){
        $root = array('status' => 1,'error'=>'');
        $user_id = intval($GLOBALS['user_info']['id']);
        if($user_id == 0){
            $root['status']=10007;
            $root['error']="请先登录";
            api_ajax_return($root);
        }
        $data['send_user_id'] = $user_id;
        $data['send_type'] = trim($_REQUEST['send_type']);
        $data['user_ids'] = $_REQUEST['user_ids'];
        $data['send_status'] = intval($_REQUEST['send_status']);
        $data['content'] = trim($_REQUEST['content']);

        $rs = FanweServiceCall("message","send",$data);

        if(intval($rs['status'])!=1){
            $root['status'] = intval($rs['status']);
            if($root['status']==10021){
                $root['error']="消息类型为空";
            }elseif($root['status']==10033){
                $root['error']="推送会员为空";
            }elseif($root['status']==10022){
                $root['error']="消息推送失败";
            }
        }

        api_ajax_return($root);
    }

    /**
     * 删除消息
     * $data = array("id"=>$id,"user_id"=>$user_id);
     * return array("status"=>$status);
     */
    public function del(){
        $root = array('status' => 1,'error'=>'');
        $user_id = intval($GLOBALS['user_info']['id']);
        if($user_id == 0){
            $root['status']=10007;
            $root['error']="请先登录";
            api_ajax_return($root);
        }
        $data['id'] = intval($_REQUEST['id']);
        $data['user_id'] = $user_id;

        $rs = FanweServiceCall("message","del",$data);

        if(intval($rs['status'])!=1){
            $root['status'] = intval($rs['status']);
            if($root['status']==10023){
                $root['error']="消息删除失败";
            }
        }
        api_ajax_return($root);
    }

    /**
     * IM推送服务
     * $data = array("pai_id"=>$pai_id,"page"=>$page,"page_size"=>$page_size);
     * return array("rs_count"=>$rs_count,"info"=>$info,"joins"=>$joins,"page"=>$page);
     */
    public function paiinfo(){

        $root = array('status' => 1,'error'=>'','data'=>array());

        $page = intval($_REQUEST['p']);//当前页

        $user_id = intval($GLOBALS['user_info']['id']);
        if($user_id == 0){
            $root['status']=10007;
            $root['error']="请先登录";
            api_ajax_return($root);
        }
        $pai_id = intval($_REQUEST['pai_id']);
        if(!$pai_id){
            $root['status']=10008;
            $root['error']="竞拍商品不存在";
            api_ajax_return($root);
        }

        if($page==0)$page = 1;
        $page_size=PAI_PAGE_SIZE;

        $rs = FanweServiceCall("message","paiinfo",array("pai_id"=>$pai_id,"page"=>$page,"page_size"=>$page_size));

        if(!$rs['info']){
            $root['status']=10008;
            $root['error']="竞拍商品不存在";
            api_ajax_return($root);
        }
        $data = array();
        format_pai_goods($rs['info'],1);
        $data['info'] = $rs['info'];
        $data['joins'] = $rs['joins'];
        $data['page'] = $rs['page'];
        $data['rs_count'] =  intval($rs['rs_count']);

        $root['data'] = $data;
        api_ajax_return($root);
    }

    /**
     * 消息内容
     * $data = array("user_id"=>$user_id,"id"=>$id);
     * return array("status"=>$status,"data"=>$data);
     */
    public function info(){
        $root = array('status' => 1,'error'=>'','data'=>array());
        $status = 1;
        $user_id = intval($GLOBALS['user_info']['id']);
        if($user_id == 0){
            $root['status']=10007;
            $root['error']="请先登录";
            api_ajax_return($root);
        }
        $id = intval($_REQUEST['id']);
        if(!$id){
            $root['status']=10001;
            $root['error']="消息内容不存在";
            api_ajax_return($root);
        }

        $rs = FanweServiceCall("message","info",array("user_id"=>$user_id,"id"=>$id));

        if(intval($rs['status'])!=1){
            $root['status'] = intval($rs['status']);
            if($root['status'] == 10001){
                $root['error']="消息内容不存在";
            }
        }else{
            $rs['data']['create_time_format'] = to_date($rs['data']['create_time'],'m-d H:i:s');
            $root['data'] = $rs['data'];
        }

        api_ajax_return($root);
    }

}

?>