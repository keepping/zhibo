<?php
// +----------------------------------------------------------------------
// | Fanwe 方维众筹商业系统
// +----------------------------------------------------------------------
// | Copyright (c) 2011 http://www.fanwe.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: 甘味人生(526130@qq.com)
// +----------------------------------------------------------------------

class VideoContributionRedisService extends BaseRedisService
{

    var $user_contribution; //:podcast_id   zset 有序数据,user_id:贡献数
    var $video_contribution;//：video_id  zset 有序数据,user_id:贡献数

    var $video_vote_number_db; //   zset video_id:映票数(vote_number)

//    var $user_hash_db; //所有会员数据 user_id hash数据 存储在线数据
    /**
     +----------------------------------------------------------
     * 架构函数
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     */
    public function __construct()
    {

        parent::__construct();
        $this->user_contribution = $this->prefix.'user_contribution:';
        $this->video_contribution = $this->prefix.'video_contribution:';
        $this->user_hash_db = $this->prefix.'user_hash_db';

//        $this->video_db = $this->prefix.'video:';
        $this->video_vote_number_db = $this->prefix.'video_vote_number';
    }

//    public function test_add_redis($user_list){
//
//
//        set_time_limit(0);
//        $viewer_id = 0;
//        $pipe = $this->redis->multi();
//        foreach($user_list as $k=>$v){
//            $viewer_id = $v['id'];
//
//            if($viewer_id){
//                $data = $v;
//                $pipe->hMSet($this->user_contribution.$viewer_id,$data)
//                ;
//            }
//        }
//
//        $replies = $pipe->exec();
//        $this->set_auto_val('viewer_id',$viewer_id);
//
//    }

