<?php
// +----------------------------------------------------------------------
// | Fanwe 方维众筹商业系统
// +----------------------------------------------------------------------
// | Copyright (c) 2011 http://www.fanwe.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: 甘味人生(526130@qq.com)
// +----------------------------------------------------------------------

class VideoViewerRedisService extends BaseRedisService
{
	//var $video_viewer_level_db;//:video_id  zset 房间观众列表,user_id:会员级别

    /**
     *
     +----------------------------------------------------------
     * 架构函数
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     */
    public function __construct()
    {
        parent::__construct();

        //注：本redis只产生了一个：video_viewer_level_db 数据；观众列表
    }

    /*
     * 新会员进入
     */
    public function member_join($post){
        $GroupId = $post['GroupId'];
        $video_id = $this->redis->hGet($this->video_group_db,$GroupId);

        if(!$video_id){
            return false;
        }
        $video = $this->redis->hMGet($this->video_db.$video_id,array('room_type','virtual_number','robot_num','watch_number'));

        //前台展示的观众列表数量
        $now_num =  $video['robot_num'] + $video['watch_number'];

        //新入群成员列表
        foreach ( $post['NewMemberList'] as $k => $v ){
        	$user_id = $v['Member_Account'];

        	$user_db =  $this->redis->hMGet($this->user_db.$user_id,array('user_level','is_robot','is_authentication'));

        	$sort_num = $user_db['user_level']*$this->gz_level_weight;
        	if($user_db['is_robot']==0){
        		$sort_num+= $this->gz_real_weight;
        	}
        	if($user_db['is_authentication']==2){
        		$sort_num+= $this->gz_rz_weight;
        	}

        	if ($sort_num < 1) $sort_num = 1;

            //过滤重复加入的
            if ($this->redis->zAdd($this->video_viewer_level_db.$video_id,$sort_num,$user_id) > 0){


            	/*
	            //实际观众看人数+1
	            $watch_number = $this->redis->hIncrBy($this->video_db.$video_id,'watch_number',1);

				 //修复
				if($video['room_type'] == 1){
					$total_num = $this->redis->zCount($this->video_viewer_level_db.$video_id, '-inf', '+inf');
					if ($watch_number != $total_num){
						$this->redis->hSet($this->video_db.$video_id,'watch_number',$total_num);
					}
				}
				*/

	            $now_num += 1;
	            $virtual_number = 0;

	            //非私密直播,1个真实用户进来,带一些机器人;且只有观众列表数大于6时，才加
	            if ($video['room_type'] != 1 && $now_num > 6&&intval($video['virtual_number'])>0){

	            	$start_num = intval($video['virtual_number']/2)?intval($video['virtual_number']/2):2;

	            	$virtual_number =  rand($start_num,$video['virtual_number']);

	            	//添加虚拟人数
	            	$this->redis->hIncrBy($this->video_db.$video_id,'virtual_watch_number',$virtual_number);
	            }

	            //一个实际人数+虚拟人数
	            $virtual_number += 1;
	            $this->redis->hIncrBy($this->video_db.$video_id,'max_watch_number',$virtual_number);
            }

            //实际观众数统计：累计观众列表和; [score 为负数是：机器人; 正数是：真实观众]
            if($video['room_type'] == 1 || $video['room_type'] == 3){
            	$watch_number = $this->redis->zCount($this->video_viewer_level_db.$video_id, '1', '+inf');
            	$this->redis->hSet($this->video_db.$video_id,'watch_number',$watch_number);
            }
        }
        return true;
    }
    /*
     * 会员离开
     */
    public function member_exit($post){

        $GroupId = $post['GroupId'];
        $video_id = $this->redis->hGet($this->video_group_db,$GroupId);
        if(!$video_id){
            return false;
        }
         $video = $this->redis->hMGet($this->video_db.$video_id,array('room_type','virtual_number','virtual_watch_number',));
		if(intval($video['virtual_number'])>0){
			$number = intval($video['virtual_number']/2)?intval($video['virtual_number']/2):2;
		}else{
			$number = 0;
		}


        $virtual_watch_number = intval($video['virtual_watch_number']);

        //退出群的成员列表
        foreach ( $post['ExitMemberList'] as $k => $v ){
            $user_id = $v['Member_Account'];
            //用户移除成功
            if ($this->redis->zRem($this->video_viewer_level_db.$video_id,$user_id) > 0){
            	/*
            	//实际观众看人数-1
            	$watch_number = $this->redis->hIncrBy($this->video_db.$video_id,'watch_number',-1);
            	//确保,实际观看人数,不为会负数 chenfq by add 20161025
            	if ($watch_number < 0){
            		$watch_number = 0;

            		//zcount fanwe0000008:video_viewer_level:207 -inf +inf
            		//
            		$total_num = $this->redis->zCount($this->video_viewer_level_db.$video_id, '-inf', '+inf');
            		//$robot_num = $this->redis->zCount($this->user_robot_db.$video_id, '-inf', '+inf');
            		$robot_num = intval($this->redis->hGet($this->video_db.$video_id,'robot_num'));

            		$watch_number = $total_num - $robot_num;
            		if ($watch_number < 0){
            			$watch_number = 0;
            		}

            		$this->redis->hSet($this->video_db.$video_id,'watch_number',$watch_number);
            	}
            	*/

            	if ($video['room_type'] != 1 && $virtual_watch_number > 0){
	            	//随机减少一些虚拟人数
	            	$virtual_number =  rand(1,$number);
	            	if ($virtual_number > $virtual_watch_number){
	            		$virtual_number = $virtual_watch_number;
	            	}

	            	$this->redis->hIncrBy($this->video_db.$video_id,'virtual_watch_number',-$virtual_number);

	            	$virtual_watch_number = intval($this->redis->hGet($this->video_db.$video_id,'virtual_watch_number'));
	            	if ($virtual_watch_number < 0){
	            		$virtual_watch_number = 0;
	            		$this->redis->hSet($this->video_db.$video_id,'virtual_watch_number',0);
	            	}
            	}
            }

            //实际观众数统计：累计观众列表和; [score 为负数是：机器人; 正数是：真实观众]
            if($video['room_type'] == 1 || $video['room_type'] == 3){
            	$watch_number = $this->redis->zCount($this->video_viewer_level_db.$video_id, '1', '+inf');
            	$this->redis->hSet($this->video_db.$video_id,'watch_number',$watch_number);
            }
        }
    }


