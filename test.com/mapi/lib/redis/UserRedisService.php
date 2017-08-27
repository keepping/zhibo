<?php
// +----------------------------------------------------------------------
// | Fanwe 方维众筹商业系统
// +----------------------------------------------------------------------
// | Copyright (c) 2011 http://www.fanwe.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: 甘味人生(526130@qq.com)
// +----------------------------------------------------------------------

class UserRedisService extends BaseRedisService
{

    //var $user_db; //:user_id  hash数据
    //var $user_hash_db; //所有会员数据 user_id hash数据 存储在线数据
    //var $user_robot_db; //user_id  set数据 ,机器人的集合

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
    }



    public function test_add_redis($user_list){


        set_time_limit(0);
        $user_id = 0;
        $pipe = $this->redis->multi();
        foreach($user_list as $k=>$v){
            $user_id = $v['id'];
            $hash_db = $this->get_user_hash($v);
            if($user_id){
                $data = $v;
               // $data = $this->reg_data($data);
                $data['focus_count'] = 0;
                $data['fans_count'] = 0;
                $data['video_count'] = 0;

                $data['use_diamonds'] = 0;
                $data['diamonds'] = 0;
                $data['ticket'] = 0;
                $data['user_level'] = 1;
                $data['v_type'] = 0;
                $data['v_explain'] = '';
                $data['v_icon'] = '';
                $data['is_remind'] = 0;
                $data['score'] = 0;
                $data['refund_ticket'] = 0;
                $pipe->hMSet($this->user_db.$user_id,$data);
                $pipe->hMSet($this->user_hash_db,array($user_id=>json_encode($hash_db)));

                if($data['is_robot']==1){
                    $pipe->sAdd($this->user_robot_db, $user_id);
                }
            }
        }

        $replies = $pipe->exec();
        $this->set_user_id($user_id);
        return $replies;

    }
