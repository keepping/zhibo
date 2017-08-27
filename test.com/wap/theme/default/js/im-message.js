var curPlayAudio = null;
function onChangePlayAudio(playAudio) {
    if (curPlayAudio) {
        if (curPlayAudio != playAudio) {
            curPlayAudio.currentTime = 0;
            curPlayAudio.pause();
            curPlayAudio = playAudio;
        }
    } else {
        curPlayAudio = playAudio;
    }
}

//单击图片事件
function imageClick(imgObj) {
    var imgUrls = imgObj.src;
    var imgUrlArr = imgUrls.split("#"); //字符分割
    var smallImgUrl = imgUrlArr[0];//小图
    var bigImgUrl = imgUrlArr[1];//大图
    var oriImgUrl = imgUrlArr[2];//原图
    var html = '<img class="img-thumbnail" src="' + bigImgUrl + '" />';
    $.weeboxs.open(html, {animate:false, showButton:false, showCancel:false, showOk:false, modal: true, showTitle: false, clickClose: true});
}

function convertMsg(msg) {
    return convertMsgtoHtml(msg);
}

function convertMsgtoHtml(msg) {
    var html = "", elems, elem, type, content;
    elems = msg.getElems();//获取消息包含的元素数组
    for (var i in elems) {
        elem = elems[i];
        type = elem.getType();//获取元素类型
        content = elem.getContent();//获取元素对象
        switch (type) {
            case webim.MSG_ELEMENT_TYPE.TEXT:
                var text = content.getText();
                if(typeof html == 'object'){
                    html.text = text;
                } else {
                    html += text;
                }
                break;
            case webim.MSG_ELEMENT_TYPE.FACE:
                html += convertFaceMsgToHtml(content);
                break;
            case webim.MSG_ELEMENT_TYPE.IMAGE:
                return convertImageMsgToHtml(content);
            case webim.MSG_ELEMENT_TYPE.SOUND:
                return convertSoundMsgToHtml(content);
            case webim.MSG_ELEMENT_TYPE.FILE:
                html += convertFileMsgToHtml(content);
                break;
            case webim.MSG_ELEMENT_TYPE.LOCATION://暂不支持地理位置
                //html += convertLocationMsgToHtml(content);
                break;
            case webim.MSG_ELEMENT_TYPE.CUSTOM:
                html = convertCustomMsgToHtml(content);
                break;
            case webim.MSG_ELEMENT_TYPE.GROUP_TIP:
                // html += convertGroupTipMsgToHtml(content);
                break;
            default:
                webim.Log.error('未知消息元素类型: elemType=' + type);
                break;
        }
    }
    return html;
}

//解析语音消息元素
function convertSoundMsgToHtml(content) {
    var second = content.getSecond();//获取语音时长
    var downUrl = content.getDownUrl();
    if (webim.BROWSER_INFO.type == 'ie' && parseInt(webim.BROWSER_INFO.ver) <= 8) {
        return '[这是一条语音消息]demo暂不支持ie8(含)以下浏览器播放语音,语音URL:' + downUrl;
    }
    return '<audio src="' + downUrl + '" controls="controls" onplay="onChangePlayAudio(this)" preload="none"></audio>';
}

//解析图片消息元素
function convertImageMsgToHtml(content) {
    var smallImage = content.getImage(webim.IMAGE_TYPE.SMALL);//小图
    var bigImage = content.getImage(webim.IMAGE_TYPE.LARGE);//大图
    var oriImage = content.getImage(webim.IMAGE_TYPE.ORIGIN);//原图
    if (!bigImage) {
        bigImage = smallImage;
    }
    if (!oriImage) {
        oriImage = smallImage;
    }
    return	"<img src='" + smallImage.getUrl() + "#" + bigImage.getUrl() + "#" + oriImage.getUrl() + "' style='cursor: pointer;' id='" + content.getImageId() + "' bigImgUrl='" + bigImage.getUrl() + "' onclick='imageClick(this)' />";
}

