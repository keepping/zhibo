<?php
class wx_bindModule
{
    var $signPackage = '';
    var $user_info = '';
    var $wx_url = '';
    var $video_id = '';
    var $user_id = '';


    //提现首页
    public function index()
    {

        $call_back = SITE_DOMAIN.'/mapi/index.php?ctl=wx_bind';
        $from = strim($_REQUEST['from']);
        $this->check_user_info($call_back);
        //流程 wx_url 有值跳转,到微信绑定
        // user_info 有值已绑定
        //user_info 无值 ，没有账号，“用户未登陆方维主播，请先登录注册再绑定”
        /*if($this->user_info){
         $data['user_info'] = $this->user_info;
         if($from!='app'){
             $show_url =  SITE_DOMAIN.'/index.php?c=money_carry_wx.php';
             app_redirect($show_url);
         }
     }else{
         $data['user_info'] = false;
     }
     $data['wx_url'] = $this->wx_url;

     $data['app_down'] =  SITE_DOMAIN.'/mapi/index.php?ctl=app_download';

     header("Content-Type:text/html; charset=utf-8");
     echo(json_encode($data));
     exit;*/
    }

    //检查用户是否登陆
    public  function check_user_info($back_url){
        if($_REQUEST['ttype']==1){
            return true;
        }
        $is_weixin=isWeixin();

        if(!$is_weixin){
            return false;
        }
        fanwe_require(APP_ROOT_PATH."system/utils/weixin.php");

        $m_config =  load_auto_cache("m_config");//初始化手机端配置

        if($m_config['wx_gz_appid']==''||$m_config['wx_gz_secrit']==''){
            print_r("公众号未配置");exit;
        }else{
            $wx_appid = strim($m_config['wx_gz_appid']);
            $wx_secrit = strim($m_config['wx_gz_secrit']);
        }
        $wx_status = (($wx_appid&&$wx_secrit))?1:0;
        if($_REQUEST['code']&&$_REQUEST['state']==1&&$wx_status){
            $weixin=new weixin($wx_appid,$wx_secrit,$back_url);
            $wx_info=$weixin->scope_get_userinfo($_REQUEST['code']);
            $url1 = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".$wx_appid."&secret=".$wx_secrit;
            $access_token_info=$weixin->https_request($url1);
            $access_token_info_str=json_decode($access_token_info['body'],1);
            $url2="https://api.weixin.qq.com/cgi-bin/user/info?access_token=".$access_token_info_str['access_token']."&openid=".$wx_info['openid']."&lang=zh_CN";
            $unionid_info=$weixin->https_request($url2);
            $unionid_info_str=json_decode($unionid_info['body'],1);
            $wx_info['subscribe']=$unionid_info_str['subscribe'];
            $wx_info['openid']=$unionid_info_str['openid'];
            $wx_info['unionid']=$unionid_info_str['unionid'];
            /*if($wx_info['errcode']>0){
              var_dump($wx_info);exit;
          }*/
        }else{
            if($is_weixin&&$wx_status){
                $weixin_2=new weixin($wx_appid,$wx_secrit,$back_url);
                $wx_url=$weixin_2->scope_get_code();
                app_redirect($wx_url);
            }else{
                $weixin_2=new weixin($wx_appid,$wx_secrit,$back_url);
                $wx_url=$weixin_2->scope_get_code();
                app_redirect($wx_url);
            }
        }
        if($wx_info['openid']!=''){
            if($wx_info['unionid']==''){
                echo  '<!DOCTYPE html>
	<html>
	    <head>
	        <meta charset="utf-8">
	        <meta http-equiv="X-UA-Compatible" content="IE=edge">
	        <title></title>
         	<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=2.0, user-scalable=0,minimum-scale=0.5">
	        <link rel="shortcut icon" href="/favicon.ico">
	        <meta name="apple-mobile-web-app-capable" content="yes">
	        <meta name="apple-mobile-web-app-status-bar-style" content="black">
	    </head>
	    <body>
			<div style="padding:15px;margin-top:20px;font-size:16px;text-align:center;">公众号未绑定开放平台</div>
		</body>
	</html>
';exit;
            }
            $has_user = $GLOBALS['db']->getAll("select id,head_image,nick_name from ".DB_PREFIX."user where wx_unionid = '".$wx_info['unionid']."' ");
            $total = count($has_user);
            if(intval($total)==0){
                $this->wx_url = '';
                $this->user_info = false;
                $app_down =  SITE_DOMAIN.'/mapi/index.php?ctl=app_download';
                echo  '<!DOCTYPE html>
	<html>
	    <head>
	        <meta charset="utf-8">
	        <meta http-equiv="X-UA-Compatible" content="IE=edge">
	        <title></title>
         	<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=2.0, user-scalable=0,minimum-scale=0.5">
	        <link rel="shortcut icon" href="/favicon.ico">
	        <meta name="apple-mobile-web-app-capable" content="yes">
	        <meta name="apple-mobile-web-app-status-bar-style" content="black">
	    </head>
	    <body>
			<div style="padding:15px;margin-top:20px;font-size:16px;text-align:center;">您还未登陆'.$m_config['short_name'].'，请先登录注册再绑定</div>
		 	<div style="padding:16px;">
	  			<a href="'.$app_down.'" style="display:block;width:100%;height:40px;line-height:40px;font-size:18px;color:#fff;background:#8ee2d3;border-radius:20px;text-align:center;text-decoration: none;">点击立即下载</a>
			</div>
		</body>
	</html>
';exit;

            }else{
                $data_info = array();
                if($has_user['gz_openid']!=$wx_info['openid']){
                    $data_info['gz_openid'] = $wx_info['openid'];
                }
                if($has_user['subscribe']!=$wx_info['subscribe']){
                    $data_info['subscribe'] = $wx_info['subscribe'];
                }

                $user = array();
                $html = '';
                foreach($has_user as $k=>$v){
                    $arr[$k] =$v['id'];
                    $user[$k]['head_image'] = get_spec_image($v['head_image']);
                    $user[$k]['nick_name'] = $v['nick_name'];
                    $user[$k]['id'] = $v['id'];

                    fanwe_require(APP_ROOT_PATH.'mapi/lib/redis/BaseRedisService.php');
                    fanwe_require(APP_ROOT_PATH.'mapi/lib/redis/UserRedisService.php');
                    $user_redis = new UserRedisService();
                    $fields = array("ticket","refund_ticket");
                    $user_ticket_info =   $user_redis->getRow_db($v['id'],$fields);
                    $ticket_catty_ratio = $GLOBALS['db']->getOne("select alone_ticket_ratio from ".DB_PREFIX."user where id=".$v['id']);
                    if(!$ticket_catty_ratio){
                        $ticket_catty_ratio = $m_config['ticket_catty_ratio'];
                    }
                    $ticket = intval($user_ticket_info['ticket'])-intval($user_ticket_info['refund_ticket']);
                    $money = number_format($ticket*$ticket_catty_ratio,2);
                    $user[$k]['ticket'] =$ticket;
                    $user[$k]['use_ticket'] =$money/$ticket_catty_ratio;
                    $user[$k]['money'] = $money;

                    $ready_refund_id =intval($GLOBALS['db']->getOne("select id from ".DB_PREFIX."user_refund where user_id = ".intval($v['id'])." and (is_pay =0 or is_pay=1)"));

                    $submit = SITE_DOMAIN.'/mapi/index.php?ctl=user_center&act=submitrefundwx&&id='.$k;
                    $html .='<div class="list-block media-list" style="margin:10px 0;font-size:14px;">
	    <ul style="background: #fff;list-style: none;padding: 0;margin: 0;position: relative;font-size:14px;"><li v-for="user_info in user_info" style="border-bottom: 1px solid #e5e5e5;box-sizing: border-box; position: relative;">
	      		<a href="#" class="item-content" style="box-sizing:border-box;padding-left:15px;min-height:44px;display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-pack:justify;-webkit-justify-content:space-between;justify-content:space-between;-webkit-box-align:center;-webkit-align-items:center;align-items:center;text-decoration:none;color:#333;">
	          		<div class="item-media" style=" display:-webkit-box;display:-webkit-flex;display:flex;-webkit-flex-shrink:0;-ms-flex:0 0 auto;-webkit-flex-shrink:0;flex-shrink:0;-webkit-box-lines:single;-moz-box-lines:single;-webkit-flex-wrap:nowrap;flex-wrap:nowrap;box-sizing:border-box;-webkit-box-align:center;-webkit-align-items:center;align-items:center;padding-top:9px;padding-bottom:10px;">
	          			<img src="'.$user[$k]['head_image'].'" style="width:50px;height:50px;border-radius:50%;" />
	          		</div>
 		 			<div class="item-inner" style="padding-right:15px;position:relative;width:100%;padding-top:10px;;padding-bottom:9px;;min-height:55px;overflow:hidden;box-sizing:border-box;display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-flex:1;-ms-flex:1;-webkit-box-pack:justify;-webkit-justify-content:space-between;justify-content:space-between;-webkit-box-align:center;-webkit-align-items:center;align-items:center;margin-left:15px;display:block;-webkit-align-self:stretch;align-self:stretch;">
		 				 	<div class="item-title-row" style="display:-webkit-box;display:-webkit-flex;display:flex;-webkit-box-pack:justify;-webkit-justify-content:space-between;justify-content:space-between;">
			              	<div class="item-title" style="-webkit-flex-shrink:1;-ms-flex:0 1 auto;-webkit-flex-shrink:1;flex-shrink:1;white-space:nowrap;position:relative;overflow:hidden;text-overflow:ellipsis;max-width:100%;font-weight:500;font-size:16px;">'.$v['nick_name'].'</div>
			            </div>
			            <div class="item-subtitle" style="height:initial;font-size:12px;position:relative;overflow:hidden;white-space:nowrap;max-width:100%;text-overflow:ellipsis;color:#333;">'.$v['id'].'</div>
    					<div class="item-text" style="height:initial;font-size:12px;color:#333;line-height:21px;;position:relative;overflow:hidden;height:42px;text-overflow:ellipsis;-webkit-line-clamp:2;-webkit-box-orient:vertical;display:-webkit-box;">可领取'.$user[$k]['money'].'元</div>';
                    if($ready_refund_id){
                        $html.='<div class="item-text" style="color:red;">提现审核中</div>';
                    }
                    $html.='</div>
	          	</a>
	      	</li></ul>
  	</div>
 	<div class="content-block" style="margin:35px 0;padding:0 15px;color:#6d6d72;">
   	 	<div class="row" style="overflow:hidden;margin-left:-4%;">
      		<div class="col-100" style="width:96%;margin-left:4%;box-sizing:border-box;float:left;">
      			<a href="'.$submit.'" class="button button-big button-fill button-round button-theme" style="border:1px solid #ff7552;color:#fff;text-decoration:none;text-align:center;display:block;height:44px;line-height:44px;;box-sizing:border-box;-webkit-appearance:none;-moz-appearance:none;-ms-appearance:none;appearance:none;background:0 0;padding:0 10px;margin:0;white-space:nowrap;position:relative;text-overflow:ellipsis;font-size:14px;font-family:inherit;cursor:pointer;-moz-border-radius:25px;-khtml-border-radius:25px;-webkit-border-radius:25px;border-radius:25px;background-color:#ff7552;border:none;font-size:17px;opacity:.9;">确定</a>
      		</div>
	 	</div>
  	</div>';
                    $submit = '';
                }
                $arr_id =implode(',',$arr);
                $GLOBALS['db']->autoExecute(DB_PREFIX."user",$data_info,'UPDATE'," id in (".$arr_id.")");
                $user_info = $has_user;
                es_session::set("user_info_wx", $user);
            }
            $user_info['authorizer_access_token'] = $wx_info['authorizer_access_token'];
            $user_info['authorizer_appid'] = $wx_info['authorizer_appid'];

            echo '<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>微信提现</title>
     <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=2.0, user-scalable=0,minimum-scale=0.5">
    <link rel="shortcut icon" href="/favicon.ico">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black">
</head>
<body style="background:#f0f7f6;">
	<div class="content-block-title" style="overflow:hidden;white-space: nowrap;text-overflow: ellipsis;font-size: 14px;text-transform: uppercase;line-height: 1;color: #6d6d72;margin: 35px 15px 10px;">请选择要领取红包的帐号</div>
	      	'.$html.'
</body>
</html>';
        }else{
            $weixin_2=new weixin($wx_appid,$wx_secrit,$back_url,'snsapi_base');
            $wx_url=$weixin_2->scope_get_code();
            app_redirect($wx_url);
        }
    }

