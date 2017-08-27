// 收入
$(document).on("pageInit","#user_center-income", function(e, pageId, $page) {
	var nowyer=year;
	var income_type = GetQueryString("type");
	//var displayyear=new Array('2010年', '2011年', '2012年', '2013年', '2014年', '2015年', '2016年', '2017年')
	$("#Date").picker({
	  	toolbarTemplate: '<header class="bar bar-nav">\
	  	<button class="button button-link pull-right close-picker">确定</button>\
	  	<h1 class="title">请选择年份月份</h1>\
	  	</header>',
	  	onClose:function(){
	  		var val=$("#Date").val();
	  		var date=val.split(" ");	
	  		$("#year").text(date[0]);
	  		$("#month").text(date[1]);

	  		yes_counted_url = APP_ROOT+"/wap/index.php?ctl=user_center&act=income&type=0&year="+date[0]+"&month="+date[1];
	  		no_counted_url = APP_ROOT+"/wap/index.php?ctl=user_center&act=income&type=1&year="+date[0]+"&month="+date[1];
    		//location.href= tmpl+"index.php?ctl=user_center&act=income&year="+date[0]+"&month="+date[1];
    		$.ajax({
				url:APP_ROOT+"/wap/index.php?ctl=user_center&act=income&year="+date[0]+"&month="+date[1]+"&type="+income_type,
				type:"post",
				dataType:"html",
				success:function(result){
					$(".content").find(".incomelist").html($(result).find(".content").find(".incomelist").html());
				}
			});
  		},
	  	cols: [
		    {
		      	textAlign: 'center',
		      	//如果你希望显示文案和实际值不同，可以在这里加一个displayValues: [.....]
		      	displayValues: [nowyer-5+'年',nowyer-4+'年',nowyer-3+'年',nowyer-2+'年',nowyer-1+'年',nowyer+'年'],
		      	values: [nowyer-5,nowyer-4,nowyer-3,nowyer-2,nowyer-1,nowyer]
		    },
		    {
		      	textAlign: 'center',
		      	displayValues: ['1月', '2月', '3月', '4月', '5月', '6月', '7月','8月','9月','10月','11月','12月'],
		      	values: ['1', '2', '3', '4', '5', '6', '7','8','9','10','11','12']
		    }
	  	]
	});
 	$(".J-view-income").on('click',function(){
        var iscounted = Number($(this).attr("data-iscounted"));
        iscounted ? location.href = yes_counted_url : location.href = no_counted_url;
    });
});


// 收入
$(document).on("pageInit","#user_center-goods_income_details", function(e, pageId, $page) {
	var nowyer=year;
	var income_type = GetQueryString("type");
	//var displayyear=new Array('2010年', '2011年', '2012年', '2013年', '2014年', '2015年', '2016年', '2017年')
	$("#Date").picker({
	  	toolbarTemplate: '<header class="bar bar-nav">\
	  	<button class="button button-link pull-right close-picker">确定</button>\
	  	<h1 class="title">请选择年份月份</h1>\
	  	</header>',
	  	onClose:function(){
	  		var val=$("#Date").val();
	  		var date=val.split(" ");	
	  		$("#year").text(date[0]);
	  		$("#month").text(date[1]);

	  		yes_goods_url = APP_ROOT+"/wap/index.php?ctl=user_center&act=goods_income_details&type=1&year="+date[0]+"&month="+date[1];
	  		no_goods_url = APP_ROOT+"/wap/index.php?ctl=user_center&act=goods_income_details&type=2&year="+date[0]+"&month="+date[1];
	  		invalid_goods_url = APP_ROOT+"/wap/index.php?ctl=user_center&act=goods_income_details&type=3&year="+date[0]+"&month="+date[1];
    		//location.href= tmpl+"index.php?ctl=user_center&act=income&year="+date[0]+"&month="+date[1];
    		$.ajax({
				url:APP_ROOT+"/wap/index.php?ctl=user_center&act=goods_income_details&year="+date[0]+"&month="+date[1]+"&type="+income_type,
				type:"post",
				dataType:"html",
				success:function(result){
					$(".content").find(".incomelist").html($(result).find(".content").find(".incomelist").html());
				}
			});
  		},
	  	cols: [
		    {
		      	textAlign: 'center',
		      	//如果你希望显示文案和实际值不同，可以在这里加一个displayValues: [.....]
		      	displayValues: [nowyer-5+'年',nowyer-4+'年',nowyer-3+'年',nowyer-2+'年',nowyer-1+'年',nowyer+'年'],
		      	values: [nowyer-5,nowyer-4,nowyer-3,nowyer-2,nowyer-1,nowyer]
		    },
		    {
		      	textAlign: 'center',
		      	displayValues: ['1月', '2月', '3月', '4月', '5月', '6月', '7月','8月','9月','10月','11月','12月'],
		      	values: ['1', '2', '3', '4', '5', '6', '7','8','9','10','11','12']
		    }
	  	]
	});
 	$(".J-view-goods").on('click',function(){
        var iscounted = Number($(this).attr("data-iscounted"));
        if(iscounted == 1){
        	location.href = yes_goods_url;
        }
        else if(iscounted == 2){
    		location.href = no_goods_url;
        }
        else{
        	location.href = invalid_goods_url;
        }
    });
});

