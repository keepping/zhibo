<?php
// +----------------------------------------------------------------------
// | Fanwe 方维p2p借贷系统
// +----------------------------------------------------------------------
// | Copyright (c) 2011 http://www.fanwe.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: 云淡风轻(88522820@qq.com)
// +----------------------------------------------------------------------

class settingsModule  extends baseModule
{

    //账号与安全初始化
    public function security(){
        $root = array('status'=>1,'error'=>'');
        if(!$GLOBALS['user_info']){
            $root['error'] = "用户未登陆,请先登陆.";
            $root['status'] = 0;
            $root['user_login_status'] = 0;//有这个参数： user_login_status = 0 时，表示服务端未登陆、要求登陆，操作
        }else{
            $mobile = $GLOBALS['db']->getOne("SELECT mobile FROM ".DB_PREFIX."user where id=".intval($GLOBALS['user_info']['id']));
            //redis 获取数据
            fanwe_require(APP_ROOT_PATH.'mapi/lib/redis/UserRedisService.php');
	        $user_redis = new UserRedisService();
	        $user_mobile = $user_redis->getRow_db($GLOBALS['user_info']['id'],array('mobile'));
			
            if($mobile){
                $root['is_security'] = 1;
                $root['mobile'] = $user_mobile['mobile'];
            }else{
                $root['is_security'] = 0;
                $root['mobile'] = '';
            }
        }
        ajax_return($root);
    }

    //账号与安全保存
    public function mobile_binding(){
        $root = array('status'=>1,'error'=>'');
        if(!$GLOBALS['user_info']){
            $root['error'] = "用户未登陆,请先登陆.";
            $root['status'] = 0;
            $root['user_login_status'] = 0;//有这个参数： user_login_status = 0 时，表示服务端未登陆、要求登陆，操作
            ajax_return($root);
        }
        $mobile=strim($_REQUEST["mobile"]);
        $verify=strim($_REQUEST["verify_code"]);
        fanwe_require(APP_ROOT_PATH.'mapi/lib/redis/UserRedisService.php');
	    $user_redis = new UserRedisService();
        if(strlen($verify)< 0 || strlen($verify)== 0){
            $root['status'] = 0;
            $root['error'] = '请输入手机验证号码';
            ajax_return($root);
        }
        
        if($mobile){
            $old_mobile = $GLOBALS['db']->getOne("SELECT mobile FROM ".DB_PREFIX."user where id=".intval($GLOBALS['user_info']['id']));
           	//redis 读取
//	        $old_mobile = $user_redis->getRow_db($GLOBALS['user_info']['id'],array('mobile'));
                    
            if($mobile==$old_mobile){
                $root['status'] = 0;
                $root['error'] = '该手机已绑定';
                ajax_return($root);
            }
        }
        if (defined('ONE_MOBILE') && ONE_MOBILE) {
            if ($GLOBALS['db']->getOne("select id from  ".DB_PREFIX."user where mobile={$mobile}")) {
                ajax_return([
                    'status'=>0,
                    'error'=>'该手机已绑定其它账户',
                ]);
            }
        }
        $login_type_sql = "select login_type from ".DB_PREFIX."user where id = '".$GLOBALS['user_info']['id']."'";
		$login_type = $GLOBALS['db']->getOne($login_type_sql);
        $check_mobile_info =array(
        	'mobile'=>$mobile,
        	'login_type'=>$login_type,
        );
        check_registor_mobile($check_mobile_info);
        if(!$mobile){
            $condition="mobile = '".$old_mobile."'  and verify_code='".$verify."' ";
        }else{
            $condition="mobile = '".$mobile."'  and verify_code='".$verify."' ";
        }
        $num=$GLOBALS['db']->getOne("select count(*) from ".DB_PREFIX."mobile_verify_code where $condition  ORDER BY id DESC");

        if($num<=0){
            $root['status'] = 0;
            $root['error'] = '验证码错误';
            ajax_return($root);
        }else{
            $GLOBALS['db']->query("update ".DB_PREFIX."user set mobile='".$mobile."' where id=".intval($GLOBALS['user_info']['id']));
          	//redis 更新
	        $user_redis->update_db($GLOBALS['user_info']['id'],array('mobile'=>$mobile));
           
            $root['status'] = 1;
            $root['error'] = '保存成功';
            //微信绑定  
            $user= $GLOBALS['db']->getRow("select subscribe,wx_openid,mobile from ".DB_PREFIX."user where id=".intval($GLOBALS['user_info']['id']));
            //redis 读取
            $user = $user_redis->getRow_db($GLOBALS['user_info']['id'],array('subscribe','wx_openid','mobile'));
            
            if($user['subscribe'])
            $root['subscribe'] =1;
            else
            $root['subscribe'] =0;
			
			if($user['wx_openid']!='')
	        $root['binding_wx'] = 1;
	        else
	        $root['binding_wx'] = 0;
			
			if($user['mobile']!='')
	        $root['mobile_exist'] = 1;
	        else
	        $root['mobile_exist'] = 0;
	        
            ajax_return($root);
        }
    }