/*
 * 只更新用户的钻石，货币
 */
 	public function test_update_redis($user_list){


        set_time_limit(0);
        $user_id = 0;
        $pipe = $this->redis->multi();
        foreach($user_list as $k=>$v){
            $user_id = $v['id'];
            $hash_db = $this->get_user_hash($v);
            if($user_id){
                $data = $v;
               // $data = $this->reg_data($data);

                $data['use_diamonds'] = 0;
                $data['diamonds'] = 0;
                $data['ticket'] = 0;
                $data['refund_ticket'] = 0;

                $pipe->hMSet($this->user_db.$user_id,$data);
                $pipe->hMSet($this->user_hash_db,array($user_id=>json_encode($hash_db)));

                if($data['is_robot']==1){
                    $pipe->sAdd($this->user_robot_db, $user_id);
                }
            }
        }

        $replies = $pipe->exec();
        $this->set_user_id($user_id);
        return $replies;

    }

    /*
     * 添加视频
     * hash数据存储
     *
     */
    public function insert_db($user_id,$data){

        $pipe = $this->redis->multi();

        if(!$user_id){
            $user_id = $this->get_user_id();
        }else{
            $pipe->hSet($this->auto_id_db,'user_id',$user_id);
        }
        $data['id'] = $user_id;
        filter_null($data);
        $hash_db = $this->get_user_hash($data);
        $this->redis->hMSet($this->user_db.$user_id,$data);
        $this->redis->hMSet($this->user_hash_db,array($user_id=>json_encode($hash_db))) ;
        if($data['is_robot']==1){
            $pipe->sAdd($this->user_robot_db, $user_id);
        }


        $replies = $pipe->exec();
        if($replies[0]){
            return $user_id;
        }else{
            return 0;
        }
    }
  /*
   * 构建 user_hash_db 的数据
   * is_authentication,nick_name,signature,sex,province,city,head_image
   * v_type,v_explain,v_icon,emotional_state,job,birthday
   */
    public function get_user_hash($data){
        return array(
            'is_authentication'=>$data['is_authentication'],
            'nick_name'=>$data['nick_name'],
            'signature'=>$data['signature'],
            'sex'=>$data['sex'],
            'user_level'=>$data['user_level'],
            'province'=>$data['province'],
            'city'=>$data['city'],
            'head_image'=>$data['head_image'],
            'thumb_head_image'=>$data['thumb_head_image'],
            'v_type'=>$data['v_type'],
            'v_explain'=>$data['v_explain'],
            'v_icon'=>$data['v_icon'],
            'emotional_state'=>$data['emotional_state'],
            'job'=>$data['job'],
            'birthday'=>$data['birthday'],
            'apns_code'=>$data['apns_code'],
            'family_id'=>$data['family_id'],
            'is_robot'=>intval($data['is_robot']),
            'family_chieftain'=>$data['family_chieftain'],
            'room_title'=>$data['room_title'],
            'fans_count'=>intval($data['fans_count']),
            'focus_count'=>intval($data['focus_count'])
        );
    }
    /*
     * 初始化数据
     */

    public function reg_data($data){
        $data['is_agree'] = 0;
        $data['is_authentication'] = 0;
        $data['signature'] = '';
        if(!isset( $data['sex'])){
            $data['sex'] = 0;
        }
        if(!isset( $data['province'])) {
            $data['province'] = '';
        }
        if(!isset( $data['city'])) {
            $data['city'] = '';
        }
        if(!isset( $data['head_image'])){
            $data['head_image'] = '';
        }
        if(!isset( $data['thumb_head_image'])){
            $data['thumb_head_image'] = '';
        }
        $data['focus_count'] = 0;
        $data['fans_count'] = 0;
        $data['video_count'] = 0;

        $data['use_diamonds'] = 0;
        $data['diamonds'] = 0;
        $data['ticket'] = 0;
        $data['user_level'] = 1;
        $data['v_type'] = 0;
        $data['v_explain'] = '';
        $data['v_icon'] = '';
        $data['is_remind'] = 1;
        $data['score'] = 0;
        $data['family_id']='';
        $data['family_chieftain']='';
        $data['room_title']='';
        return $data;
    }


    /*
     * 更新视频信息
     * $user_id
     * $data 要更新的数组array('nick_name'=>$name)
     */
    public function update_db($user_id,$data){

        $user_info = $this->redis->hGetAll($this->user_db.$user_id);

        $user_info = array_merge($user_info,$data);

        $hash_db = $this->get_user_hash($user_info);
        filter_null($data);
        $pipe = $this->redis->multi();
        $pipe->hMSet($this->user_db.$user_id,$data);
        $pipe->hMSet($this->user_hash_db,array($user_id=>json_encode($hash_db))) ;
        $replies = $pipe->exec();

        return $replies;
    }
    /*
     * 会员相关字段自增 或者 自减
     */
    public function inc_field($user_id,$field,$val){
       return  $this->redis->hIncrBy($this->user_db.$user_id,$field,$val);
    }
    
    /**
     * 添加积分
     * @param unknown_type $user_id
     * @param unknown_type $val
     */
    public function inc_score($user_id,$val){
    	$this->inc_field($user_id,'score',$val);

    	//更新等级
    	$user_data = $this->getRow_db($user_id,array('id','score','online_time','user_level'));
		user_leverl_syn($user_data);
    }

    /*
     * 获取单个字段
     */
    public function getOne_db($user_id,$field){
       return $this->redis->hGet($this->user_db.$user_id,$field);
    }
    /*
     * 获取多个字段
     * $fields 为空的话，则是获取所有
     */
    public function getRow_db($user_id,$fields=''){
        if(!$fields){
            return $this->redis->hGetAll($this->user_db.$user_id);
        }else{
            return $this->redis->hMGet($this->user_db.$user_id,$fields);
        }

    }
    /*
     * 同一时间内 ，同一用户 钻石数 只允许操作一次，并发时候 会延时10ms
     * @return 若减少的钻石数，少于当前钻石数，则返回false;否则返回改变后的数量
     */
    public function lock_diamonds($user_id,$diamonds_change){
        $num = $this->inc_field($user_id,'diamonds',$diamonds_change);
        if($num<0){
            $this->inc_field($user_id,'diamonds',$diamonds_change*-1);
            return false;
        }
        return $num;

    }
    /*
     * 根据user_id 批量获取用户信息 $user_list = array(11,12,13);
     */
    public function get_m_user($user_list){
    	$user_list2 = array();
        $user_list_array =  $this->redis->hMGet($this->user_hash_db,$user_list);
        if(is_array($user_list_array)){
            foreach($user_list_array as $k=>$v){
                if($v){
                    $user = json_decode($v,true);
                    $user_con = array();
                    $user_con['user_id'] = $k;
                    $user_con['is_robot'] = intval($user['is_robot']);
                    $user_con['nick_name'] = $user['nick_name']?$user['nick_name']:'';
                    $user_con['sex'] = $user['sex']?$user['sex']:'0';
                    $user_con['v_icon'] = $user['v_icon']?$user['v_icon']:'';
                    $user_con['v_type'] = $user['v_type']?$user['v_type']:'';
                    $user_con['user_level'] = $user['user_level']?$user['user_level']:'1';
                    $user_con['head_image'] = get_spec_image($user['head_image']);
                    $user_list2[] = $user_con;
                }

            }
        }
        return $user_list2;
    }

}//类定义结束

?>