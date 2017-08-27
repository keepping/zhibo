<?php
	class Ridescommon{
		public function __construct()
		{
			require_once APP_ROOT_PATH.'mapi/lib/redis/BaseRedisService.php';
		}
		 //粉丝 
		 public function video_follw_list($user_id,$is_follow =0,$page=0)
		 {

			require_once(APP_ROOT_PATH.'mapi/lib/redis/UserFollwRedisService.php');
			$user_redis = new UserFollwRedisService($user_id);
			if($is_follow){
				//粉丝人数
				$follow_count = $user_redis->follower_count();
				//获取粉丝人数
				$list =  $user_redis->get_follonging_by_user($user_id,$page,C('PAGE_LISTROWS'));
			}else{
				//查看关注人数
				$follow_count = $user_redis->follow_count();
				//获取关注列表
				$list =  $user_redis->get_follonging_user($user_id,$page,C('PAGE_LISTROWS'));
			}
			return array('list'=>$list,'total_num'=>$follow_count);
		 }
		 //贡献
		 public function video_contribute_list($user_id,$video_id=0,$podcast_id=0,$page=0)
		 {
			require_once(APP_ROOT_PATH.'mapi/lib/redis/VideoContributionRedisService.php');
			$video_redis = new VideoContributionRedisService($user_id);
			//获取本视频 最多贡献
			if($video_id){
				$video_contribute = $video_redis->get_video_contribute($video_id,$page,C('PAGE_LISTROWS'),$is_only_list = false);
				return $video_contribute;
			}
			
            // 获取当前主播 最多贡献
			if($podcast_id){
				$video_contribute = $video_redis->get_podcast_contribute($podcast_id,$page,C('PAGE_LISTROWS'),$is_only_list = false);
				return $video_contribute;
			}
		 }
		/*
     	 * 更新会员信息（后台暂时不用）
     	 * $user_id
      	 * $data 要更新的数组array('nick_name'=>$name)
      	 * return int
		 */
		/*public function user_redis_list($user_id,$data){
			require_once(APP_ROOT_PATH.'mapi/lib/redis/UserRedisService.php');
			$user_redis = new UserRedisService();
			$list =  $user_redis->update_db($user_id,$data);
			return $list;
		}*/
		 /*
        * 对话题 进行增 删 改 查 操作
        * $cate_name 话题名称
        * $data :id    sort   desc
        * type: update 更新 insert 增加 delete删除
        * $user_id:
        */
		 public function video_cate_list($cate_name,$data=array(),$type='update'){
		 	/*
			require_once(APP_ROOT_PATH.'mapi/lib/redis/VideoRedisService.php');
			$video_redis = new VideoRedisService();
			//return $data;
			//return $cate_name."--".$type;
			$result =  $video_redis->syn_cate_db($cate_name,$data,$type);
			return $result;
			*/
		 }
		/*
         * 获取热门视频
         * $video_watch_sort_key 指定其他key 默认为空
         * $user_id :
         * $video_id:
         * $sort
         */
		 public function video_redis_list($user_id,$video_id=0,$sort=0)
		 {
		 	/*
			require_once(APP_ROOT_PATH.'mapi/lib/redis/VideoRedisService.php');
			$video_redis = new VideoRedisService($user_id);
			//更新视频排序信息
			if($sort&&$video_id){
				$return = $video_redis-> update_video_sort($video_id,$sort);
				return $return;
			}else{
				//获取视频列表
				$list =  $video_redis->get_hot_db($page=0,'',30);
				return $list;
			}
			*/
		 }
		
		
	}
?>
