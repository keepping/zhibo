<?php

class select_weibo_list_auto_cache extends auto_cache{
	private $key = "select:list:";
	public function load($param)
	{
		fanwe_require(APP_ROOT_PATH.'mapi/xr/core/common.php');
		$user_id = intval($param['user_id']);
		$page=$param['page']>0?$param['page']:1;
		$page_size=$param['page_size']>0?$param['page_size']:20;
		$limit = (($page-1) * $page_size) . "," . $page_size;

		$this->key .= md5(serialize($param));
		$key_bf = $this->key.'_bf';

		$list = $GLOBALS['cache']->get($this->key,true);

		if ($list === false) {
			$is_ok =  $GLOBALS['cache']->set_lock($this->key);
			if(!$is_ok){
				$list = $GLOBALS['cache']->get($key_bf,true);
			}else{
				$order_list_array = array();
				$diggs_array = array();


				$order_list = $GLOBALS['db']->getAll("select order_id from ".DB_PREFIX."payment_notice where user_id = ".$user_id." and type in (11,13,14) and is_paid =1");
				if(count($order_list)>0){
					foreach($order_list as $k=>$v){
						$order_list_array[] = $v['order_id'];
					}
				}
				$diggs  = $GLOBALS['db']->getAll("select weibo_id from ".DB_PREFIX."weibo_comment where user_id = ".$user_id." and type = 2");
				if(count($diggs)>0){
					foreach($diggs as $k=>$v){
						$diggs_array[] = $v['weibo_id'];
					}
				}

				$sql_black = "select black_user_id from ".DB_PREFIX."black where  user_id = ".$user_id;
				$black_list = $GLOBALS['db']->getAll($sql_black,true,true);
				if(count($black_list)>0){
					foreach($black_list as $k=>$v){
						$black_list_array[] = $v['black_user_id'];
					}
				}

				fanwe_require(APP_ROOT_PATH.'mapi/lib/redis/UserRedisService.php');
				$userfollow_redis = new UserFollwRedisService($user_id);
				$ids = $userfollow_redis->following();
				$ids[] = $user_id;
				if(count($black_list_array)>0){
					$ids = array_unique(array_merge($ids,$black_list_array));
				}
				$top_list = array();

				$sql_top = "select w.user_id,w.id as weibo_id,u.head_image,u.is_authentication,w.content,w.red_count,w.digg_count,w.comment_count,w.video_count,w.data,u.nick_name,w.sort_num,w.photo_image ,w.is_top,w.price,w.type,w.create_time,w.city,w.province,w.address from ".DB_PREFIX."weibo as w
				left join ".DB_PREFIX."user as u on w.user_id = u.id where w.user_id = $user_id and w.status = 1 and is_top=1";				
				$top_list = $GLOBALS['db']->getAll($sql_top,true,true);
				$top_keys = array();
				foreach($top_list as $k=>$v){
					$top_keys[] = $v['weibo_id'];
				}
				$where = '';
				if($top_keys){
					$where = " and w.id not in ( ".implode(',',$top_keys) .")";
				}

				$sql = "select w.user_id,w.id as weibo_id,u.head_image,u.is_authentication,w.content,w.red_count,w.digg_count,w.comment_count,w.video_count,w.data,u.nick_name,w.sort_num,w.photo_image ,w.is_top,w.price,w.type,w.create_time,w.city,w.province,w.address from ".DB_PREFIX."weibo as w
					left join ".DB_PREFIX."user as u on w.user_id = u.id where w.user_id in ( ".implode(',',$ids) .") and w.status = 1 ".$where;


				$sql .= "  order by  w.id desc";
				$sql .= " limit " .$limit;
				$list = $GLOBALS['db']->getAll($sql,true,true);
				if($page==1) {
					if($top_list){
						$list = array_merge($top_list,$list);
					}
				}

				if(count($list)>0){
					foreach($list as $k=>$v){

						$list[$k]['is_show_weibo_report'] = 0;
						$list[$k]['is_show_user_report'] = 0;
						$list[$k]['is_show_user_black'] = 0;
						$list[$k]['is_show_top'] = 1;
						if($v['is_top']){
							$list[$k]['show_top_des'] = '取消置顶';
						}else{
							$list[$k]['show_top_des'] = '置顶动态';
						}
						if($user_id !=$v['user_id']){
							$list[$k]['is_top'] = 0;
						}
						$list[$k]['is_show_deal_weibo'] =1;
						if(in_array($v['weibo_id'],$diggs_array)){
							$list[$k]['has_digg'] = 1;
						}else{
							$list[$k]['has_digg'] = 0;
						}
						$list[$k]['left_time'] = $this->time_tran($v['create_time']);
						if($v['head_image']){
							$list[$k]['head_image'] = deal_weio_image($v['head_image'],'head_image');
						}
						if($v['photo_image']){
							$list[$k]['photo_image'] = deal_weio_image($v['photo_image'],$v['type']);
						}
						$list[$k]['images_count'] = 0;
						$list[$k]['goods_url'] = '';
						if($v['type']=='goods'){
							$list[$k]['goods_url'] =  SITE_DOMAIN.'/wap/xr/index.html#/weiboGoodsInfo?weibo_id='.$v['weibo_id'];
						}
						$address_x = str_replace("福建省","",$v['address']);
						$address_x = str_replace("福州市","",$address_x);
						$list[$k]['weibo_place'] = $v['province'].$v['city'].$address_x;
						if($v['type']=='video'){
							$list[$k]['images'] = array();
							$url = $v['data'];
							$list[$k]['video_url'] = get_file_oss_url($url);
						}else{
							$images = unserialize($v['data']);
							$list[$k]['images'] = $images;
							//只针对 $to_user_id==$user_id
							$is_pay = 0;
							if(in_array($v['weibo_id'],$order_list_array)||$v['user_id']==$user_id){
								$is_pay =1;
							}
							$price = floatval($v['price']);
							if(count($images)>0){
								foreach($images as $k1=>$v1){
									if(is_object($v1)){
										$v1 = (array)$v1;
									}
									$images[$k1]['orginal_url'] = '';
									if($v1['url']){
										if($price>0){
											$is_model = $v1['is_model'];
											if($is_pay){
												$images[$k1]['url'] =  deal_weio_image($v1['url']);
												$images[$k1]['is_model'] =  0;
												$images[$k1]['orginal_url'] =  get_spec_image($v1['url']);
											}else{
												if($is_model){
													$images[$k1]['url'] = deal_weio_image($v1['url'],$v['type'],1);

												}else{
													$images[$k1]['url'] =  deal_weio_image($v1['url']);
													$images[$k1]['orginal_url'] =  get_spec_image($v1['url']);
												}

											}
										}else{
											$images[$k1]['url'] =  deal_weio_image($v1['url']);
											$images[$k1]['is_model'] =  0;
											$images[$k1]['orginal_url'] =  get_spec_image($v1['url']);
										}
									}
								}
								$list[$k]['images'] = $images;
								$list[$k]['images_count'] = count($images);
							}else{
								$list[$k]['images'] = array();
							}

							$list[$k]['video_url'] = '';
						}
						unset($list[$k]['data']);
					}
				}else{
					$list = array();
				}

				
				$GLOBALS['cache']->set($this->key, $list, 3, true);
				
				$GLOBALS['cache']->set($key_bf, $list, 86400, true);//备份
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
	public function time_tran($the_time)
	{
		$now_time = to_date(NOW_TIME,"Y-m-d H:i:s");
		$now_time = to_timespan($now_time);
		$show_time = to_timespan($the_time);
		$dur = $now_time - $show_time;
		if ($dur < 0) {
			return to_date($show_time,"Y-m-d");
		} else {
			if ($dur < 60) {
				return $dur . '秒前';
			} else {
				if ($dur < 3600) {
					return floor($dur / 60) . '分钟前';
				} else {
					if ($dur < 86400) {
						return floor($dur / 3600) . '小时前';
					} else {
						if ($dur < 2592000) {//30天内
							return floor($dur / 86400) . '天前';
						} else {
							return to_date($show_time,"Y-m-d");
						}
					}
				}
			}
		}
	}

}
?>