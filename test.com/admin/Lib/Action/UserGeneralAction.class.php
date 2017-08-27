<?php
// +----------------------------------------------------------------------
// | Fanwe 方维众筹商业系统
// +----------------------------------------------------------------------
// | Copyright (c) 2011 http://www.fanwe.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: 甘味人生(526130@qq.com)
// +----------------------------------------------------------------------

class UserGeneralAction extends CommonAction{
	public function __construct()
	{	
		parent::__construct();
		require_once APP_ROOT_PATH."/admin/Lib/Action/UserCommonAction.class.php";
        require_once APP_ROOT_PATH."/system/libs/user.php";
	}
	public function index()
	{
		$common = new UserCommon();
		$data = $_REQUEST;
//		$data['is_authentication'] = array('in',array(0,1,3));
        $data['is_robot'] = 0;
		$common->index($data);
	}

	public function edit() {		
		$common = new UserCommon();
		$data = $_REQUEST;
		$common->edit($data);
	}
		

	public function delete() {
		//彻底删除指定记录
        $common = new UserCommon();
        $data = $_REQUEST;
        $common->delete($data);
	}
	
		
	
	public function update() {
        $common = new UserCommon();
        $data = $_REQUEST;
        $common->update($data);
		
	}

	public function set_effect()
	{
        $common = new UserCommon();
        $data = $_REQUEST;
        $n_is_effect = $common->set_effect($data);
        $this->ajaxReturn($n_is_effect,l("SET_EFFECT_".$n_is_effect),1);
	}

    public function set_ban()
    {
        $common = new UserCommon();
        $data = $_REQUEST;
        $n_is_effect = $common->set_ban($data);
        $this->ajaxReturn($n_is_effect,l("SET_BAN_".$n_is_effect),1);
    }
    //禁热门
    public function set_hot_on()
    {
        $common = new UserCommon();
        $data = $_REQUEST;
        $n_is_effect = $common->set_hot_on($data);
        $this->ajaxReturn($n_is_effect,l("SET_HOT_ON_".$n_is_effect),1);
    }
    //新增关注
    public function add_focus(){
        $common = new UserCommon();
        $data = $_REQUEST;
        $common->add_focus($data);
    }

    //新增关注
    public function set_follow(){
        $common = new UserCommon();
        $data = $_REQUEST;
        $common->set_follow($data);
    }
    //关注列表
    public function focus_list(){
        $common = new UserCommon();
        $data = $_REQUEST;
        $common->focus_list($data);
    }

    //新增粉丝
    public function add_fans(){
        $common = new UserCommon();
        $data = $_REQUEST;
        $common->add_fans($data);
    }

    //新增粉丝
    public function set_follower(){
        $common = new UserCommon();
        $data = $_REQUEST;
        $common->set_follower($data);
    }

    //粉丝列表
    public function fans_list(){
        $common = new UserCommon();
        $data = $_REQUEST;
        $common->fans_list($data);
    }

    //删除关注
    public function del_focus_list(){
        $common = new UserCommon();
        $data = $_REQUEST;
        $common->del_focus_list($data);
    }

    //删除粉丝
    public function del_fans_list(){
        $common = new UserCommon();
        $data = $_REQUEST;
        $common->del_fans_list($data);
    }

    //印票贡献榜
    public function contribution_list(){
        $common = new UserCommon();
        $data = $_REQUEST;
        $common->contribution_list($data);
    }

    /**
     * 删除印票贡献榜
     */
    /*public function del_contribution_list()
    {
        $ajax = intval($_REQUEST['ajax']);
        $id = $_REQUEST ['id'];
        if (isset ( $id )) {
            $common = new UserCommon();
            $data = $_REQUEST;
            $status = $common->del_contribution_list($data);

            if ($status!==false) {
                $this->success (l("FOREVER_DELETE_SUCCESS"),$ajax);
            } else {
                $this->error (l("FOREVER_DELETE_FAILED"),$ajax);
            }
        } else {
            $this->error (l("INVALID_OPERATION"),$ajax);
        }
    }*/

    //消息推送
    public function push(){
        $common = new UserCommon();
        $data = $_REQUEST;
        $common->push($data);
    }