    //黑名单列表
    public function black_list(){
        $root = array('status'=>1,'error'=>'');
        if(!$GLOBALS['user_info']){
            $root['error'] = "用户未登陆,请先登陆.";
            $root['status'] = 0;
            $root['user_login_status'] = 0;//有这个参数： user_login_status = 0 时，表示服务端未登陆、要求登陆，操作
        }else{
        	$page = intval($_REQUEST['p']);//取第几页数据
        	if($page==0){
				$page = 1;
			}
			//每次20条
			$page_size=20;
			$limit = (($page-1)*$page_size).",".$page_size;

            $user_id = intval($GLOBALS['user_info']['id']);
            $user = $GLOBALS['db']->getAll("select u.id as user_id,u.nick_name,u.signature,u.sex,u.head_image,u.user_level,u.v_icon,b.id as bid from ".DB_PREFIX."user as u left join ".DB_PREFIX."black as b on u.id = b.black_user_id  where b.user_id=".$user_id." limit ".$limit);
            foreach($user as $k=>$v){
                $user[$k]['head_image'] = get_spec_image($v['head_image']);
                
                if($v['signature']==''){
                	$user[$k]['signature'] = '';
                }
                $user[$k]['black_url'] = url_app('home',array('podcast_id'=>$v['id']));
                $user[$k]['signature'] = htmlspecialchars_decode($user[$k]['signature']);
                $user[$k]['nick_name'] = htmlspecialchars_decode($user[$k]['nick_name']);
            }
            $root['user'] = $user;
            
            $rs_count = $GLOBALS['db']->getOne("select count(*) from ".DB_PREFIX."user as u left join ".DB_PREFIX."black as b on u.id = b.black_user_id  where b.user_id=".$user_id." ");
            if($page==0){
				$root['has_next'] = 0;
			}else{		
				if ($rs_count >= $page*$page_size){
                    $root['has_next'] = 1;
                }
				else {
                    $root['has_next'] = 0;
                }
			}
			
            $root['page'] = $page;
            
            
        }

        ajax_return($root);
    }

    //移除黑名单（无效）
    public function del_black(){
        /*$root = array('status'=>1,'error'=>'');
        if(!$GLOBALS['user_info']){
            $root['error'] = "用户未登陆,请先登陆.";
            $root['status'] = 0;
            $root['user_login_status'] = 0;//有这个参数： user_login_status = 0 时，表示服务端未登陆、要求登陆，操作
            ajax_return($root);
        }
        $user_id = intval($GLOBALS['user_info']['id']);
        $black_user_id=intval($_REQUEST['black_user_id']);
        if($GLOBALS['db']->getOne("SELECT count(*) FROM ".DB_PREFIX."black WHERE user_id=".$user_id.' and black_user_id='.$black_user_id)==0){
            $root['status'] =0;
            $root['error']= '黑名单不存在！';
            ajax_return($root);
        }
        $GLOBALS['db']->query("delete from ".DB_PREFIX."black where user_id = ".$user_id.' and black_user_id='.$black_user_id);
        if($GLOBALS['db']->affected_rows()>0){
            $root['status'] =1;
            $root['error']= '移除成功！';
            ajax_return($root);
        }else{
            $root['status'] =0;
            $root['error']= '移除失败！';
            ajax_return($root);
        }*/
    }

