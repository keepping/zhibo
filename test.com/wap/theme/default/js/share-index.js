// 分享页面
$(document).on("pageInit","#page-share-indexs", function(e, pageId, $page) {
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
            	if(!device || device=='iphone'){
            		var option ={
				     	"live_url": live_url,
	                    "live_url2": live_url2,
	                    "width": width,
	                    "height": 320,
	                    "x5_type": "h5",
	                    "x5_fullscreen": true,
	                    "h5_start_patch":{
	                    	"url": head_image_url,
	                    	"stretch": true
	                    }
					};
            	}
            	else{
            		var option ={
				     	"live_url": live_url,
	                    "live_url2": live_url2,
	                    "width": width,
	                    "height": 320,
	                    "h5_start_patch":{
	                    	"url": head_image_url,
	                    	"stretch": true
	                    }
					};
            	}
		        var player = new qcVideo.Player("id_video_container", option, {
				    playStatus: function (status,type){
				        //TODO
				        console.log(status);
				        if(status == "playing"){
				        	if(!device || device=='iphone'){
				        		player.resize(width, height);
				        		$(".vedio_wp").css({"height": height});
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
            	if(!device || device=='iphone'){
            		var option ={
			     		"channel_id": channel_id,
	                    "app_id": app_id,
	                    "width": width,
	                    "height": 320,
	                    "x5_type": "h5",
	                    "x5_fullscreen": true,
	                    "h5_start_patch":{
	                    	"url": head_image_url,
	                    	"stretch": true
	                    }
					};
            	}
            	else{
            		var option ={
				     	"channel_id": channel_id,
	                    "app_id": app_id,
	                    "width": width,
	                    "height": 320,
	                    "h5_start_patch":{
	                    	"url": head_image_url,
	                    	"stretch": true
	                    }
					};
            	}
		        var player = new qcVideo.Player("id_video_container", option, {
				    playStatus: function (status){
			         	//TODO
				        console.log(status);
				        if(status == "playing"){
				        	if(!device || device=='iphone'){
				        		player.resize(width, height);
				        		$(".vedio_wp").css({"height": height});
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
        	if(!device || device=='iphone'){
        		var option ={
	     			"file_id": file_id,
		            "app_id": app_id,
		            "width":width,
		            "height":320,
		            "x5_type": "h5",
	                "x5_fullscreen": true
				};
        	}
        	else{
        		var option ={
			     	"file_id": file_id,
		            "app_id": app_id,
		            "width":width,
		            "height":320
				};
        	}
	        var player = new qcVideo.Player("id_video_container", option, {
			    playStatus: function (status,type){
			        //TODO
			        console.log(status);
			        if(status == "playing"){
			        	if(!device || device=='iphone'){
			        		player.resize(width, height);
			        		$(".vedio_wp").css({"height": height});
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