    //删除推送消息
    public function del_push(){
        $ajax = intval($_REQUEST['ajax']);
        $id = $_REQUEST ['id'];
        if (isset ( $id )) {
            $common = new UserCommon();
            $data = $_REQUEST;
            $status = $common->del_push($data);

            if ($status!==false) {
                $this->success (l("FOREVER_DELETE_SUCCESS"),$ajax);
            } else {
                $this->error (l("FOREVER_DELETE_FAILED"),$ajax);
            }
        } else {
            $this->error (l("INVALID_OPERATION"),$ajax);
        }
    }

	//账户管理
	public function account()
	{
        $common = new UserCommon();
        $data = $_REQUEST;
        $status = $common->account($data);
	}
	//账户修改
	public function modify_account()
	{
        $common = new UserCommon();
        $data = $_REQUEST;
        $status = $common->modify_account($data);
        if($status){
        	$this->success(L("UPDATE_SUCCESS"));
        }else{
        	$this->error("累计充值数据有误！");
        }
        
    }

	//账户日志
	public function account_detail()
	{
        $common = new UserCommon();
        $data = $_REQUEST;
        $common->account_detail($data);
	}

    //兑换日志
    public function exchange_log()
    {
        $common = new UserCommon();
        $data = $_REQUEST;
        $common->exchange_log($data);
    }
	//删除账户日志
	public function foreverdelete_account_detail()
	{

        //彻底删除指定记录
        $ajax = intval($_REQUEST['ajax']);
        $id = $_REQUEST ['id'];
        $data = $_REQUEST;
        if (isset ( $id )) {
            $common = new UserCommon();
            $status = $common->foreverdelete_account_detail($data);
            if ($status!==false) {
                $this->success (l("FOREVER_DELETE_SUCCESS"),$ajax);
            } else {
                $this->error (l("FOREVER_DELETE_FAILED"),$ajax);
            }
        } else {
            $this->error (l("INVALID_OPERATION"),$ajax);
        }
	}
    //删除兑换日志
    public function foreverdelete_exchange_log()
    {

        //彻底删除指定记录
        $ajax = intval($_REQUEST['ajax']);
        $id = $_REQUEST ['id'];
        $data = $_REQUEST;
        if (isset ( $id )) {
            $common = new UserCommon();
            $status = $common->foreverdelete_exchange_log($data);
            if ($status!==false) {
                $this->success (l("FOREVER_DELETE_SUCCESS"),$ajax);
            } else {
                $this->error (l("FOREVER_DELETE_FAILED"),$ajax);
            }
        } else {
            $this->error (l("INVALID_OPERATION"),$ajax);
        }
    }
	//检查用户
	public function check_user(){
        $common = new UserCommon();
        $user_id = $_REQUEST['id'];
        admin_ajax_return($common->check_user($user_id));
	}
    //礼物日志
    public function prop()
    {
        $common = new UserCommon();
        $data = $_REQUEST;
        $common->prop($data);
    }
    //收礼物日志
    public function closed_prop(){
        $data = $_REQUEST;
        $now=get_gmtime();
        $user_id = intval($_REQUEST['id']);
        $user_info = M("User")->getById($user_id);
        $prop_list = M("prop")->where("is_effect <>0")->findAll();

        $where = "l.to_user_id=".$user_id ;
        $model = D ("video_prop");
        //赠送时间

        $current_Year = date('Y');


        $current_YM = date('Ym');
        for ($i=0; $i<5; $i++)
        {
            $years[$i] = $current_Year - $i;
        }

        for ($i=01; $i<13; $i++)
        {
            $month[$i] = str_pad(0+$i,2,0,STR_PAD_LEFT);
        }

        if(strim($data['years'])!=-1&&strim($data['month']!=-1)){
            $time=$data['years'].''.$data['month'];
        }else{
            $time=$current_YM;
        }
        if(strim($data['years'])!=-1&&strim($data['month']==-1)){
            $this->error("请选择月份");
        }
        if(strim($data['years'])==-1&&strim($data['month']!=-1)){
            $this->error("请选择年份");
        }

        //查询ID
        if(strim($data['from_user_id'])!=''){
            $parameter.= "l.from_user_id=".intval($data['from_user_id']). "&";
            $sql_w .= "l.from_user_id=".intval($data['from_user_id'])." and ";
        }
        //查询昵称
        if(trim($data['nick_name'])!='')
        {
            $parameter.= "u.nick_name like " . urlencode ( '%'.trim($data['nick_name']).'%' ) . "&";
            $sql_w .= "u.nick_name like '%".trim($data['nick_name'])."%' and ";

        }
        if (!isset($_REQUEST['prop_id'])) {
            $_REQUEST['prop_id'] = -1;
        }
        //查询礼物
        if($_REQUEST['prop_id']!=-1) {
            if (isset($data['prop_id'])) {
                $parameter .= "l.prop_id=" . intval($data['prop_id']) . "&";
                $sql_w .= "l.prop_id=" . intval($data['prop_id']) . " and ";
            }
        }

        //默认查询本月的记录,选择查询时间时,如果查询时间 不等于当前时间,则查询他表
        if($data['years']!=''&&$data['month']!=''){
            $sql_str = "SELECT l.id,l.create_ym,l.to_user_id, l.create_time,l.prop_id,l.prop_name,l.from_user_id,l.create_date,l.num,l.total_ticket,u.nick_name
                         FROM   ".DB_PREFIX."video_prop_".$time." as l
                         LEFT JOIN ".DB_PREFIX."user AS u  ON l.from_user_id = u.id" ." LEFT JOIN ".DB_PREFIX."prop AS v ON l.prop_name = v.name" ."
                         WHERE $where "."  and ".$sql_w." 1=1  ";

            $count_sql = "SELECT count(l.id)  as tpcount
                         FROM   ".DB_PREFIX."video_prop_".$time." as l
                         LEFT JOIN ".DB_PREFIX."user AS u  ON l.from_user_id = u.id" ." LEFT JOIN ".DB_PREFIX."prop AS v ON l.prop_name = v.name" ."
                         WHERE $where "."  and ".$sql_w." 1=1  ";

            $total_ticket_sql = "SELECT SUM(l.total_ticket)  as tpcount
                         FROM   ".DB_PREFIX."video_prop_".$time." as l
                         LEFT JOIN ".DB_PREFIX."user AS u  ON l.from_user_id = u.id" ." LEFT JOIN ".DB_PREFIX."prop AS v ON l.prop_name = v.name" ."
                         WHERE $where "."   and ".$sql_w." 1=1  ";
        }else{

            $sql_str = "SELECT l.id,l.create_ym,l.to_user_id, l.create_time,l.prop_id,l.prop_name,l.from_user_id,l.create_date,l.num,l.total_ticket,u.nick_name
                         FROM   ".DB_PREFIX."video_prop_".date('Ym',NOW_TIME)." as l
                         LEFT JOIN ".DB_PREFIX."user AS u  ON l.from_user_id = u.id" ." LEFT JOIN ".DB_PREFIX."prop AS v ON l.prop_name = v.name" ."
                         WHERE $where "."   and ".$sql_w." 1=1  ";

            $count_sql = "SELECT count(l.id)  as tpcount
                         FROM   ".DB_PREFIX."video_prop_".date('Ym',NOW_TIME)." as l
                         LEFT JOIN ".DB_PREFIX."user AS u  ON l.from_user_id = u.id" ." LEFT JOIN ".DB_PREFIX."prop AS v ON l.prop_name = v.name" ."
                         WHERE $where "."   and ".$sql_w." 1=1  ";

            $total_ticket_sql = "SELECT SUM(l.total_ticket)  as tpcount
                         FROM   ".DB_PREFIX."video_prop_".date('Ym',NOW_TIME)." as l
                         LEFT JOIN ".DB_PREFIX."user AS u  ON l.from_user_id = u.id" ." LEFT JOIN ".DB_PREFIX."prop AS v ON l.prop_name = v.name" ."
                         WHERE $where "."   and ".$sql_w." 1=1  ";
        }

        $count = $GLOBALS['db']->getOne($count_sql);
        $total_ticket = $GLOBALS['db']->getOne($total_ticket_sql);

        $volist = $this->_Sql_list($model,$sql_str,'&'.$parameter,1,0,$count_sql);
        foreach($volist as $k=>$v){
            if($volist[$k]['prop_id']==12){
                $volist[$k]['total_ticket']='';
            }
            $volist[$k]['create_time']=date('Y-m-d',$volist[$k]['create_time']);
        }

        $this->assign("user_info",$user_info);
        $this->assign("prop",$prop_list);
        $this->assign("years",$years);
        $this->assign("month",$month);
        $this->assign("list", $volist);
        $this->assign("count",intval($count));
        $this->assign('total_ticket',intval($total_ticket));
        $this->display ();
    }
    //分享奖励
    public function distribution_log(){
        $common = new UserCommon();
        $data = $_REQUEST;
        $common->distribution_log($data);
    }
    //分销子成员奖励
    public function distribution_user(){
        $common = new UserCommon();
        $data = $_REQUEST;
        $common->distribution_user($data);
    }
    //删除礼物日志
    public function delete_prop()
    {

        //彻底删除指定记录
        $ajax = intval($_REQUEST['ajax']);
        $id = intval($_REQUEST ['id']);
        $data = $_REQUEST;
        if (isset ( $id )) {
            $common = new UserCommon();
            $status = $common->del_prop($data);
            if ($status!==false) {
                $this->success (l("FOREVER_DELETE_SUCCESS"),$ajax);
            } else {
                $this->error (l("FOREVER_DELETE_FAILED"),$ajax);
            }
        } else {
            $this->error (l("INVALID_OPERATION"),$ajax);
        }
    }

