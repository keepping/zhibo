<?php
// +----------------------------------------------------------------------
// | Fanwe 方维p2p借贷系统
// +----------------------------------------------------------------------
// | Copyright (c) 2011 http://www.fanwe.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: 云淡风轻(88522820@qq.com)
// +----------------------------------------------------------------------

class indexModule  extends baseModule
{

 //首页
	public function index()
	{	$root = array();
	
	
		$sex = intval($_REQUEST['sex']);//性别 0:全部, 1-男，2-女
		$cate_id = intval($_REQUEST['cate_id']);//话题id
		$city = strim($_REQUEST['city']);//城市(空为:热门)
		if($city=='热门' || $city=='null'){
			$city = '';
		}
		
		if ($cate_id ==0){
			//首页 轮播
			$root['banner'] = load_auto_cache("banner_list");
			if($root['banner']==false){
				$root['banner'] = array();
			}
		}else{
			//主题相关内容
			$cate = load_auto_cache("cate_id",array('id'=>$cate_id));
			if ($cate['url'] != '' && $cate['image'] != ''){
				$root['banner'] = $cate['banner'];
				$root['cate'] = $cate;
			}
		}	
		
		$root['sex'] = $sex;//
		$root['cate_id'] = $cate_id;//
		$root['city'] = $city;//
		
		$m_config =  load_auto_cache("m_config");//初始化手机端配置
		$sdk_version_name = strim($_REQUEST['sdk_version_name']);
		$dev_type = strim($_REQUEST['sdk_type']);
		if($dev_type == 'ios' && $m_config['ios_check_version'] != '' && $m_config['ios_check_version'] == $sdk_version_name){
			$list = $this->check_video_list("select_video_check",array('sex_type'=>$sex,'area_type'=>$city,'cate_id'=>$cate_id));
		}else{
			$list = load_auto_cache("select_video",array('sex_type'=>$sex,'area_type'=>$city,'cate_id'=>$cate_id));
		}
		if (defined('SHOW_IS_GAMING') && SHOW_IS_GAMING) {
			fanwe_require(APP_ROOT_PATH . 'mapi/lib/redis/VideoRedisService.php');
			$video_redis = new VideoRedisService();
			foreach ($list as $key => $value) {
				$live_in = $video_redis->getOne_db(intval($value['room_id']), 'live_in') == 1;
				$list[$key]['is_gaming'] = intval($video_redis->getOne_db(intval($value['room_id']), 'game_log_id')) && $live_in ? 1 : 0;
			}
		}
		
		
		$root['list'] = $list;
		$root['status'] = 1;
		$root['has_next'] = 0;
		$root['page'] = 1;//
		
		$root['init_version'] = intval($m_config['init_version']);//手机端配置版本号
		
		ajax_return($root);
	}
	
    //最新
    public function new_video(){
    	
    	$root = array();
    	
    	$root['cate_top'] = load_auto_cache("cate_top");
    	
    	$m_config =  load_auto_cache("m_config");//初始化手机端配置
		$sdk_version_name = strim($_REQUEST['sdk_version_name']);
		$dev_type = strim($_REQUEST['sdk_type']);
		if($dev_type == 'ios' && $m_config['ios_check_version'] != '' && $m_config['ios_check_version'] == $sdk_version_name){
			$list = $this->check_video_list("new_video_check");
		}else{
			$list = load_auto_cache("new_video");
		}

    	$root['list'] = $list;
    	$root['status'] = 1;
    	$root['has_next'] = 0;
    	$root['page'] = 1;//
    	$root['init_version'] = intval($m_config['init_version']);//手机端配置版本号
    	
    	ajax_return($root);
    	
    }
    public function new_pc_video(){
    	$m_config =  load_auto_cache("m_config");//初始化手机端配置
		$sdk_version_name = strim($_REQUEST['sdk_version_name']);
		$dev_type = strim($_REQUEST['sdk_type']);
		if($dev_type == 'ios' && $m_config['ios_check_version'] != '' && $m_config['ios_check_version'] == $sdk_version_name){
			$list = $this->check_video_list("new_video_check", array('create_type' => 1));
		}else{
			$list = load_auto_cache("new_video", array('create_type' => 1));
		}

        $root = array();
    	$root['list'] = $list;
    	$root['status'] = 1;
    	$root['has_next'] = 0;
    	$root['page'] = 1;//

    	ajax_return($root);

    }