function convertCustomMsgToHtml(content) {
    var data = content.getData();//自定义数据
    var desc = content.getDesc();//描述信息

    var msg = JSON.parse(data);

    var user_level, nick_name, text;
    switch (msg.type) {
        case 0: // 正常文字聊天消息
            user_level = msg.sender.user_level;
            nick_name = msg.sender.nick_name;
            text = msg.text;
            break;
        case 1: // 收到发送礼物消息
            user_level = msg.sender.user_level;
            nick_name = msg.sender.nick_name;
            var icon = msg.pc_icon || msg.icon;
            text = msg.desc + '&nbsp;<img class="icon" style="height:2em;position:absolute;" src="' + icon + '" />';
            break;
        case 2: // 收到弹幕消息
            user_level = msg.sender.user_level;
            nick_name = msg.sender.nick_name;
            text = msg.desc || msg.text;
            break;
        case 3: // 主播结束直播
            user_level = msg.sender.user_level;
            nick_name = msg.sender.nick_name;
            text = '直播结束';
            break;
        case 5: // 观众进入直播间消息
            if(typeof msg.sender.nick_name === 'undefined'){
                return;
            }
            user_level = 99;
            nick_name = '[直播消息]';
            text = '金光一闪，' + msg.sender.nick_name + '加入了...';
            break;
        case 6: // 观众离开直播间消息
            if(typeof msg.sender.nick_name === 'undefined'){
                return;
            }
            user_level = 99;
            nick_name = '[直播消息]';
            text = '白光闪闪，' + msg.sender.nick_name + '离开了...';
            break;
        case 7: // 主播结束直播消息，直播间内的人可收到
            if(msg.sender){
                user_level = msg.sender.user_level;
                nick_name = msg.sender.nick_name;
            } else {
                user_level = 99;
                nick_name = '[系统消息]';
            }
            text = msg.desc;
            break;
        case 8: // 红包消息
            user_level = msg.sender.user_level;
            nick_name = msg.sender.nick_name;
            text = msg.desc;
            break;
        case 9: // 直播消息
            user_level = 99;
            nick_name = "[直播消息]";
            text = msg.desc;
            break;
        case 10: // 主播离开
            user_level = msg.sender.user_level;
            nick_name = msg.sender.nick_name;
            text = msg.text;
            break;
        case 11: // 主播回来
            user_level = msg.sender.user_level;
            nick_name = msg.sender.nick_name;
            text = msg.text;
            break;
        case 12: // 点亮
            if (!msg.showMsg) {
                 //return;
            }
            user_level = msg.sender.user_level;
            nick_name = msg.sender.nick_name;
            text = '我点亮了';

            if(typeof Emotions[msg.imageName] !== 'undefined'){
                text += '<img class="icon" style="height:1.5em;" src="' + Emotions[msg.imageName] + '" />';
            } else {
                text += '[' + msg.imageName + ']';
            }
            break;
        case 17: // 踢人
            user_level = msg.sender.user_level;
            nick_name = msg.sender.nick_name;
            text = msg.desc;
            break;
        case 20: // 私聊文字消息
            user_level = msg.sender.user_level;
            nick_name = msg.sender.nick_name;
            text = msg.text.replace(/\[(face\d+)\]/g, function(word, g1){
                if(typeof Emotions[g1] !== 'undefined'){
                    return '<img class="icon" style="height:1.5em;" src="' + Emotions[g1] + '" />';
                }
                
                return word;
            });
            break;
        case 21: // 私聊语音消息
            user_level = msg.sender.user_level;
            nick_name = msg.sender.nick_name;
            text = msg.desc;
            break;
        case 22: // 私聊图片消息
            user_level = msg.sender.user_level;
            nick_name = msg.sender.nick_name;
            text = msg.desc;
            break;
        case 23: // 私聊礼物消息
            user_level = msg.sender.user_level;
            nick_name = msg.sender.nick_name;
            text = msg.conversationDesc + msg.from_msg + '&nbsp;<img class="icon" style="height:2em;" src="' + msg.prop_icon + '" />';
            break;
        case 18: // 任何主播的结束，服务端都会推这条消息下来，用于更新列表状态
        default:
            return;
    }

    if(msg.sender && msg.sender.user_id == loginInfo.identifier) {
        if(msg.sender.user_level > loginInfo.user_level){
            loginInfo.user_level = msg.sender.user_level;
        } else {
            user_level = loginInfo.user_level;
        }
    }

    return {"type": msg.type, "user_level": user_level, "nick_name": nick_name, "text": text, "ext": msg};
}