    public function forbid_msg(){
        $common = new UserCommon();
        $data = $_REQUEST;
        $common->forbid_msg($data);
    }

    //商品管理
    public function goods(){

        $user_id = intval($_REQUEST['user_id']);
        if(strim($_REQUEST['name'])!=''){
            $map['name'] = array('like','%'.strim($_REQUEST['name']).'%');
        }
        $map['is_effect'] = 1;
        $model = D ('goods');
        if (! empty ( $model )) {
            $this->_list ( $model, $map );
        }
        $list = $this->get('list');
        $user_list = $GLOBALS['db']->getAll("select goods_id from ".DB_PREFIX."user_goods where is_effect=1 and user_id=".$user_id);
        foreach($list as $k => $v){
            $imgs=array();
            $imgs_details=array();
            $imgs=json_decode($v['imgs'],1);
            $imgs_details=json_decode($v['imgs_details'],1);
            $list[$k]['imgs'] = $imgs[0];
            $list[$k]['imgs_details'] = $imgs_details[0];
            $list[$k]['has']= '否';
            foreach($user_list as $value1){
                if($v['id'] == $value1['goods_id']){
                    $list[$k]['has'] = '是';
                    break;
                }
            }
        }
        $sort = array_column($list, 'has');
        array_multisort($sort, SORT_DESC, $list);

        $this->assign("list",$list);
        $this->display();

    }

