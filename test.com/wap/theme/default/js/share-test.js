var media_info = {
	"live_in": video.live_in,
    "file_living": video.play_hls,
    "file_playback": video.urls,
    "image": video.head_image,
    "watch_number": video.watch_number
};

console.log("media_info:"+JSON.stringify(media_info));
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

    if (data.type == 1){
        var sender = data.ext.sender;
        var gif = data.ext.pc_gif || data.ext.pc_icon || data.ext.icon;
		showGift([{
            nick_name: sender.nick_name,
            fromId: sender.user_id,
            num: data.ext.plus_num,
            giftId: data.ext.prop_id,
            gift_type: data.ext.desc,
            gift_image: gif,
            head_image: sender.head_image,
        }]);
	}

    // if (data.type == 2 && showBarrage && player) {
    //     var barrage = [
    //         { "type": "content", "content": data.text, "time": "0" },
    //     ];
    //     player.addBarrage(barrage);
    // }

    $('#msg_box').append('<p><img class="msg-level" src="/public/images/rank/rank_' + data.user_level + '.png"> <label>'+data.nick_name+':</label><span style="color:#E66973">'+data.text+'</span></p>');
}
function showGift(i) {
    console.log(i);
    var i = i || [
        {nick_name:"昵称1", fromId:"34535", num:20, giftId:1111, gift_type:"棒棒糖", gift_image:"http://img.meelive.cn/Mjk4NTIxNDQ3MTQ0MjM2.jpg"},
        {nick_name:"昵称1", fromId:"34534", num:20, giftId:1111, gift_type:"棒棒糖", gift_image:"http://img.meelive.cn/Mjk4NTIxNDQ3MTQ0MjM2.jpg"},
        {nick_name:"昵称1", fromId:"567567", num:20, giftId:1111, gift_type:"棒棒糖", gift_image:"http://img.meelive.cn/Mjk4NTIxNDQ3MTQ0MjM2.jpg"},
        {nick_name:"昵称1", fromId:"7878", num:20, giftId:1111, gift_type:"棒棒糖", gift_image:"http://img.meelive.cn/Mjk4NTIxNDQ3MTQ0MjM2.jpg"},
        {nick_name:"昵称1", fromId:"5656", num:20, giftId:1111, gift_type:"棒棒糖", gift_image:"http://img.meelive.cn/Mjk4NTIxNDQ3MTQ0MjM2.jpg"},
        {nick_name:"昵称1", fromId:"345345", num:20, giftId:1111, gift_type:"棒棒糖", gift_image:"http://img.meelive.cn/Mjk4NTIxNDQ3MTQ0MjM2.jpg"},
        {nick_name:"昵称1", fromId:"7687887", num:20, giftId:1111, gift_type:"棒棒糖", gift_image:"http://img.meelive.cn/Mjk4NTIxNDQ3MTQ0MjM2.jpg"}
    ];
    for (var e = 0; e < i.length; e++) {
        // if (!i[e].gift) return;
        // var t = i.ms[e].gift,
        // n = i.ms[e].from,
        // o = t.seq ? t.seq: 0,
        a = {
            nick: i[e].nick_name,
            fromId: i[e].fromId + "" + i[e].giftId,
            num: i[e].num,
            giftId: i[e].giftId,
            gift_type: i[e].gift_type,
            gift_image: i[e].gift_image,
            head_image: i[e].head_image
        };
        ShowGiftAnimate.init(a)
    }
}
var chat = function(i, e) {
    this.opt = {
        isGiftShow: !0,
        isGiftManager: !0,
        isRecordChat: !0
    },
    $.extend(!0, this.opt.opt || {}),
    this.listeners = {
            loginSuccess: function () {
            im_message.applyJoinBigGroup(avChatRoomId);
        },
        recieveGroupMsg: function (newMsgList) {

            for (var j in newMsgList) {//遍历新消息
                var newMsg = newMsgList[j];
                addMsg(newMsg);
            }
            
            var el = $('#msg_box');
            el.scrollTop(el.prop("scrollHeight"));
        },
        sendMsgOk: function (msg) {
            $('#input-chat-speak').val('');
        },
    },
    this.recordOne = function() {
        var i = this;
        // e = i.record.data;
        // if (!e || 0 == e.length) return void i.getRecordData();
        var t = i.pageObj.playerObj.player.getState();
        if ("PLAYING" != t && "BUFFERING" != t) return void console.log("录播视频暂停状态 停止 聊天信息");
        // var n = e.shift();
        // if (i.recordRender(n.data), !e.length) return void i.getRecordData();
        // var o = e[0].timestamp - n.timestamp;
        // setTimeout(function() {
        //     i.recordOne()
        // },
        // o || 100)
    }
},
ShowGiftAnimate = {
    datas: [],
    bigDatas: [],
    isAllPlaying: !1,
    showSize: 2,
    container: "js-gift-show-container",
    bigContainer: $("#js-show-big-gift-wrap"),
    bigGiftTpl: $("#js-big-gift-wrap-tpl"),
    duration: 2e3,
    timerArr: [],
    isInit: !1,
    isGetGift: !1,
    giftInfoList: [],
    readyBig: [65, 132],
    isBigPlaying: !1,
    creatList: function() {
        for (var i = this,
        e = 0; e < $(".gift-pic").length; e++) {
            var t = {
                id: $(".gift-list li:eq(" + e + ")").attr("data-id"),
                icon: $(".gift-pic:eq(" + e + ")").attr("src")
            };
            i.giftInfoList.push(t)
        }
    },
    init: function(i) {
        var e = this;

        e.datas.push(i), e.isAllPlaying || e.start();

        // e.giftInfoList.length || e.isGetGift ? void(e.isGetGift && e.giftInfoList.length && (i.giftPic = e.getGiftPic(i.giftId), e.readyBig.indexOf(i.giftId) == -1 ? (e.datas.push(i), e.isAllPlaying || e.start()) : Util.ua().android || (e.bigDatas.push(i), e.startBig()), e.isInit || (e.bindEvent(), e.isInit = !0))) : (e.isGetGift = !0, void e.getAllInfo())
    },
    getAllInfo: function() {
        var i = this;
        Common.ajax({
            url: "/mobile/gift_list",
            success: function(e) {
                e && 1 * e.error_code === 0 ? i.giftInfoList = e.data: console.log(e.error_msg)
            }
        })
    },
    getGiftPic: function(i) {
        for (var e = this,
        t = "",
        n = 0,
        o = e.giftInfoList.length; n < o; n++) if (e.giftInfoList[n].id == i) {
            t = e.giftInfoList[n].image;
            break
        }
        return t
    },
    start: function() {
        var i = this,
        e = i.datas,
        t = $("#" + i.container).children("li");
        t.each(function(n, o) {
            var a = $(o);
            if ("false" === a.attr("data-playing") && e.length > 0) {
                for (var s = null,
                r = i.getCurIds(), d = 0, l = e.length; d < l; d++) {
                    var c = e[d];
                    if (r.indexOf(c.fromId) == -1) {
                        s = e.splice(d, 1)[0];
                        break
                    }
                }
                (i.showOne(a, s), n == t.length - 1 && (i.isAllPlaying = !0))
            }
        })
    },
    showOne: function(i, e) {
        var t = this;
        i.find(".name").html(e.nick || ""),
        i.find(".giftType").html(e.gift_type),
        1 * e.num === 0 && (e.num = 1),
        i.find(".star").attr("data-num", e.num).html(""),
        i.find(".giftImg img").attr("src", e.gift_image),
        i.find(".headImg img").attr("src", e.head_image);
        var o = i.attr("data-fromId");
        if (o == e.fromId) {
            console.log(11);
            i.find(".pride").addClass("show");
            var a = i.find(".star");
            a.addClass("zoomIn").show().html("X" + (a.attr("data-num") || 0)),
            setTimeout(function() {},
            50),
            function(i) {
                setTimeout(function() {
                    t.next(i)
                },
                t.duration)
            } (i)
        } else i.find(".pride").removeClass("show"),
        setTimeout(function() {
            i.find(".pride").addClass("show"),
            i.find(".giftImg").removeClass("bounceInLeft"),
            setTimeout(function() {
                i.find(".giftImg").addClass("bounceInLeft")
            },
            50),
            function(i) {
                setTimeout(function() {
                    t.next(i)
                },
                t.duration)
            } (i)
        },
        30);
        i.attr("data-playing", "true").attr("data-fromId", e.fromId)
    },
    next: function(i) {
        var e = this,
        t = e.datas,
        n = (i.index(), i.attr("data-fromId")),
        o = null,
        a = e.getCurIds();
        if (0 == t.length || !t) return e.hideOne(i),
        void(e.isAllPlaying = !1);
        if (t[0].fromId == n) o = t.shift();
        else {
            var s = t.length;
            if (t.length > 1) for (var r = 1; r < s; r++) if (a.indexOf(t[r].fromId) == -1) {
                o = t.splice(r, 1)[0];
                break
            }
        }
        o ? e.showOne(i, o) : (e.hideOne(i), e.isAllPlaying = !1)
    },
    getCurIds: function() {
        var i = this,
        e = [];
        return $("#" + i.container).children("li").each(function(i, t) {
            var n = $(t),
            o = 1 * n.attr("data-fromId");
            o && e.indexOf(o) == -1 && e.push(1 * o)
        }),
        e
    },
    hideOne: function(i) {
        i.find(".pride").removeClass("show"),
        i.attr("data-playing", "false").removeAttr("data-fromId")
    },
    clearTimer: function(i) {
        clearTimeout(i),
        i = null
    },
    startBig: function() {
        var i = this,
        e = i.bigDatas;
        if (!e || 0 == e.length) return void i.bigContainer.hide();
        if (!i.isBigPlaying) {
            var t = e.shift(),
            n = t.giftId,
            o = $("#big-gift-box-" + n);
            if (!o.length) return void console.error("big gift id box is null");
            $("#live-bg .play-btn").is(":hidden") ? i.bigContainer.show() : i.bigContainer.hide(),
            o.find(".u-name").html(t.nick),
            o.find(".b-gift-type").html("送一个" + t.type),
            o.addClass("show"),
            i.isBigPlaying = !0
        }
    },
    bindEvent: function() {
        var i = this;
        $("#" + i.container).find(".star").bind("webkitAnimationStart",
        function() {}),
        $("#" + i.container).find(".giftImg").bind("webkitAnimationEnd",
        function() {
            var i = $(this).closest("li").find(".star");
            i.addClass("zoomIn").show().html("X" + (i.attr("data-num") || 0))
        }),
        $("#" + i.container).find(".star").bind("webkitAnimationEnd",
        function() {
            $(this).removeClass("zoomIn")
        }),
        i.bigContainer.on("webkitAnimationEnd ",
        function(e) {
            console.log("bigContainer webkitAnimationEnd");
            var t = $(e.target);
            if (console.log(t), t.hasClass("big-gift-box") || t.hasClass("animate-main")) {
                var n = t.hasClass("big-gift-box") ? t: t.closest(".big-gift-box");
                n.removeClass("show"),
                setTimeout(function() {
                    i.isBigPlaying = !1,
                    i.startBig()
                },
                50)
            }
        })
    }
},
PlayControl = {
    player: null,
    playerFromRecordList: null,
    isRecordList: 0,
    mediaInfo: null,
    bufferStart: 0,
    logBufferState: "",
    logBufferTmpNum: 0,
    logTimer: null,
    conf: "",
    opt: {},
    isAndroid: /Android/i.test(navigator.userAgent),
    isSafari: /Safari/i.test(navigator.userAgent),
    auto_height: "",
    init: function(i) {
        var e = this;
        return e.conf = i || {},
        e.bindEvent(),
        e
    },
    setUp: function(i, e) {
        var t = this;
        t.isRecordList = e || 0,
        t.bufferStart = 0;
        var n = 640 * $(window).width() / 368;
        t.auto_height = t.isAndroid ? n: "100%";
        var o = {
            type: "mp4",
            width: $(window).width(),
            height: 667,
            image: "",
            stretching: "fill",
            controls: !1,
            primary: "html5",
            events: {
                onReady: t.onReady,
                onPlay: t.onPlay,
                onPause: t.onPause,
                onBuffer: t.onBuffer,
                onDisplayClick: t.onDisplayClick,
                onSetupError: t.onSetupError,
                onComplete: t.onComplete,
                onIdle: t.onIdle,
                onBeforeComplete: t.onBeforeComplete,
                onError: t.onError
            }
        };
        $.extend(!0, o, i || {}),
        t.opt = o,
        console.log("player defaults", o),
        t.player = jwplayer("container").setup(o)
    },
    onReady: function() {
        console.log("onReady", (new Date).getTime());
        var i = $("#container").find("video");
     	i.attr({
            "x5-video-player-type": "h5",
            "x5-video-player-fullscreen": "true"
        }),
        i.attr("playsinline", !0);
    },
    onPlay: function() {
         console.log("onplay", (new Date).getTime()),
        $("#js-player-loading").hide();
        var i = $("#container").find("video");
        i.attr({
            "x5-video-player-type": "h5",
            "x5-video-player-fullscreen": "true"
        }),
        i.attr("playsinline", !0);
        var e = PlayControl;
        // t = e.conf.media_info;
        // if (e.bufferStart) {
        //     var n = e.opt.file || e.opt.playlist && e.opt.playlist && e.opt.playlist[0].sources[0].file,
        //     o = {
        //         video_loadtime: (new Date).getTime() - e.bufferStart,
        //         video_status: e.opt.status,
        //         video_url: n || ""
        //     };
        //     Common.trace({
        //         report_type: "qa",
        //         other: o
        //     }),
        //     e.bufferStart = 0
        // }
        $("#live-bg").show().attr("class", "is-play"),
        $("video") && (device=="android") && setTimeout(function() {
            var i = $("video").height();
            $("#top").height(window.innerHeight),
            i > e.conf.winHeight;
            var t = parseInt(window.innerHeight - $("#top").height()) + 15;
            $(".btn-box").css("bottom", t),
            $("#topBom").css("bottom", t)
        },
        200),
        $("#js-top-id").show(),
        $("#js-looked-num").show(),
        $(".js-user-info-con").removeClass("up"),
        1 == 1 ? ($(".btn-box").show(), e.conf.view_uid > 0) : ($(".btn-box").hide(), $("#hf-text").hide()),
        $("#topBom").show(),
        setTimeout(function() {
            $("#msg_box").show(),
            $("#js-gift-show-container").show()
        },
        50),
        $("#bestTop").hide()
    },
    onPause: function() {
        console.log("onpause");
        var i = PlayControl;
        i.conf.media_info;
        if (i.logBufferTmpNum = 0, $("#live-bg").attr("class", "is-pause"), $(".user,#js-top-id").hide(), i.conf.ua.isAndroid) {
            var e = $("video") && $("video").height() || 0;
            e > i.conf.winHeight && setTimeout(function() {
                $("#top").height(window.innerHeight)
            },
            100)
        }
        $(".btn-box,#msg_box,#js-gift-show-container").hide(),
        $("#topBom").hide(),
        $("#bestTop").show()
    },
    onBuffer: function() {
        var i = PlayControl;
        console.log("onBuffer", (new Date).getTime()),
        $("#js-player-loading").show(),
        i.bufferStart = (new Date).getTime(),
        i.logTimer || (i.logTimer = setInterval(function() {
            i.logBufferTimes()
        },
        1500))
    },
    onDisplayClick: function() {
        console.log("onDisplayClick", (new Date).getTime());
        var i = PlayControl,
        e = i.conf.media_info;
        $(window).scrollTop(0),
        0 == i.isRecordList && 1 == 1 && ($(".btn-box").show(), $(".talk-box").hide());
        var t = i.player.getState();
        "PLAYING" == t ? 1 != e.status && i.player.pause() : "PAUSED" == t || "IDLE" == t ? i.player.play() : console.log("otherState", t)
    },
    onSetupError: function() {
        console.log("onSetupError", arguments)
    },
    onComplete: function() {
        var i = PlayControl,
        e = media_info;
        window.console && console.log("onComplete"),
        $(document).trigger("living:oncomplete")
    },
    onIdle: function() {
        console.log("onIdle", arguments)
    },
    onBeforeComplete: function() {
        console.log("onBeforeComplete", arguments)
    },
    onError: function() {
        $("#js-player-loading").hide(),
        console.error("player onError", arguments)
    },
    logBufferTimes: function() {
        // 缓冲时间
        var i = this,
        e = i.player.getState();
        if ("BUFFERING" == e) if (i.logBufferTmpNum > 0 && "playing" == i.logBufferState) {
            var t = i.opt.file || i.opt.playlist && i.opt.playlist && i.opt.playlist[0].sources[0].file;
            Common.trace({
                report_type: "qa",
                other: {
                    video_url: t || ""
                }
            }),
            i.logBufferState = "buffer"
        } else i.logBufferTmpNum = i.logBufferTmpNum + 1;
        else "PLAYING" == e && (i.logBufferState = "playing")
    },
    bindEvent: function() {
        var i = this;
        $("#live-bg").on("click",
        function(e) {
            $(this).hasClass("is-finished") || i.onDisplayClick()
        }),
        $(".shadd3").click(function() {
            i.onDisplayClick(),
            $(".shadd3").hide()
        })
    }
},
Page = {
    back_video: 0,
    ua: {},
    isInit: !1,
    gConfig: "",
    queryData: "",
    playerObj: "",
    chatObj: "",
    giftObj: "",
    wrap: $("#js-all-wrap"),
    isLogin: !1,
    needPlayBeforeLoaded: !1,
    init: function() {
        var i = this;
        i.initSize(),
        i.gConfig.winWidth = i.wrap.width(),i.gConfig.winHeight = $(window).height(),
        i.playerObj = PlayControl.init(i.gConfig),
        i.initStatus(),
        i.isInit || (i.isInit = !0, i.bindEvent())
 	},
 	initSize: function() {
        $("#top").height($(window).height());
    },
    initStatus: function() {
        var i = this,
        e = media_info;
        e.live_in == 0 ? i.renderNoLiveAndRecord() : (i.renderLiveOrRecord(), i.initPlay()),
        i.updateUserNum(e.watch_number, e.live_in)

    },
 	initPlay: function() {
        var i = this, live_in = Number(media_info.live_in);
        switch(live_in){
            case 1:
                t = {
                    file: media_info.file_living,
                    image: media_info.image
                };
                break;
            case 3:
                t = {
                    file: media_info.file_playback,
                    image: media_info.image
                };
                break;
            default:
                t = {
                    file: '',
                    image: media_info.image
                };
                break;
        }

        // // 测试数据
        // t = {
        //     file: "http://200022096.vod.myqcloud.com/200022096_81a18c95b6894109a7ef689f00135c49.f0.mp4",
        //     image: ""
        // };
        t.status = 1,
        i.playerObj.setUp(t)
        // i.startPlay()
    },
    renderNoLiveAndRecord: function() {
        $(".no-player-text,.bg_user_pic").show(),
        $("#topBom").hide(),
        $("#hf-text").hide(),
        $("#msg_box").html("").hide(),
        $(".btn-box").hide(),
        $("#live-bg").show().attr("class", "is-finished"),
        $("#top").height("16rem"),
        $("#bestTop").css("position", "fixed"),
        $("#top2").removeClass("top"),
        $("#shadow").show(),
        $("#js-gift-show-container").hide()
    },
 	renderLiveOrRecord: function() {
        $("#live-bg").show(),
        $("#shadow").hide(),
        $("#topBom").show()
    },
 	startPlay: function() {
        var i = this;
        i.playerObj && i.playerObj.player && i.playerObj.player.play(),
        i.initChatRoom()
    },
    updateUserNum: function(i, e) {
        var t = this,
        i = i || 0;
        $("#user_num").html(i + "人看过").show(),
        $("#user_num2").html(i);
        var n = $("#js-looked-num");
        return isAndroid && n.addClass("android"),
        void 1 == e ? void n.removeClass("is-live").addClass("is-record") : void(1 == e ? n.removeClass("is-record").addClass("is-live") : n.removeClass("is-live").addClass("is-record"))
    },
    initGift: function() {
        // 初始化礼物
        var i = this,
        e = i.gConfig;
        i.giftObj = new gift_manager(e, i),
        i.ua.isWeixin,
        i.giftObj.init()
    },
    initChatRoom: function() {
        // 初始化聊天区
        var i = this;
        console.log("initChatRoom");

        if(typeof loginInfo !== 'undefined'){
            var chatObj = new chat();
            im_message.init(loginInfo, chatObj.listeners);
        }
    },
    showLoading: function(i) {
        var e = $("#js-mask-loading");
        i ? e.show() : e.hide()
    },
    bindEvent: function() {
        var i = this;
        $("#live-bg").on("click",
        function(e) {
           setTimeout(function() {
                i.initChatRoom(),
                $("#js-gift-show-container").show()
            },
            100)
            // i.chatObj && i.chatObj.socket && i.chatObj.socket.socket.connected,
            // i.userLog({
            //     click_id: "play_btn"
            // })
        }),
        $("#shadow").click(function() {
            i.playerObj || (i.needPlayBeforeLoaded = !0, console.log("提前play"), i.showLoading(!0))
        }),
        $(document).on("living:userNumUpdate",
        function(e, t) {
            i.updateUserNum(t.num)
        }),
        $(document).on("living:oncomplete",
        function(e, t) {
            i.renderNoLiveAndRecord()
        }),
        $(document).on("recordStartInit",
        function(e, t) {
            console.log(t),
            i.renderRecord(t),
            i.chatObj && i.chatObj.leaveRoom()
        }),
        $(window).resize(function() {
            setTimeout(function() {
                window.scrollTo(0, 0)
            },
            100)
        }),
        $(".js-btn-open-app").on("click",
        function(e) {
            var t = $(this);
            t.attr("data-id") && i.userLog({
                click_id: t.attr("data-id")
            }),
            i.goApp(!0, t),
            e.preventDefault()
        })
    }
};

// 分享页面
$(function(){

    Page.init();

    // 点击弹出下载提示窗
    $(".show_pop_wp").on('click',function(){
        $(".pop_wp").css({display:"flex"});
    });
    $(".pop_close").on('click',function(){
        $(".pop_wp").css({display:"none"});
    });


    $(".js-list-tab").find(".t-one").on('click',function(){
        $(this).addClass('active').siblings().removeClass('active');
        $(".tab-con-list").find(".one-page").eq($(this).index()).show().siblings().hide();
    });
   
});

// 微信分享
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