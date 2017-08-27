<?php
// +----------------------------------------------------------------------
// | Fanwe 方维p2p借贷系统
// +----------------------------------------------------------------------
// | Copyright (c) 2011 http://www.fanwe.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: 云淡风轻(88522820@qq.com)
// +----------------------------------------------------------------------

class pai_podcastModule  extends baseModule
{



	//主播端竞拍列表管理
	function goods(){
		$this->index();
	}
	//主播端竞拍支付管理页面
	function order(){
		//$root = array('status' => 1,'error'=>'',"data"=>array());
		//api_ajax_return($root);
		$this->virtual_order_details();
	}
	/**
	 * 我的竞拍列表
	 * int p  当前页
	 * int istrue  0虚拟产品 1真实产品
	 */
	public function index(){
		$root = array('status' => 1,'error'=>'',"data"=>array(),"page_title"=>"竞拍管理");

		$page = intval($_REQUEST['p']);//取第几页数据
		$is_true = intval($_REQUEST['is_true']);
		$page_size = intval($_REQUEST['page_size']);//取第几页数据
		if($page_size==0)$page_size=PAI_PAGE_SIZE;

		$user_id = intval($GLOBALS['user_info']['id']);
		if($user_id == 0){
			$root['status']=10007;
			$root['error']="请先登录";
			api_ajax_return($root);
		}

		if($page==0)$page = 1;
		$rs = FanweServiceCall("pai_podcast","goods",array("podcast_id"=>$user_id,"is_true"=>$is_true,"page"=>$page,"page_size"=>$page_size));

		$m_config =  load_auto_cache("m_config");//手机端配置
		$ticket_name = $m_config['ticket_name'];
		foreach($rs['list'] as $k=>$v){
			format_pai_goods($v,1);
			$rs['list'][$k] = $v;
			$rs['list'][$k]['mark'] = $ticket_name;
		}

		$data = array();
		$data['rs_count'] =  intval($rs['rs_count']);
		$data['list'] = $rs['list'] ;
		$data['page'] =  $rs['page'];

		$root['is_podcast'] = 1;
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
	 * 添加竞拍
	 * int istrue  0虚拟产品 1真实产品
	 * int $goods_id  真实产品  商品ID 不为空
	 */
	public function addpai(){

		$user_id = intval($GLOBALS['user_info']['id']);
		if($user_id == 0){
			$root['status']=10007;
			$root['error']="请先登录";
			api_ajax_return($root);
		}

		$pai_goods=array();
		$pai_goods['podcast_id']  = $user_id;
		$pai_goods['is_true']  = intval($_REQUEST['is_true']);
		$pai_goods['goods_id']  = intval($_REQUEST['goods_id']);

		//$pai_goods['imgs']  = $this->imgs(json_decode($_REQUEST['imgs']),$user_id);
		//$pai_goods['imgs'] = json_encode($pai_goods['imgs']);

		$imgs = json_decode($_REQUEST['imgs']);

		$result_imgs=array();
		foreach($imgs as $k=>$v){
			$result_imgs[]=$v;
		}
		$pai_goods['imgs'] = json_encode($result_imgs);

		$pai_goods['tags']  = strim($_REQUEST['tags']);
		$pai_goods['name']  = strim($_REQUEST['name']);
		$pai_goods['description']  = strim($_REQUEST['description']);
		$pai_goods['date_time']  = strim($_REQUEST['date_time']);
		$pai_goods['place']  = strim($_REQUEST['place']);
		$pai_goods['district']  = strim($_REQUEST['district']);
		$pai_goods['contact']  = strim($_REQUEST['contact']);
		$pai_goods['mobile']  = strim($_REQUEST['mobile']);
		$pai_goods['qp_diamonds']  = intval($_REQUEST['qp_diamonds']);
		$pai_goods['bz_diamonds']  = intval($_REQUEST['bz_diamonds']);
		$pai_goods['jj_diamonds']  = intval($_REQUEST['jj_diamonds']);
		$pai_goods['pai_time']  = floatval($_REQUEST['pai_time']);
		$pai_goods['pai_yanshi']  = intval($_REQUEST['pai_yanshi']);
		$pai_goods['max_yanshi']  = intval($_REQUEST['max_yanshi']);

		if(OPEN_GOODS == 1){
			$pai_goods['shop_id'] = intval($_REQUEST['shop_id']);
			$pai_goods['shop_name'] = strim($_REQUEST['shop_name']);
		}

		$time=NOW_TIME;
		$date_time = strtotime(strim($_REQUEST['date_time']))-28800;
		$pai_time = $_REQUEST['pai_time']*3600;

		$pai_goods['end_time'] = intval($pai_time+$time);

		if($pai_goods['is_true'] && $pai_goods['goods_id']==0){
			$root['status']=10008;
			$root['error']="商品不存在";
			api_ajax_return($root);
		}

		if(!$pai_goods['is_true']){

			if($pai_goods['name']==''){
				$root['status']=10038;
				$root['error']="名称不能为空";
				api_ajax_return($root);
			}elseif($pai_goods['description']==''){
				$root['status']=10039;
				$root['error']="描述不能为空";
				api_ajax_return($root);
			}elseif($pai_goods['date_time']==''){
				$root['status']=10040;
				$root['error']="时间不能为空";
				api_ajax_return($root);
			}elseif($date_time < ($time + $pai_time)){
				$root['status']=10056;
				$root['error']="约会时间早于竞拍完成时间，请重新选择约会时间";
				api_ajax_return($root);
			}elseif($pai_goods['place']==''){
				$root['status']=10041;
				$root['error']="地点不能为空";
				api_ajax_return($root);
			}elseif($pai_goods['contact']==''){
				$root['status']=10042;
				$root['error']="联系人不能为空";
				api_ajax_return($root);
			}elseif(strlen($pai_goods['mobile'])<= 0 || strlen($pai_goods['mobile'])>11  ||!check_mobile($pai_goods['mobile'])){
				$root['status']=10043;
				$root['error']="请输入正确的联系电话";
				api_ajax_return($root);
			}elseif($pai_goods['qp_diamonds']==0){
				$root['status']=10044;
				$root['error']="竞拍价格不能为0";
				api_ajax_return($root);
			}elseif($pai_goods['district']==''){
				$root['status']=10034;
				$root['error']="区域数据错误";
				api_ajax_return($root);
			}
		}else{
			if($pai_goods['imgs']==''){
				$root['status']=10038;
				$root['error']="图片不能为空";
				api_ajax_return($root);
			}elseif($pai_goods['goods_id']==0){
				$root['status']=10044;
				$root['error']="请选择商品";
				api_ajax_return($root);
			}


		}

		/*if($pai_goods['bz_diamonds']==0){
		 $root['status']=10008;
		$root['error']="";
		api_ajax_return($root);
		}else*/
		if($pai_goods['jj_diamonds']==0){
			$root['status']=10045;
			$root['error']="每次加价幅度不能为0";
			api_ajax_return($root);
		}elseif($pai_goods['pai_time']==0){
			$root['status']=10046;
			$root['error']="竞拍时长不能为0";
			api_ajax_return($root);
		}elseif($pai_goods['pai_yanshi']==0){
			$root['status']=10047;
			$root['error']="每次竞拍延时不能为0";
			api_ajax_return($root);
		}elseif($pai_goods['max_yanshi']==0){
			$root['status']=10048;
			$root['error']="最大延时不能为0";
			api_ajax_return($root);
		}


		$rs = FanweServiceCall("pai_podcast","addpai",$pai_goods);
		if ($rs['status']==1) {
			/*$data = array();
			$data['info'] =  $rs['data']['info'];
			$data['pai_list'] =  $rs['data']['pai_list'];
			$data['page'] =  $rs['data']['page'];
			$root['data'] = $data;
			*/
			api_ajax_return($rs);
		}if ($rs['status']==10049) {
			$root['status']=10049;
			$root['error']="存在未完成的竞拍，创建竞拍失败";
			api_ajax_return($root);

		}elseif($rs['status']==10025) {

			$root['status']=10025;
			$root['error']="创建竞拍失败";
			api_ajax_return($root);
		}elseif($rs['status']==10055) {

			$root['status']=10055;
			$root['error']="直播间已关闭，无法创建竞拍";
			api_ajax_return($root);
		}elseif($rs['status']==10010) {

			$root['status']=10010;
			$root['error']="竞拍商品不存在";
			api_ajax_return($root);
		}elseif ($root['status']==0){
			$root['status']=0;
			$root['error']="您已被永久禁言";
			api_ajax_return($root);
		}


	}

	/**
	 * 编辑竞拍
	 */
	public function editpai(){

		$user_id = intval($GLOBALS['user_info']['id']);
		if($user_id == 0){
			$root['status']=10007;
			$root['error']="请先登录";
			api_ajax_return($root);
		}

		$pai_goods=array();
		if(intval($pai_goods['podcast_id'])  == $user_id){

			$pai_goods['is_true']  = intval($_REQUEST['is_true']);
			$pai_goods['goods_id']  = intval($_REQUEST['goods_id']);

			$pai_goods['imgs']  = $this->imgs(json_decode($_REQUEST['imgs']),$user_id);
			$pai_goods['imgs'] = json_encode($pai_goods['imgs']);

			$pai_goods['tags']  = strim($_REQUEST['tags']);
			$pai_goods['name']  = strim($_REQUEST['name']);
			$pai_goods['description']  = strim($_REQUEST['description']);
			$pai_goods['date_time']  = strim($_REQUEST['date_time']);
			$pai_goods['place']  = strim($_REQUEST['place']);
			$pai_goods['district']  = strim($_REQUEST['district']);
			$pai_goods['contact']  = strim($_REQUEST['contact']);
			$pai_goods['mobile']  = strim($_REQUEST['mobile']);
			$pai_goods['qp_diamonds']  = intval($_REQUEST['qp_diamonds']);
			$pai_goods['bz_diamonds']  = intval($_REQUEST['bz_diamonds']);
			$pai_goods['jj_diamonds']  = intval($_REQUEST['jj_diamonds']);
			$pai_goods['pai_time']  = floatval($_REQUEST['pai_time']);
			$pai_goods['pai_yanshi']  = intval($_REQUEST['pai_yanshi']);
			$pai_goods['max_yanshi']  = intval($_REQUEST['max_yanshi']);

		}else{

			$root['status']=10008;
			$root['error']="商品不存在";
			api_ajax_return($root);
		}

		if($pai_goods['is_true'] && $pai_goods['goods_id']==0){
			$root['status']=10008;
			$root['error']="商品不存在";
			api_ajax_return($root);
		}
		if(!$pai_goods['is_true']){

			if($pai_goods['name']==''){
				$root['status']=10038;
				$root['error']="名称不能为空";
				api_ajax_return($root);
			}elseif($pai_goods['description']==''){
				$root['status']=10039;
				$root['error']="描述不能为空";
				api_ajax_return($root);
			}elseif($pai_goods['date_time']==''){
				$root['status']=10040;
				$root['error']="时间不能为空";
				api_ajax_return($root);
			}elseif($pai_goods['place']==''){
				$root['status']=10041;
				$root['error']="地点不能为空";
				api_ajax_return($root);
			}elseif($pai_goods['contact']==''){
				$root['status']=10042;
				$root['error']="联系人不能为空";
				api_ajax_return($root);
			}elseif(strlen($pai_goods['mobile'])<= 0 || strlen($pai_goods['mobile'])>11  ||!check_mobile($pai_goods['mobile'])){
				$root['status']=10043;
				$root['error']="请输入正确的联系电话";
				api_ajax_return($root);
			}elseif($pai_goods['qp_diamonds']==0){
				$root['status']=10044;
				$root['error']="竞拍价格不能为0";
				api_ajax_return($root);
			}elseif($pai_goods['district']==''){
				$root['status']=10034;
				$root['error']="区域数据错误";
				api_ajax_return($root);
			}
		}

		$rs = FanweServiceCall("pai_podcast","editpai",$pai_goods);
		if ($rs['status']==1) {
			$data = array();
			/*$data['info'] =  $rs['data']['info'];
			$data['pai_list'] =  $rs['data']['pai_list'];
			$data['page'] =  $rs['data']['page'];*/
			$root['status'] = 1;
			$root['data'] = $data;
			api_ajax_return($root);
			//api_ajax_return($rs);
		}if ($rs['status']==10049) {
			$root['status']=10049;
			$root['error']="存在未完成的竞拍，编辑竞拍失败";
			api_ajax_return($root);

		}else{

			$root['status']=10025;
			$root['error']="编辑竞拍失败";
			api_ajax_return($root);
		}


	}

	/**
	 * 保存入库
	 */
	public function doaddpai(){}

	/**
	 * 竞拍的商品（无）
	 * int id  商品ID
	 */
	public function view(){
		$root = array('status' => 1,'error'=>'',"data"=>array());

		$id = intval($_REQUEST['id']);
		$user_id = intval($GLOBALS['user_info']['id']);
		if($user_id == 0){
			$root['status']=10007;
			$root['error']="请先登录";
			api_ajax_return($root);
		}

		if($id == 0){
			$root['status']=10008;
			$root['error']="商品不存在";
			api_ajax_return($root);
		}

		api_ajax_return($root);
	}


	/**
	 * 参与竞拍人员列表
	 * int p  当前页
	 * int id  商品ID
	 */
	public function pailogs(){

		$root = array('status' => 1,'error'=>'',"data"=>array());

		$page = intval($_REQUEST['p']);//取第几页数据
		$pai_id = intval($_REQUEST['id']);
		$user_id = intval($GLOBALS['user_info']['id']);
		if($user_id == 0){
			$root['status']=10007;
			$root['error']="请先登录";
			api_ajax_return($root);
		}

		if($pai_id == 0){
			$root['status']=10008;
			$root['error']="竞拍商品不存在";
			api_ajax_return($root);
		}

		if($page==0)$page = 1;

		$page_size=PAI_PAGE_SIZE;

		$rs = FanweServiceCall("pai_user","pailogs",array("pai_id"=>$pai_id,"user_id"=>$user_id,"page"=>$page,"page_size"=>$page_size));
		$data = array();

		$data['rs_count'] =  $rs['rs_count'];
		if($rs['rs_count'] > 0)
			$data['total_page'] = ceil($rs['rs_count']/$page_size);
		else {
			$data['total_page'] =  1;
		}

		$data['list'] =  $rs['list'];
		$data['page'] =  $page;

		$root['data'] = $data;

		api_ajax_return($root);
	}

	/**
	 * 某竞拍竞拍详情
	 * int id  商品ID
	 */
	public function goods_detail(){
		$root = array('status' => 1,'error'=>'',"data"=>array());

		$id = intval($_REQUEST['id']);
		$user_id = intval($GLOBALS['user_info']['id']);
		if($user_id == 0){
			$root['status']=10007;
			$root['error']="请先登录";
			api_ajax_return($root);
		}

		if($id == 0){
			$root['status']=10008;
			$root['error']="商品不存在";
			api_ajax_return($root);
		}
		$data=array();
		$data['podcast_id']=$user_id;
		$data['pai_id']=$id;
		$data['get_pailogs']=intval($_REQUEST['get_pailogs']);
		if ($data['get_pailogs']>0) {
			$page=intval($_REQUEST['p']);
			if($page==0)$page = 1;
			$page_size=PAI_PAGE_SIZE;

			$data['page']=$page;
			$data['page_size']=$page_size;
		}

		$rs = FanweServiceCall("pai_podcast","goods_detail",$data);

		format_pai_goods($rs['info'],1);
		$data['info'] = $rs['info'];
		format_pai_logs($rs['pai_list'],$rs['info']['status']);
		$data['pai_list'] =  $rs['pai_list'];
		$data['rs_count'] =  $rs['rs_count'];
		$data['page'] =  $rs['page'];
		$root['data'] = $data;

		api_ajax_return($root);


	}

	/**
	 * 主播关闭 竞拍下架
	 * int id  商品ID
	 */
	public function shelves(){
		$root = array('status' => 1,'error'=>'',"data"=>array());

		$pai_id = intval($_REQUEST['pai_id']);
		$user_id = intval($GLOBALS['user_info']['id']);
		$video_id = intval($_REQUEST['video_id']);
		if($user_id == 0){
			$root['status']=10007;
			$root['error']="请先登录";
			api_ajax_return($root);
		}

		if($pai_id == 0){
			$root['status']=10008;
			$root['error']="商品不存在";
			api_ajax_return($root);
		}
		$data=array();
		$data['podcast_id']=$user_id;
		$data['pai_id']=$pai_id;

		$rs = FanweServiceCall("pai_podcast","stop_pai",$data);

		if ($rs==10027) {
			$root['error']="关闭竞拍失败";
		}else if($rs==10021){
			$root['error']="消息类型为空";
		}else if($rs==10033){
			$root['error']="推送会员为空";
		}else if($rs==10022){
			$root['error']="消息推送失败";
		}
		$root['status'] = $rs;
		api_ajax_return($root);

	}

	/**
	 * 主播提醒买家付款
	 */
	public function remind_buyer_pay(){
		$root = array('status' => 1,'error'=>'',"data"=>array());

		$user_id = intval($GLOBALS['user_info']['id']);
		$order_sn  = trim($_REQUEST['order_sn']);
		$to_buyer_id = intval($_REQUEST['to_buyer_id']);

		if($user_id == 0){
			$root['status']=10007;
			$root['error']="请先登录";
			api_ajax_return($root);
		}

		$data=array();
		$data['podcast_id']=$user_id;
		$data['to_buyer_id']=$to_buyer_id;
		$data['order_sn']=$order_sn;
		$rs = FanweServiceCall("pai_podcast","remind_buyer_pay",$data);

		if($rs['status']==10021){
			$root['error']="消息类型为空";
		}else if($rs['status']==10033){
			$root['error']="推送会员为空";
		}else if($rs['status']==10022){
			$root['error']="消息推送失败";
		}else if($rs['status']==1){
			$root['error']="提醒成功";
		}
		$root['status'] = intval($rs['status']);
		api_ajax_return($root);

	}

	/**
	 * 主播提醒买家收货
	 */
	public function remind_buyer_receive(){
		$root = array('status' => 1,'error'=>'',"data"=>array());

		$user_id = intval($GLOBALS['user_info']['id']);
		$order_sn  = strim($_REQUEST['order_sn']);
		$to_buyer_id = intval($_REQUEST['to_buyer_id']);

		if($user_id == 0){
			$root['status']=10007;
			$root['error']="请先登录";
			api_ajax_return($root);
		}

		$data=array();
		$data['podcast_id']=$user_id;
		$data['to_buyer_id']=$to_buyer_id;
		$data['order_sn']=$order_sn;
		$rs = FanweServiceCall("pai_podcast","remind_buyer_receive",$data);

		if($rs['status']==10021){
			$root['error']="消息类型为空";
		}else if($rs['status']==10033){
			$root['error']="推送会员为空";
		}else if($rs['status']==10022){
			$root['error']="消息推送失败";
		}else if($rs['status']==1){
			$root['error']="提醒成功";
		}
		$root['status'] = intval($rs['status']);
		api_ajax_return($root);
	}

	/**
	 * 主播提醒买家约会
	 */
	public function remind_buyer_to_date(){
		$root = array('status' => 1,'error'=>'',"data"=>array());

		$user_id = intval($GLOBALS['user_info']['id']);
		$order_sn  = strim($_REQUEST['order_sn']);
		$to_buyer_id = intval($_REQUEST['to_buyer_id']);

		if($user_id == 0){
			$root['status']=10007;
			$root['error']="请先登录";
			api_ajax_return($root);
		}

		$data=array();
		$data['podcast_id']=$user_id;
		$data['to_buyer_id']=$to_buyer_id;
		$data['order_sn']=$order_sn;
		$rs = FanweServiceCall("pai_podcast","remind_buyer_to_date",$data);

		if($rs['status']==10021){
			$root['error']="消息类型为空";
		}else if($rs['status']==10033){
			$root['error']="推送会员为空";
		}else if($rs['status']==10022){
			$root['error']="消息推送失败";
		}else if($rs['status']==1){
			$root['error']="提醒成功";
		}
		$root['status'] = intval($rs['status']);
		api_ajax_return($root);

	}

	/**
	 * 主播确认完成虚拟竞拍
	 */
	public function confirm_virtual_auction(){
		$root = array('status' => 1,'error'=>'',"data"=>array());

		$user_id = intval($GLOBALS['user_info']['id']);
		$order_sn  = strim($_REQUEST['order_sn']);
		$to_buyer_id = intval($_REQUEST['to_buyer_id']);

		if($user_id == 0){
			$root['status']=10007;
			$root['error']="请先登录";
			api_ajax_return($root);
		}

		$data=array();
		$data['podcast_id']=$user_id;
		$data['to_buyer_id']=$to_buyer_id;
		$data['order_sn']=$order_sn;
		$rs = FanweServiceCall("pai_podcast","confirm_virtual_auction",$data);

		if($rs['status']==10021){
			$root['error']="消息类型为空";
		}else if($rs['status']==10033){
			$root['error']="推送会员为空";
		}else if($rs['status']==10022){
			$root['error']="消息推送失败";
		}else if($rs['status']==10028){
			$root['error']="确认完成虚拟竞拍失败";
		}else if($rs['status']==1){
			$root['error']="确认成功";
		}
		$root['status'] = intval($rs['status']);

		api_ajax_return($root);

	}

	/**
	 * 主播虚拟商品订单-同意退款
	 */
	public function return_virtual_pai(){
		$root = array('status' => 1,'error'=>'',"data"=>array());

		$user_id = intval($GLOBALS['user_info']['id']);
		$order_sn  = strim($_REQUEST['order_sn']);

		if($user_id == 0){
			$root['status']=10007;
			$root['error']="请先登录";
			api_ajax_return($root);
		}

		$data=array();
		$data['podcast_id']=$user_id;
		$data['order_sn']=$order_sn;
		$rs = FanweServiceCall("pai_podcast","return_virtual_pai",$data);

		if($rs['status']==10021){
			$root['error']="消息类型为空";
		}else if($rs['status']==10033){
			$root['error']="推送会员为空";
		}else if($rs['status']==10022){
			$root['error']="消息推送失败";
		}else if($rs['status']==10029){
			$root['error']="确认竞拍退款失败";
		}else if($rs['status']==1){
			$root['error']="操作成功";
		}
		$root['status'] = intval($rs['status']);
		api_ajax_return($root);

	}

	/**
	 * 主播申诉虚拟商品订单
	 */
	public function complaint_virtual_goods(){
		$root = array('status' => 1,'error'=>'',"data"=>array());

		$user_id = intval($GLOBALS['user_info']['id']);
		$order_sn  = strim($_REQUEST['order_sn']);

		if($user_id == 0){
			$root['status']=10007;
			$root['error']="请先登录";
			api_ajax_return($root);
		}

		$data=array();
		$data['podcast_id']=$user_id;
		$data['order_sn']=$order_sn;
		$rs = FanweServiceCall("pai_podcast","complaint_virtual_goods",$data);

		if($rs['status']==10021){
			$root['error']="消息类型为空";
		}else if($rs['status']==10033){
			$root['error']="推送会员为空";
		}else if($rs['status']==10022){
			$root['error']="消息推送失败";
		}else if($rs['status']==10030){
			$root['error']="申诉竞拍失败";
		}else if($rs['status']==1){
			$root['error']="操作成功";
		}
		$root['status'] = intval($rs['status']);
		api_ajax_return($root);

	}

	/**
	 * 虚拟产品标签
	 */
	public function tags(){
		$root = array('status' => 1,'error'=>'',"data"=>array());

		/*$user_id = intval($GLOBALS['user_info']['id']);
		$order_sn  = strim($_REQUEST['order_sn']);

		if($user_id == 0){
			$root['status']=10007;
			$root['error']="请先登录";
			api_ajax_return($root);
		}*/

		$data=array();
		$rs = FanweServiceCall("pai_podcast","tags",$data);

		$data = array();
		$data['list'] =  $rs['list'];
		$root['data'] = $data;
		api_ajax_return($root);

	}


	//-------------买家

	/**
	 * 买家撤销
	 */
	public function oreder_revocation(){
		$root = array('status' => 1,'error'=>'',"data"=>array());

		$user_id = intval($GLOBALS['user_info']['id']);
		$order_sn  = strim($_REQUEST['order_sn']);
		$to_podcast_id  = intval($_REQUEST['to_podcast_id']);

		if($user_id == 0){
			$root['status']=10007;
			$root['error']="请先登录";
			api_ajax_return($root);
		}

		$data=array();
		$data['podcast_id']=$user_id;
		$data['order_sn']=$order_sn;
		$rs = FanweServiceCall("pai_podcast","oreder_revocation",$data);

		if($rs['status']==10032){
			$root['error']="撤销失败";
		}else if($rs['status']==1){
			$root['error']="操作成功";
		}
		$root['status'] = intval($rs['status']);
		api_ajax_return($root);

	}

	/**
	 * 买家确认约会
	 */
	public function buyer_confirm_date(){
		$root = array('status' => 1,'error'=>'',"data"=>array());

		$user_id = intval($GLOBALS['user_info']['id']);
		$order_sn  = strim($_REQUEST['order_sn']);
		$to_podcast_id  = intval($_REQUEST['to_podcast_id']);
		if($user_id == 0){
			$root['status']=10007;
			$root['error']="请先登录";
			api_ajax_return($root);
		}

		$data=array();
		$data['user_id']=$user_id;
		$data['order_sn']=$order_sn;
		$data['to_podcast_id']=$to_podcast_id;
		$rs = FanweServiceCall("pai_podcast","buyer_confirm_date",$data);

		if($rs['status']==10031){
			$root['error']="确认约会失败";
		}else if($rs['status']==1){
			$root['error']="确认约会成功";
		}
		$root['status'] = intval($rs['status']);
		api_ajax_return($root);

	}

	/**
	 * 买家提醒约会
	 */
	public function remind_podcast_to_date(){
		$root = array('status' => 1,'error'=>'',"data"=>array());

		$user_id = intval($GLOBALS['user_info']['id']);
		$order_sn  = strim($_REQUEST['order_sn']);
		$to_podcast_id  = intval($_REQUEST['to_podcast_id']);
		if($user_id == 0){
			$root['status']=10007;
			$root['error']="请先登录";
			api_ajax_return($root);
		}

		$data=array();
		$data['podcast_id']=$user_id;
		$data['order_sn']=$order_sn;
		$data['to_podcast_id']=$to_podcast_id;
		$rs = FanweServiceCall("pai_podcast","remind_podcast_to_date",$data);
		if($rs['status']==1){
			$root['error']="提醒成功";
		}
		api_ajax_return($root);

	}

	/**
	 * 买家提醒主播确认约会
	 */
	public function remind_podcast_to_confirm_date(){
		$root = array('status' => 1,'error'=>'',"data"=>array());

		$user_id = intval($GLOBALS['user_info']['id']);
		$order_sn  = strim($_REQUEST['order_sn']);
		$to_podcast_id  = intval($_REQUEST['to_podcast_id']);
		if($user_id == 0){
			$root['status']=10007;
			$root['error']="请先登录";
			api_ajax_return($root);
		}

		$data=array();
		$data['podcast_id']=$user_id;
		$data['order_sn']=$order_sn;
		$data['to_podcast_id']=$to_podcast_id;
		$rs = FanweServiceCall("pai_podcast","remind_podcast_to_confirm_date",$data);
		if($rs['status']==1){
			$root['error']="提醒成功";
		}
		api_ajax_return($root);

	}

	/**
	 * 买家要求退款
	 */
	public function buyer_to_refund(){
		$root = array('status' => 1,'error'=>'',"data"=>array());

		$user_id = intval($GLOBALS['user_info']['id']);
		$order_sn  = strim($_REQUEST['order_sn']);
		$to_podcast_id  = intval($_REQUEST['to_podcast_id']);
		if($user_id == 0){
			$root['status']=10007;
			$root['error']="请先登录";
			api_ajax_return($root);
		}

		$data=array();
		$data['podcast_id']=$user_id;
		$data['order_sn']=$order_sn;
		$data['to_podcast_id']=$to_podcast_id;
		$rs = FanweServiceCall("pai_podcast","buyer_to_refund",$data);

		if($rs['status']==10031){
			$root['error']="退款失败";
		}else if($rs['status']==1){
			$root['error']="操作成功";
		}
		$root['status'] = intval($rs['status']);
		api_ajax_return($root);

	}

	/**
	 * 买家投诉/（实物，申请售后）
	 */
	public function buyer_to_complaint(){
		$root = array('status' => 1,'error'=>'',"data"=>array());

		$user_id = intval($GLOBALS['user_info']['id']);
		$order_sn  = strim($_REQUEST['order_sn']);
		$to_podcast_id  = intval($_REQUEST['to_podcast_id']);
		if($user_id == 0){
			$root['status']=10007;
			$root['error']="请先登录";
			api_ajax_return($root);
		}

		$data=array();
		$data['podcast_id']=$user_id;
		$data['order_sn']=$order_sn;
		$data['to_podcast_id']=$to_podcast_id;
		$rs = FanweServiceCall("pai_podcast","buyer_to_complaint",$data);

		if($rs['status']==10031){
			$root['error']="操作失败";
		}else if($rs['status']==1){
			$root['error']="操作成功";
		}
		$root['status'] = intval($rs['status']);
		api_ajax_return($root);

	}

	/**
	 * 买家确认退货
	 */
	public function buyer_confirm_to_refund(){
		$root = array('status' => 1,'error'=>'',"data"=>array());

		$user_id = intval($GLOBALS['user_info']['id']);
		$order_sn  = strim($_REQUEST['order_sn']);
		$to_podcast_id  = intval($_REQUEST['to_podcast_id']);
		if($user_id == 0){
			$root['status']=10007;
			$root['error']="请先登录";
			api_ajax_return($root);
		}

		$data=array();
		$data['podcast_id']=$user_id;
		$data['order_sn']=$order_sn;
		$data['to_podcast_id']=$to_podcast_id;
		$rs = FanweServiceCall("pai_podcast","buyer_confirm_to_refund",$data);

		if($rs['status']==10031){
			$root['error']="操作失败";
		}else if($rs['status']==1){
			$root['error']="操作成功";
		}
		$root['status'] = intval($rs['status']);
		api_ajax_return($root);

	}

	/**
	 * 主播端查看虚拟订单详情
	 */
	public function virtual_order_details(){
		$root = array('status' => 1,'error'=>'',"data"=>array(),"page_title"=>"订单详情");

		$user_id = intval($GLOBALS['user_info']['id']);
		$order_sn  = strim($_REQUEST['order_sn']);
		$pai_id = intval($_REQUEST['pai_id']);

		if($user_id == 0){
			$root['status']=10007;
			$root['error']="请先登录";
			api_ajax_return($root);
		}
		if ($order_sn=="") {
			$root['status']=10037;
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

		$data=array();
		$data['podcast_id']=$user_id;
		$data['order_sn']=$order_sn;
		$data['pai_id']=$pai_id;
		$rs = FanweServiceCall("pai_podcast","virtual_order_details",$data);

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
		$goods_detail=FanweServiceCall("pai_podcast","goods_detail",$data);
		$goods=$goods_detail['info'];
		format_pai_goods($goods,1);
		$rs['name']=$goods['name'];
		$rs['date_time']=$goods['date_time'];
		$rs['pay_time']=$goods['pay_time'];
		$rs['status']=$goods['status'];
		$rs['expire_date_time']=$goods['expire_date_time'];
		$rs['last_pai_diamonds']=$goods['last_pai_diamonds'];
		$rs['info_status']=$goods['info_status'];
		$rs['button_status']=$goods['button_status'];
		$rs['expire_time']=$goods['expire_time'];
		$rs['img']=$goods['img'];
		$rs['final_time']=$goods['final_time'];
		$rs['order_status_time']=$goods['order_status_time'];
		if (OPEN_GOODS==1) {
			$rs['shop_id']=$goods['shop_id'];
			$rs['shop_name']=$goods['shop_name'];
		}		
		$rs['is_true']=$goods['is_true'];
		$root['data'] = $rs;

		$m_config =  load_auto_cache("m_config");//手机端配置
		$ticket_name = $m_config['ticket_name'];
		$root['mark'] = $ticket_name;

		api_ajax_return($root);

	}

	/**
	 * 创建竞拍时检查
	 */
	public function check(){
		$root = array('status' => 1,'error'=>'',"data"=>array());

		$user_id = intval($GLOBALS['user_info']['id']);

		if($user_id == 0){
			$root['status']=10007;
			$root['error']="请先登录";
			api_ajax_return($root);
		}

		$data=array();
		$data['podcast_id']=$user_id;
		$rs = FanweServiceCall("pai_podcast","check",$data);

		$root['status'] = intval($rs['status']);
		if ($root['status']==10049) {
			$root['error']="存在未完成的竞拍，创建竞拍失败";
		}elseif ($root['status']==10051){
			$root['error']="禁止发起竞拍，创建竞拍失败";
		}
		api_ajax_return($root);

	}

	/**
	 * 主播 -  竞拍删除
	 */
	public function del(){
		$root = array('status' => 1,'error'=>'',"data"=>array());

		$user_id = intval($GLOBALS['user_info']['id']);
		$pai_id = intval($_REQUEST['pai_id']);
		if($user_id == 0){
			$root['status']=10007;
			$root['error']="请先登录";
			api_ajax_return($root);
		}

		$data=array();
		$data['podcast_id']=$user_id;
		$data['pai_id']=pai_id;
		$rs = FanweServiceCall("pai_podcast","del",$data);

		$root['status'] = intval($rs['status']);
		api_ajax_return($root);

	}
	
	//第三方商城---竟拍商品详情图片接口
	public function getauction_commodity_detail(){
		$root=array();
		$user_id = intval($GLOBALS['user_info']['id']);
		if ($user_id == 0) {
			$root['status'] = 10007;
			$root['error']  = "请先登录";
			api_ajax_return($root);
		}
	
		$goods_id = intval($_REQUEST['goods_id']);
		$head_args['commodityId']=$goods_id;
	
		$ret=third_interface($user_id,'http://gw1.yimile.cc/V1/Commodity.json?action=GetAuctionCommodityDetail',$head_args);
		if($ret['code'] == 0){
			$root['status'] = 1;
//			if($ret['data']['commodityImages'] != ''){
//				foreach($ret['data']['commodityImages'] as $key => $vaule){
//					$pai_goods = array();
//					$pai_goods['image_width'] =$vaule['imageWidth'];
//					$pai_goods['image_height'] =$vaule['imageHeight'];
//					$pai_goods['image_url'] =$vaule['imageUrl'];
//					$root['pai_goods'][] = $pai_goods;
//				}
//			}else{
			$root['pai_goods'][] = array();
//			}
			if($ret['data']['detailImages'] != ''){
				foreach($ret['data']['detailImages'] as $key => $vaule){
					$goods_detail = array();
					$goods_detail['image_width'] =$vaule['detailImageWidth'];
					$goods_detail['image_height'] =$vaule['detailImageHeight'];
					$goods_detail['image_url'] =$vaule['detailImageUrl'];
					$root['goods_detail'][] = $goods_detail;
				}
			}else{
				$root['goods_detail'][] = array();
			}
	
		}else{
			$root['error'] = '获取失败';
		}
	
		api_ajax_return($root);
	}

	public function imgs($imgs,$user_id){

		$result_imgs=array();
		foreach($imgs as $k=>$v){
			preg_match("/data:image\/(.*?);base64,(.*?)/", $v,$stype);
			$ftype = $stype[1];

			$message = base64_decode(substr($v,strlen('data:image/'.strtolower($ftype).';base64,')));
			$filename = md5(time().rand(100,999)).".".$ftype;

			$furl = "/public/paiimgs";
			if (!is_dir(APP_ROOT_PATH.$furl)) {
				@mkdir(APP_ROOT_PATH.$furl);
				@chmod(APP_ROOT_PATH.$furl, 0777);
			}

			$furl .= "/".$user_id;

			if (!is_dir(APP_ROOT_PATH.$furl)) {
				@mkdir(APP_ROOT_PATH.$furl);
				@chmod(APP_ROOT_PATH.$furl, 0777);
			}

			$furl .= "/".to_date(NOW_TIME,"Ym");

			if (!is_dir(APP_ROOT_PATH.$furl)) {
				@mkdir(APP_ROOT_PATH.$furl);
				@chmod(APP_ROOT_PATH.$furl, 0777);
			}

			$furl .= "/".to_date(NOW_TIME,"d");
			if (!is_dir(APP_ROOT_PATH.$furl)) {
				@mkdir(APP_ROOT_PATH.$furl);
				@chmod(APP_ROOT_PATH.$furl, 0777);
			}

			$furl .= "/".to_date(NOW_TIME,"H");
			if (!is_dir(APP_ROOT_PATH.$furl)) {
				@mkdir(APP_ROOT_PATH.$furl);
				@chmod(APP_ROOT_PATH.$furl, 0777);
			}


			//开始写文件
			$file = $furl."/".$filename;
			if(@file_put_contents(APP_ROOT_PATH.$file, $message) === false){
				$result['status'] = 0;
			}
			else{
				$result['status'] = 1;

				$result['src'] = ".".$file;
				$result['thumb'] = $result['src'];

				if($GLOBALS['distribution_cfg']['OSS_TYPE']&&$GLOBALS['distribution_cfg']['OSS_TYPE']!='NONE')
				{
					syn_to_remote_image_server($result['src']);
				}

				$result_imgs[]=$file;
			}
		}

		return $result_imgs;

	}

	//提醒卖家发货
    public function remind_seller_delivery(){
        $root =array();
        $user_id = intval($GLOBALS['user_info']['id']);
        if ($user_id == 0) {
            $root['status'] = 10007;
            $root['error']  = "请先登录";
            api_ajax_return($root);
        }

        $order_sn = strim($_REQUEST['order_sn']);
        $head_args['orderNo']=$order_sn;

        $ret=third_interface($user_id,'http://gw1.yimile.cc/V1/Order.json?action=OrderRemindConsignment',$head_args);
        if($ret['code'] == 0){
            $root['status']=1;
            $root['error']="提醒成功";
        }else{
            $root['status']=0;
            $root['error']="消息推送失败";
        }

        api_ajax_return($root);

    }

}