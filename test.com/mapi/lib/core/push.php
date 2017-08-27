<?php

	/**
	 * 用户粉丝数:fans_count,很关键的一个参数；如果fans_count>5W时，需要使用专门服务器来处理：推送业务; and u.fans_count < 50000
	 * 推送类型:pust_type 当会员数超过10W时，建议也要使用专门的服务器来处理：全服推送 类型的 and u.pust_type =0
	 * @param int $min_fans 最小粉丝数
	 * @param int $max_fans	最大粉丝数
	 * @param array $pust_type 推送类型
	 * 
	 * 
	 * 推送数据存储优化方案[备用]
	 * 1、记录禁用推送功能的用户ID fanwe_user.is_remind
	 * 2、记录不接受某个主播推送消息（主播ID，用户ID）
	 * 3、在redis中建2张hash表【 push:ios user_id apns; push:android user_id apns】 可以在ctl=user&act=apns中更新
	 * 4.1、全服推送时,只要取出3后，过滤1中的数据即可
	 * 4.2、给某个主播推送时，通过$user_redis->followed_by($user_id)取出粉丝列表后，过滤1、2数量，然后分别3取并集即可
	 * 
	 * 5、在fanwe_push_anchor表中,可以直接添加fans_count字段，插入表数据时，固化
	 * 
	 */
	 //服务器进程执行时间
	ini_set("max_execution_time", 600);//服务器会在 60 秒后强行中止正在执行的程序
	 
	function push_notice($min_fans = 0,$max_fans = 0,$pust_type = array(0,1)){
		$sql = "select pa.id, pa.pust_type, pa.user_id, pa.nick_name, pa.city,pa.room_id, v.room_type from ".DB_PREFIX."push_anchor as pa 
				left join ".DB_PREFIX."video as v on v.id = pa.room_id 
				left join ".DB_PREFIX."user as u on u.id = pa.user_id 		
				where v.room_type = 3 and v.live_in= 1 and pa.status = 0 and u.mobile != '13888888888' and  u.mobile != '13999999999' ";// pa.pust_type = 0 and u.fans_count < 50000
		
		if ($min_fans > 0) $sql .= ' and u.fans_count>='.$min_fans;
		if ($max_fans > 0) $sql .= ' and u.fans_count<='.$max_fans;
		if (is_array($pust_type) && count($pust_type) > 0) $sql .= ' and pa.pust_type in('.implode(',',$pust_type).")";
		
		
		$list = $GLOBALS['db']->getAll($sql,true,true);
		
		if (count($list)> 0){
			fanwe_require(APP_ROOT_PATH.'system/schedule/android_list_schedule.php');
			fanwe_require(APP_ROOT_PATH.'system/schedule/ios_list_schedule.php');
			fanwe_require(APP_ROOT_PATH.'system/schedule/android_file_schedule.php');
			fanwe_require(APP_ROOT_PATH.'system/schedule/ios_file_schedule.php');
			fanwe_require(APP_ROOT_PATH.'mapi/lib/redis/BaseRedisService.php');
			fanwe_require(APP_ROOT_PATH.'mapi/lib/redis/UserFollwRedisService.php');
		}
		
		foreach ($list as $k => $v )
		{
			//查询主播设备号
			$apns_code_sql = "select u.apns_code from ".DB_PREFIX."user as u  where u.id =".$v['user_id'];
			$my_apns_code =  $GLOBALS['db']->getOne($apns_code_sql,true,true);
			$sql = "update ".DB_PREFIX."push_anchor set status = 1 where status = 0 and id = ".$v['id'];
			$status = $GLOBALS['db']->query($sql);
			if($GLOBALS['db']->affected_rows()==1)
			{
				//$code_sql = "select u.apns_code,u.device_type  from ".DB_PREFIX."video_private as vp left join ".DB_PREFIX."user as u on u.id = vp.user_id  where  u.is_effect =1 and u.device_type <> '' and vp.video_id =".$v['room_id'];
				//$code_list = $GLOBALS['db']->getAll($code_sql);
				
					//判断推送类型
					if($v['pust_type'] == 1){
						//查找全服推送【如果会员数大多如上百万时，建议使用独立的服务器来专门做推送，并做好缓存，直接分IOS，安卓缓存成友盟需要的文件格式】
						$code_sql = "select u.apns_code,u.device_type from ".DB_PREFIX."user as u  where u.id not in(".$v['user_id'].") and u.device_type in (1,2) and u.is_effect =1 and u.is_remind=1";
						$code_list = $GLOBALS['db']->getAll($code_sql,true,true);					
					}else{
						//查找粉丝推送
						
						$user_id = $v['user_id'];
						$code_list = array();
						$user_redis = new UserFollwRedisService($user_id);
						$list =  $user_redis->followed_by($user_id);//获得粉丝列表,可能很大，如有上百W的粉丝，做推送时，需要独立的服务器来处理
						do{
							$keys = array_splice($list,0,4000);//一次读取4000条数据
							
							if (count($keys)>0){
								$user_list = implode(',',$keys);
								$code_sql = "select u.apns_code,u.device_type from ".DB_PREFIX."user as u where  u.is_effect =1 and u.device_type in (1,2) and u.id in (".$user_list.") and u.is_remind=1";
								$apns_list = $GLOBALS['db']->getAll($code_sql,true,true);
								$code_list = array_merge($code_list,$apns_list);
							}
						}while (count($keys)>0);
						
						//$code_list = array_unique($code_list);//加上array_unique，合并时可以过滤重复的？
						
						/*
						$page =1;
						$page_size =1000;
						$code_list = array();
						do{
							//$list =  $user_redis->get_follonging_by_user($user_id,$page,$page_size);
							$list =  $user_redis->followed_by($user_id);
							$start = ($page-1)*$page_size;
							$keys = array_slice($list,$start,$page_size);
							
							//file_put_contents(APP_ROOT_PATH_PUSH."/public/push.txt", print_r($list,1),FILE_APPEND);
							
							$user_list = implode(',',$keys);
							$code_sql = "select u.apns_code,u.device_type  from ".DB_PREFIX."user as u  where  u.is_effect =1 and u.device_type in (1,2) and u.id in (".$user_list.") and u.is_remind=1";
							//file_put_contents(APP_ROOT_PATH_PUSH."/public/push.txt", print_r($code_sql,1),FILE_APPEND);
							$apns_list = $GLOBALS['db']->getAll($code_sql,true,true);
							
							foreach($apns_list as $kk =>$vv){
								$code_list[] = $vv;
							}
							//$code_list = array_merge($code_list,$apns_list);
							
							$page = $page + 1;
						}while (count($keys) == $page_size);
						*/
												
					}
				
								
				//推送消息文本
				$content =$v['nick_name']."正在".$v['city']."直播，邀请你一起";
				$room_id = $v['room_id'];
				
				//过滤重复的推送数据数据
				$array = array_map('json_encode', $code_list);
				$array = array_unique($array);
				$code_list = array_map('json_decode', $array);
				
				$num =count($code_list);
				//大于10000条的推送，使用文件方式，小于1000条直接推送
				if(intval($num)>10000){
					$code_arr =array();
					$code_android_arr =array();
					$code_ios_arr =array();
					foreach($code_list as $ck =>$cv){
						$apns_code = $cv->apns_code;
						$device_type = $cv->device_type;
						//排除主播设备号
						if($my_apns_code==$apns_code||in_array($apns_code,$code_android_arr)||in_array($apns_code,$code_ios_arr)){
							continue;
						}				
						if($device_type==1){
							$code_android_arr[] = $apns_code;
						}
						if($device_type==2){
							$code_ios_arr[] = $apns_code;
						}
						
					}
					if($code_android_arr){
						$code_android_file = implode("\n",$code_android_arr);
					}
					
					if($code_ios_arr){
						$code_ios_file = implode("\n",$code_ios_arr);
					}
					
					//device_type 1：安卓机型。2：ios
					//安卓推送信息
					if($code_android_arr){
					    $AndroidFile = new android_file_schedule();		   
					    $data = array(
							'file_code' =>$code_android_file,
							'content' =>$content,
							'room_id'=>$room_id,
							'type'=>0,
						);
						$return = $AndroidFile->exec($data);
						$sql = "update ".DB_PREFIX."push_anchor set ret_android_status = '".$return['res']['ret']."', ret_android_data = '".serialize($return['res']['data'])."', android_file_id = '".$return['file_id']."' where  id = ".$v['id'];
						$GLOBALS['db']->query($sql);
					}
					//ios 推送信息
					if($code_ios_arr){
						$IosFile = new ios_file_schedule();
					   	$Ios_data = array(
							'file_code' =>$code_ios_file,
							'content' =>$content,
							'room_id'=>$room_id,
							'type'=>0,
						);
						$return = $IosFile->exec($Ios_data);
						$sql = "update ".DB_PREFIX."push_anchor set ret_ios_status = '".$return['res']['ret']."', ret_ios_data = '".serialize($return['res']['data'])."', ios_file_id = '".$return['file_id']."' where  id = ".$v['id'];
						$GLOBALS['db']->query($sql);
					}
				}else{
					//得到机器码列表
					$apns_app_code_list = array();
					$apns_ios_code_list = array();
					
					$j=$i=0;
					foreach($code_list as $kk=>$vv){
						$apns_code = $vv->apns_code;
						$device_type = $vv->device_type;
						
						//排除主播设备号
						if($my_apns_code==$apns_code||in_array($apns_code,$apns_app_code_list)){
							continue;
						}						
						//获取android机器码
						if($device_type==1){
							$apns_app_code_list[$i] = $apns_code;
							$i++;
						}
						
						//获取IOS机器码
						if($device_type==2){
							$apns_ios_code_list[$j] = $apns_code;
							$j++;
						}
						
						//安卓推送信息
						if($i%500==0&&$i!=0){
						   $AndroidList = new android_list_schedule();
						   $android_dest = implode(",",$apns_app_code_list);
						   $data = array(
								'dest' =>$android_dest,
								'content' =>$content,
								'room_id'=>$room_id,
								'type'=>0,
							);
							$return = $AndroidList->exec($data);
							$sql = "update ".DB_PREFIX."push_anchor set  ret_android_status = '".$return['res']['ret']."', ret_android_data = '".serialize($return['res']['data'])."' where  id = ".$v['id'];
							$GLOBALS['db']->query($sql);
							//重置机器列表
							$i=0;
							unset($apns_app_code_list);
							 
						}
						
						//ios 推送信息
						if($j%500==0&&$j!=0){
							$IosList = new ios_list_schedule();
							$ios_dest = implode(",",$apns_ios_code_list);
						   	$ios_data1 = array(
								'dest' =>$ios_dest,
								'content' =>$content,
								'room_id'=>$room_id,
								'type'=>0,
							);
							$return = $IosList->exec($ios_data1);
							// ios_dest = '".$ios_dest."',
							$sql = "update ".DB_PREFIX."push_anchor set ret_ios_status = '".$return['res']['ret']."', ret_ios_data = '".serialize($return['res']['data'])."' where  id = ".$v['id'];
							$GLOBALS['db']->query($sql);
							//重置机器列表
							$j=0;
							unset($apns_ios_code_list);
						}							
					}
					//安卓推送信息
					if(count($apns_app_code_list)>0){
					   $AndroidList = new android_list_schedule();
					   $android_dest = implode(",",$apns_app_code_list);
					   $data = array(
							'dest' =>$android_dest,
							'content' =>$content,
							'room_id'=>$room_id,
							'type'=>0,
						);
						$return = $AndroidList->exec($data);
						$sql = "update ".DB_PREFIX."push_anchor set ret_android_status = '".$return['res']['ret']."', ret_android_data = '".serialize($return['res']['data'])."' where  id = ".$v['id'];
						$GLOBALS['db']->query($sql);
						
						//打印推送列表
						//$this->chack_push_notice($android_dest);
						//$this->chack_push_notice($return);
					}
					

					//ios 推送信息
					if(count($apns_ios_code_list)>0){
						 $IosList = new ios_list_schedule();
						 $ios_dest = implode(",",$apns_ios_code_list);
					   	 $ios_data = array(
							'dest' =>$ios_dest,
							'content' =>$content,
							'room_id'=>$room_id,
							'type'=>0,
						);
						$return = $IosList->exec($ios_data);
						$sql = "update ".DB_PREFIX."push_anchor set ret_ios_status = '".$return['res']['ret']."', ret_ios_data = '".serialize($return['res']['data'])."' where  id = ".$v['id'];
						$GLOBALS['db']->query($sql);
					}
					
					//打印推送列表
					//$this->chack_push_notice($android_dest);
					//$this->chack_push_notice($return);
				}
				//推送结束
				$sql = "update ".DB_PREFIX."push_anchor set status = 2 where status = 1 and id = ".$v['id'];
				$GLOBALS['db']->query($sql);
			}
		}
		
		return true;
	}
	// @param array or string $dates
	function chack_push_notice($dates){
		if(IS_DEBUG){
			$api_log = array();
			if(is_array($dates)){
				$parma= implode('',$dates);
			}else{
				$parma = $dates;
			}
			$api_log['parma'] = $parma;
			$GLOBALS['db']->autoExecute(DB_PREFIX."api_log", $api_log,'INSERT');
		}
	}
?>
