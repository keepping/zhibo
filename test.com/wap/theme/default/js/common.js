// 验证
$.minLength = function(value, length , isByte) {
    var strLength = $.trim(value).length;
    if(isByte)
        strLength = $.getStringLength(value);
        
    return strLength >= length;
};
$.maxLength = function(value, length , isByte) {
    var strLength = $.trim(value).length;
    if(isByte)
        strLength = $.getStringLength(value);
        
    return strLength <= length;
};
$.getStringLength=function(str)
{
    str = $.trim(str);
    if(str=="")
        return 0; 
        
    var length=0; 
    for(var i=0;i <str.length;i++) 
    { 
        if(str.charCodeAt(i)>255)
            length+=2; 
        else
            length++; 
    }
    return length;
};
$.checkMobilePhone = function(value){
    if($.trim(value)!='')
        return /^\d{6,}$/i.test($.trim(value));
    else
        return true;
};
$.checkEmail = function(val){
    var reg = /^\w+((-\w+)|(\.\w+))*\@[A-Za-z0-9]+((\.|-)[A-Za-z0-9]+)*\.[A-Za-z0-9]+$/; 
    return reg.test(val);
};
//判断是否为整数
$.checkint=function isInteger(obj) {
    return obj%1 === 0
};
/**
 * 判断变量是否空值
 * undefined, null, '', false, 0, [], {} 均返回true，否则返回false
 */
$.checkEmpty = function(val){
    switch (typeof val){
        case 'undefined' : return true;
        case 'string'    : if($.trim(val).length == 0) return true; break;
        case 'boolean'   : if(!val) return true; break;
        case 'number'    : if(0 === val) return true; break;
        case 'object'    :
            if(null === val) return true;
            if(undefined !== val.length && val.length==0) return true;
            for(var k in val){return false;} return true;
            break;
    }
    return false;
};

// 限制只能输入金额
function amount(th){
    var regStrs = [
        ['^0(\\d+)$', '$1'], //禁止录入整数部分两位以上，但首位为0
        ['[^\\d\\.]+$', ''], //禁止录入任何非数字和点
        ['\\.(\\d?)\\.+', '.$1'], //禁止录入两个以上的点
        ['^(\\d+\\.\\d{2}).+', '$1'] //禁止录入小数点后两位以上
    ];
    for(i=0; i<regStrs.length; i++){
        var reg = new RegExp(regStrs[i][0]);
        th.value = th.value.replace(reg, regStrs[i][1]);
    }
}

/**
 * 判断变量是否空值
 * undefined, null, '', false, 0, [], {} 均返回true，否则返回false
 */
function empty(v){
    switch (typeof v){
        case 'undefined' : return true;
        case 'string'    : if($.trim(v).length == 0) return true; break;
        case 'boolean'   : if(!v) return true; break;
        case 'number'    : if(0 === v) return true; break;
        case 'object'    :
            if(null === v) return true;
            if(undefined !== v.length && v.length==0) return true;
            for(var k in v){return false;} return true;
            break;
    }
    return false;
}

/*
    下拉刷新
    url：刷新请求数据接口链接
    page：当前刷新的页面ID
    cls：刷新内容层的class
    content：当前触发刷新层class
    callback：执行回调
*/
function refresh(url,page,cls,content,callback){
    var refreshing = false;
    if (refreshing) return;
    refreshing =true;
    var query = new Object();
    query.p  =  1;
    query.page_size = 10;
    $.ajax({
        url:url,
        type:"post",
        data:query,
        dataType:"html",
        success:function(result){
            setTimeout(function(){
                refreshing = false;
                var tplElement = $('<div id="tmpHTML"></div>').html(result),
                htmlObject = tplElement.find("#"+page).find(cls),
                html = $(htmlObject).html();
                $("#"+page).find(cls).html(html);
                // html_list_length >= 10 ? document.querySelector(".m-infinite-scroll-preloader").innerHTML = '<div class="infinite-scroll-preloader"><div class="preloader"></div></div>' : document.querySelector(".m-infinite-scroll-preloader").innerHTML = '<div class="infinite-scroll-preloader data-null"><div class="preloader">暂无更多数据</div></div>';
                $.pullToRefreshDone(content);
                if(callback){
                    callback.call(this);
                }
            },500);
        },
        error:function(){
            refreshing = false;
            $.toast("请求失败！");
            $.pullToRefreshDone(content);
        }
    });
}