    //上架商品
    public function shelves(){

        $ajax = intval($_REQUEST['ajax']);
        $goods_id = intval($_REQUEST ['id']);
        $user_id = intval($_REQUEST ['user_id']);

        if (isset($goods_id)) {
            $where['goods_id'] = $goods_id;
            $where['user_id'] = $user_id;
            $user_goods = M('user_goods')->where($where)->select();
            $goods_info = $GLOBALS['db']->getRow("select name,imgs,imgs_details,price,pai_diamonds,kd_cost,score,is_effect from ".DB_PREFIX."goods where is_effect=1 and id=".$goods_id);
            if($user_goods){
                $list = M('user_goods')->where($where)->save($goods_info);
            }else{
                $data = array_merge($where,$goods_info);
                $list = M('user_goods')->add($data);
            }

            if ($list!==false) {
                $result['info'] = "上架成功！";
                $result['status'] = 1;
            } else {
                $result['info'] = "上架失败！";
                $result['status'] = 0;
            }
            $this->ajax_return($result);
        } else {
            $this->error (l("INVALID_OPERATION"),$ajax);
        }

    }

    //下架商品
    public function sold(){

        $ajax = intval($_REQUEST['ajax']);
        $goods_id = intval($_REQUEST ['id']);
        $user_id = intval($_REQUEST ['user_id']);

        if (isset($goods_id)) {
            $condition['goods_id'] = $goods_id;
            $condition['user_id'] = $user_id;
            $user_goods = M('user_goods')->where($condition)->select();
            if($user_goods){
                $list = M('user_goods')->where($condition)->delete();
            }

            if ($list!==false) {
                $result['info'] = "下架成功！";
                $result['status'] = 1;
            } else {
                $result['info'] = "下架失败！";
                $result['status'] = 0;
            }
            $this->ajax_return($result);
        } else {
            $this->error (l("INVALID_OPERATION"),$ajax);
        }

    }
    
    public function clear_view_count(){
    	$sql = "update  ".DB_PREFIX."user set view_count = 0";
    	$res = $GLOBALS['db']->query($sql);
    	if($res){
    		$result['info'] = "清除成功！";
        	$result['status'] = 1;
    	}else{
    		$result['info'] = "清除失败！";
        	$result['status'] = 0;
    	}
        admin_ajax_return($result);
    }

