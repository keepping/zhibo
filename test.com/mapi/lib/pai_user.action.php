<?php
// +----------------------------------------------------------------------
// | Fanwe 方维p2p借贷系统
// +----------------------------------------------------------------------
// | Copyright (c) 2011 http://www.fanwe.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: 云淡风轻(88522820@qq.com)
// +----------------------------------------------------------------------

class pai_userModule  extends baseModule
{
	

	//观众端竞拍支付管理页面
	function order(){
		//$root = array('status' => 1,'error'=>'',"data"=>array());
		//api_ajax_return($root);
		$this->virtual_order_details();
	}
	
	/**
	 * 观众-获得参与的竞拍列表
	 * int p  当前页
	 * int is_true  0虚拟产品 1真实产品
	 */
	public function goods(){
		$root = array('status' => 1,'error'=>'',"data"=>array(),"page_title"=>"我的竞拍");
		
		$page = intval($_REQUEST['p']);//取第几页数据
		$is_true = intval($_REQUEST['is_true']);
		$user_id = intval($GLOBALS['user_info']['id']);
		$page_size = intval($_REQUEST['page_size']);//取第几页数据
		
		if($user_id == 0){
			$root['status']=10007;
			$root['error']="请先登录";
			api_ajax_return($root);
		}
		
		if($page==0)$page = 1;
		
		if($page_size==0)$page_size=PAI_PAGE_SIZE;
		
		$rs = FanweServiceCall("pai_user","goods",array("user_id"=>$user_id,"is_true"=>$is_true,"page"=>$page,"page_size"=>$page_size));
		$data = array();
		$data['rs_count'] =  intval($rs['rs_count']);
		foreach($rs['list'] as $k=>$v){
			format_pai_goods($v);
			$rs['list'][$k] = $v;
		}
		
		$data['list'] =  $rs['list'];
		$data['page'] =  $rs['page'];
		
		$root['is_podcast'] = 0;
		$root['is_true'] = $is_true;
		$root['data'] = $data;
		
		if (intval($_REQUEST['ajax'])==1) {
			$GLOBALS['tmpl']->assign("data",$root);
			$request['html'] =$GLOBALS['tmpl']->fetch("/inc/ajax_pai_goods.html");
			$request['is_has'] =$data['page']['has_next'];
			api_ajax_return($request);
		}else{
			api_ajax_return($root);
		}
	}
	
	
	/**
	 * 竞拍的商品
	 * int id  商品ID
	 * int user_id  竞拍人id
	 * int get_joindata 是否获取user_id的参与记录
	 * int get_pailogs  是否获取竞拍记录
	 */
	public function goods_detail(){
		$root = array('status' => 1,'error'=>'',"data"=>array());
		$page = intval($_REQUEST['p']);//取第几页数据
		$pai_id = intval($_REQUEST['id']);
		$user_id = intval($GLOBALS['user_info']['id']);
		$get_joindata = intval($_REQUEST['get_joindata']);
		$get_pailogs = intval($_REQUEST['get_pailogs']);
		
		if($user_id == 0){
			$root['status']=10007;
			$root['error']="请先登录";
			api_ajax_return($root);
		}
		
		//查询商品信息
		$goodsinfo = FanweServiceCall("pai_user","p_goodsinfo",array("pai_id"=>$pai_id));
		
		if($pai_id==0||$goodsinfo['status']!=1){
			$root['status']=10008;
			$root['error']="竞拍商品不存在";
			api_ajax_return($root);
		}

		$data = array();
		if($page==0)$page = 1;
			$page_size=PAI_PAGE_SIZE;

		$rs = FanweServiceCall("pai_user","goods_detail",array("pai_id"=>$pai_id,"user_id"=>$user_id,"get_joindata"=>$get_joindata,"get_pailogs"=>$get_pailogs,"page"=>$page,"page_size"=>$page_size));
		if($rs['status']==1){
			format_pai_goods($rs['list']['info']);
			$data['info'] =  $rs['list']['info'];
			$data['has_join'] =  $rs['list']['has_join'];
			$data['join_data'] =  $rs['list']['join_data'];
			format_pai_logs($rs['list']['pai_list'],$rs['list']['info']['status']);
			$data['pai_list'] =  $rs['list']['pai_list'];
			$data['page'] =  $rs['page'];
			$data['rs_count'] =  $rs['rs_count'];
			
			if($data['join_data']==""){
				$data['join_data']=array();
			}
			if($data['pai_list']==""){
				$data['pai_list']=array();
			}
			
		}else{
			$root['status'] = intval($rs['status']);
			$root['error'] = $rs['error'];
		}
		
		$root['data'] = $data;
		api_ajax_return($root);
	}
	