// ajax公用封装
var handleAjax;
if (!handleAjax) handleAjax = {};
(function (h) {
    h.ajax = function(url, param, dataType, ajaxType){
        // 利用了jquery延迟对象回调的方式对ajax封装，使用done()，fail()，always()等方法进行链式回调操作
        // 如果需要的参数更多，比如有跨域dataType需要设置为'jsonp'等等，也可以不做这一层封装，还是根据工程实际情况判断吧，重要的还是链式回调
        dataType ? dataType = dataType : dataType = '';
        ajaxType ? ajaxType = ajaxType : ajaxType = '';
        if(dataType == "html"){
            param ? param = param : param = '';
        }
        else{
            param ? param = $.extend(param, {post_type:'json', itype:'shop'}) : param = {post_type:'json', itype:'shop'};
        }

        return $.ajax({
            url: url,
            data: param || {},
            type: 'POST',
            dataType: dataType || 'json',
            beforeSend:function(){
                $.showIndicator();
            }
        });
    };
    h.handle=function(url, param, dataType, ajaxType){
        return h.ajax(url, param, dataType).then(function(result){
            // 成功回调
            $.hideIndicator();
            dataType ? dataType = dataType : dataType = '';
            ajaxType ? ajaxType = ajaxType : ajaxType = '';
            if(dataType == 'html' || ajaxType == 1){
                return result;
                // 直接返回要处理的数据，作为默认参数传入之后done()方法的回调

            }
            else{
                if(result.status == 1){
                    if(result.error)
                        return result.error;
                    else
                        return '操作成功';
                    // 直接返回要处理的数据，作为默认参数传入之后done()方法的回调
                }
                else{
                    return $.Deferred().reject(result.error ? result.error : "操作失败"); // 返回一个失败状态的deferred对象，把错误代码作为默认参数传入之后fail()方法的回调
                }
            }
        }, function(err){
            // 失败回调
            // $.hideIndicator();
            return err;
            // console.log(err.status); // 打印状态码
        });
    };
})(handleAjax);

// 倒计时
var setInterval_left_time = null;
var left_time = function(left_time,element){
    if(left_time){
        setInterval_left_time = setInterval(function() {
            if(left_time){
                var day  =  parseInt(left_time / 24 /3600);
                var hour = parseInt((left_time % (24 *3600)) / 3600);
                var min = parseInt((left_time % 3600) / 60);
                var sec = parseInt((left_time % 3600) % 60);
                var cc = document.getElementById(element);
                day<10 ? day="0"+day : day=day;
                hour<10 ? hour="0"+hour : hour=hour;
                min<10 ? min="0"+min : min=min;
                sec<10 ? sec="0"+sec : sec=sec;
                $(element).html(day+"天"+hour+"时"+min+"分"+sec+"秒");
                left_time--;
            }
            else{
                clearInterval(setInterval_left_time);
                location.reload();
                $.showPreloader();
            }
        }, 1000);
        document.addEventListener("visibilitychange", function (e) {
            clearInterval(setInterval_left_time);
            $(element).html("--天--时--分--秒");
        }, false);
    }
};

// 获取地址栏参数
function GetQueryString(name) { 
    var reg = new RegExp("(^|&)" + name + "=([^&]*)(&|$)", "i"); 
    var r = window.location.search.substr(1).match(reg); 
    if (r != null) return unescape(r[2]); return null; 
} 