<?php

class select_weibo_info_auto_cache extends auto_cache{
	private $key = "select:weibo_info:";
	public function load($param)
	{
//		$type = intval($param['type']);
//		$this->key .= $type;
		fanwe_require(APP_ROOT_PATH.'mapi/xr/core/common.php');
		$this->key .= md5(serialize($param));
		$page=$param['page']>0?$param['page']:1;
		$page_size=$param['page_size']>0?$param['page_size']:20;
		$limit = (($page-1) * $page_size) . "," . $page_size;
		$weibo_id = intval($param['weibo_id']);
		$key_bf = $this->key.'_bf';
		
		//$list = $GLOBALS['cache']->get($this->key,true);
		$list = false;
		if ($list === false) {
			$is_ok =  $GLOBALS['cache']->set_lock($this->key);
			if(false){
				$list = $GLOBALS['cache']->get($key_bf,true);
			}else{
				if($page == 1){
					$weibo_info = $GLOBALS['db']->getRow("select w.user_id,w.id as weibo_id,u.head_image,u.is_authentication,w.content,w.red_count,w.digg_count,w.comment_count,w.video_count,w.data,u.nick_name,w.sort_num,w.photo_image,u.city,w.is_top,w.price,w.type,w.create_time
        from ".DB_PREFIX."weibo as w left join ".DB_PREFIX."user as u on w.user_id = u.id where w.id = ".$weibo_id);
					if(!$weibo_info){
						$root['error'] = "动态不存在!";
						$root['status'] = 0;
						api_ajax_return($root);
					}

					$weibo_info['left_time'] = time_tran($weibo_info['create_time']);
					if($weibo_info['head_image']){
						$weibo_info['head_image'] = deal_weio_image($weibo_info['head_image']);
					}
					if($weibo_info['photo_image']){
						$weibo_info['photo_image'] = deal_weio_image($weibo_info['photo_image'],$weibo_info['type'].'_info');
					}
					$weibo_info['images_count'] = 0;
					if($weibo_info['type']=='video'){
						$weibo_info['images'] = array();
						$url = $weibo_info['data'];
						$weibo_info['video_url'] = get_file_oss_url($url);
					}else{
						$images = unserialize($weibo_info['data']);
//						if(in_array($weibo_info['weibo_id'],$order_list_array)){
//							$is_pay =1;
//						}else{
//							$is_pay =0;
//						}
						if(count($images)>0){

							$weibo_info['images'] = $images;
							$weibo_info['images_count'] = count($images);
						}else{
							$weibo_info['images'] = array();

						}
						$weibo_info['video_url'] = '';

					}

					$root['info'] = $weibo_info;
					//点赞列表
					$digg_list = $GLOBALS['db']->getAll("select wc.user_id,u.nick_name,u.head_image,u.is_authentication from ".DB_PREFIX."weibo_comment as wc
            left join ".DB_PREFIX."user as u on wc.user_id = u.id where wc.weibo_id = ".$weibo_id." and type = 2   order by wc.comment_id desc limit 0,7");
					$digg_user_list = array();
					if($digg_list){
						foreach($digg_list as $k=>$v){
							if($v){
								$digg_user_list[] = $v['user_id'];
								$digg_list[$k]['head_image'] = get_spec_image($v['head_image']);
							}
						}
					}
					$root['digg_user_list'] = $digg_user_list;
					$root['digg_list'] = $digg_list;
				}

				//评论列表
				$comment_list = $GLOBALS['db']->getAll("select wc.comment_id, wc.user_id,u.nick_name,u.head_image,wc.content,wc.to_comment_id,wc.to_user_id,wc.create_time,u.is_authentication from ".DB_PREFIX."weibo_comment as wc
            left join ".DB_PREFIX."user as u on wc.user_id = u.id where wc.weibo_id = ".$weibo_id." and type = 1  order by wc.comment_id desc limit $limit");
				if($comment_list){
					$to_comment_user = array();
					foreach($comment_list as $k=>$v){
						if($v){
							$comment_list[$k]['head_image'] = get_spec_image($v['head_image']);
							$comment_list[$k]['left_time'] = time_tran($v['create_time']);
							if($v['to_comment_id']){
								$comment_list[$k]['is_to_comment'] =1;
								$to_comment_user[] = $v['to_user_id'];
							}else{
								$comment_list[$k]['is_to_comment'] = 0;
							}
							$comment_list[$k]['to_nick_name'] = '';
						}
					}
					if(count($to_comment_user)>0){

						$user_list = $GLOBALS['db']->getAll("select id,nick_name from ".DB_PREFIX."user where id in (".implode(',',$to_comment_user).")");
						$user_array = array();
						foreach($user_list as $k=>$v){
							$user_array[$v['id']] = $v['nick_name'];
						}
						foreach($comment_list as $k=>$v){
							if( $v['to_user_id']){
								$comment_list[$k]['to_nick_name'] = $user_array[$v['to_user_id']];
							}
						}
					}
				}
				$root['comment_list'] = $comment_list;
				$list = $root;
				//$GLOBALS['cache']->set($this->key, $list, 5, true);
				
				//$GLOBALS['cache']->set($key_bf, $list, 86400, true);//备份
				//echo $this->key;
			}
 		}
 		
 		if ($list == false) $list = array();
 		
		return $list;
	}
	
	public function rm()
	{

		//$GLOBALS['cache']->clear_by_name($this->key);
	}
	
	public function clear_all()
	{
		
		//$GLOBALS['cache']->clear_by_name($this->key);
	}
}
?>