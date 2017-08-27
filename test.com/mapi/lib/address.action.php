<?php

class addressModule  extends baseModule
{

    /**
     * 收货地址列表
     */
    public function address(){

        $user_id = intval($GLOBALS['user_info']['id']);
        if($user_id == 0){
            $root['status']=10007;
            $root['error']="请先登录";
            api_ajax_return($root);
        }

        $root = array('status' => 1,'error'=>'','data'=>array());
        $page = intval($_REQUEST['p']);//当前页

        if($page==0)$page = 1;
        $page_size=PAI_PAGE_SIZE;

        $data  = array();
        if(OPEN_GOODS == 1){

            //显示调用结果
            $addressdetail = get_user_addressdetail($user_id);
            if($addressdetail['consignee_address'] == ''){
                $data['rs_count'] = 0;
                $data['list'] = array();
            }else{
                $data['rs_count'] = 1;
                $list =array();
                $list[]=$addressdetail;
                $data['list'] = $list;
            }

            $page = array();
            $page['page'] = 1;
            $page['has_next']= 0;
            $data['page'] = $page;
            $data['url'] = go_h5($user_id,'http://gw1.yimile.cc/Web/Shopping/EditAddress.aspx');
            $root['data'] =  $data;

        }else{
            $rs = FanweServiceCall("address","address",array("user_id"=>$user_id,"page"=>$page,"page_size"=>$page_size));
            $data['rs_count'] = intval($rs['rs_count']);
            $data['list'] = $rs['list'];
            $data['page'] = $rs['page'];

            $root['data'] =  $data;
        }

        api_ajax_return($root);
    }

    /**
     * 添加收货地址
     * $data = array("user_id"=>$user_id,"consignee"=>$consignee,"consignee_mobile"=>$consignee_mobile,"consignee_district"=>$consignee_district,"consignee_address"=>$consignee_address,"is_default"=>$is_default);
     * return array("status"=>$status,"data"=>$data);
     */
    public function addaddress(){
        $root = array('status' => 1,'error'=>'','data'=>array());
        $user_id = intval($GLOBALS['user_info']['id']);
        if($user_id == 0){
            $root['status']=10007;
            $root['error']="请先登录";
            api_ajax_return($root);
        }
        $data['user_id'] = $user_id;
        $data['consignee'] = trim($_REQUEST['consignee']);
        $data['consignee_mobile'] = trim($_REQUEST['consignee_mobile']);
        $data['consignee_address'] = trim($_REQUEST['consignee_address']);
        $data['is_default'] = intval($_REQUEST['is_default']);
        $data['consignee_district'] = trim($_REQUEST['consignee_district']);
        /*$consignee_district['province'] = trim($_REQUEST['province']);
         $consignee_district['city'] = trim($_REQUEST['city']);
        $consignee_district['area'] = trim($_REQUEST['area']);
        $consignee_district['zip'] = trim($_REQUEST['zip']);
        $consignee_district['lng'] = trim($_REQUEST['lng']);
        $consignee_district['lat'] = trim($_REQUEST['lat']);
        $data['consignee_district'] = json_encode($consignee_district);*/

        $rs = FanweServiceCall("address","addaddress",$data);

        if(intval($rs['status'])!=1){
            $root['status'] = intval($rs['status']);
            if($root['status']==10009){
                $root['error']="主播不存在";
            }elseif($root['status']==10017){
                $root['error']="姓名为空";
            }
            elseif($root['status']==10018){
                $root['error']="手机号码为空";
            }
            elseif($root['status']==10019){
                $root['error']="手机号码格式错误";
            }
            elseif($root['status']==10015){
                $root['error']="添加收货地址失败";
            }else{
                $root['error']="收货人名称过长";
            }
        }else{
            $root['data'] = $rs['data'];
        }

        api_ajax_return($root);
    }

