<?php
// +----------------------------------------------------------------------
// | Fanwe 方维众筹商业系统
// +----------------------------------------------------------------------
// | Copyright (c) 2011 http://www.fanwe.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: 甘味人生(526130@qq.com)
// +----------------------------------------------------------------------

class UserFollwRedisService  extends BaseRedisService
{

    var $user_follow_db; //set有序数据=user_id  用户关注的会员ID
    var $user_followed_by_db; // set有序数据=user_id  关注用户的会员ID
//    var $user_hash_db; //所有会员数据 user_id hash数据 存储在线数据
    //var $user_db; //:user_id  hash数据，会员数据
    private $id;
    /**
     +----------------------------------------------------------
     * 架构函数
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     */
    public function __construct($userID)
    {

        parent::__construct();
        $this->user_follow_db = $this->prefix.'user_following:';
        $this->user_followed_by_db =  $this->prefix.'user_followed_by:';
//        $this->user_hash_db = $this->prefix.'user_hash_db';
        //$this->user_db = $this->prefix.'user:';
        $this->id = $userID;
    }
    /*
	 * Makes this user follow the user with the given ID.
	 * In order to stay efficient, we need to make a two-way
	 * directed graph. This means when we follow a user, we also
	 * say that that user is followed by this user, making a forward
	 * and backword directed graph.
	 */
    public function follow($user,$room_id=0) {
        $this->redis->sAdd($this->user_follow_db.$this->id, $user);
        $this->redis->sAdd($this->user_followed_by_db.$user, $this->id);

        if($room_id){
        	//$this->incry($this->video_db.$room_id, 'fans_count', 1);
            $this->redis->hIncrBy($this->video_db.$room_id,'fans_count',1);
        }
    }

    /*
     * Makes this user unfollow the user with the given ID.
     * First we check to make sure that we are actually following
     * the user we want to unfollow, then we remove both the forward
     * and backward references.
     */
    public function unfollow($user,$room_id=0) {
        if ($this->is_following($user)) {
            $this->redis->srem($this->user_follow_db.$this->id, $user);
            $this->redis->srem($this->user_followed_by_db.$user, $this->id);

            if($room_id){
            	//$this->incry($this->video_db.$room_id, 'fans_count', -1);
                $this->redis->hIncrBy($this->video_db.$room_id,'fans_count',-1);
            }
        }
    }

    /*
     * Returns an array of user ID's that this user follows.
     */
    public function following() {
        return $this->redis->sMembers($this->user_follow_db.$this->id);
    }

    public function get_follonging_user($user_id,$page=1,$page_size=20){
        $start = ($page-1)*$page_size;
        $list = $this->redis->sMembers($this->user_follow_db.$user_id);
        $keys = array_slice($list,$start,$page_size);
        $user_list = $this->redis->hMGet($this->user_hash_db,$keys);
        $user_array = array();
        if($user_id!=$this->id){
           $common_following = $this->common_following(array($user_id));
        }
        foreach($user_list as $k=>$v){
            $v= json_decode($v,true);
            $v['head_image'] = get_spec_image($v['head_image']);

            $v['user_id'] = $k;
            $v['nick_name'] = $v['nick_name']?$v['nick_name']:'';
            $v['sex'] = $v['sex']?$v['sex']:'0';
            $v['v_icon'] = $v['v_icon']?$v['v_icon']:'';
            $v['v_type'] = $v['v_type']?$v['v_type']:'';
            $v['user_level'] = $v['user_level']?$v['user_level']:'1';
            $v['fans_count'] = $v['fans_count']?$v['fans_count']:'0';
            if($user_id==$this->id){
                $v['follow_id'] = $k;
            }elseif(in_array($k,$common_following)){
                $v['follow_id'] = $k;
            }else{
                $v['follow_id'] = 0;
            }
            $user_array[] = $v;
        }
        return $user_array;

    }
    public function get_follonging_by_user($user_id,$page=1,$page_size=20){
        //return array();
        $start = ($page-1)*$page_size;
        $list = $this->redis->sMembers($this->user_followed_by_db.$user_id);
        $keys = array_slice($list,$start,$page_size);
       // if($user_id!=$this->id){
            $common_following = $this->common_followed_by(array($user_id));
        //}
        $user_array = array();
        if($keys){
            $user_list = $this->redis->hMGet($this->user_hash_db,$keys);
            foreach($user_list as $k=>$v){
                $v= json_decode($v,true);
                $v['head_image'] = get_spec_image($v['head_image']);
                $v['user_id'] = $k;
                $v['nick_name'] = $v['nick_name']?$v['nick_name']:'';
                $v['sex'] = $v['sex']?$v['sex']:'0';
                $v['v_icon'] = $v['v_icon']?$v['v_icon']:'';
                $v['v_type'] = $v['v_type']?$v['v_type']:'';
                $v['user_level'] = $v['user_level']?$v['user_level']:'1';
                $v['fans_count'] = $v['fans_count']?$v['fans_count']:'0';

               
                $user_array[] = $v;
            }
        }

        return $user_array;
    }

