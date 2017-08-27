<?php
// +----------------------------------------------------------------------
// | Fanwe 方维众筹商业系统
// +----------------------------------------------------------------------
// | Copyright (c) 2011 http://www.fanwe.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: 甘味人生(526130@qq.com)
// +----------------------------------------------------------------------

class WeiboConfAction extends CommonAction{
    //检查模块开关是否开启
    public function  check_Module(){
        $m_config =  load_auto_cache("m_config");
        if($m_config['has_dirty_words']==0){
            $this->redirect('APP_ROOT+'.get_manage_url_name().'?m=Conf&a=mobile&');
        }
    }

	public function mobile()
	{
		$config = M("MConfig")->order("sort asc")->findAll();
		$unset_group_id = array(
			'PC端设置','付费直播配置','方维云','应用设置','排序权重'
		);
		$unset_code_array = array(
			'profit_ratio','login_send_score','upgrade_level','share_ticket','open_share_ticket','ticket_exchange_rate',
			'authent_alipay','alipay_partner','alipay_key','open_vip','open_room_hide',
			'society_public_rate','society_user_rate','coin_exchange_rate','full_group_id','on_line_group_id','live_pay_max','live_pay_min',
			'live_pay_scene_max','live_pay_scene_min','beauty_close','mic_max_num','video_type','register_gift','register_gift_diamonds',
			'register_gift_coins','iap_recharge','diamonds_rate','exchange_rate','short_name','ticket_name','must_cate','must_authentication',
			'rank_day','rank_month','rank_all','rank_day_user','is_limit_time','is_limit_time_start','is_limit_time_end','join_room_remind_limit',
			'join_room_limit','has_private_chat','tim_sdkappid','tim_identifier','tim_identifier','vodset_app_id','qcloud_bizid','video_resolution_type',
			'has_save_video',
		);
		foreach($config as $k=>$v){
			if(in_array($v['group_id'],$unset_group_id)){
				unset($config[$k]);
				continue;
			}

			if(in_array($v['code'],$unset_code_array)){
				unset($config[$k]);
				continue;
			}

        	if($v['type']==2){
        		$v['val'] =get_spec_image($v['val']);
        	}
        	
			if($v['type']==4){
				$v['value_scope']=explode(',',$v['value_scope']);
				$v['title_scope']=explode(',',$v['title_scope']);
 			}else{
 				$v['value_scope']='';
 			}
 			$conf[$v['group_id']][] = $v;
		}

  		
  		$is_limit_time_h = array("01","02","03","04","05","06","07","08","09","10","11","12","13","14","15","16","17","18","19","20","21","22","23","24");
  		$this->assign("is_limit_time_h",$is_limit_time_h);
  		$this->assign("conf",$conf);
  		$this->assign("domain_url",get_domain());
		$this->display();
	}
	
	public function savemobile()
	{
		
		
		foreach($_POST as $k=>$v)
		{
			

            if($k=='rank_day'||$k=='rank_month'||$k=='rank_all'||$k=='rank_day_user'){
            	$result = $this->check_rank_time(array('name'=>$k,'value'=>strim($v)));
            	if($result['status']==0){
            		$this->error($result['error']);
            	}
            }
            if((defined('OPEN_LIVE_PAY')&&OPEN_LIVE_PAY==1)&&($k=='live_pay_num'||$k=='live_pay_rule'||$k=='live_pay_fee'||$k=='live_count_down'||$k=='ticket_to_rate'||$k=='uesddiamonds_to_score')){
            	if(floor($v)!=$v){
            		$this->error("付费直播的参数必须为整数");
            	}
            }
            if((defined('OPEN_LIVE_PAY')&&OPEN_LIVE_PAY==1)&&($k=='live_pay_max'||$k=='live_pay_min'||$k=='live_pay_scene_max'||$k=='live_pay_scene_min')){
            	if(floor($v)!=$v){
            		$this->error("付费直播的收费参数必须为整数");
            	}
            }



            if(defined('OPEN_LIVE_PAY')&&OPEN_LIVE_PAY==1){
                if((!defined('LIVE_PAY_TIME')||LIVE_PAY_TIME!=1)){
                    if(intval($_POST['live_pay_min'])<1){
                        $this->error("按时收费最低收费参数不能小于1");
                    }
                    if(intval($_POST['live_pay_max'])<intval($_POST['live_pay_min'])){
                        $this->error("按时收费最低收费参数必须小于按时收费最高收费参数");
                    }
                }
                if(defined('LIVE_PAY_SCENE')&&LIVE_PAY_SCENE==1){
                    if(intval($_POST['live_pay_scene_min'])<1){
                        $this->error("按场收费最低收费参数不能小于1");
                    }
                    if(intval($_POST['live_pay_scene_max'])<intval($_POST['live_pay_scene_min'])){
                        $this->error("按场收费最低收费参数必须小于按场收费最高收费参数");
                    }
                }
            }
            
            
            
			M("MConfig")->where("code='".$k."'")->setField("val",trim($v));
		}
		clear_auto_cache("m_config");
        $log_info = "手机端配置";
        save_log($log_info.L("UPDATE_SUCCESS"),1);
		$this->success("保存成功");
	}
	//脏字库
	public function dirty_words(){
        $this->check_Module();
		//调用tim
        require_once(APP_ROOT_PATH.'system/tim/TimApi.php');
        $api = createTimAPI();
        $ret = $api->openim_dirty_words_get();
		$vo = array();
		$vo['title'] = '脏字库';
		$vo['content'] = implode(',',$ret['DirtyWordsList']);
		$this->assign ( 'vo', $vo );
		$this->display ();
	}

	//检查排行榜缓存时间
	function check_rank_time($data){
		$result = array('status'=>1,'error'=>'');
		//日榜
		if($data['name']=='rank_day'&&$data['value'] <1800){
			$result['status'] = 0;
			$result['error'] = '总排行日榜缓存时间,不得低1800秒';		
		}
		//总排行月榜
		if($data['name']=='rank_month'&&$data['value'] <28800){
			$result['status'] = 0;
			$result['error'] = '总排行月榜缓存时间,不得低28800秒';		
		}
		//总排行总榜
		if($data['name']=='rank_all'&&$data['value'] <86400){
			$result['status'] = 0;
			$result['error'] = '总排行总榜缓存时间,不得低86400秒';
		}
		//主播日排行榜
		if($data['name']=='rank_day_user'&&$data['value'] <300){
			$result['status'] = 0;
			$result['error'] = '主播日排行榜缓存时间,不得低300秒';		
		}
		return $result;
	}

}
?>