    public function weibo_index()
    {
        $common = new UserCommon();
        $data = $_REQUEST;
//		$data['is_authentication'] = array('in',array(0,1,3));
        $data['is_robot'] = 0;
        $common->index($data);
    }

    public function set_sort()
    {
        $id = intval($_REQUEST['id']);
        $sort = intval($_REQUEST['sort']);
        $log_info = M("User")->where("id=".$id)->getField("nick_name");
        if(!check_sort($sort))
        {
            $this->error(l("SORT_FAILED"),1);
        }
        M("User")->where("id=".$id)->setField("weibo_recommend_weight",$sort);
        save_log($log_info.l("SORT_SUCCESS"),1);

        $this->success(l("SORT_SUCCESS"),1);
    }

    public function game_rate()
    {
        if ($_POST) {
            $user_id = intval($_REQUEST['user_id']);
            $rate    = intval($_REQUEST['rate']);
            if (!$user_id) {
                $this->ajax_return(array(
                    'status' => 0,
                    'error'  => '参数错误',
                ));
            }
            if ($rate > 100 || $rate < 0) {
                $this->ajax_return(array(
                    'status' => 0,
                    'error'  => '参数错误',
                ));
            }
            $where      = array('id' => $user_id);
            $user_model = M('user');
            $res        = $user_model->setField('rate', $rate, $where);
            if ($res) {
                fanwe_require(APP_ROOT_PATH . 'mapi/lib/redis/BaseRedisService.php');
                fanwe_require(APP_ROOT_PATH . 'mapi/lib/redis/UserRedisService.php');
                $user_redis = new UserRedisService();
                $user_redis->update_db($user_id, ['rate' => $rate]);
                $this->ajax_return(array(
                    'status' => 1,
                    'error'  => '更新成功',
                ));
            }
            $this->ajax_return(array(
                'status' => 0,
                'error'  => '更新失败',
            ));
        } else {
            $user_id    = intval($_REQUEST['user_id']);
            $user_model = M('user');
            $user_info  = $user_model->field(array('id', 'rate'))->find($user_id);
            $user_info['rate'] = +$user_info['rate'];
            $this->assign("user_info", $user_info);
            $this->display();
        }
    }
    protected function ajax_return($data)
    {
        header("Content-Type:text/html; charset=utf-8");
        echo (json_encode($data));
        exit;
    }
    public function game_distribution()
    {

        if ($_POST) {
            $user_id            = intval($_REQUEST['user_id']);
            $game_distribution1 = intval($_REQUEST['game_distribution1']);
            $game_distribution2 = intval($_REQUEST['game_distribution2']);
            if (!$user_id) {
                $this->ajax_return(array(
                    'status' => 0,
                    'error'  => '参数错误',
                ));
            }
            $where      = array('id' => $user_id);
            $user_model = M('user');
            $res        = $user_model->where($where)->save(['game_distribution1'=>$game_distribution1,'game_distribution2'=>$game_distribution2]);
            if ($res) {
                fanwe_require(APP_ROOT_PATH . 'mapi/lib/redis/BaseRedisService.php');
                fanwe_require(APP_ROOT_PATH . 'mapi/lib/redis/UserRedisService.php');
                $user_redis = new UserRedisService();
                $user_redis->update_db($user_id, ['game_distribution1'=>$game_distribution1,'game_distribution2'=>$game_distribution2]);
                $this->ajax_return(array(
                    'status' => 1,
                    'error'  => '更新成功',
                ));
            }
            $this->ajax_return(array(
                'status' => 0,
                'error'  => '更新失败',
            ));
        } else {
            $user_id    = intval($_REQUEST['user_id']);
            $user_model = M('user');
            $user_info  = $user_model->field(array('id', 'game_distribution1', 'game_distribution2'))->find($user_id);
            $user_info['game_distribution1'] = +$user_info['game_distribution1'];
            $user_info['game_distribution2'] = +$user_info['game_distribution2'];
            $this->assign("user_info", $user_info);
            $this->display();
        }
    }
    public function invitation_code()
    {
        $user_id    = intval($_REQUEST['user_id']);
        $user_model = M('user');
        $user_info  = $user_model->field(['invitation_code','create_time'])->find($user_id);
        if ($user_info['invitation_code']) {
            exit($user_info['invitation_code']);
        }
        $users = $user_model->field(['id','create_time'])->where(['invitation_code' => ''])->findAll();
        foreach ($users as $v) {
            $res = $user_model->save(['invitation_code' => substr(md5($v['id'] . ':' . $v['create_time']), -16),'id' => $v['id']]);
        }
        exit(substr(md5($user_id . ':' . $user_info['create_time']), -16));
    }
    public function game_distribution_detail()
    {
        $user_id = intval($_REQUEST['user_id']);
        if ($_REQUEST['type']) {
            $model = M('game_distribution');
            $table =  DB_PREFIX . 'user u,' . DB_PREFIX . 'game_distribution gd';
            $field = "u.id,u.nick_name,u.head_image,sum(gd.first_distreibution_money * (gd.first_distreibution_id = {$user_id}) + gd.second_distreibution_money * (gd.second_distreibution_id = {$user_id})) as `sum`,gd.is_ticket";
            $where = "(gd.first_distreibution_id = $user_id or gd.second_distreibution_id = $user_id) and u.id = gd.user_id";
            $group = 'u.id,gd.is_ticket';
            $list = $model->table($table)->field($field)->where($where)->group($group)->findAll();
            $this->assign("list", $list);
        } else {
            $map = [
                'user_id' => $user_id,
            ];
            $model = M('game_distribution');
            if (!empty($model)) {
                $this->_list($model, $map);
            }
        }
        $this->assign("user_id", $user_id);
        $this->display();
    }
    //禁游戏
    public function forbid_game(){
        $id= intval($_REQUEST['user_id']);
        log_result($id);
        $open_game =$GLOBALS['db']->getRow("select open_game from ".DB_PREFIX."user where id =".$id);
        $where = 'id='.$id;
        require_once APP_ROOT_PATH.'mapi/lib/redis/BaseRedisService.php';
        require_once APP_ROOT_PATH.'mapi/lib/redis/UserRedisService.php';
        $user_redis = new UserRedisService();

        $data['open_game'] = 1;
        $info = '';
        if($open_game['open_game']==1) {
            $data['open_game'] = 0;
            $info = '取消';
        }

        $list = $GLOBALS['db']->autoExecute(DB_PREFIX."user",$data,'UPDATE',$where);
        log_result($list);
        if ($list!==false) {
            $user_redis->update_db($id, $data);
            $this->success ($info.'禁游戏成功',1);

        }else{
            $this->error ($info.'禁游戏成功',1);
        }
    }
    //禁付费
    public function forbid_pay(){
        $id= intval($_REQUEST['user_id']);
        log_result($id);
        $open_game =$GLOBALS['db']->getRow("select open_pay from ".DB_PREFIX."user where id =".$id);
        $where = 'id='.$id;
        require_once APP_ROOT_PATH.'mapi/lib/redis/BaseRedisService.php';
        require_once APP_ROOT_PATH.'mapi/lib/redis/UserRedisService.php';
        $user_redis = new UserRedisService();

        $data['open_pay'] = 1;
        $info = '';
        if($open_game['open_pay']==1) {
            $data['open_pay'] = 0;
            $info = '取消';
        }

        $list = $GLOBALS['db']->autoExecute(DB_PREFIX."user",$data,'UPDATE',$where);
        log_result($list);
        if ($list!==false) {
            $user_redis->update_db($id, $data);
            $this->success ($info.'禁付费成功',1);

        }else{
            $this->error ($info.'禁付费成功',1);
        }
    }
    //禁竞拍
    public function forbid_auction(){
        $id= intval($_REQUEST['user_id']);
        log_result($id);
        $open_game =$GLOBALS['db']->getRow("select open_auction from ".DB_PREFIX."user where id =".$id);
        $where = 'id='.$id;
        require_once APP_ROOT_PATH.'mapi/lib/redis/BaseRedisService.php';
        require_once APP_ROOT_PATH.'mapi/lib/redis/UserRedisService.php';
        $user_redis = new UserRedisService();

        $data['open_auction'] = 1;
        $info = '';
        if($open_game['open_auction']==1) {
            $data['open_auction'] = 0;
            $info = '取消';
        }

        $list = $GLOBALS['db']->autoExecute(DB_PREFIX."user",$data,'UPDATE',$where);
        log_result($list);
        if ($list!==false) {
            $user_redis->update_db($id, $data);
            $this->success ($info.'禁竞拍成功',1);

        }else{
            $this->error ($info.'禁竞拍成功',1);
        }
    }
}
?>