    /*
     * 获取热门视频
     */
    public function get_viewer_list($GroupId,$page=0,$page_size=200){


        $video_id = $this->redis->hGet($this->video_group_db,$GroupId);
        if(!$video_id){
            return array(
                'list'=>array(),
                'has_next'=>0,
                'page'=>1,
                'status'=>1
            );
        }else{
        	return $this->get_viewer_list2($video_id,$page,$page_size);
        }
        /*
        $root = array();
        if($page==0){
            $page = 1;
        }
        $root['page'] = $page;
        $start = ($page-1)*$page_size;
        $end = $page*$page_size-1;

        $video_user_level_array =  $this->redis->zRevRange($this->video_viewer_level_db.$video_id,$start,$end,true);


        $user_keys = array_keys($video_user_level_array);


        $user_list_array = $this->redis->hMGet($this->user_hash_db,$user_keys);

        $user_list = array();
        if(is_array($user_list_array)){
            foreach($user_list_array as $k=>$v){
                // $user = array();
                if($v){
                    $user = json_decode($v,true);
                    $user['video_viewer_level'] = $video_user_level_array[$k];
                    $user['user_level'] = $user['user_level'];
                    $user['user_id'] = $k;
                    $user['head_image'] = get_spec_image($user['head_image']);
                    $user['nick_name'] = $user['nick_name']?$user['nick_name']:'';
                    $user['nick_name'] =  htmlspecialchars_decode($user['nick_name']);
                    $user['signature'] = $user['signature']?$user['signature']:'';
                    $user['signature'] =  htmlspecialchars_decode($user['signature']);
                    $user_list[] = $user;
                }

            }
        }

        $root['list'] = $user_list;

        if($page == 0){
            $root['has_next'] = 0;
        }else{
            if((count($video_user_level_array)==$page_size)  ){
                $root['has_next'] = 1;
            }else{
                $root['has_next'] = 0;
            }
        }
        $root['status'] = 1;
        //$root['watch_number'] =  $this->redis->zScore($this->video_watch_sort,$video_id);
        $root['watch_number'] =  $this->get_video_watch_num($video_id);

        return $root;
        */
    }
    /*
     * 是否在当前房间用户列表 
     * 
     */
    public function existence_viewer_list($video_id,$user_id){
    	 $root = array();
    	 $root['video_viewer_level_score'] = $this->redis->zScore($this->video_viewer_level_db.$video_id,$user_id);
    	 return $root;
    }