    //推送管理(无效)
    public function push_list(){
       /* $root = array('status'=>1,'error'=>'');
        if(!$GLOBALS['user_info']){
            $root['error'] = "用户未登陆,请先登陆.";
            $root['status'] = 0;
            $root['user_login_status'] = 0;//有这个参数： user_login_status = 0 时，表示服务端未登陆、要求登陆，操作
        }else{
            $user_id = intval($GLOBALS['user_info']['id']);
            $user = $GLOBALS['db']->getRow("SELECT id as user_id,head_image,sex,user_level FROM ".DB_PREFIX."user WHERE id=".$user_id);
            //$focus = $GLOBALS['db']->getAll("select u.id,u.head_image,u.nick_name,u.signature,u.sex,u.user_level,f.id as fid,f.is_remind from ".DB_PREFIX."user as u left join ".DB_PREFIX."focus as f on f.podcast_id=u.id where f.user_id=".$user_id);
            $user['head_image'] = get_spec_image($user['head_image']);

            /*foreach($focus as $k=>$v){
                $focus[$k]['head_image'] = get_abs_img_root($v['head_image']);
                $focus[$k]['push_url'] = url_app('home',array('podcast_id'=>$v['id']));
            }

            $root['focus'] = $focus;
            $root['user'] = $user;
        }
        ajax_return($root);*/
    }
    /*
     * 设置推送
     */
    public function set_push(){
        $root = array('status'=>1,'error'=>'');
        if(!$GLOBALS['user_info']){
            $root['error'] = "用户未登陆,请先登陆.";
            $root['status'] = 0;
            $root['user_login_status'] = 0;//有这个参数： user_login_status = 0 时，表示服务端未登陆、要求登陆，操作
            ajax_return($root);
        }
        $type = strim($_REQUEST['type']);
        if($type==1){//设置登录用户推送消息
            $user_id = intval($GLOBALS['user_info']['id']);
            $is_remind = intval($_REQUEST['is_remind'])?1:0;
            $status = $GLOBALS['db']->query("update ".DB_PREFIX."user set is_remind=".$is_remind." where id=".$user_id);
        }
        /*if($type == 2){//设置登录用户关注人推送消息
            $focus_id = strim($_REQUEST['focus_id']);
            $is_remind = strim($_REQUEST['is_remind']);
            $GLOBALS['db']->query("update ".DB_PREFIX."focus set is_remind=".$is_remind." where id=".$focus_id);
        }*/
       if($status){
	       	$root['status'] =1;
	        $root['error']= '设置成功！';
	        //REDIS 数据
	        fanwe_require(APP_ROOT_PATH.'mapi/lib/redis/BaseRedisService.php');
			fanwe_require(APP_ROOT_PATH.'mapi/lib/redis/UserRedisService.php');
			$user_redis = new UserRedisService();
			$data = array('is_remind'=>$is_remind);
            $user_redis->update_db($user_id,$data);
       }else{
       		$root['status'] =0;
        	$root['error']= '设置失败！';
       }
      ajax_return($root); 
        
    }

