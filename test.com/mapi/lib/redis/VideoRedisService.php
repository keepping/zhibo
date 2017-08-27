<?php
// +----------------------------------------------------------------------
// | Fanwe 方维众筹商业系统
// +----------------------------------------------------------------------
// | Copyright (c) 2011 http://www.fanwe.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: 甘味人生(526130@qq.com)
// +----------------------------------------------------------------------

class VideoRedisService extends BaseRedisService
{

	//var $video_db; //:video_id  hash数据
    //var $video_group_db; // hMSet 有 group:id:video_id
    var $video_like_db;// zset 有 video_id:user 点赞,每个用户只记录一次
    var $video_forbid_group;//禁言组 video_forbid_group:group_id  set数据 user_id
    //var $video_robot_db;//:video_id set 机器人头像列表;
    var $video_red_db; //:video_id set 红包ID 列表
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
        
        $this->video_forbid_group = $this->prefix.'video_forbid_group:';
        
        $this->video_like_db = $this->prefix.'video_like_db:';
        
        //$this->video_robot_db = $this->prefix.'video_robot_db:';
        
        $this->video_red_db = $this->prefix.'video_red_db:';
    }

    /*
     * 添加视频
     */
    public function insert_db($video_id,$data){
        $video_id = intval($video_id);
        $data['id'] = $video_id;
        filter_null($data);
        $this->redis->hMSet($this->video_db.$video_id,$data);
        ;
       return $video_id;

    }

    //视频上线
    public  function video_online($video_id,$group_id){
        $this->redis->hMSet($this->video_group_db,array($group_id=>$video_id));
        return true;
    }

    /*
     * 清空redis上视频相关数量【fanwe_video,禁言,点赞,观众列表,group_id与 video_id对应数据】
     */
    public function del_db($video_id){
    	/*
        $pipe = $this->redis->multi();

        $pipe->delete($this->video_db.$video_id);

        $replies = $pipe->exec();
        return $replies;
        */
    	$group_id = $this->getOne_db($video_id,'group_id');
    	
    	$this->redis->hDel($this->video_group_db, $group_id);//删除 group_id 与 video_id 对应数据
    	$this->redis->delete($this->video_forbid_group.$group_id);//删除禁言记录
    	
    	$this->redis->delete($this->video_like_db.$video_id);//删除：点赞
    	//$this->redis->delete($this->video_viewer_level_db.$video_id);//删除：观众列表
    	$this->redis->delete($this->video_db.$video_id);//删除：视频数据
    	
    	//$this->redis->delete($this->video_robot_db.$video_id);//删除：机器人头像列表
    	
    	
    	//删除红包领取记录
    	$red_list = $this->get_reds($video_id);
    	if (count($red_list) > 0){
    		fanwe_require(APP_ROOT_PATH.'mapi/lib/redis/VideoRedRedisService.php');
    		$videoRed_redis = new VideoRedRedisService();
    		foreach ($red_list as $red_id) {
    			$videoRed_redis->redis->delete($videoRed_redis->user_winning_db.$red_id);
    		}
    	}

    	$this->redis->delete($this->video_red_db.$video_id);//删除：红包ID列表
    }

    /**
     * 删除：观众列表
     * @param unknown_type $video_id
     */
    public function del_viewer($video_id){
    	$this->redis->delete($this->video_viewer_level_db.$video_id);
    }

    /**
     * 获取直播房间机器人头像user_id列表,score 小于0的为：机器人
     * @param unknown_type $video_id
     */
    public function get_robot($video_id){
    	//return $this->redis->sMembers($this->video_robot_db.$video_id);
    	$video_user_level_array =  $this->redis->zRangeByScore($this->video_viewer_level_db.$video_id,'-inf',0,array('withscores' => TRUE));
    	$robot_list = array_keys($video_user_level_array);
    	return $robot_list;
    }
    
    /**
     * 记录直播间发的：红包 记录; 主要用于,直播结束后,处理还未被领取的红包
     * @param unknown_type $video_id
     * @param unknown_type $red_id
     */
    public function add_red($video_id,$red_id){
    	return $this->redis->sAdd($this->video_red_db.$video_id, $red_id);
    }
    
    /**
     * 获取直播间，红包 发放记录
     * @param unknown_type $video_id
     */
    public function get_reds($video_id){
    	return $this->redis->sMembers($this->video_red_db.$video_id);
    }
    
    /*
     * 更新视频信息
     */
    public function update_db($video_id,$data){
        filter_null($data);
        return $this->redis->hMSet($this->video_db.$video_id,$data);
    }
    /*
     * 获取视频单个字段
     */
    public function getOne_db($video_id,$field){
       return $this->redis->hGet($this->video_db.$video_id,$field);
    }
    /*
     * 获取多个字段
     */
    public function getRow_db($video_id,$fields=''){
        if(!$fields){
            return $this->redis->hGetAll($this->video_db.$video_id);
        }else{
            return $this->redis->hMGet($this->video_db.$video_id,$fields);
        }

    }
    
    /*
     * 更新排序
     */
    public function update_video_sort($video_id,$sort){
    	$this->redis->hSet($this->video_db.$video_id,'sort',$sort);
    	$this->syn_sort_num($video_id);
    }

    public function get_videoid_by_groupid($group_id){
    	if ($group_id)
    		return intval($this->redis->hGet($this->video_group_db,$group_id));
    	else
    		return 0;
    }
    
    /**
     * 通过$group_id获得，视频数据（注：解散聊天组后，无法通过该方法获得)
     * @param unknown_type $group_id
     * @param unknown_type $fields
     */
    public function getRow_db_ByGroupId($group_id,$fields=''){
    	$video_id = $this->get_videoid_by_groupid($group_id);
    	
    	return $this->getRow_db($video_id,$fields);
    }

    /**
     * 设置禁言某个用户
     * @param unknown_type $group_id
     * @param unknown_type $user_id
     */
    public function set_forbid_msg($group_id,$user_id,$shutup_time){
       	//$this->video_forbid_group = $this->prefix.'video_forbid_group:';

        //return	$this->redis->sAdd($this->video_forbid_group.$group_id,$user_id);
        return $this->redis->zIncrBy($this->video_forbid_group.$group_id,$shutup_time,$user_id);
    }

    /**
     * 取消禁言某个用户
     * @param unknown_type $group_id
     * @param unknown_type $user_id
     */
    public function unset_forbid_msg($group_id,$user_id){
        $this->redis->zrem($this->video_forbid_group.$group_id,$user_id);
    }
    
    /**
     * 判断某个用户是否被禁言(被禁言返回：true; 未被禁言返回：false)
     * @param unknown_type $group_id
     * @param unknown_type $user_id
     */
    public function has_forbid_msg($group_id,$user_id){
        //return $this->redis->sismember($this->video_forbid_group.$group_id, $user_id);
        return $this->redis->zScore($this->video_forbid_group.$group_id, $user_id);
    }

    /**
     * 计算排序权重,每由定时器执行,每几秒执行一次即可
     * @param unknown_type $video_id
     */
    public function syn_sort_num($video_id){
    	//热门排序：sort_num = sort_init + share_count * 分享权重 + like_count * 点赞权重 + fans_count * 关注权重 + sort * 排序权重 + ticket(本场收到的印票) * 印票权重
    	$m_config =  load_auto_cache("m_config");
    	$video = $this->getRow_db($video_id,array('sort_init','watch_number','share_count','like_count','fans_count','sort','vote_number','stick'));
    	
    	$sort_num = intval($video['sort_init']);//持有映票权重+等级权重+当前有的关注数权重
    	
    	$sort_num += intval($video['watch_number']) * floatval($m_config['num_weight']);//观看人数权重
    	$sort_num += intval($video['sort']) * floatval($m_config['sort_weight']);//排序权重
    	$sort_num += intval($video['vote_number']) * floatval($m_config['video_ticket_weight']);//当前视频获取映票权重
    	$sort_num += intval($video['fans_count']) * floatval($m_config['video_focus_weight']);//房间内关注数
    	$sort_num += intval($video['share_count']) * floatval($m_config['video_share_weight']);//房间内分享数的权重
    	$sort_num += intval($video['like_count']) * floatval($m_config['video_like_weight']);//点赞
    	
    	$data = array();

    	$data['sort_num'] = $sort_num;
        if($video['stick']==1){
            $data['sort_num'] +=  $m_config['top_weight']*100000000;
        }
    	$this->update_db($video_id, $data);
    }
    
    //记录点赞,每个用户只记录一次
    public function like($video_id,$user_id){
    	
    	if($this->redis->zScore($this->video_like_db.$video_id, $user_id) === false){
    		$this->redis->zAdd($this->video_like_db.$video_id, 1,$user_id);
    		$this->redis->hIncrBy($this->video_db.$video_id,'like_count',1);
    	}
    }
    public function inc_field($id, $key, $value)
    {
        $id    = intval($id);
        $value = intval($value);
        if (!$id) {
            return false;
        }
        return $this->redis->hIncrBy($this->video_db . $id, $key, $value);
    }
    
    
}//类定义结束


?>