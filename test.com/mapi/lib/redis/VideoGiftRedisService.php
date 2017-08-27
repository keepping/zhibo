<?php
// +----------------------------------------------------------------------
// | Fanwe 方维众筹商业系统
// +----------------------------------------------------------------------
// | Copyright (c) 2011 http://www.fanwe.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: 甘味人生(526130@qq.com)
// +----------------------------------------------------------------------

class VideoGiftRedisService extends BaseRedisService
{

    var $video_gift_db; //:gift_id  hash数据
    var $video_gift_zset; //:gift_id  zset数据

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
        $this->video_gift_db = $this->prefix.'video_gift:';
        $this->video_gift_zset = $this->prefix.'video_gift_zset';

    }

//    public function test_add_redis($user_list){
//
//
//        set_time_limit(0);
//        $gift_id = 0;
//        $pipe = $this->redis->multi();
//        foreach($user_list as $k=>$v){
//            $gift_id = $v['id'];
//
//            if($gift_id){
//                $data = $v;
//                $pipe->hMSet($this->video_gift_db.$gift_id,$data)
//                ;
//            }
//        }
//
//        $replies = $pipe->exec();
//        $this->set_gift_id($gift_id);
//
//    }

    /*
     * 添加
     */
    public function insert_db($gift_id,$data){

    	$pipe = $this->redis->multi();

        if(!$gift_id){
            $gift_id = $this->get_gift_id();
        }else{
        	$pipe->hSet($this->auto_id_db,'gift_id',$gift_id);
        }
        $data['id'] = $gift_id;
        $pipe->hMSet($this->video_gift_db,array($gift_id=>json_encode($data)));
       // $pipe->hMSet($this->video_gift_db.$gift_id,$data);
        $pipe->zAdd($this->video_gift_zset,0,$gift_id);
        $replies = $pipe->exec();

        if($replies[0]!==false){
        	return $gift_id;
        }else{
        	return 0;
        }
    }

    /*
     * 删除视频
     */
    public function del_db($gift_id){
        $this->redis->zRem($this->video_gift_zset,$gift_id);
        return $this->redis->hDel($this->video_gift_db,$gift_id);
        //return $this->redis->delete($this->video_gift_db.$gift_id);

    }


    /*
     * 更新视频信息
     */
    public function update_db($gift_id,$data){

        $old_data = $this->redis->hGet($this->video_gift_db,$gift_id);

        $old_data =  json_decode($old_data,true);


        $data = array_merge($old_data,$data);

        return $this->redis->hSet($this->video_gift_db,$gift_id,json_encode($data));
       // return $this->redis->hMSet($this->video_gift_db.$gift_id,$data);
    }
    /*
     * 获取视频单个字段
     */
    public function getOne_db($gift_id,$field){
        $old_data = $this->redis->hGet($this->video_gift_db,$gift_id);
        $old_data = json_decode($old_data,true);
        if($old_data[$field]){
            return $old_data[$field];
        }else{
            return false;
        }
      // return $this->redis->hGet($this->video_gift_db.$gift_id,$field);
    }
    /*
     * 获取多个字段
     */
    public function getRow_db($gift_id,$fields=''){
        $old_data = $this->redis->hGet($this->video_gift_db,$gift_id);
        $old_data = json_decode($old_data,true);
        return $old_data;

    }
    /*
     * 获取全部礼物
     */
    public function getAll($num=300){
        if($num==-1){
            $data = $this->redis->hGetAll($this->video_gift_db);
        }else{
            $gift_keys =  $this->redis->zRevRange($this->video_gift_zset,0,$num,true);
            $keys = array_keys($gift_keys);
            $data = $this->redis->hMGet($this->video_gift_db,$keys);
        }

        return $data;
    }
    /*
     * 批量删除礼物
     */
    public function  hdel($field_arr){
        $data = $this->redis->hDel($this->video_gift_db,$field_arr);
        return $data;
    }

}//类定义结束

?>