	/**
	 * 参与竞拍提交保证金
	 * int id  商品ID
	 * consignee  string 收货人姓名
	 * consignee_mobile  string 收货人手机号
	 * consignee_district  json 区域JSON数据[虚拟非必需]
	 * consignee_address  string 地址[虚拟非必需]
	 */
	public function dojoin(){
	    $root = array('status' => 1,'error'=>'',"data"=>array());
		
		$pai_id = intval($_REQUEST['id']);
		$user_id = intval($GLOBALS['user_info']['id']);
		$consignee = strim($_REQUEST['consignee']);
		$consignee_mobile = strim($_REQUEST['consignee_mobile']);
		$consignee_district = strim($_REQUEST['consignee_district']);
		$consignee_address = strim($_REQUEST['consignee_address']);


		if($user_id == 0){
			$root['status']=10007;
			$root['error']="请先登录";
			api_ajax_return($root);
		}

		
		//查询商品信息
		$goodsinfo = FanweServiceCall("pai_user","p_goodsinfo",array("pai_id"=>$pai_id));
		
		if($pai_id==0||intval($goodsinfo['status'])!=1 || $goodsinfo['info']['status']!=0){
			$root['status']=10008;
			$root['error']="竞拍商品不存在";
			api_ajax_return($root);
		}
		//判段收货信息
		
		if($consignee==''){
			$root['status']=10017;
			$root['error']="姓名为空";
			api_ajax_return($root);
		}
			
		if($consignee_mobile==''){
			$root['status']=10018;
			$root['error']="手机号码为空";
			api_ajax_return($root);
		}
			
		if(strlen($consignee_mobile)<= 0|| strlen($consignee_mobile)>11  ||!check_mobile($consignee_mobile)){
			$root['status']=10019;
			$root['error']="手机号码格式错误";
			api_ajax_return($root);
		}
		
		if($goodsinfo['info']['is_ture']!=0){
			
			if($consignee_district==''){
				$root['status']=10034;
				$root['error']="区域数据错误";
				api_ajax_return($root);
			}
			
			if($consignee_address==''){
				$root['status']=10035;
				$root['error']="收货地址为空";
				api_ajax_return($root);
			}
			
		}
		
		$rs = FanweServiceCall("pai_user","dojoin",array("pai_id"=>$pai_id,"user_id"=>$user_id,"consignee"=>$consignee,"consignee_mobile"=>$consignee_mobile,"consignee_district"=>$consignee_district,"consignee_address"=>$consignee_address));
		
		if($rs['status']!=1){
			$root['status'] =$rs['status'];
			$root['error'] =$rs['error'];
		}else{
			$root['error'] ="缴纳保证金成功";
		}
		api_ajax_return($root);
	}
	
	
	/**
	 * 参与竞拍
	 * int id  商品ID
	 */
	public function dopai(){
		$root = array('status' => 1,'error'=>'',"data"=>array());

		$pai_id = intval($_REQUEST['id']);
		$pai_diamonds = intval($_REQUEST['pai_diamonds']);
		$user_id = intval($GLOBALS['user_info']['id']);

		if($user_id == 0){
			$root['status']=10007;
			$root['error']="请先登录";
			api_ajax_return($root);
		}
		
		//查询商品信息
		$goodsinfo = FanweServiceCall("pai_user","p_goodsinfo",array("pai_id"=>$pai_id,"condition"=>"status"));
		
		if($pai_id==0||$goodsinfo['status']!=1){
			$root['status']=10008;
			$root['error']="竞拍商品不存在";
			api_ajax_return($root);
		}
		
		$rs = FanweServiceCall("pai_user","dopai",array("pai_id"=>$pai_id,"pai_diamonds"=>$pai_diamonds,"user_id"=>$user_id));
		if($rs['status']==1){
			$root['data'] = $rs['data'];
			foreach($rs['date']['info'] as $k=>$v){
					    format_pai_goods($v);
					    $rs['date']['info'][$k] = $v;
			}
			$root['error'] ='出价成功';
		}else{
			$root['status'] =$rs['status'];
			if ($rs['status']==10052) {
				$root['error'] ='未提交保证金';
			}elseif ($rs['status']==10053) {
				$root['error'] ='竞拍失败,出价非最高价';
				//$root['data'] = $rs['data'];
				$root['pai_diamonds'] = $rs['data']['pai_diamonds'];
			}else{
				$root['error'] ='竞拍失败';
			}
			
			
		}
		api_ajax_return($root);
	}
	
