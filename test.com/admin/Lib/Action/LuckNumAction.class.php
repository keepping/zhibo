<?php

class LuckNumAction extends CommonAction{
    //检查模块开关是否开启
    public function  check_Module(){
        if(OPEN_LUCK_NUM == 0){
            $this->error("模块开关关闭");
        }
    }
    public function index()
    {
        $data = $_REQUEST;

        $this->check_Module();
        //查询昵称
        if (trim($data['nick_name'] != '')){
            $parameter = "nick_name like " . urlencode ( '%'.trim($data['nick_name']).'%' ) . "&";
            $sql_w = "nick_name like '%".trim($data['nick_name'])."%' and ";
        }
        //查询靓号
        if (intval($data['luck_num'] > 0)){
            $parameter.= "luck_num=" . intval($data['luck_num']). "&";
            $sql_w .= "l.luck_num=".intval($data['luck_num'])." and ";
        }
        //是否卖出
        if ($data['is_sale'] != ''){
            if (intval($data['is_sale']) == 1){
                $parameter.= "is_sale=" . intval($data['is_sale']). "&";
                $sql_w .= "is_sale=".intval($data['is_sale'])." and ";
            }elseif(intval($data['is_sale']) == 0){
                $parameter.= "is_sale=" . intval($data['is_sale']). "&";
                $sql_w .= "is_sale=".intval($data['is_sale'])." and ";
            }
        }

        $model = D();
        $sql_str = "SELECT l.*,u.nick_name,u.id as user_id FROM ".DB_PREFIX
            ."luck_num AS l LEFT OUTER JOIN ".DB_PREFIX."user AS u ON l.luck_num = u.luck_num"
            ." WHERE 1=1" . " AND " .$sql_w . " 1=1 ORDER BY l.luck_num ASC";//取出靓号表中的数据，到user表中取靓号昵称

		 $count_sql = "SELECT count(*)  as tpcount FROM ".DB_PREFIX
            ."luck_num AS l LEFT OUTER JOIN ".DB_PREFIX."user AS u ON l.luck_num = u.luck_num"
            ." WHERE 1=1" . " AND " .$sql_w . " 1=1 ORDER BY l.luck_num ASC";

        $volist = $this->_Sql_list($model,$sql_str,'&'.$parameter,'',0,$count_sql);

        $this->assign("list", $volist);
        $this->display();
    }

    public function foreverdelete(){
        $this->check_Module();
        $ajax = intval($_REQUEST['ajax']);
        $id =$_REQUEST['id'];

        if (isset($id)){
            $condition = array ('id' => array ('in', explode ( ',', $id ) ) );

            $rel_data = M(MODULE_NAME)->where($condition)->findAll();
            foreach($rel_data as $data)
            {
                $info[] = $data['lucknum'];
            }

            if($info) $info = implode(",",$info);
            $result = M(MODULE_NAME)->where($condition)->delete();
            if ($result!==false){
                //成功
                save_log($info.l("FOREVER_DELETE_SUCCESS"),1);
                $this->success(l("FOREVER_DELETE_SUCCESS"),$ajax);
            }else {
                //失败
                save_log($info.l("FOREVER_DELETE_FAILED"),0);
                $this->error (l("FOREVER_DELETE_FAILED"),$ajax);
            }
        } else {
            $this->error (l("INVALID_OPERATION"),$ajax);
        }
    }

    //进入编辑页
    public function edit(){
        $this->check_Module();

        $id = intval($_REQUEST['id']);
        $num_data = M(MODULE_NAME)->where('id='.$id)->find();//根据ID获取靓号相关数据
        if (intval($num_data['is_sale']) == 1){
            $condition['id'] = $num_data['luck_num'];
            $vo = M('user')->where($condition)->find();
        }
        $vo['luck_num'] = $num_data['luck_num'];
        $vo['is_sale'] = $num_data['is_sale'];
        $vo['price'] = $num_data['price'];
        $vo['id'] = $id;

        $this->assign('vo',$vo);

        $region_lv2 = $GLOBALS['db']->getAll("select * from ".DB_PREFIX."region_conf where region_level = 2 order by py asc");  //二级地址
        $this->assign("region_lv2",$region_lv2);

        //会员等级信息
        $user_level = $GLOBALS['db']->getAll("select * from ".DB_PREFIX."user_level order by level ASC");
        $this->assign("user_level",$user_level);

        //认证类型
        $authent_list = M("AuthentList")->findAll();
        $this->assign("authent_list",$authent_list);

        $this->display();
    }

    public function add(){
        $this->check_Module();
        $this->display();
    }

