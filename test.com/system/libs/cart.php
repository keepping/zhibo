<?php
// +----------------------------------------------------------------------
// | Fanwe 方维众筹商业系统
// +----------------------------------------------------------------------
// | Copyright (c) 2011 http://www.fanwe.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: 甘味人生(526130@qq.com)
// +----------------------------------------------------------------------

//用户可删除已用余额支付的订单，并将余额退回帐户
define("PAYMENT_NOT_EXIST",0); //支付单被删除(提示联系管理员)
define("PAY_SUCCESS",1);  //充值成功(充值到相应的会员帐户中，并生成日志)或者订单支付成功
define("ORDER_REPAY",2);    //订单重复支付(即付款单所属的订单已支付，将支付的金额转存到会员帐户，并生成日志)
define("ORDER_EXPIRED",3);   //订单支付失败(限时已到，无法完成订单支付，退款到会员帐户，并生成日志)
define("ORDER_SOLDOUT",4);   //订单支付失败(即库存已满，无法完成订单支付，退款到会员帐户，并生成日志)

//付款记录支付
//返回
function payment_paid($notice_sn,$outer_sn)
{
	$payment_notice = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."payment_notice where notice_sn = '".$notice_sn."'");
	if($payment_notice)
	{
		$pInTrans = $GLOBALS['db']->StartTrans();
		try
		{
			$total_diamonds = 0;
			//充值单
			$sql = "update ".DB_PREFIX."payment_notice set pay_time = '".get_gmtime()."',is_paid = 1,outer_notice_sn = '".$outer_sn."' , pay_date = '".to_date(get_gmtime(), 'Y-m-d')."' where id = ".$payment_notice['id']." and is_paid = 0";
			$GLOBALS['db']->query($sql);

			if($GLOBALS['db']->affected_rows())
			{
				$GLOBALS['db']->query("update ".DB_PREFIX."payment set total_amount = total_amount + ".$payment_notice['money']." where id = ".$payment_notice['payment_id']);
				if ($payment_notice['order_id']>0) {

					//判断是否为鲜肉
					if($payment_notice['type']==11){
						$weibo_id = $payment_notice['order_id'];
						$weibo = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."weibo where id = ".$weibo_id);
						$money = $payment_notice['money'];
						if($weibo){
							// 更新状态

							$GLOBALS['db']->query("UPDATE ".DB_PREFIX."weibo set red_count = red_count+1 where id = ".$weibo_id);
							$GLOBALS['db']->query("UPDATE ".DB_PREFIX."user set weibo_money = weibo_money+$money where id = ".$weibo['user_id']);
							fanwe_require(APP_ROOT_PATH . 'mapi/lib/redis/BaseRedisService.php');
							fanwe_require(APP_ROOT_PATH.'mapi/xr/redis/WeiboContributionRedisService.php');
							$weiboCont_redis = new WeiboContributionRedisService();
							$weiboCont_redis->insert_db($payment_notice['user_id'], $weibo['user_id'], $money);
							//进行分销
							$weiboCont_redis->distribution_calculate($payment_notice['user_id'], $money,$payment_notice['recharge_name']);
						}else{
							//增加到付款人的金额中
							$GLOBALS['db']->query("UPDATE ".DB_PREFIX."user set weibo_money = weibo_money+$money where id = ".$payment_notice['user_id']);
							$GLOBALS['db']->query("UPDATE ".DB_PREFIX."payment_notice set  is_paid= 3 where id = ".$payment_notice['id']);//退款到余额中

						}

					}else{
						$is_p = $GLOBALS['db']->getOne("select is_p from ".DB_PREFIX."goods_order where id = ".$payment_notice['order_id']);
						if(intval($is_p) == 0){
							//购买
							$goods_order = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."goods_order where id = ".$payment_notice['order_id']);
							if($goods_order){
								// 更新订单状态
								$pay_time = date("Y-m-d H:i:s",time());
								$goods_info = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."goods where id = ".$goods_order['goods_id']);
								$video_info = $GLOBALS['db']->getRow("select * from " . DB_PREFIX . "video where user_id=" . $goods_order['podcast_id'] . " and live_in =1");

								if($goods_order['buy_type'] == 0){

									$table = DB_PREFIX . "goods_order";
									$sql   = "UPDATE $table SET order_status = '2',pay_time='".$pay_time."',pay_diamonds='".$goods_order['total_diamonds']."' WHERE `id`=".$payment_notice['order_id'];
									$GLOBALS['db']->query($sql);

//									$sql = "update ".DB_PREFIX."user set ticket = ticket + ".$goods_order['podcast_ticket']." where id = ".$goods_order['podcast_id'];
//									$GLOBALS['db']->query($sql);//主播增加佣金
//									user_deal_to_reids(array(intval($goods_order['podcast_id'])));

									if($goods_info){
										$root['quantity'] = intval($goods_order['number']);
										$root['goods_logo'] = json_decode(get_spec_image($goods_info['imgs']),true)[0];
										$root['goods_name'] = $goods_info['name'];
										$root['order_sn'] = $goods_order['order_sn'];
										$root['empirivalue'] = $goods_info['score'];
									}
									$ext = array();
									$ext['goods'] = $root;

									$root['roomId'] = intval($video_info['id']);
									$root['groupId'] = $video_info['group_id'];
									$root['createrId'] = $video_info['user_id'];
									$root['loadingVideoImageUrl'] = get_spec_image($video_info['head_image']);
									$root['video_type'] = $video_info['video_type'];

									$ext['type']    = 37;
									$ext['room_id'] = intval($video_info['id']);
									$ext['post_id'] = $goods_order['viewer_id'];
									$ext['desc'] = "我购买“" .$root['goods_name']. "”";
									$ext['is_self'] = 1;
									$ext['score'] = $root['empirivalue'];

								}else{

									$order_info=array();
									$order_info['order_status']=4;
									$order_info['order_status_time']=NOW_TIME;
									$order_info['pay_time']=$pay_time;
									$order_info['pay_diamonds'] = $goods_order['total_diamonds'];
									$GLOBALS['db']->autoExecute(DB_PREFIX."goods_order", $order_info, $mode = 'UPDATE', "order_sn='".$goods_order['order_sn']."'");

									$sql = "update ".DB_PREFIX."user set ticket = ticket + ".$goods_order['podcast_ticket']." where id = ".$goods_order['podcast_id'];
									$GLOBALS['db']->query($sql);//主播增加佣金
									user_deal_to_reids(array(intval($goods_order['podcast_id'])));

									if($goods_info){
										$root['quantity'] = intval($goods_order['number']);
										$root['goods_logo'] = json_decode(get_spec_image($goods_info['imgs']),true)[0];
										$root['goods_name'] = $goods_info['name'];
										$root['order_sn'] = $goods_order['order_sn'];
										$root['empirivalue'] = $goods_info['score'];
									}
									$ext = array();
									$ext['goods'] = $root;

									$root['roomId'] = intval($video_info['id']);
									$root['groupId'] = $video_info['group_id'];
									$root['createrId'] = $video_info['user_id'];
									$root['loadingVideoImageUrl'] = get_spec_image($video_info['head_image']);
									$root['video_type'] = $video_info['video_type'];

									$ext['type']    = 37;
									$ext['room_id'] = intval($video_info['id']);
									$ext['post_id'] = $goods_order['viewer_id'];
									$ext['desc'] = "我购买“" .$root['goods_name']. "”赠送主播";
									$ext['is_self'] = 0;
									$ext['score'] = $root['empirivalue'];

								}

								fanwe_require(APP_ROOT_PATH . 'mapi/lib/redis/BaseRedisService.php');
								fanwe_require(APP_ROOT_PATH . 'mapi/lib/redis/UserFollwRedisService.php');
								fanwe_require(APP_ROOT_PATH . 'mapi/lib/redis/UserRedisService.php');
								$user_redis = new UserRedisService();
								$fields = array('head_image', 'user_level', 'v_type', 'v_icon', 'nick_name');
								$ext['user'] = $user_redis->getRow_db($goods_order['viewer_id'], $fields);
								$ext['user']['user_id']    = $goods_order['viewer_id'];
								$ext['user']['head_image'] = get_spec_image($ext['user']['head_image']);

								$user_score = $GLOBALS['db']->query("update ".DB_PREFIX."user set score=score+".intval($root['empirivalue'])." where id = ".$goods_order['viewer_id']);
								$podcast_score = $GLOBALS['db']->query("update ".DB_PREFIX."user set score=score+".intval($root['empirivalue'])." where id = ".$goods_order['podcast_id']);
								if($user_score && $podcast_score){
									//更新经验
									$user_redis->inc_score($goods_order['viewer_id'],intval($root['empirivalue']));
									$user_redis->inc_score($goods_order['podcast_id'],intval($root['empirivalue']));
								}

//								$sql = "update ".DB_PREFIX."goods set inventory=inventory-".$goods_order['number']." where id = ".$goods_order['goods_id'];
//								$GLOBALS['db']->query($sql);//商品库存减少

								#构造高级接口所需参数
								$tim_data = array();
								$tim_data['ext'] = $ext;
								$tim_data['podcast_id'] = strim($goods_order['viewer_id']);
								$tim_data['group_id'] = strim($video_info['group_id']);
								$tim_data['score'] = intval($root['empirivalue']);
								get_tim_api($tim_data);

							}
						}else{
							$goods_order = $GLOBALS['db']->getAll("select * from ".DB_PREFIX."goods_order where pid= ".$payment_notice['order_id']);
							if($goods_order){
								$pay_time = date("Y-m-d H:i:s",time());
								$sql   = "UPDATE ".DB_PREFIX."goods_order SET order_status = '2',pay_time='".$pay_time."' WHERE `id`=".$payment_notice['order_id'];
								$GLOBALS['db']->query($sql);

								foreach($goods_order as $key =>$value){
									$goods_info = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."goods where id = ".$value['goods_id']);
									$video_info = $GLOBALS['db']->getRow("select * from " . DB_PREFIX . "video where user_id=" . $value['podcast_id'] . " and live_in =1");
									if($value['buy_type'] == 0){
										$table = DB_PREFIX . "goods_order";
										$sql   = "UPDATE $table SET order_status = '2',pay_time='".$pay_time."',pay_diamonds='".$goods_order['total_diamonds']."' WHERE `id`=".$value['id'];
										$GLOBALS['db']->query($sql);

//										$sql = "update ".DB_PREFIX."user set ticket = ticket + ".$value['podcast_ticket']." where id = ".$value['podcast_id'];
//										$GLOBALS['db']->query($sql);//主播增加佣金
//										user_deal_to_reids(array(intval($value['podcast_id'])));

										if($goods_info){
											$root['quantity'] = intval($value['number']);
											$root['goods_logo'] = json_decode(get_spec_image($goods_info['imgs']),true)[0];
											$root['goods_name'] = $goods_info['name'];
											$root['order_sn'] = $value['order_sn'];
											$root['empirivalue'] = $goods_info['score'];
										}
										$ext = array();
										$ext['goods'] = $root;

										$root['roomId'] = intval($video_info['id']);
										$root['groupId'] = $video_info['group_id'];
										$root['createrId'] = $video_info['user_id'];
										$root['loadingVideoImageUrl'] = get_spec_image($video_info['head_image']);
										$root['video_type'] = $video_info['video_type'];

										$ext['type']    = 37;
										$ext['room_id'] = intval($video_info['id']);
										$ext['post_id'] = $value['viewer_id'];
										$ext['desc'] = "我购买“" .$root['goods_name']. "”";
										$ext['is_self'] = 1;
										$ext['score'] = $root['empirivalue'];

									}else{
										$order_info=array();
										$order_info['order_status']=4;
										$order_info['order_status_time']=NOW_TIME;
										$order_info['pay_time']=$pay_time;
										$order_info['pay_diamonds'] = $goods_order['total_diamonds'];
										$GLOBALS['db']->autoExecute(DB_PREFIX."goods_order", $order_info, $mode = 'UPDATE', "order_sn='".$value['order_sn']."'");

										$sql = "update ".DB_PREFIX."user set ticket = ticket + ".$value['podcast_ticket']." where id = ".$value['podcast_id'];
										$GLOBALS['db']->query($sql);//主播增加佣金
										user_deal_to_reids(array(intval($value['podcast_id'])));

										if($goods_info){
											$root['quantity'] = intval($value['number']);
											$root['goods_logo'] = json_decode(get_spec_image($goods_info['imgs']),true)[0];
											$root['goods_name'] = $goods_info['name'];
											$root['order_sn'] = $value['order_sn'];
											$root['empirivalue'] = $goods_info['score'];
										}
										$ext = array();
										$ext['goods'] = $root;

										$root['roomId'] = intval($video_info['id']);
										$root['groupId'] = $video_info['group_id'];
										$root['createrId'] = $video_info['user_id'];
										$root['loadingVideoImageUrl'] = get_spec_image($video_info['head_image']);
										$root['video_type'] = $video_info['video_type'];

										$ext['type']    = 37;
										$ext['room_id'] = intval($video_info['id']);
										$ext['post_id'] = $value['viewer_id'];
										$ext['desc'] = "我购买“" .$root['goods_name']. "”赠送主播";
										$ext['is_self'] = 0;
										$ext['score'] = $root['empirivalue'];
									}

									fanwe_require(APP_ROOT_PATH . 'mapi/lib/redis/BaseRedisService.php');
									fanwe_require(APP_ROOT_PATH . 'mapi/lib/redis/UserFollwRedisService.php');
									fanwe_require(APP_ROOT_PATH . 'mapi/lib/redis/UserRedisService.php');
									$user_redis = new UserRedisService();
									$fields = array('head_image', 'user_level', 'v_type', 'v_icon', 'nick_name');
									$ext['user'] = $user_redis->getRow_db($value['viewer_id'], $fields);
									$ext['user']['user_id']    = $value['viewer_id'];
									$ext['user']['head_image'] = get_spec_image($ext['user']['head_image']);

									$user_score = $GLOBALS['db']->query("update ".DB_PREFIX."user set score=score+".intval($root['empirivalue'])." where id = ".$value['viewer_id']);
									$podcast_score = $GLOBALS['db']->query("update ".DB_PREFIX."user set score=score+".intval($root['empirivalue'])." where id = ".$value['podcast_id']);
									if($user_score && $podcast_score){
										//更新经验
										$user_redis->inc_score($value['viewer_id'],intval($root['empirivalue']));
										$user_redis->inc_score($value['podcast_id'],intval($root['empirivalue']));
									}

//									$sql = "update ".DB_PREFIX."goods set inventory=inventory-".$value['number']." where id = ".$value['goods_id'];
//									$GLOBALS['db']->query($sql);//商品库存减少

									#构造高级接口所需参数
									$tim_data = array();
									$tim_data['ext'] = $ext;
									$tim_data['podcast_id'] = strim($value['viewer_id']);
									$tim_data['group_id'] = strim($video_info['group_id']);
									$tim_data['score'] = intval($root['empirivalue']);
									get_tim_api($tim_data);

								}
							}
						}
					}


				}else{
					$user_id = intval($payment_notice['user_id']);
					if(intval($payment_notice['type'])==1){
						$day_num = intval($payment_notice['diamonds']);
						if($day_num>0){
							$vip_time = $day_num * 24 * 3600;//购买的会员时长
							$sql = "select id,is_vip,vip_expire_time from ".DB_PREFIX."user where id = ".$user_id;
							$user = $GLOBALS['db']->getRow($sql,true,true);
							if(intval($user['is_vip'])==1){
								$vip_expire_time = intval($user['vip_expire_time']) + $vip_time; //已经是会员，会员到期时间 = 当前会员到期时间 + 购买的会员时长
							}else{
								$vip_expire_time = NOW_TIME + $vip_time;//非会员 会员到期时间 = 当前时间 + 购买的会员时长
							}
							$sql = "update ".DB_PREFIX."user set is_vip = 1,vip_expire_time = ".$vip_expire_time." where id = ".$user_id;
							$GLOBALS['db']->query($sql);
						}
					}elseif(intval($payment_notice['type'])==11){
							// 'weixin'=>'购买微信号',
							//'reward'=>'打赏',
                           // 'chat'=>'聊天付费',
//							$weibo_id = $payment_notice['order_id'];
//							$weibo = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."weibo where id = ".$weibo_id);
							if($payment_notice['type_cate']=='weixin'||$payment_notice['type_cate']=='reward'||$payment_notice['type_cate']=='chat'){
								$money = $payment_notice['money'];

								$GLOBALS['db']->query("UPDATE ".DB_PREFIX."user set weibo_money = weibo_money+$money where id = ".$payment_notice['to_user_id']);
								fanwe_require(APP_ROOT_PATH . 'mapi/lib/redis/BaseRedisService.php');
								fanwe_require(APP_ROOT_PATH.'mapi/xr/redis/WeiboContributionRedisService.php');
								log_result('===cart===');
								log_result('$payment_notice:user_id:'.$payment_notice['user_id']);
								log_result('weibo:user_id:'.$payment_notice['user_id']);
								$weiboCont_redis = new WeiboContributionRedisService();
								$weiboCont_redis->insert_db($payment_notice['user_id'], $payment_notice['to_user_id'], $money);
								//进行分销
								$weiboCont_redis->distribution_calculate($payment_notice['user_id'], $money,$payment_notice['recharge_name']);
							}
					}
					else{
						$diamonds = intval($payment_notice['diamonds']);
						if ($diamonds == 0){
							$payment_info = $GLOBALS['db']->getOne("select id from ".DB_PREFIX."payment where class_name='Iappay'");
							if(intval($payment_info['id'])==intval($payment_notice['payment_id'])){
								$sql = "select (iap_diamonds+gift_diamonds) as num from ".DB_PREFIX."recharge_rule where id = ".$payment_notice['recharge_id'];
							}else{
								$sql = "select (diamonds+gift_diamonds) as num from ".DB_PREFIX."recharge_rule where id = ".$payment_notice['recharge_id'];
							}
							$diamonds = $GLOBALS['db']->getOne($sql,true,true);
						}
						if ($diamonds > 0){
							$sql = "update ".DB_PREFIX."user set diamonds = diamonds + ".$diamonds." where id = ".$user_id;
							$GLOBALS['db']->query($sql);

						}
						//赠送游戏币
						if (OPEN_GAME_MODULE==1) {
							$sql = "select gift_coins from ".DB_PREFIX."recharge_rule where id = ".$payment_notice['recharge_id'];
							$coins = $GLOBALS['db']->getOne($sql,true,true);

							if ($coins > 0){
								$sql = "update ".DB_PREFIX."user set coins = coins + ".$coins." where id = ".$user_id;
								$GLOBALS['db']->query($sql);

							}
						}
					}
				}

				$GLOBALS['db']->Commit($pInTrans);
				$pInTrans = false;

				if (!function_exists("fanwe_require")) {
					require APP_ROOT_PATH."system/common.php";
				}

				user_deal_to_reids(array($user_id));

				fanwe_require(APP_ROOT_PATH.'mapi/lib/redis/BaseRedisService.php');
				fanwe_require(APP_ROOT_PATH.'mapi/lib/redis/UserRedisService.php');

				$user_redis = new UserRedisService();
				$total_diamonds = $user_redis->getOne_db($user_id, 'diamonds');

			}else{
				$data= array();
				$data['error'] = '更新订单失败';
				$data['payment_info'] = $payment_notice;
				log_err_file(array(__FILE__,__LINE__,__METHOD__,$data));
				$GLOBALS['db']->Rollback($pInTrans);
			}

			//get_mortgate();
			return array("error"=>"恭喜您,成功","status"=>1, "total_diamonds"=>$total_diamonds,'url'=>$url);  //已充值

		}catch(Exception $e){
			//异常回滚
			$GLOBALS['db']->Rollback($pInTrans);
			log_err_file(array(__FILE__,__LINE__,__METHOD__,$e->getMessage()));
			file_put_contents(APP_ROOT_PATH."public/alipaylog/exc_msg.txt",$e->getMessage());
		}
	}
	else
	{
		return array("error"=>"无效的支付单号(".$notice_sn.")，请联系管理员","status"=>0);
	}
}



?>