	/**
	 * 参与竞拍人员列表
	 * int p  当前页
	 * int id  商品ID
	 */
	public function pailogs(){
		
		$root = array('status' => 1,'error'=>'',"data"=>array(),"page_title"=>"竞拍记录");
		
		$page = intval($_REQUEST['p']);//取第几页数据
		$pai_id = intval($_REQUEST['id']);
		$user_id = intval($GLOBALS['user_info']['id']);
		
		if($user_id == 0){
			$root['status']=10007;
			$root['error']="请先登录";
			api_ajax_return($root);
		}
		
		//查询商品信息
		$goodsinfo = FanweServiceCall("pai_user","p_goodsinfo",array("pai_id"=>$pai_id));
		
		if($pai_id==0||$goodsinfo['status']!=1){
			$root['status']=10008;
			$root['error']="竞拍商品不存在";
			api_ajax_return($root);
		}
		
		
		if($page==0)$page = 1;
		$page_size=PAI_PAGE_SIZE;
		
		$rs = FanweServiceCall("pai_user","pailogs",array("pai_id"=>$pai_id,"page"=>$page,"page_size"=>$page_size));
		//print_r("-rs-");echo "<hr/>";
		//print_r($rs);echo "<hr/>";
		/*foreach($rs['list'] as $k=>$v){
			foreach($rs['list'] as $k=>$v){
				if($k==0){
					$rs['list'][$k]['pai_status'] = "领先";
				}
				else{
					$rs['list'][$k]['pai_status'] = "出局";
				}
				
				//出价
				$rs['list'][$k]['total_diamonds'] = $v['pai_sort'] * $v['jj_diamonds']+$v['qp_diamonds'];
				
				$rs['list'][$k]['pai_date_format'] = to_date($v['pai_time'],"m.d H:i:s");

				if($v['status'] == 0){
					$rs['list'][$k]['status_format'] = "未支付"; 
				}
				elseif($v['status'] ==1){
					$rs['list'][$k]['status_format'] = "已支付"; 
				}
				elseif($v['status'] ==2){
					$rs['list'][$k]['status_format'] = "已流拍"; 
				}
				
			}
		}*/
		format_pai_logs($rs['list'],$rs['status']);
		
		$data['list'] =  $rs['list'];
		$data['page'] =  $page;
		
		$root['data'] = $data;
		api_ajax_return($root);
	}
	/**
	 * 参与竞拍的人 - 列表
	 * int id  商品ID
	 * int p  当前页
	 */
	public function joins(){
		$root = array('status' => 1,'error'=>'',"data"=>array());
		
		$pai_id = intval($GLOBALS['id']);
		$page = intval($_REQUEST['p']);//取第几页数据
		$page_size = PAI_PAGE_SIZE;

		//查询商品信息
		$goodsinfo = FanweServiceCall("pai_user","p_goodsinfo",array("pai_id"=>$pai_id));
		
		if($pai_id==0||$goodsinfo['status']!=1){
			$root['status']=10008;
			$root['error']="竞拍商品不存在";
			api_ajax_return($root);
		}
		
		
		if($page==0)$page = 1;
		
		
		$rs = FanweServiceCall("pai_user","joins",array("pai_id"=>$pai_id,"page"=>$page,"page_size"=>$page_size));
	
		$data['rs_count'] =  $rs['rs_count'];
		$data['list'] =  $rs['list'];
		$data['page'] =  $rs['page'];
		$root['data'] = $data;

		api_ajax_return($root);
		
	}
	/**
	 * 参与竞拍的人
	 *  int id  商品ID
	 *  int pai_status  -1所有状态 ，0 出局 1待付款 2排队中 3超时出局
	 */
	 function getjoin(){
	 	$root = array('status' => 1,'error'=>'',"data"=>array());
	 	
	 	$pai_id = intval($_REQUEST['id']);
		$user_id = intval($GLOBALS['user_info']['id']);
		$pai_status = intval($_REQUEST['pai_status']);

		if($user_id == 0){
			$root['status']=10007;
			$root['error']="请先登录";
			api_ajax_return($root);
		}
		
		//查询商品信息
		$goodsinfo = FanweServiceCall("pai_user","p_goodsinfo",array("pai_id"=>$pai_id));
		
		if($pai_id==0||$goodsinfo['status']!=1){
			$root['status']=10008;
			$root['error']="竞拍商品不存在";
			api_ajax_return($root);
		}
		
		
		$rs = FanweServiceCall("pai_user","getjoin",array("pai_id"=>$pai_id,"user_id"=>$user_id,"pai_status"=>$pai_status));

		if($rs['status']==1){
			$root['data'] = $rs['list'];
		}else{
			$root['status'] = intval($rs['status']);
		}

		api_ajax_return($root);
	 }
	 /**
	 * 支付单支付成功
	 * string $order_sn 要查询的订单编号
	 */
	function pay_diamonds(){
		$root = array('status' => 1);

		$order_sn = strim($_REQUEST['order_sn']);
		$user_id = intval($GLOBALS['user_info']['id']);

		if($user_id == 0){
			$root['status']=10007;
			$root['error']="请先登录";
			api_ajax_return($root);
		}

		if($order_sn==''){
			$root['status']=10037;
			$root['error']="订单已支付";
			api_ajax_return($root);
		}


		$rs = FanweServiceCall("pai_user","pay_diamonds",array("order_sn"=>$order_sn,"user_id"=>$user_id));
		if($rs['status']!=1){
			$root['status'] =$rs['status'];
			if ($root['status']==10037) {
				$root['error']="订单号错误";
			}elseif ($root['status']==10004){
				$root['error']="订单支付失败";
			}elseif ($root['status']==10054){
				$root['error']="订单已付款";
			}elseif ($root['status']==10062){
				$root['error']="金额不足,请先充值";
			}elseif ($root['status']==10063){
				$root['error']="订单已超时";
			}
		}else{
			$root['error']="付款成功";
			$root['is_true'] = $rs['is_true'];
			$root['order_sn']=$rs['order_sn'];
		}
		api_ajax_return($root);
	}
	 /*公众端查看虚拟订单详情
	  * 
	  */
	function virtual_order_details(){
		$root = array('status' => 1,'error'=>'',"data"=>array(),"page_title"=>"订单详情");
		
		//$podcast_id = intval($_REQUEST['viewer_id']);//购买人id
		$user_id = intval($GLOBALS['user_info']['id']);
		$order_sn = strim($_REQUEST['order_sn']);//要查询的订单编号
		$pai_id = intval($_REQUEST['pai_id']);//拍卖ID

		if($user_id == 0){
			$root['status']=10007;
			$root['error']="请先登录";
			api_ajax_return($root);
		}		
		if($order_sn==''){
			$root['status']='10037';
			$root['error']="订单号错误";
			api_ajax_return($root);
		}
		//查询商品信息
		$goodsinfo = FanweServiceCall("pai_user","p_goodsinfo",array("pai_id"=>$pai_id));
		
		if($pai_id==0||$goodsinfo['status']!=1){
			$root['status']=10008;
			$root['error']="竞拍商品不存在";
			api_ajax_return($root);
		}
		
		$rs = FanweServiceCall("pai_user","virtual_order_details",array("podcast_id"=>$user_id,"order_sn"=>$order_sn,"pai_id"=>$pai_id));
		if(isset($rs['consignee_district']) && $rs['consignee_district']!=""){
			$rs['consignee_district'] = json_decode($rs['consignee_district'],1);
			if($rs['consignee_district']==""){
				$rs['consignee_district'] = array();
			}
		}
		else{
			$rs['consignee_district'] = array();
		}
		
		foreach($rs['goods_list'] as $k=>$v){
			if ($v['goods_icon']!='') {
				$rs['goods_list'][$k]['goods_icon'] = json_decode($rs['goods_list'][$k]['goods_icon'],1);
				if ($v['goods_icon']=="") {
					$rs['goods_list'][$k]['goods_icon']=array();
				}else{
					foreach($rs['goods_list'][$k]['goods_icon'] as $k1=>$v1){
						//$rs['goods_list'][$k]['goods_icon'][$k1]=get_domain().APP_ROOT.$v1;		
						$rs['goods_list'][$k]['goods_icon'][$k1]=get_spec_image($v1);
					}
				}
			}else{
				$rs['goods_list'][$k]['goods_icon']=array();
			}
		}
		$goods_detail=FanweServiceCall("pai_podcast","goods_detail",array("podcast_id"=>$user_id,"order_sn"=>$order_sn,"pai_id"=>$pai_id));
		$goods=$goods_detail['info'];	
		format_pai_goods($goods);
		$rs['name']=$goods['name'];
		$rs['date_time']=$goods['date_time'];
		$rs['pay_time']=$goods['pay_time'];	
		$rs['status']=$goods['status'];
		//如果订单未付款，将付款时间改为0
		$rs['final_time']=$goods['final_time'];
		if ($rs['order_status']==6) {
			$rs['pay_time']=0;
			$rs['final_time']=0;
		}
		$rs['expire_date_time']=$goods['expire_date_time'];
		$rs['last_pai_diamonds']=$goods['last_pai_diamonds'];
		$rs['info_status']=$goods['info_status'];
		$rs['button_status']=$goods['button_status'];
		$rs['expire_time']=$goods['expire_time'];
		
		$rs['contact']=$goods['contact'];
		$rs['mobile']=$goods['mobile'];
		$rs['img']=$goods['img'];
		$rs['pai_id']=$pai_id;
		$rs['order_status_time']=$goods['order_status_time'];
		
		if (OPEN_GOODS==1) {
			$rs['shop_id']=$goods['shop_id'];
			$rs['shop_name']=$goods['shop_name'];
		}
		$rs['is_true']=$goods['is_true'];
		$root['data'] = $rs;
		api_ajax_return($root);
	}
	
