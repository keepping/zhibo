<?php
// +----------------------------------------------------------------------
// | Fanwe 方维众筹商业系统
// +----------------------------------------------------------------------
// | Copyright (c) 2011 http://www.fanwe.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: 甘味人生(526130@qq.com)
// +----------------------------------------------------------------------

class VideoRedRedisService extends BaseRedisService
{

    var $user_red_db; //:red_id list数据 存储 中奖金额列表
    var $user_winning_db;//:red_id zset user_id:money
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
        $this->user_red_db = $this->prefix.'user_red:';
        $this->user_winning_db = $this->prefix.'user_winning:';

    }


    /*
     * 抢红包
     */
    public function push_red($red_id,$money){

        return $this->redis->rPush($this->user_red_db.$red_id,$money);
    }

    public function red_exists($red_id){
        return $this->redis->exists($this->user_red_db.$red_id);
    }

    /*
     *
     */
    public function pop_red($red_id){
        return $this->redis->lPop($this->user_red_db.$red_id);
    }

    /*
     * 添加中奖用户
     */
    public function add_user_winning($red_id,$user_id,$money){
    	
    	return $this->redis->zIncrBy($this->user_winning_db.$red_id,$money,$user_id);
        //return $this->redis->zAdd($this->user_winning_db.$red_id,$money,$user_id);
    }
    /*
     * 获取中奖用户的值
     * 未中奖 返回false
     */
    public function get_user_winning($red_id,$user_id){
        return $this->redis->zScore($this->user_winning_db.$red_id,$user_id);
    }

    /*
     * 获取中奖的红包
     */
    public function get_winnings($red_id){
        $user_num_array =  $this->redis->zRevRange($this->user_winning_db.$red_id,0,-1,true);
        $user_ids = array_keys($user_num_array);
        $user_list_array = $this->redis->hMGet($this->user_hash_db,$user_ids);
        $user_list = array();
        foreach($user_list_array as $k=>$v){
            if($v){
                $user = json_decode($v,true);
                $user['user_id'] = $k;
                $user['diamonds'] = $user_num_array[$k];
                $user['head_image'] = get_spec_image($user['head_image']);
                $user_list[] = $user;
            }

        }
        return $user_list;
    }

}//类定义结束

?>