    /*
     * 添加
     * $video_id 为0时候，是直接发送礼物
     */
    public function insert_db($user_id,$podcast_id,$video_id=0,$num){
        $data = array();
        if($video_id){
            $data = $this->redis->hMGet($this->video_db.$video_id,array('province','sex','title','room_type','live_in'));
        }
        $pipe = $this->redis->multi();
        //主播获取ticket，发送人 获取积分
        $pipe->zIncrBy($this->user_contribution.$podcast_id,$num,$user_id);
        //$pipe->hIncrBy($this->user_db.$podcast_id,'ticket',$num);//del by chenfq 20160907 放数据库中
        if($video_id){
            $pipe->zIncrBy($this->video_contribution.$video_id,$num,$user_id);
            $pipe->hIncrBy($this->video_db.$video_id,'vote_number',$num);
            $pipe->zIncrBy($this->video_vote_number_db,$num,$video_id);

        }

        $replies = $pipe->exec();
        //$this->set_best($user_id,$podcast_id,$num);
        return $replies[0];
    }
    /*
     *  获取本视频主播当日最多贡献
     */
    public function get_video_contribute($video_id,$page,$page_size=20,$is_only_list = false){
        $root = array();
        if($page==0){
            $page = 1;
        }
        $root['page'] = $page;
       // $page_size=20;
        $start = ($page-1)*$page_size;
        $end = $page*$page_size-1;
        fanwe_require(APP_ROOT_PATH."mapi/lib/core/common.php");
        //获取主播当日印票贡献排行
        $table = createPropTable();
        
        $user_id= $this->redis->hGet($this->video_db.$video_id,'user_id');
                
        $m_config =  load_auto_cache("m_config");//初始化手机端配置
        $rank_cache_time = $m_config['rank_day_user']!=''?$m_config['rank_day_user']:300;
		
        $param = array('user_id'=>$user_id,'table'=>$table,'page'=>$page,'page_size'=>$page_size,'cache_time'=>$rank_cache_time);
		$user_list = load_auto_cache("video_contribute",$param);


        $total_num = 0;
        foreach ($user_list as $k=>&$v){
            $v['num'] = intval($v['num']);
            $total_num += $v['num'];
            $v['head_image'] = get_spec_image($v['head_image']);
        }
//        $user_num_array =  $this->redis->zRevRange($this->video_contribution.$video_id,$start,$end,true);
//        $user_keys = array_keys($user_num_array);
//        $user_list_array = $this->redis->hMGet($this->user_hash_db,$user_keys);
//        $user_list = array();
//        $root['total_num'] = intval($this->redis->zCard($this->video_contribution.$video_id));;
//        if(is_array($user_list_array)){
//            foreach($user_list_array as $k=>$v){
//                if($v){
//                    $user = json_decode($v,true);
//                    $user_con = array();
//                    $user_con['user_id'] = $k;
//                    $user_con['nick_name'] = $user['nick_name']?$user['nick_name']:'';
//                    $user_con['sex'] = $user['sex']?$user['sex']:'0';
//                    //$user_con['v_icon'] = $user['v_icon']?$user['v_icon']:'';
//                    //$user_con['v_type'] = $user['v_type']?$user['v_type']:'';
//                    $user_con['user_level'] = $user['user_level']?$user['user_level']:'1';
//                    $user_con['head_image'] = get_spec_image($user['head_image']);
//                    $user_con['num'] = $user_num_array[$k];
//                    $user_list[] = $user_con;
//                }
//
//            }
//        }
//        $root['total_ticket_num'] = $this->redis->zScore($this->video_vote_number_db,$video_id);
//        $root['total_ticket_num'] = intval($root['total_ticket_num']);
//        if($is_only_list){
//            return $user_list;
//        }


        $root['list'] = $user_list;
        $root['total_ticket_num'] = $total_num;
        $root['user'] = $this->redis->hMGet($this->user_db.$user_id,array('nick_name','sex','head_image','ticket','user_level','v_type','v_icon'));
        $user = $root['user'];
        $root['user']['nick_name'] = $user['nick_name']?$user['nick_name']:$user_id;
        $root['user']['sex'] = $user['sex']?$user['sex']:0;
        $root['user']['ticket'] = intval($user['ticket'])?intval($user['ticket']):'';
        $root['user']['user_level'] = $user['user_level']?$user['user_level']:'1';
        $root['user']['v_type'] = $user['v_type']?$user['v_type']:'';
        $root['user']['v_icon'] = $user['v_icon']?$user['v_icon']:'';
        $root['user']['user_id'] = $user_id;
        $root['user']['head_image'] = get_spec_image( $root['user']['head_image']);
        if($page == 0){
            $root['has_next'] = 0;
        }else{
            if((count($user_list)==$page_size)  ){
                $root['has_next'] = 1;
            }else{
                $root['has_next'] = 0;
            }
        }
        $root['status'] = 1;
        return $root;
    }
    /*
     * 获取当前主播 最多贡献
     *
     */
    public function get_podcast_contribute($podcast_id,$page,$page_size=20,$is_only_list = false){
        $root = array();
        if($page==0){
            $page = 1;
        }
        $root['page'] = $page;
       //$page_size=20;
        $start = ($page-1)*$page_size;
        $end = $page*$page_size-1;
        $user_num_array =  $this->redis->zRevRange($this->user_contribution.$podcast_id,$start,$end,true);
        $user_keys = array_keys($user_num_array);
        $user_list_array = $this->redis->hMGet($this->user_hash_db,$user_keys);
        $user_list = array();

        $root['total_num'] = intval($this->redis->zCard($this->user_contribution.$podcast_id));
        if(is_array($user_list_array)){
            foreach($user_list_array as $k=>$v){
              if($v){
                  $user = json_decode($v,true);
                  $user_con = array();
                  $user_con['user_id'] = $k;
                  $user_con['nick_name'] = $user['nick_name']?$user['nick_name']:'';
                  $user_con['sex'] = $user['sex']?$user['sex']:'0';
                  $user_con['v_icon'] = $user['v_icon']?$user['v_icon']:'';
                  $user_con['v_type'] = $user['v_type']?$user['v_type']:'';
                  $user_con['user_level'] = $user['user_level']?$user['user_level']:'1';
                  $user_con['head_image'] = get_spec_image($user['head_image']);
                  $user_con['num'] = $user_num_array[$k];
                  $user_con['use_ticket']=intval($user_num_array[$k]);
                  $user_list[] = $user_con;
              }
            }
        }
        if($is_only_list){
            return $user_list;
        }
        $root['list'] = $user_list;
        $root['user'] = $this->redis->hMGet($this->user_db.$podcast_id,array('nick_name','sex','head_image','ticket','no_ticket','user_level','v_type','v_icon'));
        $user = $root['user'];
        $root['user']['sex'] = $user['sex']?$user['sex']:0;
        $root['user']['nick_name'] = $user['nick_name']?$user['nick_name']:$podcast_id;
        $root['user']['ticket'] = intval($user['ticket']+$user['no_ticket'])?intval($user['ticket']+$user['no_ticket']):'';
        $root['user']['user_level'] = $user['user_level']?$user['user_level']:'1';
        $root['user']['v_type'] = $user['v_type']?$user['v_type']:'';
        $root['user']['v_icon'] = $user['v_icon']?$user['v_icon']:'';
        $root['user']['user_id'] = $podcast_id;
        $root['user']['head_image'] = get_spec_image( $root['user']['head_image']);
        if($page == 0){
            $root['has_next'] = 0;
        }else{
            if((count($user_num_array)==$page_size)  ){
                $root['has_next'] = 1;
            }else{
                $root['has_next'] = 0;
            }
        }
        $root['rs_count']=count($user_num_array);
        $root['status'] = 1;
        return $root;
    }


}//类定义结束

?>