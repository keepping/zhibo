<?php
// +----------------------------------------------------------------------
// | Fanwe 方维众筹商业系统
// +----------------------------------------------------------------------
// | Copyright (c) 2011 http://www.fanwe.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: 甘味人生(526130@qq.com)
// +----------------------------------------------------------------------

class VideoPrivateRedisService extends BaseRedisService
{
    //记录私密直播邀请名单
    var $video_private_db;//:video_id hash数据key:user_id; value:1/0 [1:邀请;0:踢除]
    //记录用户可以进入的：私密直播
    var $user_private_db;//:user_id set数据key:video_id
    
    
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
        
        $this->video_private_db = $this->prefix.'video_private:';
        $this->user_private_db = $this->prefix.'user_private:';
       
    }


    /**
     * 邀请用户加入私密直播
     * @param int $video_id
     * @param int $user_id
     */
    public function push_user($video_id,$user_id){
    	//:video_id hash数据key:user_id; value:1/0 [1:邀请;0:踢除]
    	$this->redis->hMSet($this->video_private_db.$video_id,array($user_id=>1));
    	//记录用户可以进入的：私密直播 权限
    	$this->redis->sAdd($this->user_private_db.$user_id,$video_id);
    }
    
    /**
     * 将用户踢出私密直播
     * @param int $video_id
     * @param int $user_id
     */
    public function drop_user($video_id,$user_id){
    	//:video_id hash数据key:user_id; value:1/0 [1:邀请;0:踢除]
    	$this->redis->hMSet($this->video_private_db.$video_id,array($user_id=>0));
    	//移除用户可以进入的：私密直播 权限
    	$this->redis->srem($this->user_private_db.$user_id,$video_id);
    	
    }

	/**
	 * 
     * 检查用户是否被：踢出；踢出后不能重新加入,除非被重新邀请
     * @param int $video_id
     * @param int $user_id
	 * @return boolean true被踢出; false未被踢出
	 */
    public function check_user_drop($video_id,$user_id){
    	//:video_id hash数据key:user_id; value:1/0 [1:邀请;0:踢除]
    	$status = $this->redis->hGet($this->video_private_db.$video_id,$user_id);
    	if ($status === false || $status == 1){
    		return false;
    	}else{
    		return true;
    	}
    }
    
    /**
     *
     * 检查用户是否被：邀请 [未踢除状态]
     * @param int $video_id
     * @param int $user_id
     * @return boolean true被邀请; false未被邀请
     */
    public function check_user_push($video_id,$user_id){
    	//:video_id hash数据key:user_id; value:1/0 [1:邀请;0:踢除]
    	$status = $this->redis->hGet($this->video_private_db.$video_id,$user_id);
    	if ($status == 1){
    		return true;
    	}else{
    		return false;
    	}
    }
    
    
    /**
     * 获得用户被邀请加入的：私密直播 列表
     * @param int $user_id
     * @return list 
     */
    public function get_video_list($user_id){
    	$list = $this->redis->sMembers($this->user_private_db.$user_id);
    	return $list;
    }
    
    /**
     * 私密直播结束,清空相关数据
     * @param unknown_type $video_id
     */
    public function drop_video($video_id){
    	$key = $this->video_private_db.$video_id;
    	$list = $this->redis->hGetAll($key);
    	foreach($list as $k=>$v){
    		//移除用户可以进入的：私密直播 权限
    		$this->redis->srem($this->user_private_db.$k,$video_id);
    	}
    	$this->redis->delete($key);
    }
    
}//类定义结束

?>