// 分享页面
$(document).on("pageInit","#page-share-index", function(e, pageId, $page) {
	// 点击弹出下载提示窗
	$(".show_pop_wp").on('click',function(){
		$(".pop_wp").css({display:"flex"});
	});
	$(".pop_close").on('click',function(){
		$(".pop_wp").css({display:"none"});
	});
   	var width = $(window).width();
    var height = $(window).height();
    console.log("live_in:"+live_in);
	if(live_in==1){
        if(live_url || live_url2){
            (function () {
		        var player = new qcVideo.Player("id_video_container", {
		            "live_url": live_url,
                    "live_url2": live_url2,
                    "width": width,
                    "height": 320,
                    "h5_start_patch":{
                    	"url": head_image_url,
                    	"stretch": true
                    }
		        },	{
				    playStatus: function (status,type){
				        //TODO
				        console.log(status);
				        if(status == "playing"){
				        	player.resize(width, height);
				        	if(!device || device=='iphone'){
				        		$(".live_info").show();
				        		$(".pop_download").hide();
				        	}
				        }
				        else{
				        	player.resize(width, 320);
				        }
				    }
				});
			 	$("#startplay").on('click',function(){
	        		$("#liveing").show();
	        		$("#preVedio").hide();
			    	player.play();
			    });
		    })();
        }else{
            (function () {
		        var player = new qcVideo.Player("id_video_container", {
		           	"channel_id": channel_id,
                    "app_id": app_id,
                    "width": width,
                    "height": 320,
                    "h5_start_patch":{
                    	"url": head_image_url,
                    	"stretch": true
                    }
		        }, {
				    playStatus: function (status){
			         	//TODO
				        console.log(status);
				        if(status == "playing"){
				        	player.resize(width, height);
				        	if(!device || device=='iphone'){
				        		$(".live_info").show();
				        		$(".pop_download").hide();
				        	}
				        }
				        else{
				        	player.resize(width, 320);
				        }
				    }
				});
				$("#startplay").on('click',function(){
					$("#liveing").show();
	        		$("#preVedio").hide();
			    	player.play();
			    });
		    })();
        }
	}else if(live_in==3){
        (function () {
	        var player = new qcVideo.Player("id_video_container", {
	           	"file_id": file_id,
	            "app_id": app_id,
	            "width":width,
	            "height":320
	        }, {
				    playStatus: function (status,type){
				        //TODO
				        console.log(status);
				        if(status == "playing"){
				        	player.resize(width, height);
				        	if(!device || device=='iphone'){
				        		$(".live_info").show();
				        		$(".pop_download").hide();
				        	}
				        }
				        else{
				        	player.resize(width, 320);
				        }
				    }
				});
	        	$("#startplay").on('click',function(){
	        		$("#liveing").show();
	        		$("#preVedio").hide();
			    	player.play();
			    });
	    })();

        /*var player = new qcVideo.Player("id_video_container", {
            "width":width,
            "height":height,
            "stretch_full":1,
            "stop_time":60,
            "third_video": {
                "urls":{
                    20 : urls//演示地址，请替换实际地址
                }
            }
        });*/
    }else{

    }

	wx.ready(function () {
		// 在这里调用 API
		wx.onMenuShareTimeline({
			title: wx_title, // 分享标题
			link: wx_link, // 分享链接
			imgUrl: wx_img, // 分享图标
			success: function () {
				// 用户确认分享后执行的回调函数
			},
			cancel: function () {
				// 用户取消分享后执行的回调函数
			}
		});
		wx.onMenuShareAppMessage({
			title: wx_title, // 分享标题
			desc: wx_desc, // 分享描述
			link: wx_link,  // 分享链接
			imgUrl: wx_img, // 分享图标
			type: 'link', // 分享类型,music、video或link，不填默认为link
			// dataUrl: '', // 如果type是music或video，则要提供数据链接，默认为空
			success: function () {
				// 用户确认分享后执行的回调函数
			},
			cancel: function () {
				// 用户取消分享后执行的回调函数
			}
		});

        wx.onMenuShareQQ({
            title: wx_title, // 分享标题
            desc: wx_desc, // 分享描述
            link: wx_link, // 分享链接
            imgUrl: wx_img, // 分享图标
            success: function () {
                // 用户确认分享后执行的回调函数
            },
            cancel: function () {
                // 用户取消分享后执行的回调函数
            }
        });


        wx.onMenuShareQZone({
            title: wx_title, // 分享标题
            desc: wx_desc, // 分享描述
            link: wx_link, // 分享链接
            imgUrl: wx_img, // 分享图标
            success: function () {
                // 用户确认分享后执行的回调函数
            },
            cancel: function () {
                // 用户取消分享后执行的回调函数
            }
        });
		wx.error(function(res){
			// config信息验证失败会执行error函数，如签名过期导致验证失败，具体错误信息可以打开config的debug模式查看，也可以在返回的res参数中查看，对于SPA可以在这里更新签名。
		});
	});

	function addMsg(msg) {
		var time = webim.Tool.formatTimeStamp(msg.getTime());
		var data = convertMsg(msg);
		if(! data){
			return;
		}

		if(typeof data !== 'object'){
			data = {
				"user_level": 122,
				"nick_name": "[群提示消息]",
				"text": data,
			};
		}

		if (data.type == 2 && showBarrage && player) {
			var barrage = [
				{ "type": "content", "content": data.text, "time": "0" },
			];
			player.addBarrage(barrage);
		}

		$('#chat-box').append('<li><p><a href="javascript:;" class="name"><i class="ico_level"></i>' + data.nick_name + '</a>' + data.text + '</p></li>');
	}

	var listeners = {
        loginSuccess: function () {
            im_message.applyJoinBigGroup(avChatRoomId);
        },
        recieveGroupMsg: function (newMsgList) {
            for (var j in newMsgList) {//遍历新消息
                var newMsg = newMsgList[j];
                addMsg(newMsg);
            }
			
			var el = $('#video_sms_list');
            el.scrollTop(el.prop("scrollHeight"));
        },
        sendMsgOk: function (msg) {
            $('#input-chat-speak').val('');
        },
    };
	if(typeof loginInfo !== 'undefined'){
		im_message.init(loginInfo, listeners);
	}
});