    //编辑价格
    public function set_price(){
        $this->check_Module();
        $model = M(MODULE_NAME);
        $data['id'] = intval($_REQUEST['id']);
        $data['price'] = intval($_REQUEST['price']);
        $res = $model->save($data);
        if ($res === false){
            $this->ajaxReturn(array("status"=>0,"error"=>"更新价格失败"));
        }else{
            $this->ajaxReturn(array("status"=>1,"error"=>"更新价格成功"));
        }
    }

    //卖出靓号
    public function update(){
        B('FilterString');
        filter_request($_REQUEST);
        $this->check_Module();
        $num_data = M(MODULE_NAME)->create();//取得要更新的靓号数据

        if(!check_empty($num_data['luck_num'])){
            $this->error("靓号不能为空");
        }

        if(intval($_REQUEST['is_new_user']) === 0){
            //是老用户，将靓号号码写入即可
            $user['id'] = $_REQUEST['user_id'];

            $id_user = M('user')->where("id=".$user['id'])->getField('id');//购买用户的id
            if($id_user == 0){
                $this->error("用户ID不存在");
            }
            //检查用户是否已有靓号
            $user_luck_num = M('user')->where('id='.$user['id'])->getField('luck_num');
            if ($user_luck_num > 0){
                M(MODULE_NAME)->where('luck_num='.$user_luck_num)->setField('is_sale',0);
            }

            $user['luck_num'] = $_REQUEST['luck_num'];
            $where = "id=".$user['id'];
            if(!$GLOBALS['db']->autoExecute(DB_PREFIX."user",$user,"UPDATE",$where)){
                //写入失败
                $log_info = $user['luck_num'];
                save_log($log_info.L("UPDATE_FAILED"),0);
                $this->error(L("UPDATE_FAILED"),0);
            }
            $this->assign("jumpUrl",u(MODULE_NAME."/index"));//成功后跳转到首页
            //同步到redis
            fanwe_require(APP_ROOT_PATH.'mapi/lib/redis/BaseRedisService.php');
            fanwe_require(APP_ROOT_PATH.'mapi/lib/redis/UserRedisService.php');
            $user_redis = new UserRedisService();
            $user_redis->update_db($user['id'],$user);
        }else{
            //是新用户的情况下
            $_REQUEST['id'] = get_max_user_id();

            $_REQUEST['nick_name'] = trim($_REQUEST['nick_name']);

            //更新用户表
            if($_REQUEST['v_explain']==''){
                $_REQUEST['v_explain'] = $_REQUEST['authentication_type'];
            }
            $_REQUEST['v_icon'] = get_spec_image(M('AuthentList')->where("name='".trim($_REQUEST['authentication_type']."'"))->getField("icon"));
            $_REQUEST['score'] =  $GLOBALS['db']->getOne("select score from ".DB_PREFIX."user_level where `level`=".$_REQUEST['user_level'],true,true);

            $res = $this->save_user($_REQUEST,'INSERT',$update_status=1);
            //写入失败
            if($res['status']==0)
            {
                $this->error($res['info']);
            }
        }

        //写入成功，更新靓号表
        $num_data['is_sale'] = 1;//标记为卖出
        $result_num = M(MODULE_NAME)->save($num_data);
        if ($result_num === false){
            //错误
            save_log(L("UPDATE_FAILED"),0);
            $this->error(L("UPDATE_FAILED"),0);
        }
        $log_info = $_REQUEST['nick_name'];
        $this->assign("jumpUrl",u(MODULE_NAME."/index"));//成功后跳转到首页
        save_log($log_info.L("INSERT_SUCCESS"),1);
        $this->success(L("INSERT_SUCCESS"));
    }

    public function insert(){
        B('FilterString');
        $this->check_Module();
        $data = M(MODULE_NAME)->create();
        $data['luck_num'] = intval($data['luck_num']);
        $data['price'] = intval($data['price']);

        if(!check_empty($data['luck_num'])){
            $this->error("靓号不能为空");
        }

        if ($data['luck_num'] <= 0 || $data['price'] <= 0){
            $this->error("请输入大于0的数字");
        }

        //查看需要添加的靓号是否已存在
        $condition['luck_num'] = $data['luck_num'];
        $count = M(MODULE_NAME)->where($condition)->count();
        $count_user = M('User')->where('id='.$data['luck_num'])->count();
        if ($count > 0){
            $this->error("该靓号已存在");
        }
        if ($count_user > 0){
            $this->error("该账号已经被注册为主播ID");
        }

        //计算靓号位数
        $data['figure'] = strlen($data['luck_num']);

        $list = M(MODULE_NAME)->add($data);
        if ($list!==false){
            //成功
            $this->assign("jumpUrl",u(MODULE_NAME."/index"));
            save_log(L("INSERT_SUCCESS"),1);
            $this->success(L("INSERT_SUCCESS"));
        }else{
            //错误
            save_log(L("INSERT_FAILED"),0);
            $this->error(L("INSERT_FAILED"),0);
        }
    }

