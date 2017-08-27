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
			),
	),
	"dealcate"	=>	array(
			"name"	=>	"视频管理",
			"key"	=>	"dealcate",
			"groups"	=>	array(
					"videocate"	=>	array(
							"name"	=>	"话题管理",
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
 									array("name"=>"直播结束视频","module"=>"VideoEnd","action"=>"endline_index"),
 									array("name"=>"回播列表","module"=>"VideoPlayback","action"=>"playback_index"),
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
					//array("name"=>"帮助列表","module"=>"Help","action"=>"index"),
                    array("name"=>"兑换规则","module"=>"ExchangeRule","action"=>"index"),
                    array("name"=>"购买规则","module"=>"RechargeRule","action"=>"index"),
                    //array("name"=>"IOS购买规则","module"=>"IosRechargeRule","action"=>"index"),
 				),
			),
		 	"ads"	=>	array(
				"name"	=>	"广告配置",
				"key"	=>	"ads",
				"nodes"	=>	array(
					array("name"=>"广告列表","module"=>"Ad","action"=>"index"),
					array("name"=>"广告区域","module"=>"AdPlace","action"=>"index"),
				),
					),
		 	"mobile"	=>	array(
						"name"	=>	"移动平台设置",
						"key"	=>	"mobile",
						"nodes"	=>	array(
							array("name"=>"手机端配置","module"=>"Conf","action"=>"mobile"),
							array("name"=>"脏字库配置","module"=>"Conf","action"=>"dirty_words"),
                            array("name"=>"昵称限制配置","module"=>"LimitName","action"=>"index"),
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
		),
	),
);
?>