$(document).on("pageInit","#page-pai_podcast-goods,#page-pai_podcast-order,#page-pai_user-goods,#page-pai_user-order", function(e, pageId, $page) {

	// 查看物流
	$(document).on('click', '.J-view_express',function(e) {
		$.alert($(this).attr("data-express"));
	});

	if(pageId == 'page-pai_podcast-goods' || pageId == 'page-pai_user-goods'){
		var ajax_url;
		pageId == 'page-pai_podcast-goods' ? ajax_url=TMPL+'index.php?ctl=pai_podcast&act=goods&post_type=json&is_true='+is_true : ajax_url=TMPL+'index.php?ctl=pai_user&act=goods&post_type=json&is_true='+is_true;
		$.ajax({
            url: ajax_url,
            data: {},
            type: 'POST',
            dataType: 'json',
            success:function(data){
            	if(data.status == 1){
            		var data_list = data.data.list;
            		var data_leftTime;
            		for (var i = 0; i < data_list.length; i++) {
	            		data_list[i].status == 0 ? data_leftTime = data_list[i].pai_left_time : data_leftTime = data_list[i].expire_time;
	            		$(".card-footer").eq(i).find(".left_time").attr("data-leftTime",data_leftTime);
	            		console.log(data_list[i].expire_time);
	            	};
            		$(".left_time").each(function(){
				    	var leftTime = Math.abs(parseInt($(this).attr("data-leftTime")));
				    	left_time(leftTime,$(this));
				    });
            	}
            	else{
            		$.toast(data.error,1000);
            	}
            },
            error:function(){
            	$.hideIndicator();
		       	$.toast('请求失败，请检查网络',1000);
		    }

        });
	}
	else{
	 	// 倒计时
	    $(".left_time").each(function(){
	    	var leftTime = Math.abs(parseInt($(this).attr("data-leftTime")));
	    	left_time(leftTime,$(this));
	    });
	}
   
    // 监听设备处于锁屏或者浏览器/页面处于后台运行状态
 	document.addEventListener("visibilitychange", function (e) {
 		var reload_ajax_url;
		switch (pageId)
		{
			case 'page-pai_podcast-goods':
			  reload_ajax_url = TMPL+"index.php?ctl=pai_podcast&act=goods&post_type=json&page_size=99999999999&is_true="+is_true;
			  break;
			case 'page-pai_user-goods':
			  reload_ajax_url = TMPL+"index.php?ctl=pai_user&act=goods&post_type=json&page_size=99999999999&is_true="+is_true;
			  break;
			case 'page-pai_podcast-order':
			  var order_sn,pai_id;
			  GetQueryString("order_sn") ? order_sn = GetQueryString("order_sn") : '';
			  GetQueryString("pai_id") ? pai_id = GetQueryString("pai_id") : '';
			  reload_ajax_url = TMPL+"index.php?ctl=pai_podcast&act=order&post_type=json&is_true="+is_true+"&order_sn="+order_sn+"&pai_id="+pai_id;
			  break;
			case 'page-pai_user-order':
		      var order_sn,pai_id;
			  GetQueryString("order_sn") ? order_sn = GetQueryString("order_sn") : '';
			  GetQueryString("pai_id") ? pai_id = GetQueryString("pai_id") : '';
			  reload_ajax_url = TMPL+"index.php?ctl=pai_user&act=order&post_type=json&is_true="+is_true+"&order_sn="+order_sn+"&pai_id="+pai_id;
			  break;
		}
        if(!e.path[0].hidden){ // e.path为页面中document的集合
            $.ajax({
	            url: reload_ajax_url,
	            data: {},
	            type: 'POST',
	            dataType: 'json',
	            beforeSend:function(){
	                $.showIndicator();
	            },
	            success:function(data){
            		$.hideIndicator();
	            	if(data.status == 1){
		            	if(pageId == 'page-pai_podcast-goods' || pageId == 'page-pai_user-goods'){
		            		var data_list = data.data.list;
		            		for (var i = 0; i < data_list.length; i++) {
			            		$(".card-footer").eq(i).find(".left_time").attr("data-leftTime",data_list[i].expire_time);
			            		console.log(data_list[i].expire_time);
			            	};
		            		$(".left_time").each(function(){
						    	var leftTime = Math.abs(parseInt($(this).attr("data-leftTime")));
						    	left_time(leftTime,$(this));
						    });
		            	}
		            	else{
		            		var data_list = data.data;
		            	 	$(".left_time").each(function(){
						    	left_time(data_list.expire_time,$(this));
						    });
	            	 	}
	            	}
	            	else{
	            		$.toast(data.error,1000);
	            	}
	            },
	            error:function(){
	            	$.hideIndicator();
			       	$.toast('请求失败，请检查网络',1000);
			    }

	        });
        } 
    }, false);
    
    // 充值
    $(".J_recharge").on('click',function(){
    	var json = new Object();
        json.android_page = 'com.fanwe.live.activity.LiveRechargeActivity';
        json.ios_page = 'chargerViewController';
        json = JSON.stringify(json);
        App.start_app_page(json);
    });

    // 继续参拍
	$(document).on('click', '.J-join_live',function(e) {
		var pai_id = $(this).attr('data-id');
		$.ajax({
			url:TMPL+"index.php?ctl=pai_user&act=go_video&post_type=json&itype=shop&videoType=1&pai_id="+pai_id,
			type:"post",
			dataType:"html",
			beforeSend:function(){
				$.showIndicator();
			},
			success:function(result){
				$.hideIndicator();
				result = JSON.parse(result);
				var roomId = result.roomId;
				var groupId = result.groupId;
				var createrId = result.createrId;
				var loadingVideoImageUrl = result.loadingVideoImageUrl;
				var videoType=1;
				if(result.status == 1){
					if(roomId>0){
						var json = new Object(); json.roomId = roomId.toString(),json.videoType=videoType.toString(), json.groupId = groupId.toString(), json.createrId = createrId.toString(), json.loadingVideoImageUrl = loadingVideoImageUrl.toString(), json = JSON.stringify(json);
						App.join_live(json);
					}
					else{
						$.toast("请求失败，房间已关闭");
						return false;
					}
				}
				else{
					$.toast(result.error ? result.error : '操作失败');
					return false;
				}
			},
			error:function(){
				$.hideIndicator();
				$.toast("请求失败，请检查网络");
			}
		});
	});
   	
 	// 观众端
    if(pageId == 'page-pai_user-goods' || pageId == 'page-pai_user-order'){

	    // 进入竞拍详情（未生成订单）
	    $(document).on('click', '.J-pai_live',function(e) {
	    	var id = $(this).attr("data-id");
	    	var json = new Object();
	        json.android_page = 'com.fanwe.auction.activity.AuctionGoodsDetailActivity';
	        json.ios_page = 'detailViewController';
	        json.data = new Object();
	        json.data.id = id;
	        json.data.is_anchor = 0;
	        json = JSON.stringify(json);
	        App.start_app_page(json);
	    });
	    // 提醒约会
	    $(document).on('click', '.J-remind_podcast_to_date',function(e) {
	    	var order_sn = Number($(this).attr('data-order_sn')), to_podcast_id = Number($(this).attr('data-to_podcast_id'));
    		handleAjax.handle(TMPL+"index.php?ctl=pai_podcast&act=remind_podcast_to_date",{order_sn:order_sn, to_podcast_id:to_podcast_id}).done(function(resp){
	            $.toast("已成功提醒",1000);
	        }).fail(function(err){
	            $.toast(err,1000);
	        });
	    });

	    // 提醒主播确认约会
	    $(document).on('click', '.J-remind_podcast_to_confirm_date',function(e) {
	    	var order_sn = Number($(this).attr('data-order_sn')), to_podcast_id = Number($(this).attr('data-to_podcast_id'));
    		handleAjax.handle(TMPL+"index.php?ctl=pai_podcast&act=remind_podcast_to_confirm_date",{order_sn:order_sn, to_podcast_id:to_podcast_id}).done(function(resp){
				$.toast("已成功提醒",1000);
				//$.toast(resp,1000);
	            //setTimeout(function(){
	            //    location.reload();
	            //    $.showPreloader();
	            //},1000);
	        }).fail(function(err){
	            $.toast(err,1000);
	        });
	    });
	    
	    // 确认约会
	    $(document).on('click', '.J-buyer_confirm_date',function(e) {
	    	var order_sn = Number($(this).attr('data-order_sn')), to_podcast_id = Number($(this).attr('data-to_podcast_id')), confirm_tip = $(this).attr('data-confirm-tip');
	    	$.confirm(confirm_tip,
		        function () {
		        	handleAjax.handle(TMPL+"index.php?ctl=pai_podcast&act=buyer_confirm_date",{order_sn:order_sn, to_podcast_id:to_podcast_id}).done(function(resp){
			           is_true==1 ? $.toast("确认收货成功",1000) : $.toast("确认约会成功",1000);
			            setTimeout(function(){
			                location.reload();
			                $.showPreloader();
			            },1000);
			        }).fail(function(err){
			            $.toast(err,1000);
			        });
		        }
	      	);
	    });
	    // 申请退款（我要投诉）
	    $(document).on('click', '.J-buyer_to_complaint',function(e) {
	    	var order_sn = Number($(this).attr('data-order_sn')), to_podcast_id = Number($(this).attr('data-to_podcast_id'));    		
    		handleAjax.handle(TMPL+"index.php?ctl=pai_podcast&act=buyer_to_complaint",{order_sn:order_sn, to_podcast_id:to_podcast_id}).done(function(resp){
	            $.toast("已成功提交<br/>请等待客服联系",1000);
	            setTimeout(function(){
	                location.reload();
	                $.showPreloader();
	            },1000);
	        }).fail(function(err){
	            $.toast(err,1000);
	        });
	    });

	    // 主动撤销退款
	    $(document).on('click', '#J-oreder_revocation',function(e) {
	    	var order_sn = Number($(this).attr('data-order_sn')), to_podcast_id = Number($(this).attr('data-to_podcast_id'));    		
    		handleAjax.handle(TMPL+"index.php?ctl=pai_podcast&act=oreder_revocation",{order_sn:order_sn, to_podcast_id:to_podcast_id}).done(function(resp){
	            $.toast(resp,1000);
	            setTimeout(function(){
	                location.reload();
	                $.showPreloader();
	            },1000);
	        }).fail(function(err){
	            $.toast(err,1000);
	        });
	    });

	    // 付款
	    $(document).on('click', '.J-pay_diamonds',function(e) {
	    	if($('#pay_balance').is(':checked')) {
			    var order_sn = Number($(this).attr('data-order_sn'));
		    	var pai_id = Number($(this).attr('data-pai_id'));
	    		handleAjax.handle(TMPL+"index.php?ctl=pai_user&act=pay_diamonds",{order_sn:order_sn}).done(function(resp){
		            $.toast(resp,1000);
		            setTimeout(function(){
		                location.href = APP_ROOT+"/index.php?ctl=pai_user&act=order&order_sn="+order_sn+"&pai_id="+pai_id;
		                $.showPreloader();
		            },1000);
		        }).fail(function(err){
		            $.toast(err,1000);
		        });
			}
			else{
				$.toast("请选择支付方式");
				return false;
			}
	    });

	    // 买家要求退货
	    $(document).on('click', '#J-buyer_confirm_to_refund',function(e) {
	    	var order_sn = Number($(this).attr('data-order_sn')), to_podcast_id = Number($(this).attr('data-to_podcast_id')); 
    		handleAjax.handle(TMPL+"index.php?ctl=pai_podcast&act=buyer_confirm_to_refund",{order_sn:order_sn, to_podcast_id:to_podcast_id}).done(function(resp){
	            $.toast(resp,1000);
	            setTimeout(function(){
	                location.reload();
	                $.showPreloader();
	            },1000);
	        }).fail(function(err){
	            $.toast(err,1000);
	        });
	    });

	    // 联系卖家
	    $(document).on('click', '#J-link',function(e){
	    	$.alert($(this).attr("data-link"));
	    });
    }

    // 主播端
    if(pageId == 'page-pai_podcast-goods' || pageId == 'page-pai_podcast-order'){
    	// 主播端提醒买家付款
    	$(document).on('click', '.J-remind_buyer_pay',function(e) {
    		var order_sn = Number($(this).attr('data-order_sn')), to_buyer_id = Number($(this).attr('data-to_buyer_id'));
    		handleAjax.handle(TMPL+"index.php?ctl=pai_podcast&act=remind_buyer_pay",{order_sn:order_sn, to_buyer_id:to_buyer_id}).done(function(resp){
	            $.toast(resp,1000);
	        }).fail(function(err){
	            $.toast(err,1000);
	        });
    	});
    	// 提醒买家约会
    	$(document).on('click', '.J-remind_buyer_to_date',function(e) {
    		var order_sn = Number($(this).attr('data-order_sn')), to_buyer_id = Number($(this).attr('data-to_buyer_id'));
    		handleAjax.handle(TMPL+"index.php?ctl=pai_podcast&act=remind_buyer_to_date",{order_sn:order_sn, to_buyer_id:to_buyer_id}).done(function(resp){
	            $.toast(resp,1000);
	        }).fail(function(err){
	            $.toast(err,1000);
	        });
    	});
    	// 确认完成约会
    	$(document).on('click', '.J-confirm_virtual_auction',function(e) {
    		var order_sn = Number($(this).attr('data-order_sn')), to_buyer_id = Number($(this).attr('data-to_buyer_id')), confirm_tip = $(this).attr('data-confirm-tip');
    		$.confirm(confirm_tip,function(){
    			handleAjax.handle(TMPL+"index.php?ctl=pai_podcast&act=confirm_virtual_auction",{order_sn:order_sn, to_buyer_id:to_buyer_id}).done(function(resp){
		            $.toast(resp,1000);
		            setTimeout(function(){
		               	location.reload();
		                $.showPreloader();
		            },1000);
		        }).fail(function(err){
		            $.toast(err,1000);
		        });
    		});
    	});
    	// 提醒买家确认完成约会
    	$(document).on('click', '.J-remind_buyer_receive',function(e) {
    		var order_sn = Number($(this).attr('data-order_sn')), to_buyer_id = Number($(this).attr('data-to_buyer_id'));   	
    		handleAjax.handle(TMPL+"index.php?ctl=pai_podcast&act=remind_buyer_receive",{order_sn:order_sn, to_buyer_id:to_buyer_id}).done(function(resp){
	            $.toast(resp,1000);
	        }).fail(function(err){
	            $.toast(err,1000);
	        });
    	});
    	// 进入竞拍详情（未生成订单）
    	$(document).on('click', '.J-pai_live',function(e) {
	    	var id = $(this).attr("data-id");
	    	var json = new Object();
	        json.android_page = 'com.fanwe.auction.activity.AuctionGoodsDetailActivity';
	        json.ios_page = 'detailViewController';
	        json.data = new Object();
	        json.data.id = id;
	        json.data.is_anchor = 1;
	        json = JSON.stringify(json);
	        App.start_app_page(json);
	    });

	    // 同意退款（确认收取退货）
    	$(document).on('click', '#J-return_virtual_pai',function(e) {
    		var order_sn = Number($(this).attr('data-order_sn')), to_buyer_id = Number($(this).attr('data-to_buyer_id'));   	
    		handleAjax.handle(TMPL+"index.php?ctl=pai_podcast&act=return_virtual_pai",{order_sn:order_sn, to_buyer_id:to_buyer_id}).done(function(resp){
	            $.toast(resp,1000);
	            setTimeout(function(){
	                location.reload();
	                $.showPreloader();
	            },1000);
	        }).fail(function(err){
	            $.toast(err,1000);
	        });
    	});

    	// 申请售后
    	$(document).on('click', '#J-complaint_virtual_goods',function(e) {
    		var order_sn = Number($(this).attr('data-order_sn')), to_buyer_id = Number($(this).attr('data-to_buyer_id'));   	
    		handleAjax.handle(TMPL+"index.php?ctl=pai_podcast&act=complaint_virtual_goods",{order_sn:order_sn, to_buyer_id:to_buyer_id}).done(function(resp){
	            $.toast(resp,1000);
	        }).fail(function(err){
	            $.toast(err,1000);
	        });
    	});

	 	// 提醒卖家发货
	    $(document).on('click', '.J-remind_seller_delivery',function(e) {
	    	var order_sn = Number($(this).attr('data-order_sn'));
    		handleAjax.handle(TMPL+"index.php?ctl=pai_podcast&act=remind_seller_delivery",{order_sn:order_sn}).done(function(resp){
	            $.toast("已成功提醒",1000);
	        }).fail(function(err){
	            $.toast(err,1000);
	        });
	    });
    }
});