    //传靓号
    public function get_luck_num(){
        $this->check_Module();
        $id= intval($_REQUEST['id']);

        $condition['id'] = $id;
        $model = M(MODULE_NAME);
        $root= $model->where($condition)->select();

        admin_ajax_return($root);
    }

    //出售状态切换
//    public function change_sale(){
//        $this->check_Module();
//        $id= intval($_REQUEST['id']);
//
//        $condition['id'] = $id;
//        $model = M(MODULE_NAME);
//        $info = $model->where($condition)->getField("luck_num");//取靓号号码
//        $old_sale_status = $model->where($condition)->getField('is_sale');
//        $new_sale_status = $old_sale_status==0? 1 : 0;//切换状态
//
//        $result = $model->where($condition)->setField('is_sale',$new_sale_status);
//
//        save_log("靓号".$info.l("CHANGE_SALE_".$new_sale_status),1);
//        $this->ajaxReturn($new_sale_status,l("CHANGE_SALE_".$new_sale_status),1);
//    }

    /**
     * 生成会员数据
     * @param $user_data  提交[post或get]的会员数据
     * @param $mode  处理的方式，注册或保存
     * 返回：data中返回出错的字段信息，包括field_name, 可能存在的field_show_name 以及 error 错误常量
     * $update_status后台更新标示字段
     */
    function save_user($user_data,$mode='INSERT',$update_status)
    {
        //验证结束开始插入数据
        $user_data['nick_name'] = htmlspecialchars_decode($user_data['nick_name']);
        if(trim($user_data['nick_name'])!=''){
            $user['nick_name'] = trim($user_data['nick_name']);
            //检查昵称
            if(strlen($user['nick_name'])>60){
                $res['info'] = "昵称太长";
                $res['status'] =0;
                return $res;
            }
        }else{
            $res['info'] = "昵称不能为空";
            $res['status'] =0;
            return $res;
        }

        $head_image = strim($user_data['head_image']);
        if($head_image){
            $user['head_image'] = del_domain_url($head_image);
        }else{
//            $user['head_image'] = "./public/attachment/201608/29/11/57c3ae5abe47d.JPG";
            $res['info'] = "请上传头像";
            $res['status'] =0;
            return $res;
        }

        //开始数据验证1
        $res = array('status'=>1,'info'=>'','data'=>''); //用于返回的数据

        if($user_data['mobile']!='' && !check_mobile(trim($user_data['mobile'])))
        {
            $res['info']	= '手机格式错误:'.$user_data['mobile'];
            $res['status'] = 0;
            return $res;
        }

        if($user_data['identify_number']!=''&& !isCreditNo($user_data['identify_number']) &&$update_status!=1)
        {
            $res['info']	= '请填写正确的身份证号码';
            $res['status'] = 0;
            return $res;
        }

        if(isset($user_data['luck_num'])){
            $user['luck_num'] = intval($user_data['luck_num']);
        }else{
            $res['info'] = "缺少靓号";
            $res['status'] =0;
            return $res;
        }
        $user['create_time'] = get_gmtime();
        //禁播
        if(isset($user_data['is_ban']))
            $user['is_ban'] = intval($user_data['is_ban']);

        if(intval($user_data['is_ban'])){
            $user['ban_time'] = 0;
        }else{
            if(isset($user_data['ban_time'])){
                $ban_time = strim($user_data['ban_time']);
                $user['ban_time'] = $ban_time!=''?to_timespan($ban_time):0;
            }

        }
        //机器人
        if(isset($user_data['is_robot'])){
            $user['is_robot'] = intval($user_data['is_robot']);
        }
        if(isset($user_data['user_level']))
            $user['user_level'] = intval($user_data['user_level']);

        if(isset($user_data['is_authentication']))
            $user['is_authentication'] = intval($user_data['is_authentication']);

        if(isset($user_data['authentication_type']))
            $user['authentication_type'] = strim($user_data['authentication_type']);

        if(isset($user_data['identify_number']))
            $user['identify_number'] = strim($user_data['identify_number']);

        if(isset($user_data['authentication_name']))
            $user['authentication_name'] = strim($user_data['authentication_name']);

        if(isset($user_data['contact']))
            $user['contact'] = strim($user_data['contact']);

        if(isset($user_data['from_platform']))
            $user['from_platform'] = strim($user_data['from_platform']);

        if(isset($user_data['wiki']))
            $user['wiki'] = strim($user_data['wiki']);

        if(isset($user_data['province']))
            $user['province'] = $user_data['province'];

        if(isset($user_data['city']))
            $user['city'] = $user_data['city'];

        if(isset($user_data['sex']))
            $user['sex'] = intval($user_data['sex']);

        if(isset($user_data['is_edit_sex']))
            $user['is_edit_sex'] = intval($user_data['is_edit_sex']);

        if(isset($user_data['intro']))
            $user['intro'] = strim($user_data['intro']);

        $thumb_head_image = strim($user_data['thumb_head_image']);
        if($thumb_head_image){
            $user['thumb_head_image'] = del_domain_url($thumb_head_image);
        }

        if(isset($user_data['signature']))
            $user['signature'] = htmlspecialchars_decode(trim($user_data['signature']));

        if(isset($user_data['job']))
            $user['job'] = htmlspecialchars_decode(trim($user_data['job']));

        if($user_data['birthday']!=''){
            $user['birthday'] = $user_data['birthday'];
        }
        if(isset($user_data['emotional_state']))
            $user['emotional_state']=strim($user_data['emotional_state']);

        if(isset($user_data['identify_hold_image']))
            $user['identify_hold_image']=strim($user_data['identify_hold_image']);

        if(isset($user_data['identify_positive_image']))
            $user['identify_positive_image']=strim($user_data['identify_positive_image']);

        if(isset($user_data['identify_nagative_image']))
            $user['identify_nagative_image']=strim($user_data['identify_nagative_image']);

        if(isset($user_data['v_explain']))
            $user['v_explain']=strim($user_data['v_explain']);

        if(isset($user_data['user_type']))
            $user['user_type'] = intval($user_data['user_type']);

        if(isset($user_data['score']))
            $user['score'] = intval($user_data['score']);
        //验证结束开始插入数据（这里没写user模块写不进去）
        //会员状态
        if(intval($user_data['is_effect'])!=0)
        {
            $user['is_effect'] = $user_data['is_effect'];
        }else{
            $user['is_effect'] =1;
        }

        if(isset($user_data['mobile']) && strim($user_data['mobile'])){
            $user['mobile'] = strim($user_data['mobile']);
        }

        if(isset($user_data['v_explain']) && strim($user_data['v_explain'])){
            $user['v_explain'] = strim($user_data['v_explain']);
        }
        if(isset($user_data['v_icon']) && strim($user_data['v_icon'])){
            $user['v_icon'] = strim($user_data['v_icon']);
        }

        if(isset($user_data['authent_list_id']) && strim($user_data['authent_list_id'])){
            $user['authent_list_id'] = strim($user_data['authent_list_id']);
        }

        if(isset($user_data['is_authentication'])){
            if(intval($user_data['is_authentication'])==3 || intval($user_data['is_authentication'])==1 || intval($user_data['is_authentication'])==0){
                $user['v_icon'] = '';
                $user['v_explain'] = '';
            }
        }

        if(isset($user_data['is_admin']))
            $user['is_admin'] = intval($user_data['is_admin']);

        if($mode == 'INSERT')
        {
            $user['code'] = ''; //默认不使用code, 该值用于其他系统导入时的初次认证
        }
        else
        {
            $user['code'] = $GLOBALS['db']->getOne("select code from ".DB_PREFIX."user where id =".$user_data['id']);
        }
        if($mode == 'INSERT')
        {
            $user['id'] = $user_data['id'];
            $where = '';
        }
        else
        {
            $where = "id=".intval($user_data['id']);
        }
        if($GLOBALS['db']->autoExecute(DB_PREFIX."user",$user,$mode,$where))
        {
            if($mode == 'INSERT')
            {
                //添加成功，同步信息
                require_once(APP_ROOT_PATH.'system/tim/TimApi.php');
                $api = createTimAPI();
                $ret = $api->account_import((string)$user['id'], $user['nick_name'], $user['head_image']);
                if($ret['ErrorCode']==0){
                    $GLOBALS['db']->query("update ".DB_PREFIX."user set synchronize = 1 where id =".$user['id']);
                }
                //redis化
                fanwe_require(APP_ROOT_PATH.'mapi/lib/redis/BaseRedisService.php');
                fanwe_require(APP_ROOT_PATH.'mapi/lib/redis/UserRedisService.php');
                $user_redis = new UserRedisService();
                $ridis_data = $user_redis->reg_data($user);
                $user_redis->insert_db($user['id'],$ridis_data);
                //$GLOBALS['msg']->manage_msg('MSG_MEMBER_REMIDE',$user_id,array('type'=>'会员注册','content'=>'您于 '.get_client_ip() ."注册成功!"));
            }
            else
            {
                $user_id = $user_data['id'];
                user_deal_to_reids(array($user_id));
            }
        }
        $res['data'] = $user_id;

        return $res;
    }

}

?>