    /**
     * 编辑收货地址
     * $data = array("id"=>$id,"user_id"=>$user_id,"consignee"=>$consignee,"consignee_mobile"=>$consignee_mobile,"consignee_district"=>$consignee_district,"consignee_address"=>$consignee_address,"is_default"=>$is_default);
     * return array("status"=>$status,"data"=>$data);
     */
    public function editaddress(){
        $root = array('status' => 1,'error'=>'','data'=>array());
        $user_id = intval($GLOBALS['user_info']['id']);
        if($user_id == 0){
            $root['status']=10007;
            $root['error']="请先登录";
            api_ajax_return($root);
        }
        
        $data['id'] = intval($_REQUEST['id']);
        $data['user_id'] = $user_id;
        $data['consignee'] = trim($_REQUEST['consignee']);
        $data['consignee_mobile'] = trim($_REQUEST['consignee_mobile']);
        $data['consignee_address'] = trim($_REQUEST['consignee_address']);
        $data['is_default'] = intval($_REQUEST['is_default']);
        $data['consignee_district'] = trim($_REQUEST['consignee_district']);
        /*$consignee_district['province'] = trim($_REQUEST['province']);
        $consignee_district['city'] = trim($_REQUEST['city']);
        $consignee_district['area'] = trim($_REQUEST['area']);
        $consignee_district['zip'] = trim($_REQUEST['zip']);
        $consignee_district['lng'] = trim($_REQUEST['lng']);
        $consignee_district['lat'] = trim($_REQUEST['lat']);
        $data['consignee_district'] = json_encode($consignee_district);*/
        if(json_decode($_REQUEST['consignee_district'],1) == ''){
            $district = explode(" ",$_REQUEST['consignee_district']);
//            $sheng = $district[0].'省';
//            $shi = $district[1].'市';
//            $qu = $district[2];
//            if($district[2] == ''){
//                $sheng = '';
//                $shi = $district[0].'市';
//                $qu = $district[1];
//            }
            $sheng = $district[0];
            $shi = $district[1];
            $qu = $district[2];
            if($district[2] == ''){
                $sheng = '';
                $shi = $district[0];
                $qu = $district[1];
            }
            $consignee_district = array('province'=>$sheng,'city'=>$shi,'area'=>$qu);
            $data['consignee_district'] = json_encode($consignee_district,JSON_UNESCAPED_UNICODE);
        }

        $rs = FanweServiceCall("address","editaddress",$data);

        if(intval($rs['status'])!=1){
            $root['status'] = intval($rs['status']);
            if($root['status']==10009){
                $root['error']="主播不存在";
            }elseif($root['status']==10017){
                $root['error']="姓名为空";
            }
            elseif($root['status']==10018){
                $root['error']="手机号码为空";
            }
            elseif($root['status']==10019){
                $root['error']="手机号码格式错误";
            }
            elseif($root['status']==10020){
                $root['error']="编辑收货地址失败";
            }else{
                $root['error']="收货人名称过长";
            }
        }else{
            $root['data'] = $rs['data'];
        }

        api_ajax_return($root);
    }

    /**
     * 删除收货地址
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

        $rs = FanweServiceCall("address","del",$data);

        if(intval($rs['status'])!=1){
            $root['status'] = intval($rs['status']);
            if($root['status']==10016){
                $root['error']="删除收货地址失败";
            }
        }
        api_ajax_return($root);
    }

    /**
     * 设置默认地址
     * $data = array("id"=>$id,"user_id"=>$user_id);
     * return array("status"=>$status);
     */
    public function setdefault(){

        $root = array('status' => 1,'error'=>'');
        $user_id = intval($GLOBALS['user_info']['id']);
        if($user_id == 0){
            $root['status']=10007;
            $root['error']="请先登录";
            api_ajax_return($root);
        }
        $data['id'] = intval($_REQUEST['id']);
        $data['user_id'] = $user_id;

        $rs = FanweServiceCall("address","setdefault",$data);

        if(intval($rs['status'])!=1){
            $root['status'] = intval($rs['status']);
            if($root['status']==10024){
                $root['error']="设置默认收货地址失败";
            }
        }
        api_ajax_return($root);
    }
    
    
    public function test1(){
    	 
    	$data['url'] = go_h5(517,'http://gw1.yimile.cc/Web/Shopping/EditAddress.aspx');
    	api_ajax_return($data['url']);
    	 
    }

}

?>