$(document).on("pageInit","#page-pai_user-virtual_order_details", function(e, pageId, $page) {
	// 充值
    $(".J_recharge").on('click',function(){
        var json = new Object();
        json.android_page = 'com.fanwe.live.activity.LiveRechargeDiamondsActivity';
        json.ios_page = 'chargerViewController';
        json = JSON.stringify(json);
        App.start_app_page(json);
    });

    // 付款
    $('.J-pay_diamonds').on('click',function(e) {
        var order_type = $(this).attr("data-ordertype");
        switch(order_type){
        case "h5shop":
			if($("input[name='pay-money']:checked").val()) {
				var order_sn = Number($(this).attr('data-order_sn'));
				var order_id = Number($(this).attr('data-order_id'));
				var pay_id = $("input[name='pay-money']:checked").val();
				var purchase_type = Number($(this).attr('data-purchase_type'));
				$.ajax({
					url: TMPL+"/wap/index.php?ctl=pay&act=shop_h5_pay&post_type=json",
					data: {order_id:order_id, purchase_type:purchase_type,order_sn:order_sn,pay_id:pay_id,shop_info:shop_info},
					type: 'POST',
					dataType: 'json',
					success:function(data){
						if(data.status == 1){
							try{
								App.pay_sdk(JSON.stringify(data.pay.sdk_code));
								return false;
							}
							catch(e){
								$.toast("SDK调用失败");
								return false;
							}
						}else{
							$.hideIndicator();
							$.toast(data.error,1000);
						}
					},
					error:function(){
						$.hideIndicator();
						$.toast('请求失败，请检查网络',1000);
					}
				});
			}
            else{
                $.toast("请选择支付方式");
                return false;
            }
            break;
		case "to_podcast":
			if($("input[name='pay-money']:checked").val()) {
				var order_sn = Number($(this).attr('data-order_sn'));
				var order_id = Number($(this).attr('data-order_id'));
				var pay_id = $("input[name='pay-money']:checked").val();
				var purchase_type = Number($(this).attr('data-purchase_type'));
				$.ajax({
					url: TMPL+"/wap/index.php?ctl=pay&act=shop_h5_pay&post_type=json",
					data: {order_id:order_id, purchase_type:purchase_type,order_sn:order_sn,pay_id:pay_id,shop_info:shop_info},
					type: 'POST',
					dataType: 'json',
					success:function(data){
						if(data.status == 1){
							try{
								App.pay_sdk(JSON.stringify(data.pay.sdk_code));
								return false;
							}
							catch(e){
								$.toast("SDK调用失败");
								return false;
							}
						}else{
							$.hideIndicator();
							$.toast(data.error,1000);
						}
					},
					error:function(){
						$.hideIndicator();
						$.toast('请求失败，请检查网络',1000);
					}
				});
			}
			else{
				$.toast("请选择支付方式");
				return false;
			}
			break;
        default:
			if($('#pay_balance').is(':checked')){
                var order_sn = Number($(this).attr('data-order_sn'));
                var pai_id = Number($(this).attr('data-pai_id'));
				$.ajax({
					url: TMPL+"index.php?ctl=pai_user&act=pay_diamonds&post_type=json&itype=shop&order_sn="+order_sn,
					data: '',
					type: 'POST',
					dataType: 'json',
					beforeSend:function(){
						$.showIndicator();
					},
					success:function(resp){
						$.hideIndicator();
						$.toast(resp.error,1000);
						setTimeout(function(){
							if(resp.status != 1){
								$.toast(resp.error,1000);
							}else{
								window.location.href = TMPL+"index.php?ctl=pai_user&act=order&order_sn="+resp.order_sn+"&pai_id="+pai_id;
								$.showPreloader();
							}
						},1000);
					},
					error:function(){
						$.hideIndicator();
						$.toast("请求出错",1000);
					}
				});
            }
            else{
                $.toast("请选择支付方式");
                return false;
            }
            break;
        }
    });
});

// 我的等级
$(document).on("pageInit","#page-user_center-grade", function(e, pageId, $page) {
	up_score == '满级' ? document.getElementById('grade_progress').style.width = '100%' : document.getElementById('grade_progress').style.width = ((u_score/up_score)*100).toFixed(2)+'%';
});

// 支付结果
$(document).on("pageInit","#page-pay_success", function(e, pageId, $page) {
 	// 继续参拍
    $(document).on('click', '.J-join_live',function(e) {
		// App.join_live(data_json);
		App.js_shopping_comeback_live_app();
   	});
});

//sdk支付回调
function js_pay_sdk(status){
	if(status == 1){
		window.location.href = TMPL+'/wap/index.php?ctl=shop&act=shop_order&page=1';
		$.showPreloader();
		//App.js_shopping_comeback_live_app();
	}
}