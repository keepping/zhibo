<?php
return array(
	"index"	=>	array(
		"name"	=>	"系统首页",
		"key"	=>	"index",
		"groups"	=>	array(
			"index"	=>	array(
				"name"	=>	"系统首页",
				"key"	=>	"index",
				"nodes"	=>	array(
					array("name"=>"快速导航","module"=>"Index","action"=>"main"),
					array("name"=>"网站数据统计","module"=>"Index","action"=>"statistics"),
				),
			),
			"syslog"	=>	array(
				"name"	=>	"系统日志",
				"key"	=>	"syslog",
				"nodes"	=>	array(
					array("name"=>"系统日志列表","module"=>"Log","action"=>"index"),
				),
			),

		),
	),
 	"user"	=>	array(
			"name"	=>	"主播管理",
			"key"	=>	"user",
			"groups"	=>	array(
				"user"	=>	array(
					"name"	=>	"主播管理",
					"key"	=>	"user",
					"nodes"	=>	array(
						array("name"=>"主播列表","module"=>"UserGeneral","action"=>"index"),
//						array("name"=>"认证主播","module"=>"User","action"=>"index"),
                        array("name"=>"机器人头像","module"=>"UserRobot","action"=>"index"),
						//array("name"=>"企业主播","module"=>"UserBusiness","action"=>"index"),
					),
				),
				"useraudit"	=>	array(
					"name"	=>	"无效主播",
					"key"	=>	"useraudit",
					"nodes"	=>	array(
						array("name"=>"无效主播","module"=>"UserAudit","action"=>"index"),
					),
				),
				"usercert"	=>	array(
					"name"	=>	"认证管理",
					"key"	=>	"usercert",
					"nodes"	=>	array(
						array("name"=>"主播待审认证","module"=>"UserInvestor","action"=>"index"),
						//array("name"=>"企业待审认证","module"=>"UserBusinessInvestor","action"=>"index"),
						array("name"=>"认证未通过","module"=>"UserInvestorList","action"=>"index"),
                        array("name"=>"认证名称列表","module"=>"AuthentList","action"=>"index"),
					),
				),
				"userlevel"	=>	array(
					"name"	=>	"等级管理",
					"key"	=>	"userlevel",
					"nodes"	=>	array(
						array("name"=>"等级列表","module"=>"UserLevel","action"=>"index"),
					),
				),
				"family"	=>	array(
					"name"	=>	"家族管理",
					"key"	=>	"family",
					"nodes"	=>	array(
						array("name"=>"家族列表","module"=>"Family","action"=>"index"),
						array("name"=>"家族等级列表","module"=>"FamilyLevel","action"=>"index"),
					),
				),
                "society"	=>	array(
                    "name"	=>	"公会管理",
                    "key"	=>	"society",
                    "nodes"	=>	array(
                        array("name"=>"公会列表","module"=>"Society","action"=>"index"),
                        array("name"=>"公会收入列表","module"=>"SocietyIncome","action"=>"index"),
                    ),
                ),
				"distribution"	=>	array(
					"name"	=>	"分销管理",
					"key"	=>	"distribution",
					"nodes"	=>	array(
						array("name"=>"分销列表","module"=>"Distribution","action"=>"index"),
					),
				),

				/*"uservip"	=>	array(
					"name"	=>	"VIP会员管理",
					"key"	=>	"uservip",
					"nodes"	=>	array(
						array("name"=>"VIP会员列表","module"=>"UserVip","action"=>"index"),
						array("name"=>"VIP等级列表","module"=>"UserLevel","action"=>"index"),
						array("name"=>"VIP升级记录","module"=>"UserLevelUp","action"=>"index"),
						array("name"=>"VIP降级记录","module"=>"UserLevelDown","action"=>"index"),
						array("name"=>"VIP购买记录","module"=>"UserBuy","action"=>"index"),
						array("name"=>"客服列表","module"=>"UserKefu","action"=>"index"),
						array("name"=>"客服回收站","module"=>"UserKefuDelete","action"=>"trash"),
						array("name"=>"积分兑换","module"=>"UserScore","action"=>"index"),
					),
				),*/
				/*"userquickmessage"	=>	array(
					"name"	=>	"信息快速查询",
					"key"	=>	"userquickmessage",
					"nodes"	=>	array(
						array("name"=>"普通会员信息","module"=>"UserGeneralQick","action"=>"index"),
						array("name"=>"个人会员信息","module"=>"UserQick","action"=>"index"),
						array("name"=>"企业会员信息","module"=>"UserBusinessQick","action"=>"index"),
						array("name"=>"公司列表","module"=>"UserCompanyQick","action"=>"index"),
						array("name"=>"工作信息","module"=>"UserWorkQick","action"=>"index"),
						array("name"=>"银行卡列表","module"=>"UserBankQick","action"=>"index"),

					),
				),*/

                /*"opinion"	=>	array(
                    "name"	=>	"意见反馈",
                    "key"	=>	"opinion",
                    "nodes"	=>	array(
                            array("name"=>"意见反馈列表","module"=>"Opinion","action"=>"index"),
                    ),
				),*/

					/*"referrals"	=>	array(
							"name"	=>	"会员邀请",
							"key"	=>	"referrals",
							"nodes"	=>	array(
									array("name"=>"邀请返利列表","module"=>"Referrals","action"=>"index"),
									array("name"=>"邀请统计列表","module"=>"ReferralsTotal","action"=>"referrals_count"),
  							),
					),*/
					/*"message"	=>	array(
							"name"	=>	"留言列表",
							"key"	=>	"message",
							"nodes"	=>	array(
									array("name"=>"留言分类列表","module"=>"MessageCate","action"=>"index"),
									array("name"=>"留言列表","module"=>"Message","action"=>"index"),
							),
					),*/
 					/*"integrate"	=>	array(
							"name"	=>	"会员插件管理",
							"key"	=>	"notice",
							"nodes"	=>	array(
									array("name"=>"会员整合插件","module"=>"Integrate","action"=>"index"),
									array("name"=>"同步登陆插件","module"=>"ApiLogin","action"=>"index"),
							),
					),*/
			),
	),
	
	"dealcate"	=>	array(
			"name"	=>	"视频管理",
			"key"	=>	"dealcate",
			"groups"	=>	array(
					"videocate"	=>	array(
							"name"	=>	"分类管理",
							"key"	=>	"dealcate",
							"nodes"	=>	array(
								array("name"=>"话题列表","module"=>"VideoCate","action"=>"index"),
  							),
					),
 					"video"	=>	array(
							"name"	=>	"视频管理",
							"key"	=>	"dealorder",
							"nodes"	=>	array(
									array("name"=>"直播中视频","module"=>"Video","action"=>"online_index"),
                                    array("name"=>"监控","module"=>"VideoMonitor","action"=>"monitor"),
                                    array("name"=>"警告内容列表","module"=>"WarningMsg","action"=>"index"),
 									array("name"=>"直播结束视频","module"=>"VideoEnd","action"=>"endline_index"),
 									array("name"=>"回播列表","module"=>"VideoPlayback","action"=>"playback_index"),
									array("name"=>"审核视频列表","module"=>"VideoCheck","action"=>"playback_index"),
                                    array("name"=>"推送消息列表","module"=>"PushAnchor","action"=>"index")
							),
					),


			),
	),
	"score_mall"	=>	array(
		"name"	=>	"道具管理",
		"key"	=>	"score_mall",
		"groups"	=>	array(
			"score_mall"	=>	array(
				"name"	=>	"道具管理",
				"key"	=>	"score_mall",
				"nodes"	=>	array(
					array("name"=>"道具列表","module"=>"Prop","action"=>"index"),
				),
			),

		),
	),
	"payment"	=>	array(
			"name"	=>	"资金管理",
			"key"	=>	"payment",
			"groups"	=>	array(
					"payment"	=>	array(
							"name"	=>	"支付接口",
							"key"	=>	"payment",
							"nodes"	=>	array(
									array("name"=>"支付接口列表","module"=>"Payment","action"=>"index"),
   							),
					),
					"recharge"	=>	array(
						"name"	=>	"充值管理",
						"key"	=>	"recharge",
						"nodes"	=>	array(
							array("name"=>"在线充值","module"=>"RechargeNotice","action"=>"index"),
						),
					),
				"cash"	=>	array(
					"name"	=>	"提现管理",
					"key"	=>	"cash",
					"nodes"	=>	array(
						array("name"=>"提现列表","module"=>"UserRefundList","action"=>"index"),
						array("name"=>"提现待审核记录","module"=>"UserRefund","action"=>"index"),
						array("name"=>"提现待确认记录","module"=>"UserConfirmRefund","action"=>"index"),
					),
				),
				/*"accountchange"	=>	array(
					"name"	=>	"人工账户变更",
					"key"	=>	"accountchange",
					"nodes"	=>	array(
						array("name"=>"充值","module"=>"AdminChageRecharge","action"=>"index"),
						array("name"=>"扣款","module"=>"AdminChageDeduct","action"=>"index"),
						//array("name"=>"冻结资金","module"=>"AdminChageFreeze","action"=>"index"),
						array("name"=>"变更信用","module"=>"AdminChageCredit","action"=>"index"),
						array("name"=>"变更积分","module"=>"AdminChageScore","action"=>"index"),
					),
				),*/

				/*"paymentnotice"	=>	array(
							"name"	=>	"资金日志",
							"key"	=>	"paymentnotice",
							"nodes"	=>	array(
									array("name"=>"会员资金日志","module"=>"UserPaymentNotice","action"=>"index"),
									array("name"=>"付款记录","module"=>"PaymentNotice","action"=>"index"),
 							),
					),*/
			),
	),
	
    "tipoff"	=>	array(
        "name"	=>	"举报管理",
        "key"	=>	"tipoff",
        "groups"	=>	array(
            "payment"	=>	array(
                "name"	=>	"举报管理",
                "key"	=>	"tipoff",
                "nodes"	=>	array(
                    array("name"=>"举报类型列表","module"=>"TipoffType","action"=>"index"),
                    array("name"=>"举报列表","module"=>"Tipoff","action"=>"index"),
                ),
            ),
        )
    ),
	"nav"	=>	array(
			"name"	=>	"文章管理",
			"key"	=>	"nav",
			"groups"	=>	array(
					/*"nav"	=>	array(
							"name"	=>	"前端设置",
							"key"	=>	"nav",
							"nodes"	=>	array(
									array("name"=>"导航菜单列表","module"=>"Nav","action"=>"index"),
									//array("name"=>"广告位列表","module"=>"Adv","action"=>"index"),
    							),
					),*/
				/*"mobile"	=>	array(
						"name"	=>	"移动平台设置",
						"key"	=>	"mobile",
						"nodes"	=>	array(
							array("name"=>"手机端配置","module"=>"Conf","action"=>"mobile"),
							//array("name"=>"手机端广告列表","module"=>"MAdv","action"=>"index"),
						),
					),*/
				"articlecate"	=>	array(
							"name"	=>	"关于我们",
							"key"	=>	"articlecate",
							"nodes"	=>	array(
									array("name"=>"分类管理列表","module"=>"ArticleCate","action"=>"index"),
									array("name"=>"分类管理回收站","module"=>"ArticleCateTrash","action"=>"trash"),
									array("name"=>"文章管理列表","module"=>"Article","action"=>"index"),
									array("name"=>"文章管理回收站","module"=>"ArticleTrash","action"=>"trash"),
 							),
					),
				"help"	=>	array(
							"name"	=>	"帮助与反馈",
							"key"	=>	"help",
							"nodes"	=>	array(
									array("name"=>"常见问题","module"=>"Faq","action"=>"index"),
 							),
					),

			),
	),
	"msgtemplate"	=>	array(
			"name"	=>	"短信管理",
			"key"	=>	"msgtemplate",
			"groups"	=>	array(
					/*"msgtemplate"	=>	array(
							"name"	=>	"消息模板",
							"key"	=>	"payment",
							"nodes"	=>	array(
									array("name"=>"消息模板","module"=>"MsgTemplate","action"=>"index"),
    							),
					),*/

 					/*"mailserver"	=>	array(
							"name"	=>	"邮件管理",
							"key"	=>	"mailserver",
							"nodes"	=>	array(
									array("name"=>"邮件服务器列表 ","module"=>"MailServer","action"=>"index","action_id"=>"57"),
									array("name"=>"邮件列表","module"=>"PromoteMsgMail","action"=>"mail_index","action_id"=>"667"),
 							),
					),*/
					"sms"	=>	array(
							"name"	=>	"短信管理",
							"key"	=>	"sms",
							"nodes"	=>	array(
									array("name"=>"短信接口列表","module"=>"Sms","action"=>"index","action_id"=>"58"),
									//array("name"=>"短信列表","module"=>"PromoteMsgSms","action"=>"sms_index","action_id"=>"668"),
 							),
					),
					"stationmessage"	=>	array(
							"name"	=>	"系统消息管理",
							"key"	=>	"StationMessage",
							"nodes"	=>	array(
 									array("name"=>"系统消息列表","module"=>"StationMessage","action"=>"index"),//LS
 							),
					),
					"dealmsgList"	=>	array(
							"name"	=>	"队列管理",
							"key"	=>	"dealmsgList",
							"nodes"	=>	array(
									array("name"=>"业务队列列表","module"=>"DealMsgList","action"=>"index"),
									//array("name"=>"推广队列列表","module"=>"PromoteMsgList","action"=>"index"),
									//array("name"=>"站内消息队列列表","module"=>"StationMessageMsgList","action"=>"msg_list","action_id"=>"6944"),//LS
 							),
					),


			),
	),
	"PlugIn" => array(
		"name"	=>	"插件中心",
		"key"	=>	"PlugIn",
		"groups"	=>	array(
			"PlugInconf"	=>	array(
				"name"	=>	"插件管理",
				"key"	=>	"PlugInconf",
				"nodes"	=>	array(
					array("name"=>"插件配置","module"=>"PlugIn","action"=>"index"),
				),
			),
			
			"goodsconf"	=>	array(
				"name"	=>	"商品设置",
				"key"	=>	"goodsconf",
				"nodes"	=>	array(
					array("name"=>"添加商品","module"=>"Goods","action"=>"add"),
					array("name"=>"商品管理","module"=>"Goods","action"=>"index"),
					array("name"=>"分类列表","module"=>"GoodsCate","action"=>"index"),
					array("name"=>"商品标签","module"=>"GoodsTags","action"=>"index"),
				),
			),
			"user_goodsconf"	=>	array(
				"name"	=>	"主播商品管理",
				"key"	=>	"user_goodsconf",
				"nodes"	=>	array(
					array("name"=>"主播平台商品","module"=>"User_Goods","action"=>"index"),
					array("name"=>"主播小店商品","module"=>"PodcastGoods","action"=>"index"),
					array("name"=>"购物订单列表","module"=>"PodcastOrder","action"=>"index"),
				),
			),
			
			"pai_goods"	=>	array(
				"name"	=>	"竞拍商品",
				"key"	=>	"pai_goods",
				"nodes"	=>	array(
					array("name"=>"商品列表","module"=>"PaiGoods","action"=>"index"),
				),
			),
			"goods_order"	=>	array(
				"name"	=>	"竞拍订单",
				"key"	=>	"goods_order",
				"nodes"	=>	array(
					array("name"=>"虚拟竞拍分类","module"=>"PaiTags","action"=>"index"),
					array("name"=>"竞拍订单列表","module"=>"GoodsOrder","action"=>"index"),
					array("name"=>"用户地址","module"=>"UserAddr","action"=>"index"),
					array("name"=>"消息列表","module"=>"UserNotice","action"=>"index"),
					array("name"=>"竞拍列表","module"=>"PaiJoin","action"=>"index"),
					array("name"=>"保证金记录","module"=>"UserDiamondsLog","action"=>"index"),
				),
			),
			"goods_complaint"	=>	array(
				"name"	=>	"订单申诉状态",
				"key"	=>	"goods_complaint",
				"nodes"	=>	array(
					array("name"=>"申诉订单","module"=>"Refund","action"=>"index"),
				),
			),
			"gameconf"	=>	array(
				"name"	=>	"游戏设置",
				"key"	=>	"gameconf",
				"nodes"	=>	array(
                    array("name"=>"游戏配置","module"=>"Games","action"=>"index"),
                    array("name"=>"游戏记录","module"=>"GameLog","action"=>"index"),
                    array("name"=>"游戏历史记录","module"=>"GameLogHistory","action"=>"index"),
                    array("name"=>"游戏金币记录","module"=>"Games","action"=>"betLog"),
                    array("name"=>"游戏上庄记录","module"=>"Games","action"=>"bankerLog"),
				),
			),
		
		)
	),
	"system"	=>	array(
		"name"	=>	"系统设置",
		"key"	=>	"system",
		"groups"	=>	array(
			"sysconf"	=>	array(
				"name"	=>	"系统设置",
				"key"	=>	"sysconf",
				"nodes"	=>	array(
					array("name"=>"系统配置","module"=>"Conf","action"=>"index"),
					array("name"=>"广告设置","module"=>"IndexImage","action"=>"index"),
                    array("name"=>"兑换规则","module"=>"ExchangeRule","action"=>"index"),
                    array("name"=>"购买规则","module"=>"RechargeRule","action"=>"index"),
                    array("name"=>"VIP购买规则","module"=>"VipRule","action"=>"index"),
 				),
			),
		 	"ads"	=>	array(
				"name"	=>	"广告配置",
				"key"	=>	"ads",
				"nodes"	=>	array(
					array("name"=>"广告列表","module"=>"Ad","action"=>"index"),
//					array("name"=>"广告区域","module"=>"AdPlace","action"=>"index"),
				),
					),
		 	"mobile"	=>	array(
						"name"	=>	"移动平台设置",
						"key"	=>	"mobile",
						"nodes"	=>	array(
							array("name"=>"手机端配置","module"=>"Conf","action"=>"mobile"),
							array("name"=>"脏字库配置","module"=>"Conf","action"=>"dirty_words"),
                            array("name"=>"昵称限制配置","module"=>"LimitName","action"=>"index"),
							array("name"=>"加密KEY配置","module"=>"KeyList","action"=>"index"),
							//array("name"=>"手机端广告列表","module"=>"MAdv","action"=>"index"),
						),
					),
			"admin"	=>	array(
				"name"	=>	"系统管理员",
				"key"	=>	"admin",
				"nodes"	=>	array(
					array("name"=>"管理员分组列表","module"=>"Role","action"=>"index","action_id"=>"11"),
					array("name"=>"管理员分组回收站","module"=>"RoleTrash","action"=>"trash","action_id"=>"13"),
					array("name"=>"管理员列表","module"=>"Admin","action"=>"index","action_id"=>"14"),
					array("name"=>"管理员回收站","module"=>"AdminTrash","action"=>"trash","action_id"=>"15"),
				),
			),
			"slbgroupconf"	=>	array(
				"name"	=>	"集群组配置",
				"key"	=>	"slbgroupconf",
				"nodes"	=>	array(
					array("name"=>"集群组列表","module"=>"SlbGroup","action"=>"index",),
				),
			),
			"lucknum"	=>	array(
				"name"	=>	"靓号管理",
				"key"	=>	"lucknum",
				"nodes"	=>	array(
					array("name"=>"靓号管理","module"=>"LuckNum","action"=>"index",),
				),
			),
		),
	),
);
?>