    //我关注的主播 直播
    public function focus_video(){
    	$root['page_title'] = '关注';
    	//$GLOBALS['user_info']['id'] = 320;
    	if(!$GLOBALS['user_info']){
			$root['error'] = "用户未登陆,请先登陆.";
			$root['status'] = 0;
			$root['user_login_status'] = 0;
		}else{
	        //关注
	        $user_id = intval($GLOBALS['user_info']['id']);//登录用户id
	        
	        fanwe_require(APP_ROOT_PATH.'mapi/lib/redis/UserFollwRedisService.php');
	        $userfollw_redis = new UserFollwRedisService($user_id);
	        $user_list = $userfollw_redis->following();

	        //私密直播  video_private,私密直播结束后， 本表会清空
	        fanwe_require(APP_ROOT_PATH.'mapi/lib/redis/VideoPrivateRedisService.php');
	        $video_private_redis = new VideoPrivateRedisService();
	        $private_list = $video_private_redis->get_video_list($user_id);
	        
	        /*
	        $sql = "select video_id from ".DB_PREFIX."video_private where status = 1 and user_id = ".$user_id;
	        $private_list = $GLOBALS['db']->getAll($sql,true,true);
	        */
	        
	        $list = array();
	        
	        if(sizeof($private_list) || sizeof($user_list)){
	        	$m_config =  load_auto_cache("m_config");//初始化手机端配置
				$sdk_version_name = strim($_REQUEST['sdk_version_name']);
				$dev_type = strim($_REQUEST['sdk_type']);
				if($dev_type == 'ios' && $m_config['ios_check_version'] != '' && $m_config['ios_check_version'] == $sdk_version_name){
					$list_all = load_auto_cache("select_video_check",array('has_private'=>1));
				}else{
					$list_all = load_auto_cache("select_video",array('has_private'=>1));
				}
	        	
	        	
		        foreach($list_all as $k=>$v){
		        	if ((($v['room_type'] == 1 && in_array($v['room_id'], $private_list)) || ($v['room_type'] == 3 && in_array($v['user_id'], $user_list)))&&($v['user_id']!='13888888888'||$v['user_id']!='13999999999')) {
		        		$list[] = $v;
		        	}else if($v['user_id']==$user_id&&$v['room_type']==1&&$v['live_in']==1){
		        		$user_video = array();
		        		$user_video = $v;
		        	}
		        }
	        }
	        
	        if($user_video){
	        	array_unshift($list,$user_video);
	        }
	        
	        $root['list'] = $list;
	        
	        $playback = load_auto_cache("playback_list",array('user_id'=>$user_id));
	        	
	        $root['playback'] = $playback;
	        $root['status'] = 1;
	        
    	}
        ajax_return($root);
    }

    //查询话题列表
    function search_video_cate(){
    
    	$page = intval($_REQUEST['p']);//取第几页数据
    	$title = strim($_REQUEST['title']);
    		
    	if($page==0){
    		$page = 1;
    	}
    
    	$page_size=50;
    	$limit = (($page-1)*$page_size).",".$page_size;
    
    
    	//			if($title){
    	//				fanwe_require(APP_ROOT_PATH.'mapi/lib/redis/VideoRedisService.php');
    	//				$video_redis = new VideoRedisService();
    	//				$root = $video_redis->get_all_cate($title,$page,$page_size);
    	//			}else{
    	//				$root = load_auto_cache("all_cate",array('title'=>$title,'page'=>$page,'page_size'=>$page_size));
    	//			}
    	//$root = load_auto_cache("all_cate",array('title'=>$title,'page'=>$page,'page_size'=>$page_size));
    
    	if ($title){
    		$sql = "select vc.id as cate_id,vc.title,vc.num from ".DB_PREFIX."video_cate as vc
						where vc.is_effect = 1 and vc.title like '%".$title."%' order by vc.sort desc, vc.num desc limit ".$limit;
    
    	}else{
    		$sql = "select vc.id as cate_id,vc.title,vc.num from ".DB_PREFIX."video_cate as vc
						where vc.is_effect = 1  order by vc.sort desc, vc.num desc limit ".$limit;
    	}
    	 
    	//查询话题列表,修改成 从只读数据库中取,但不是高效做法;主并发时,可以加入阿里云的搜索服务
    	//https://www.aliyun.com/product/opensearch?spm=5176.8142029.388261.62.tgDxhe
    	$list = $GLOBALS['db']->getAll($sql,true,true);
    	foreach($list as $k=>$v){
    		$list[$k]['title'] ="#".$v['title']."#";
    	}
    	if($page==0){
    		$root['has_next'] = 0;
    	}else{
    		if (count($list) == $page_size)
    			$root['has_next'] = 1;
    		else
    			$root['has_next'] = 0;
    	}
    	 
    	$root['page'] = $page;//
    	 
    	$root['list'] =$list;
    	//$root['video_cate'] =$list;
    
    	$root['status'] =1;
    	 
    	ajax_return($root);
    }
    
    //按地区（省份）
    //0:全部;1:男;2:女
    function search_area(){
    	 
    	/*
    	 fanwe_require(APP_ROOT_PATH.'mapi/lib/redis/VideoRedisService.php');
    	$video_redis = new VideoRedisService();
    	$root = $video_redis->get_sex_area_list($sex);
    	*/
    	 
    	$sex = intval($_REQUEST['sex']);//性别 0:全部, 1-男，2-女
    	$list = load_auto_cache("sex_area",array('sex'=>$sex));
    
    	$root = array();
    	$root['list'] = $list;
    	$root['status'] = 1;
    	$root['total_num'] = count($list);
    
    	ajax_return($root);
    }
	//审核版本读取的列表
	function check_video_list($type='',$date=array()){
		$list = '';
		if($type!=''){
			if($type=='new_video_check'){
				$list = load_auto_cache("new_video_check");
			}else{
				$list = load_auto_cache("select_video_check",$date);
			}
		}
		return $list;	
	}

	//分类
    public function classify()
    {
        $root = array();
        $m_config =  load_auto_cache("m_config");//初始化手机端配置
        $sdk_version_name = strim($_REQUEST['sdk_version_name']);
        $dev_type = strim($_REQUEST['sdk_type']);
        $classified_id = intval($_REQUEST['classified_id']);
        if(!$classified_id){
            $classified_id = 1;
        }
        if($dev_type == 'ios' && $m_config['ios_check_version'] != '' && $m_config['ios_check_version'] == $sdk_version_name){
            $list = $this->check_video_list("select_video_check",array('is_classify'=>$classified_id));
        }else {
			$list = load_auto_cache("select_video", array('is_classify' => $classified_id));
		}
		$root['list'] = $list;
        $root['status'] = 1;
        $root['has_next'] = 0;
        $root['page'] = 1;//
        $root['init_version'] = intval($m_config['init_version']);//手机端配置版本号

        ajax_return($root);
    }
}

?>