    /*
     * 获取热门视频
    */
    public function get_viewer_list2($video_id,$page=0,$page_size=200){

    	$root = array();
    	if($page==0){
    		$page = 1;
    	}
    	$root['page'] = $page;
    	$start = ($page-1)*$page_size;
    	$end = $page*$page_size-1;

    	$user_keys =  $this->redis->zRevRange($this->video_viewer_level_db.$video_id,$start,$end,false);

    	//echo $this->video_viewer_level_db.$video_id;
    	//echo "<br>start:".$start.";end:".$end.";page_size:".$page_size;

    	//$user_keys = array_keys($video_user_level_array);

    	//print_r($user_keys);

    	$user_list_array = $this->redis->hMGet($this->user_hash_db,$user_keys);

    	$user_list = array();
    	if(is_array($user_list_array)){
    		foreach($user_list_array as $k=>$v){
    			// $user = array();
    			if($v){

    				$user = json_decode($v,true);
    				/*
    				//$user['video_viewer_level'] = $video_user_level_array[$k];
    				$user['user_level'] = $user['user_level'];
    				$user['user_id'] = $k;
    				$user['head_image'] = get_spec_image($user['head_image'],150,150);//get_spec_image($user['head_image']);
    				$user['nick_name'] = $user['nick_name']?$user['nick_name']:'';
    				$user['nick_name'] =  htmlspecialchars_decode($user['nick_name']);
    				$user['signature'] = $user['signature']?$user['signature']:'';
    				$user['signature'] =  htmlspecialchars_decode($user['signature']);
    				$user_list[] = $user;
    				*/
                    //移除异常数据
                    if(!$k){
                        $this->redis->zRem($this->video_viewer_level_db.$video_id,$k);
                        continue;
                    }

    				$user2 = array();
    				$user2['user_id'] = $k;
    				$user2['user_level'] = $user['user_level'];
    				$user2['is_robot'] = $user['is_robot'];
                    $user2['nick_name'] = $user['nick_name']?$user['nick_name']:'';
                    $user2['nick_name'] =  htmlspecialchars_decode($user2['nick_name']);
    				$user2['head_image'] = get_spec_image($user['head_image'],150,150);
    				$user2['v_icon'] = $user['v_icon'];
                    $user2['is_authentication'] = $user['is_authentication'];
                    $user2['sex'] = $user['sex'];
    				$user_list[] = $user2;
    			}

    		}
    	}

    	$root['list'] = $user_list;

    	if($page == 0){
    		$root['has_next'] = 0;
    	}else{
    		if((count($user_keys)==$page_size)  ){
    			$root['has_next'] = 1;
    		}else{
    			$root['has_next'] = 0;
    		}
    	}
    	$root['status'] = 1;
    	//$root['watch_number'] =  $this->redis->zScore($this->video_watch_sort,$video_id);
    	$root['watch_number'] =  $this->get_video_watch_num($video_id);

    	return $root;
    }

}//类定义结束

?>