    public function get_private_user($page=1,$page_size=20){
        $start = ($page-1)*$page_size;
        $keys = $this->common_private_follower();
        $countlist=$this->redis->hMGet($this->user_hash_db,$keys);
        $rs_count=count($countlist);
        $root['countlist']=$rs_count;
        $keys = array_slice($keys,$start,$page_size);
        $user_array = array();
        if($keys){
            $user_list = $this->redis->hMGet($this->user_hash_db,$keys);
            foreach($user_list as $k=>$v){
                if($v){
                    $v= json_decode($v,true);
                    $v['head_image'] = get_spec_image($v['head_image']);
                    $v['user_id'] = $k;

                    $v['nick_name'] = $v['nick_name']?$v['nick_name']:'';
                    $v['nick_name'] =  htmlspecialchars_decode($v['nick_name']);
                    $v['signature'] = $v['signature']?$v['signature']:'';
                    $v['signature'] =  htmlspecialchars_decode($v['signature']);
                    $v['sex'] = $v['sex']?$v['sex']:'0';
                    $v['v_icon'] = $v['v_icon']?$v['v_icon']:'';
                    $v['v_type'] = $v['v_type']?$v['v_type']:'';
                    $v['user_level'] = $v['user_level']?$v['user_level']:'1';
                    $user_array[] = $v;
                }
            }
        }
        $list = $user_array;
        $root['list'] = $list;


        if($page==0){
            $root['has_next'] = 0;
        }else{
            if (count($list) == $page_size)
                $root['has_next'] = 1;
            else
                $root['has_next'] = 0;
        }

        $root['page'] = $page;//
        $root['status'] = 1;
        return $root;
    }

    /*
     * Returns an array of user ID's that this user is followed by.
     */
    public function followed_by($user_id=0) {
    	if ($user_id == 0) $user_id = $this->id;
        return $this->redis->sMembers($this->user_followed_by_db.$user_id);
    }

    /*
     * Test to see if this user is following the given user or not.
     * Returns a boolean.
     */
    public function is_following($user) {
        return $this->redis->sismember($this->user_follow_db.$this->id, $user);
    }

    /*
     * Test to see if this user is followed by the given user.
     * Returns a boolean.
     */
    public function is_followed_by($user) {
        return $this->redis->sismember($this->user_followed_by_db.$this->id, $user);
    }

    /*
     * Tests to see if the relationship between this user and the given user is mutual.
     */
    public function is_mutual($user) {
        return ($this->is_following($user) && $this->is_followed_by($user));
    }

    /*
     * Returns the number of users that this user is following.
     */
    public function follow_count() {
        return intval($this->redis->sCard($this->user_follow_db.$this->id));
    }

    /*
     * Retuns the number of users that follow this user.
     */
    public function follower_count() {
        return intval($this->redis->sCard($this->user_followed_by_db.$this->id));
    }

    /*
     * Finds all users that the given users follow in common.
     * Returns an array of user IDs
     */
    public function common_following($users) {
        $redis = $this->redis;
        $users[] = $this->id;

        $keys = array();
        foreach ($users as $user) {
            $keys[] = $this->user_follow_db.$user;
        }

        return $redis->sinter($keys);
    }

    /*
     * Finds all users that all of the given users are followed by in common.
     * Returns an array of user IDs
     */
    public function common_followed_by($users) {
        $redis = $this->redis;


        $keys = array();
        foreach ($users as $user) {
            $keys[] = $this->user_followed_by_db.$user;
        }
        $keys[] = $this->user_follow_db.$this->id;

        return $redis->sinter($keys);
    }

    public function common_private_follower(){
        $redis = $this->redis;

        $keys = array();
        
        if($GLOBALS['distribution_cfg']['IS_REDIS_JQ']){
        	$array_followed = $this->followed_by($this->id);
        	$array_follow =  $this->following($this->id);
        	return array_intersect($array_followed,$array_follow);
        }else{
        	$keys[] = $this->user_followed_by_db.$this->id;
       		$keys[] = $this->user_follow_db.$this->id;
        
       		 return $redis->sinter($keys);
        }
        
    }
    //建立set数组
    public function test_new(){
        $key = $this->user_follow_db.'77';
        $this->redis ->zAdd($key,22,2);
        $this->redis ->zAdd($key,33,3);
    }
    public function zfollowing() {
        return $this->redis->zRange($this->user_follow_db.$this->id,0,-1);
    }



}//类定义结束

?>