var im_message = {

    /**
     * 入口，所有的业务必需先调用该方法登录
     * @param loginInfo   //sdk指定的标准接入的用户数据
     * @param listeners  //与webim的中的listeners不同，根据renmai业务封装的监听
     * {loginSuccess,loginError,connectSuccess,disconnect,reconnect,recieveMsg,sendMsgOk,sendMsgFail}
     *
     * 关于listeners监听事件的回调参数说明
     * 1. loginSuccess 无
     * 2. loginError
     * 3. connectSuccess
     * 4. disconnect
     * 5. reconnect
     * 6. recieveMsg: webim.Msg列表
     * 7. sendMsgOk 无
     * 8. sendMsgFail string 错误信息
     *
     *
     *
     */
    init: function (loginInfo, listeners) {

        var listeners = this.listeners = {
            loginSuccess: listeners.loginSuccess||function(){},
            loginError: listeners.loginError||function(){},
            connectSuccess: listeners.connectSuccess||function(){},
            disconnect: listeners.disconnect||function(){},
            reconnect: listeners.reconnect||function(){},
            recieveMsg: listeners.recieveMsg||function(){},
            recieveGroupMsg: listeners.recieveGroupMsg||function(){},
            sendMsgOk: listeners.sendMsgOk||function(){},
            sendMsgFail: listeners.sendMsgFail||function(){}
        };

        //可选项
        var options  = {
            'isAccessFormalEnv': true,//是否访问正式环境，默认访问正式，选填
            'isLogOn': false//是否开启控制台打印日志,默认开启，选填
        };

        //IE9(含)以下浏览器用到的jsonp回调函数
        var jsonpCallback = function(rspData) {
            webim.setJsonpLastRspData(rspData);
        };

        //监听连接状态回调变化事件
        var onConnNotify = function(resp){
            switch(resp.ErrorCode){
                case webim.CONNECTION_STATUS.ON:
                    listeners.connectSuccess.call(listeners,resp);
                    break;
                case webim.CONNECTION_STATUS.OFF:
                    listeners.disconnect.call(listeners,resp);
                    break;
                case webim.CONNECTION_STATUS.RECONNECT:
                    listeners.reconnect.call(listeners,resp);
                    break;
                default:
                    //webim.Log.error('未知连接状态: ='+resp.ErrorInfo);
                    break;
            }
        };

        //登录后需要监听的事件
        var webim_listeners = {
            "onConnNotify": onConnNotify, //监听连接状态回调变化事件,必填
            "jsonpCallback": jsonpCallback, //IE9(含)以下浏览器用到的jsonp回调函数，
            "onMsgNotify": listeners.recieveMsg, //监听新消息(私聊，普通群(非直播聊天室)消息，全员推送消息)事件，必填
            "onBigGroupMsgNotify": listeners.recieveGroupMsg, //监听新消息(直播聊天室)事件，直播场景下必填
            /* 以下事件本系统业务暂不监听
             "onGroupSystemNotifys": onGroupSystemNotifys, //监听（多终端同步）群系统消息事件，如果不需要监听，可不填
             "onGroupInfoChangeNotify": onGroupInfoChangeNotify, //监听群资料变化事件，选填
             "onFriendSystemNotifys": onFriendSystemNotifys, //监听好友系统通知事件，选填
             "onProfileSystemNotifys": onProfileSystemNotifys//监听资料系统（自己或好友）通知事件，选填
             */
        };
        
        webim.login(loginInfo, webim_listeners, options,
            function (resp) {
                listeners.loginSuccess.call(listeners);
            },
            function (err) {
                listeners.loginError.call(listeners,err);
            }
        );
    },

    //发文本消息
    sendTextMsg:function(to_uid, text, type, is_c2c){
        var type = type || 0;
        if(! text){
            return;
        }
        
        var selSess = this.selSess;
        if(! this.selSess || is_c2c){
            var selType = webim.SESSION_TYPE.C2C; //私聊
            selSess = new webim.Session(selType, to_uid);
        }
        
        var isSend = true;//是否为自己发送
        var seq = -1;//消息序列，-1表示sdk自动生成，用于去重
        var random = Math.round(Math.random() * 4294967296);//消息随机数，用于去重
        var msgTime = Math.round(new Date().getTime() / 1000);//消息时间戳
        var subType = webim.C2C_MSG_SUB_TYPE.COMMON; //普通消息
        var from_uid = loginInfo.identifier;
        if(from_uid == '' || from_uid == to_uid){
            return;
        }

        var msg = new webim.Msg(
            selSess, isSend, seq, random,msgTime,from_uid,subType,'',loginInfo.identifierNick
        );

        if(type == 9){
            msg.addCustom(new webim.Msg.Elem.Custom(JSON.stringify({
                "type": type,
                "desc": text,
                "sender": {
                    "user_id": loginInfo.identifier,
                    "nick_name": loginInfo.identifierNick,
                    "user_level": loginInfo.user_level,
                    "head_image": loginInfo.head_image,
                },
            })));
        } else {
            msg.addText(new webim.Msg.Elem.Text(text));
            msg.addCustom(new webim.Msg.Elem.Custom(JSON.stringify({
                "type": type,
                "text": text,
                "sender": {
                    "user_id": loginInfo.identifier,
                    "nick_name": loginInfo.identifierNick,
                    "user_level": loginInfo.user_level,
                    "head_image": loginInfo.head_image,
                },
            })));
        }

        var _this = this;
        webim.sendMsg(msg, function (resp) {
            _this.listeners.sendMsgOk.call(_this.listeners, msg);
        }, function (err) {
            var error_str= err.SrcErrorInfo.replace(/secure check error! beat word/, "包含敏感词汇");;
            _this.listeners.sendMsgFail.call(_this.listeners,error_str);
        });
    },

    //主动收消息
    recieveMsg: function(to_uid, recieveMsg){
        var options = {
            'Peer_Account': to_uid.toString(), //好友帐号
            'MaxCnt': 15, //拉取消息条数
            'LastMsgTime': 0, //最近的消息时间，即从这个时间点向前拉取历史消息
            'MsgKey': '',
        };
        this.selToID = to_uid;
        webim.getC2CHistoryMsgs(options, function (resp) {
            
            if(recieveMsg){
                resp.MsgList.sort(function(a, b) { 
                    return a.getTime() < b.getTime() ? -1 : 1;
                });

                recieveMsg(resp.MsgList);
            }
        }, function(resp){
            console.log(resp);
        });
    },

    //主动接收会话
    recieveSession: function(recieveMsg){
        webim.syncMsgs(recieveMsg);
    },

    // 加入群聊天
    applyJoinBigGroup: function (groupId) {
        if(! groupId){
            return;
        }

        var options = {
            'GroupId': groupId//群id
        };
        var _this = this;
        webim.applyJoinBigGroup(
            options,
            function (resp) {
                //JoinedSuccess:加入成功; WaitAdminApproval:等待管理员审批
                if (resp.JoinedStatus && resp.JoinedStatus == 'JoinedSuccess') {
                    var selType = webim.SESSION_TYPE.GROUP;
                    var maxLen = webim.MSG_MAX_LENGTH.GROUP;
                    var selSessHeadUrl = 'img/2017.jpg';
                    _this.selSess = new webim.Session(selType, groupId, groupId, selSessHeadUrl, Math.round(new Date().getTime() / 1000));
                    if(typeof loginInfo.identifierNick === 'undefined')
                    {
                        return;
                    }
                    _this.sendTextMsg(groupId, '金光一闪，' + loginInfo.identifierNick + '加入了...', 9);
                    webim.Log.info('进群成功');
                } else {
                    webim.Log.info('进群失败');
                }
            },
            function (err) {
                $.showErr(err.ErrorInfo);
            }
        );
    },
};