	/*进入直播间-获取拍卖信息
	 *
	*/
	function get_video(){
		$root = array('status' => 1,'error'=>'',"data"=>array());
	
		$user_id = intval($GLOBALS['user_info']['id']);
		if($user_id == 0){
			$root['status']=10007;
			$root['error']="请先登录";
			api_ajax_return($root);
		}
		$pai_id = intval($_REQUEST['pai_id']);//拍卖ID
	
		if($pai_id == 0){
			$root['status']=10010;
			$root['error']="竞拍商品不存在";
			api_ajax_return($root);
		}

		$rs = FanweServiceCall("pai_user","get_video",array("pai_id"=>$pai_id,"user_id"=>$user_id));
		
		$root['status']=$rs['status'];
		format_pai_goods($rs['info']);		
		$data['info']=$rs['info'];		
		$data['buyer']=$rs['buyer'];		
		$data['join_data']=$rs['join_data'];
		$data['has_join']=$rs['has_join'];
		$root['data'] = $data;
		api_ajax_return($root);
	}
	
	//进入直播间参数
	function go_video(){
		$pai_id = intval($_REQUEST['pai_id']);
		$user_id = intval($GLOBALS['user_info']['id']);
		if($user_id == 0){
			$root['status']=10007;
			$root['error']="请先登录";
			api_ajax_return($root);
		}
		
		$video = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."video where  pai_id=".$pai_id);
		$pai_podcast = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."user where  id=".intval($video['user_id']));
		$data=array();
		if ($video) {
			
			$data['status']=1;
			$data['error']="";
			$data['roomId']=intval($video['id']);
			$data['groupId']=strim($video['group_id']);
			$data['createrId']=intval($video['user_id']);
			$data['loadingVideoImageUrl']= get_spec_image(strim($pai_podcast['head_image']));
			$data['video_type']= intval($video['video_type']);
			
		}else{
			$data['status']=0;
			$data['error']="直播间已关闭";
			$data['roomId']=0;
			$data['groupId']="";
			$data['createrId']=0;
			$data['loadingVideoImageUrl']= "";
			$data['video_type']= 0;
		}
		api_ajax_return($data);		
		
	}
	
	//测试 
	function test(){
		$id = 2;
		$rs = FanweServiceCall("pai_user","test",array("id"=>$id));
		print_r($rs);exit;
	}
	
}