    //帮助和反馈
    public function help(){
        $m_config =  load_auto_cache("m_config");
        $root = array('status'=>1,'error'=>'');
        //热门问题,取前6条
        //审核期间去除带有支付方式相关字眼的文章
        if($m_config['ios_check_version'] != ''){
            $hot_sql = "select f.* from ".DB_PREFIX."faq as f where is_effect = 1 and f.group NOT REGEXP '.*支付宝.*|.*银行.*|.*信用卡.*|.*银联.*|.*微信充值.*|.*微信支付.*|.*第三方支付.*'  and f.question NOT REGEXP '.*支付宝.*|.*银行.*|.*信用卡.*|.*银联.*|.*微信充值.*|.*微信支付.*|.*第三方支付.*' and f.answer NOT REGEXP '.*支付宝.*|.*银行.*|.*信用卡.*|.*银联.*|.*微信充值.*|.*微信支付.*|.*第三方支付.*' order by f.sort desc, f.click_count desc limit 0,6";
        }else{
            $hot_sql = "select f.* from ".DB_PREFIX."faq as f where is_effect = 1  order by f.sort desc, f.click_count desc limit 0,6";
        }
        $hot_faq = $GLOBALS['db']->getAll($hot_sql,true,true);
        foreach($hot_faq as $k=>$v){
            $hot_faq[$k]['article_url'] = url_app('article',array('id'=>$v['id']));
        }

        //全部问题分类列表
        if($m_config['ios_check_version'] != ''){
            $cate_sql = "select f.group from ".DB_PREFIX."faq as f where f.is_effect = 1 and f.group NOT REGEXP '.*支付宝.*|.*银行.*|.*信用卡.*|.*银联.*|.*微信充值.*|.*微信支付.*|.*第三方支付.*'  group by (f.group)";
        }else{
            $cate_sql = "select f.group from ".DB_PREFIX."faq as f where f.is_effect = 1  group by (f.group)";
        }
        $faq_cates = $GLOBALS['db']->getAll($cate_sql,true,true);;
        foreach ($faq_cates as $k=>$v) {
            $faq_cates[$k]['articlelist_url'] = url_app('articlelist',array('faq_group'=>urlencode($v['group'])));
        }
        $root['hot_faq'] = $hot_faq;
        $root['faq_cates'] = $faq_cates;
        $root['page_title'] = '帮助与反馈';
        api_ajax_return($root);
    }

    //同一类型问题列表
    public function faq(){
        $m_config =  load_auto_cache("m_config");
        $root = array('status'=>1,'error'=>'');
        $faq_group = strim($_REQUEST['faq_group']);
        $faq_group = $faq_group!=''?$faq_group:'充值问题';
        if($m_config['ios_check_version'] != ''){
            $sql = "select f.* from ".DB_PREFIX."faq as f where is_effect = 1 and f.group = '".$faq_group."' and f.question NOT REGEXP '.*支付宝.*|.*银行.*|.*信用卡.*|.*银联.*|.*微信充值.*|.*微信支付.*|.*第三方支付.*' and f.answer NOT REGEXP '.*支付宝.*|.*银行.*|.*信用卡.*|.*银联.*|.*微信充值.*|.*微信支付.*|.*第三方支付.*' order by f.sort desc, f.click_count desc";
        }else{
            $sql = "select f.* from ".DB_PREFIX."faq as f where is_effect = 1 and f.group = '".$faq_group."' order by f.sort desc, f.click_count desc";
        }
        $faq_list = $GLOBALS['db']->getAll($sql,true,true);
        foreach($faq_list as $k=>$v){
            $faq_list[$k]['article_url'] = url_app('article',array('id'=>$v['id']));
        }

        $root['faq_list'] = $faq_list;
        $root['page_title'] = $faq_group;
        api_ajax_return($root);
    }

    //问题展示
    public function faq_show(){
        $root = array('status'=>1,'error'=>'');
        $id = intval($_REQUEST['id']);
        $faq_info = $GLOBALS['db']->getRow("select f.* from ".DB_PREFIX."faq as f where f.id = ".$id,true,true);
        if(!empty($faq_info)){
            $GLOBALS['db']->query("UPDATE ".DB_PREFIX."faq SET click_count=click_count+1 WHERE id = ".$faq_info['id']);
        }
        $faq_info['articlelist_url'] = url_app('articlelist',array('faq_group'=>urlencode($faq_info['group'])));
        $root['faq_info'] = $faq_info;
        $root['page_title'] = $faq_info['question'];
        api_ajax_return($root);
    }