    //充值页面
    public function recharge(){
        $refresh = SITE_DOMAIN.'/mapi/index.php?ctl=wx_bind&act=recharge';
        $tips = '<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>微信充值</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=2.0, user-scalable=0,minimum-scale=0.5">
    <meta http-equiv="refresh" content="1; url='.$refresh.'"><link rel="shortcut icon" href="/favicon.ico">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black">
</head>
<body style="background:#f0f7f6;">
xxxx
</body>
</html>';
        $sql = "select id,name,class_name,logo from ".DB_PREFIX."payment where is_effect = 1 and class_name='Wwxjspay'";
        $pay = $GLOBALS['db']->getRow($sql,true,true);
        if(intval($pay['id'])==0){
            $error = "未开启微信充值.";
            echo str_replace('xxxx',$error,$tips);return;
        }
        array_unique($_REQUEST);
        $m_config =  load_auto_cache("m_config");//初始化手机端配置
        $user_id =  intval(strim($_REQUEST['user_id']));
        $is_pay = $_REQUEST['is_pay'];
        $rule_id = intval($_REQUEST['rule_id']);
        $rule_money = floatval($_REQUEST['rule_money']);
        if($is_pay!=''){
            $data['error_tip'] = '请输入'.$m_config['account_name'];
            $data['error_color'] = 'style="color:red"';
            if(strim($_REQUEST['user_id'])!=''){
                $user_info = $GLOBALS['db']->getOne("select id from ".DB_PREFIX."user where id =".$user_id);
                $data['error_tip'] = $m_config['account_name'].'不存在';
                $data['error_color'] = 'style="color:red"';
                if($user_info){
                    if($is_pay==1){
                        $data['error_tip'] = '';
                        $data['error_color'] = 'style="color:red"';
                        $data['rule_tip'] = "请选择充值金额";
                        if($rule_id){
                            $data['rule_tip'] = '';
                            $sql = "select id,money,name,iap_money,product_id,(diamonds+gift_diamonds) as diamonds from ".DB_PREFIX."recharge_rule where is_effect = 1 and is_delete = 0 and id =".$rule_id;
                            $rule = $GLOBALS['db']->getRow($sql,true,true);
                            $payment_notice['create_time'] = NOW_TIME;
                            $payment_notice['user_id'] = $user_id;
                            $payment_notice['payment_id'] = $pay['id'];
                            $payment_notice['money'] = $rule['money'];
                            $payment_notice['diamonds'] = $rule['diamonds'];//充值时,获得的钻石数量
                            $payment_notice['recharge_id'] = $rule['id'];
                            $payment_notice['recharge_name'] = $rule['name'];
                            $payment_notice['product_id'] = $rule['product_id'];
                            $payment_notice['notice_sn'] = to_date(NOW_TIME,"YmdHis").rand(100,999);
                            $GLOBALS['db']->autoExecute(DB_PREFIX."payment_notice",$payment_notice,"INSERT","","SILENT");
                            $notice_id = $GLOBALS['db']->insert_id();
                            $url = url_mapi("wx_bind#go_pay",array('id'=>$notice_id));
                            app_redirect(get_domain().'/mapi'.$url);
                        }
                    }else{
                        $data['error_tip'] = '验证通过';
                        $data['error_color'] = 'style="color:green"';
                    }
                }
            }
        }
        //print_r($data);exit;
        $rule_list = load_auto_cache("rule_list");
        $html = '<div class="content">
			        <div class="m-top">
				        <div class="m-user">
					        <div class="user-img">
						        <img src="'.get_spec_image($m_config['app_logo']).'"/>
					        </div>
					        <div class="user-name">
						        <p class="name">'.$m_config['short_name'].'直播</p>
					        </div>
					        <div class="clear"></div>
				        </div>
				        <div class="clear"></div>
			        </div>
			        <form id="form_submit" action="'.$refresh.'" method="post">
			        <div class="m-input">
				        <span>'.$m_config['account_name'].'</span>
                        <div class="input-content">
					        <input type="text" name="user_id" value="'.strim($_REQUEST['user_id']).'" placeholder="请输入'.$m_config['account_name'].'" />
                            <button class="button check" style="font-size:16px;">检测</button>
				        </div>
			        </div><div class="m-input" id="error_tip">';
        if($data['error_tip']){
            $html.='<span style="padding-left:60px;text-align:right;">&nbsp;</span><div class="input-content"><span '.$data['error_color'].'>'.$data['error_tip'].'</span></div>';
        }
        $html.='</div><div class="m-input">
				        <span>充值金额</span>
			        </div><div>';
        foreach($rule_list as $k=>$v){
            $html.='<div class="m-money">
                        <div class="money-all" data-id="'.$v['id'].'" data-money="'.$v['money'].'" ';
            if($rule_id==$v['id']){
                $html.=' style="border:1px solid #ff5500;"';
            }
            $html.='>
						    <p class="money">¥'.$v['money'].'</p><p class="title">购买'.$v['diamonds'].'钻石</p>
					</div></div>
				';
        }
        $html.='</div><div class="clear"></div><div class="m-input">
				        <span>应付金额</span>
                        <div class="input-content">
					        <span class="amount" style="color:#FF0000;">'.$rule_money.'</span><span>元</span>
					        <span id="rule_tip" style="color:#FF0000;padding-left: 10px;">';
        if($data['rule_tip']){
            $html.=$data['rule_tip'];
        }
        $html.='</span>
				        </div>
			        </div>
			        <input type="hidden" name="rule_id" value="'.$rule_id.'" class="rule_id"/>
			        <input type="hidden" name="rule_money" value="'.$rule_money.'" class="rule_money"/>
			        <input type="hidden" name="is_pay" class="is_pay" value=""/>
			<div class="button pay" style="width:30%;margin-left:25%;margin-top:10px;">确认支付
			</div></form></div>
			<script>
                $(function(){
                    $(".money-all").click(function(){
                        $(".rule_id").val($(this).attr("data-id"));
                        $(".rule_money").val($(this).attr("data-money"));
                        $(".money-all").css("border","1px solid #dedede");
                        $(this).css("border","1px solid #ff5500");
                        $(".amount").html($(this).attr("data-money"));
                        $("#rule_tip").html("");
                    });
                    $(".check").click(function(){
                        $(".is_pay").val(0);
                        $("#form_submit").submit();
                    });
                    $(".pay").click(function(){
                        $(".is_pay").val(1);
                        $("#form_submit").submit();
                    });});
            </script>';
        echo '<!DOCTYPE html><html><head>
		<meta charset="UTF-8">
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<title>'.$m_config['short_name'].'充值</title>
		<meta name="viewport" content="initial-scale=1, maximum-scale=1">
		<meta name="apple-mobile-web-app-capable" content="yes">
		<meta name="apple-mobile-web-app-status-bar-style" content="black">
		<style type="text/css">
			*{margin: 0;padding: 0;}
			body{background: #eef7f4;font-size: 16px;}
			.clear {clear: both;visibility: hidden;font-size: 0;height: 0;line-height: 0;}
			.content{padding: 20px;background: #fff;}
			.m-user{display: -webkit-flex;display: flex;-webkit-align-items: center;align-items: center;margin-bottom: 15px;}
			.user-img{width: 50px;height: 50px;overflow: hidden;border-radius: 5px;float: left;margin-right: 10px;}
			.user-img img{width: 100%;}
			.user-name{float: left;}
			.user-name .name{font-size: 20px;line-height: 18px;margin-bottom: 5px;color:#ff5500;}
			.m-money{float:left;width: 48%;padding-right:2%;padding-bottom:5px;}
			.money-all{border: 1px solid #dedede;padding-top: 10px;padding-bottom:10px;box-sizing: border-box;text-align: center;}
			.money{font-size: 24px;color: #FF0000;font-family: arial;}
			.title{text-align:center;color: #666;line-height: 20px;font-size: 14px;}
			.button{border:none;text-align: center;background: #ff5500;height: 40px;line-height: 40px;color: #fff;padding: 0 30px;display: block;text-decoration: none;border-radius: 3px;}
			.m-input{display: -webkit-flex;display: flex;-webkit-align-items: center;align-items: center;margin-top: 15px;display:-webkit-box;-webkit-box-orient:horizontal;}
			.m-input .input-content{display:flex;-webkit-box-flex:1;-moz-box-flex:1;-webkit-box-align:center;}
			.m-input input{height: 36px;line-height: 36px;border: 1px solid #dedede;padding: 0 10px;margin: 0 10px;display: flex;font-size:14px;}
			.m-input span{font-size: 14px;line-height: 30px;color: #666;}
		</style>
		<script type="text/javascript" src="'.SITE_DOMAIN.'/wap/theme/default/dist/sui-mobile/zepto.min.js"></script>
</head><body>
                        '.$html.'
            </body></html>';
    }
/*
 * 微信公众号充值
 */
    public function go_pay(){
        $refresh = SITE_DOMAIN.'/mapi/index.php?ctl=wx_bind&act=recharge';
        $notice_id = $_REQUEST['id'];
        $payment_notice = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."payment_notice where id = ".$notice_id,true,true);
        $payment_info = $GLOBALS['db']->getRow("select id,class_name from ".DB_PREFIX."payment where id=".intval($payment_notice['payment_id']),true,true);
        $class_name = $payment_info['class_name']."_payment";
        fanwe_require(APP_ROOT_PATH."system/payment/".$class_name.".php");
        $o = new $class_name;
        $pay_info= $o->get_payment_code($notice_id);
        $html = '<html><head>
            <meta http-equiv="content-type" content="text/html;charset=utf-8"/>
            <meta name="viewport" content="width=device-width, initial-scale=1"/>
            <title>微信支付</title>
            <script type="text/javascript">
	            //调用微信JS api 支付
	            function jsApiCall(){
		            WeixinJSBridge.invoke(
			            "getBrandWCPayRequest",
			            '.$pay_info.',
			            function(res){
				            WeixinJSBridge.log(res.err_msg);
				            if(res.err_msg=="get_brand_wcpay_request:fail"){
                                //alert(res.err_code+res.err_desc+res.err_msg);
                                alert("支付失败");
                            }
                            if(res.err_msg=="get_brand_wcpay_request:cancel"){
                                alert("支付取消");
                            }
                            if(res.err_msg=="get_brand_wcpay_request:ok"){
                                alert("恭喜您支付成功");
                                setTimeout(function(){
                                    location.href = "'.$refresh.'";
                                },1000);
                            }
			            }
		            );
	            }

	            function callpay(){
		            if (typeof WeixinJSBridge == "undefined"){
		                if( document.addEventListener ){
                                document.addEventListener("WeixinJSBridgeReady", jsApiCall, false);
		                }else if (document.attachEvent){
		                    document.attachEvent("WeixinJSBridgeReady", jsApiCall);
		                    document.attachEvent("onWeixinJSBridgeReady", jsApiCall);
		                }
		            }else{
		                jsApiCall();
		            }
	            }
	        </script>
            </head><body>
    <br/>
    <div style="text-align:center">
        <font color="#9ACD32"><b>支付金额为<span style="color:#f00;font-size:50px">'.$payment_notice['money'].'</span>元</b></font><br/><br/>
    </div>
	<div align="center">
		<button style="border:none;text-align: center;background: #ff5500;height: 40px;line-height: 40px;color: #fff;padding: 0 30px;display: block;text-decoration: none;border-radius: 3px;font-size:16px;" type="button" onclick="callpay()" >立即支付</button>
	</div>
</body>
</html>';
        echo $html;exit;
    }
}

?>