    //关于我们
    public function article_cate(){
        $m_config =  load_auto_cache("m_config");
        $root = array('status'=>1,'error'=>'');
        if($m_config['ios_check_version'] != ''){
            $article_cates = $GLOBALS['db']->getAll("select * from ".DB_PREFIX."article_cate where is_effect = 1 and is_delete = 0 and type_id=0 and title NOT REGEXP '.*支付宝.*|.*银行.*|.*信用卡.*|.*银联.*|.*微信充值.*|.*微信支付.*|.*第三方支付.*' order by sort desc",true,true);
        }else{
            $article_cates = $GLOBALS['db']->getAll("select * from ".DB_PREFIX."article_cate where is_effect = 1 and is_delete = 0 and type_id=0 order by sort desc",true,true);
        }
        foreach ($article_cates as $k=>$v) {
            if($m_config['ios_check_version'] != ''){
                $article = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."article where is_effect = 1 and is_delete = 0 and cate_id=".$v['id']." and title NOT REGEXP '.*支付宝.*|.*银行.*|.*信用卡.*|.*银联.*|.*微信充值.*|.*微信支付.*|.*第三方支付.*' and content NOT REGEXP '.*支付宝.*|.*银行.*|.*信用卡.*|.*银联.*|.*微信充值.*|.*微信支付.*|.*第三方支付.*' order by sort desc",true,true);
            }else{
                $article = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."article where is_effect = 1 and is_delete = 0 and cate_id=".$v['id']." order by sort desc",true,true);
            }
            if($article){
                $article_cates[$k]['aboutapp_url'] = url_app('aboutappdetail',array('cate_id'=>$v['id']));
            }else{
                unset($article_cates[$k]);
            }

        }
        $root['page_title'] = '关于';
        $root['article_cates'] = $article_cates;
        $root['site_license'] = app_conf("SITE_LICENSE");
        api_ajax_return($root);
    }

    //文章展示
    public function article_show(){
        //输出文章
        $root = array('status'=>1,'error'=>'');
        $cate_id = intval($_REQUEST['cate_id']);
        $cate_id = $cate_id>0?$cate_id:1;
        $article = $GLOBALS['db']->getRow("select a.* from ".DB_PREFIX."article as a where is_effect = 1 and is_delete = 0 and cate_id=".$cate_id." order by sort desc",true,true);
        $root['page_title'] = $article['title'];
        $root['article'] = $article;
        api_ajax_return($root);
    }

    //意见反馈（预留功能）
    public function opinion(){
        /*$root = array();
        $root['status'] = 1;
        if(!$GLOBALS['user_info']){
            $root['error'] = "用户未登陆,请先登陆.";
            $root['status'] = 0;
            $root['user_login_status'] = 0;//有这个参数： user_login_status = 0 时，表示服务端未登陆、要求登陆，操作
        }else{
            $user_id = intval($GLOBALS['user_info']['id']);//登录用户
            $data_info = array();
            $data_info['content']=strim($_REQUEST['content']);
            if(empty($data_info['content'])){
                $root['status'] = 0;
                $root['error'] = '请填写反馈内容';
            }
            $data_info['user_id'] = $user_id;
            $data_info['create_time'] = get_gmtime();
            $GLOBALS['db']->autoExecute(DB_PREFIX."opinion",$data_info,"INSERT","","SILENT");
            $message_id = $GLOBALS['db']->insert_id();
            if($message_id>0){
                $root['status'] = 1;
                $root['error'] = '添加成功';
            }else{
                $root['status'] = 0;
                $root['error'] = '添加失败';
            }
        }
       ajax_return($root);*/
    }
}


?>