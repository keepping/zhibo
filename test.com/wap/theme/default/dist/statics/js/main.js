
var vm_login = new Vue({
    el: '#vscope-login',
    data: {
        mobile: '',
        verify_coder: '',
        is_disabled: false,
        code_lefttime: 0,
        code_timeer: null
    },
    methods: {
        login: function(ajax_url){
            var self = this;
            if(! self.check()){
                return false;
            }
            // 登录
            var query = new Object();
            query.mobile = self.mobile;
            query.verify_coder = self.verify_coder;
            $.ajax({
                url:ajax_url,
                data:query,
                type:"POST",
                dataType:"json",
                success:function(result){
                    if(result.status == 1){
                        //返回分享页
                        if(result.error){
                            $.toast(result.error,1000);
                            if(result.is_url){
                                setTimeout(function(){
                                    location.href= result.url;
                                },1000);
                            }
                        }else{
                            if(result.is_url){
                               location.href= result.url;
                            }
                        }
                    }
                    else{
                        if(result.error){
                            $.toast(result.error,1000);
                            if(result.is_url){
                                setTimeout(function(){
                                    location.href= result.url;
                                },1000);
                            }
                        }
                        else{
                            $.toast("操作失败");
                        }
                    }
                }
            });
        },
        check: function(val, event){
        // 验证表单
            var self = this;
            if($.trim(self.mobile).length == 0)
            {         
                $.toast("手机号码不能为空");
                return false;
            }
            if(!$.checkMobilePhone(self.mobile))
            {   
                $.toast("手机号码格式错误");
                return false;
            }
            if(!$.maxLength(self.mobile,11,true))
            {     
                $.toast("长度不能超过11位");
                return false;
            }
            else{
                return true;
            }
        },
        send_code: function(event){
        // 发送验证码
            var self = this;
            if(self.is_disabled){
                $.toast("发送速度太快了");
                return false; 
            }
            else{
                var thiscountdown=$("#j-send-code"); 
                var query = new Object();
                query.mobile = self.mobile;
                $.ajax({
                    url:APP_ROOT+"/mapi/index.php?ctl=login&act=send_mobile_verify",
                    data:query,
                    type:"POST",
                    dataType:"json",
                    success:function(result){
                        if(result.status == 1){    
                            countdown = 60;
                            // 验证码倒计时
                            vm_login.code_lefttime = 60;
                            self.code_lefttime_fuc("#j-send-code", self.code_lefttime);
                            // $.showSuccess(result.info);
                            return false;
                        }
                        else{
                            $.toast(result.error);
                            return false;
                        }
                  }
                });
            }
        },
        code_lefttime_fuc: function(verify_name,code_lefttime){
        // 验证码倒计时
            var self = this;
            clearTimeout(self.code_timeer);
            $(verify_name).html("重新发送 "+code_lefttime);
            code_lefttime--;
            if(code_lefttime >0){
                $(verify_name).attr("disabled","disabled");
                self.is_disabled=true;
                vm_login.code_timeer = setTimeout(function(){self.code_lefttime_fuc(verify_name,code_lefttime);},1000);
            }
            else{
                code_lefttime = 60;
                self.is_disabled=false;
                $(verify_name).removeAttr("disabled");
                $(verify_name).html("发送验证码");
            }
        }
    }
});
function infinite_scroll($page,ajax_url,cls,vm_paging,func) {
    if (loading || vm_paging.page>total_page){
        $(".content-inner").css({paddingBottom:"0"});
        return;
    }
    loading = true;

    handleAjax.handle(ajax_url,{page:vm_paging.page},"html").done(function(result){
        var tplElement = $('<div id="tmpHTML"></div>').html(result),
        htmlObject = tplElement.find(cls),
        html = $(htmlObject).html();
        $(html).find(".total_page").remove();
        vm_paging.page++;
        loading = false;
        $($page).find(cls).append(html);
        $.refreshScroller();
        if(func!=null){
            func();
        }
        // $('.lazyload').picLazyLoad();
    }).fail(function(err){
        $.toast(err);
    });
}

function pull_refresh($page,ajax_url,cls,vm_paging,callback){
    var loading = false;
    if (loading) return;
    loading =true;
    

    handleAjax.handle(ajax_url,'',"html").done(function(result){
        refreshing = false;
        var tplElement = $('<div id="tmpHTML"></div>').html(result),
        htmlObject = tplElement.find(cls),
        list_ele = $($page).find(cls),
        html = $(htmlObject).html();
        value = html.replace(/\s+/g,"");

        var result = $(result).find(".content").html(), total_page = htmlObject.find("input[name='total_page']").val();
        loading =false;
        vm_paging.page = 2;
        vm_paging.total_page = total_page;
        setTimeout(function() {

            list_ele.addClass('animated fadeInUp').html(value.length > 0 ? html : '<div style="text-align:center;color:#999;font-size:0.75rem;">暂无数据</div>');

            setTimeout(function(){
                list_ele.removeClass('fadeInUp');
            }, 1000);

            // 加载完毕需要重置
            $.pullToRefreshDone('.pull-to-refresh-content');
            $(".pull-to-refcontainerresh-layer").css({"visibility":"visible"});

            // 初始化分页数据
            page = 2;

            // 初始化懒加载图片
            // $('.lazyload').picLazyLoad();

            if(typeof(callback) == 'function'){
                callback.call(this);
            }
        }, 300);

    }).fail(function(err){
        $.toast(err);
    });
}
// 分销商品列表
$(document).on("pageInit","#page-shop-distribution_goods_list", function(e, pageId, $page) {
    init_paramet();
    var vm_paging = new Vue({
        el: "#vscope-paging",
        data: {
            total_page: total_page,
            page: page,
        }
    });

    $(document).on('click', '.J-distribution', function(){
        var self = $(this);
        if(self.hasClass('is_distribution')) return;
        var goods_id = self.attr("data-id");
        handleAjax.handle(TMPL+"index.php?ctl=shop&act=add_distribution_goods",{goods_id:goods_id}).done(function(resp){
            self.addClass('is_distribution');
            $.toast(resp,1000);
            setTimeout(function(){
               self.html('已添加分销');
            },1000);
        }).fail(function(err){
            $.toast(err,1000);
        });
    });

    // 无限滚动
    $($page).on('infinite', function(e) {
        infinite_scroll($page,ajax_url,".shop-list",vm_paging);
    });

    //下拉刷新
   $($page).find(".pull-to-refresh-content").on('refresh', function(e) {
        pull_refresh($page,ajax_url,".shop-list",vm_paging);
        $("#search").val('');
        var all_options = document.getElementById("goods_cate").options;
        for (i=0; i<all_options.length; i++){
          if (all_options[i].id == 0)  // 根据option标签的ID来进行判断  测试的代码这里是两个等号
          {
             all_options[i].selected = true;
          }
       }
    });
    // 初始化参数
    function init_paramet(){
        // var urlinfo = window.location.href; //获取url  
        // paramet.content = decodeURI(urlinfo.split("&")[2].split("=")[1]);
        new_paramet = paramet.options ? '&options='+paramet.options : '',
        // new_paramet = paramet.content ? new_paramet+'&content='+paramet.content : new_paramet,
        // new_paramet = paramet.page ? new_paramet+'&page='+paramet.page : new_paramet,
        ajax_url = APP_ROOT+"/wap/index.php?ctl=shop&act=distribution_goods_list"+new_paramet;
    };
    $(function(){
        var option_url1 = TMPL + "index.php?ctl=shop&act=distribution_goods_list&options=1&cate_id=" + paramet.cate_id;
        var option_url2 = TMPL + "index.php?ctl=shop&act=distribution_goods_list&options=2&cate_id=" + paramet.cate_id;
        var option_url3 = TMPL + "index.php?ctl=shop&act=distribution_goods_list&options=3&cate_id=" + paramet.cate_id;
        $(".option1").attr("href",option_url1);
        $(".option2").attr("href",option_url2);
        $(".option3").attr("href",option_url3);
    });
    //分类筛选
    $(".select").change(function(){
        var self= $('option').not(function(){ return !this.selected });
        var id = self.attr("data-id");
        location.href = TMPL + "index.php?ctl=shop&act=distribution_goods_list&cate_id=" + id + "&options=" + paramet.options + "&page=" + data.page;
    });
    if(paramet.cate_id){
        if($("#search option").attr("data-id") == paramet.cate_id){
            $(this).attr("selected", true);
        }
    }
    //搜索关键字
    $(document).on('click', '.J-search', function(){
        var content = $("#search").val();
        console.log(data);
        location.href = TMPL + "index.php?ctl=shop&act=distribution_goods_list&content=" + content + "&options=" + paramet.options + "&page=" + data.page;
    });
});

$(document).on("pageInit","#page-shop-goods_details", function(e, pageId, $page) {
	$(document).on('click', '.J-anchor', function(){
			handleAjax.handle(TMPL+"index.php?ctl=shop&act=add_distribution_goods",{goods_id:goods_id}).done(function(resp){
            $.toast(resp,1000);
            setTimeout(function(){
               self.html('已添加分销');
            },1000);
        }).fail(function(err){
            $.toast(err,1000);
        });
	});
});
$(document).on("pageInit", "#page-shop-new_address", function(e, pageId, $page) {
    $("#city-picker").cityPicker({
        
            //value: ['四川', '内江', '东兴区']
    });
    $("#city-picker").click(function(){
        $("input:not(this)").blur();
    });
    function objBlur(obj, time){
    if(typeof obj != 'string') return false;
    var obj = document.getElementById(obj),
    time = time || 300,
    docTouchend = function(event){
        if(event.target!= obj){
            setTimeout(function(){
                 obj.blur();
                document.removeEventListener('touchend', docTouchend,false);
            },time);
        }
    };
    if(obj){
        obj.addEventListener('focus', function(){
            //注释这部分是在一个页面多个这样的调用时禁止冒泡让他不要让ios默认输入框上下弹，最好写在对应页面里给对应元素写这里效率低，这种写法很差所以先注释掉下次优化再贴
            // var input = document.getElementsByTagName('input'),
            // ilength = input.length;
            // for(var i=0; i<ilength; i++){
            //     input[i].addEventListener('touchend',function(e){e.stopPropagation()},false);
            // }
            // var textarea = document.getElementsByTagName('textarea'),
            // tlength = textarea.length;
            // for(var i=0; i<tlength; i++){
            //     textarea[i].addEventListener('touchend',function(e){e.stopPropagation()},false);
            // }
            document.addEventListener('touchend', docTouchend,false);
        },false);
    }else{
        //找不到obj
    }
};

var isIPHONE = navigator.userAgent.toUpperCase().indexOf('IPHONE')!= -1;
            if(isIPHONE){
                var input = objBlur('input');
                input = null;
            }




    $('.item-input').find("input[name=consignee]").blur(function() {
        var consignee = $(this).val();
        data.consignee = consignee;
    });
    $('.item-input').find("input[name=consignee_mobile]").blur(function() {
        var consignee_mobile = $(this).val();
        data.consignee_mobile = consignee_mobile;
    });
    $('.item-input').find("input[name=consignee_address]").blur(function() {
        var consignee_address = $(this).val();
        data.consignee_address = consignee_address;
    });
    $(".J-save").click(function() {
        data.consignee_district = $("#city-picker").val();
        console.log(data.consignee_district);
        if ($.checkEmpty(data.consignee)) {
            $.toast("收货人不能为空");
            return false;
        }
        if ($.trim(data.consignee_mobile).length == 0) {
            $.toast("手机号码不能为空");
            return false;
        }
        if (!$.checkMobilePhone(data.consignee_mobile)) {
            $.toast("手机号码格式错误");
            return false;
        }
        if (!$.maxLength(data.consignee_mobile, 11, true)) {
            $.toast("手机号码长度不能超过11位");
            return false;
        }


        if ($.checkEmpty(data.consignee_address)) {
            $.toast("请输入收货地址");
            return false;
        }
        if ($.checkEmpty(data.consignee_district)) {
            $.toast("请输入收货地址");
            return false;
        }

        $.ajax({
            url: APP_ROOT + "/wap/index.php?ctl=shop&act=editaddress&post_type=json&itype=shop",
            type: "post",
            data: data,
            dataType: "json",
            beforeSend: function() {
                $.showIndicator();
                onload = function() {
                    var a = document.querySelector("a");
                    a.onclick = function() {
                        if (this.disabled) {
                            return false;
                        }
                        this.style.color = 'grey';
                        this.disabed = true;
                    };
                }
            },
            success: function(result) {
                if (result.status == 1) {
                    $.toast("操作成功");
                    history.back();
                } else {
                    $.toast(result.error);
                }
            },
            error: function() {
                $.toast(err);
            },
            complete: function() {
                $.hideIndicator();
            }

        });

    });

    

    



});

$(document).on("pageInit", "#page-shop-order_settlement", function(e, pageId, $page) {
   for(var i=0;i<shop_info.length;i++){
        var obj_shop_info = shop_info[i], goods_obj = {};
        for(var j in obj_shop_info){
            if(j == "goods_id"){
                goods_obj.goods_id = shop_info[i][j];
            }
            if(j == "number"){
                goods_obj.number = shop_info[i][j];
            }
            if(j == "podcast_id"){
                goods_obj.podcast_id = shop_info[i][j];
            }
            if(j == "order_sn"){
                goods_obj.order_sn = shop_info[i][j];
            }
        }
        goods_arr.push(goods_obj);
    };
    var data_shop_arr =  JSON.stringify(goods_arr);
    $(document).on('click', '.J-submit-order', function() {
        handleAjax.handle(APP_ROOT + "/wap/index.php?ctl=shop&act=goods_inventory", { shop_info: data_shop_arr }, '', 1).done(function(result) {
            if (result.status == 1) {
                location.href = TMPL + "index.php?ctl=pay&act=h5_pay&purchase_type=" + data.purchase_type + "&shop_info=" + data_shop_arr;
            } else {
                $.toast(result.error);
                return false;
            }
        }).fail(function(err) {
            $.toast(err);
        });

    });
});

$(document).on("pageInit", "#page-shop-order_settlement_user", function(e, pageId, $page) {
    $(document).on('click', '.J-address', function() {

        location.href = TMPL + "index.php?ctl=shop&act=new_address&address_id=" + data.address_id;
    });
    for(var i=0;i<shop_info.length;i++){
        var obj_shop_info = shop_info[i], goods_obj = {};
        for(var j in obj_shop_info){
            if(j == "goods_id"){
                goods_obj.goods_id = shop_info[i][j];
            }
            if(j == "number"){
                goods_obj.number = shop_info[i][j];
            }
            if(j == "podcast_id"){
                goods_obj.podcast_id = shop_info[i][j];
            }
            if(j == "order_sn"){
                goods_obj.order_sn = shop_info[i][j];
            }
        }
        goods_arr.push(goods_obj);
    };
    
    $(document).on('click','.confirm-ok', function () {
        var id = $(this).attr("data-id"), val = $(this).val();
        console.log(val);
        $.alert('<textarea class="footer-input" type="text" name="remarks" data-id="'+id+'" value="'+val+'" placeholder="选填:对本次交易的说明(建议填写已和卖家协商一致的内容)">'+val+'</textarea>', function() {
            var text = $(".modal").find("textarea"),text_id = text.attr("data-id");
            $(".liuyan-"+id).val(text.val());
        });
    });
    $(document).on('click', '.J-submit-order', function() {
        $(".goods-item").each(function(index, element){
            var self = $(this), i = index, input_remarks = self.find("input[name='remarks']");
            goods_arr[i].memo =  input_remarks.val();
            
        });
        console.log(goods_arr);
        var data_shop_arr =  JSON.stringify(goods_arr);
        if (data.address_id == '') {
            $.toast('地址不能为空', 1000);

        } else {
            handleAjax.handle(APP_ROOT + "/wap/index.php?ctl=shop&act=goods_inventory", { shop_info: data_shop_arr }, '', 1).done(function(result) {
            if (result.status == 1) {
                location.href = TMPL + "index.php?ctl=pay&act=h5_pay&address_id=" + data.address_id + "&purchase_type=" + data.purchase_type + "&shop_info="+data_shop_arr;
            } else {
                    $.toast(result.error);
                    return false;
                }
            }).fail(function(err) {
                $.toast(err);
            });
        }

    });
    
});

// 商品管理列表
$(document).on("pageInit", "#page-shop-podcasr_goods_management", function(e, pageId, $page) {

    init_paramet();
    var vm_paging = new Vue({
        el: "#vscope-paging",
        data: {
            total_page: total_page,
            page: page,
        }
    });

    // 下架商品
    $(document).on('click', '.J-podcasr_shelves_goods', function() {
        var self = $(this);
        var goods_id = self.attr("data-id");
        handleAjax.handle(TMPL + "index.php?ctl=shop&act=podcasr_shelves_goods", { goods_id: goods_id }).done(function(resp) {
            $.toast(resp, 1000);
            setTimeout(function() {
                $("#goods-item-" + goods_id).remove();
            }, 1000);
        }).fail(function(err) {
            $.toast(err, 1000);
        });
    });

    // 删除下架商品
    $(document).on('click', '.J-podcasr_delete_goods', function() {
        var self = $(this);
        var goods_id = self.attr("data-id");
        handleAjax.handle(TMPL + "index.php?ctl=shop&act=podcasr_delete_goods", { goods_id: goods_id }).done(function(resp) {
            $.toast(resp, 1000);
            setTimeout(function() {
                $("#goods-item-" + goods_id).remove();
            }, 1000);
        }).fail(function(err) {
            $.toast(err, 1000);
        });
    });

    // 添加分销商品
    /*    $(document).on('click', '#J-add_distribution_goods', function(){
           var self = $(this);
           var goods_id = self.attr("data-id");
           handleAjax.handle(TMPL+"index.php?ctl=shop&act=add_distribution_goods",{goods_id:goods_id}).done(function(resp){
               $.toast(resp,1000);
               setTimeout(function(){
                   location.reload();
               },1000);
           }).fail(function(err){
               $.toast(err,1000);
           });
        });*/

    // 清空下架商品
    $(document).on('click', '#J-podcasr_empty_goods', function() {
        handleAjax.handle(TMPL + "index.php?ctl=shop&act=podcasr_empty_goods").done(function(resp) {
            $.toast(resp, 1000);
            setTimeout(function() {
                var html = '<div class="tc" style="color:#999;margin-top:50%;">' +
                    '   <i class="icon iconfont" style="font-size:3rem;line-height:1;">&#xe63f;</i>' +
                    '   <div>暂无分销商品，点击马上添加哦~</div>' +
                    '</div>';
                $($page).find(".goods-list").html(html);
            }, 1000);
        }).fail(function(err) {
            $.toast(err, 1000);
        });
    });

    // 无限滚动
    $($page).on('infinite', function(e) {
        infinite_scroll($page,ajax_url,".goods-list",vm_paging);
    });

    //下拉刷新
   $($page).find(".pull-to-refresh-content").on('refresh', function(e) {
        pull_refresh($page,ajax_url,".goods-list",vm_paging);
        $("#search").val("");
    });
    // 初始化参数
    function init_paramet(){
        // var urlinfo = window.location.href; //获取url  
        // paramet.content = decodeURI(urlinfo.split("&")[2].split("=")[1]);

        new_paramet = paramet.state ? '&state='+paramet.state : '',
        // new_paramet = paramet.content ? new_paramet+'&content='+paramet.content : new_paramet,
        // new_paramet = paramet.page ? new_paramet+'&page='+paramet.page : new_paramet,
        ajax_url = APP_ROOT+"/wap/index.php?ctl=shop&act=podcasr_goods_management"+new_paramet;
    };

    $(document).on('click', '.J-search', function(){
        var content = $("#search").val();

        location.href = encodeURI(TMPL + "index.php?ctl=shop&act=podcasr_goods_management&content=" + content + "&state=" + data.state + "&page=" + data.page);
    });

});

$(document).on("pageInit","#page-shop-shop_goods_details", function(e, pageId, $page) {
	var shop_arr = [];
	shop_arr.push({podcast_id:data.podcast_id,goods_id:data.goods_id,number:data.number});
	var data_shop_arr =  JSON.stringify(shop_arr);
	$(document).on('click', '.J-anchor', function(){

			
			location.href = TMPL+"index.php?ctl=shop&act=order_settlement&shop_info="+data_shop_arr;
		
	});
	$(document).on('click', '.J-oneself', function(){
		
			location.href = TMPL+"index.php?ctl=shop&act=order_settlement_user&shop_info="+data_shop_arr;
		
		
	});
});
$(document).on("pageInit", "#page-shop-shop_goods_list", function(e, pageId, $page) {
    init_paramet();
    var vm_paging = new Vue({
        el: "#vscope-paging",
        data: {
            total_page: total_page,
            page: page,
        }
    });

    // 无限滚动
    $($page).on('infinite', function(e) {
        infinite_scroll($page, ajax_url, ".goods-list", vm_paging);
    });

    // 下拉刷新
    $($page).find(".pull-to-refresh-content").on('refresh', function(e) {
        pull_refresh($page, ajax_url, ".goods-list", vm_paging);
        $("#search").val('');
    });



    //增加购买数量
    $(".input-goods-num").val(0);
    $(document).on('click', '.add', function() {
        var self = $(this);
        var goods_id = self.attr("data-id");

        var num = parseInt($(this).siblings(".input-goods-num").val()) || 0;

        if(num < 99){
           num = num + 1;
        }

        // $(".input-goods-num").val(0);
        $(this).siblings(".input-goods-num").val(num);
        data.goods_id = goods_id;
        data.number = Number(num);
    });
    //减少购买数量
    $(document).on('click', '.lost', function() {
        var self = $(this);
        var goods_id = self.attr("data-id");

        var num = parseInt($(this).siblings(".input-goods-num").val()) || 0;

        if(num > 0){
            num = num - 1;
        }

        // $(".input-goods-num").val(0);
        $(this).siblings(".input-goods-num").val(num);

        data.goods_id = goods_id;
        data.number = Number(num);
    });
    $('.input-goods-num').blur(function() {
        var self = $(this);
        var goods_id = self.attr("data-id");
        data.goods_id = goods_id;
        data.number = Number($(this).val());
    });
    $('.input-goods-num').bind('input propertychange', function() {
        var self = $(this),
            self_num = self.val();
        // if (self_num) {
        //     $(".input-goods-num").not(self).val(0);
        // }
        if (self_num>99) {
            $(this).val(99);
        }
    });



    //买给主播
    $(document).on('click', '.J-anchor', function() {
        var shop_arr = [];
        $(".goods-item").each(function(){
            var self = $(this), input_amount = self.find("input[name='amount']");
            if(input_amount.val()>0){
                shop_arr.push({podcast_id:data.podcast_id,goods_id: input_amount.attr("data-id"), number: input_amount.val()});  
            }
        });
        
        var data_shop_arr =  JSON.stringify(shop_arr);
        if (shop_arr.length) {
            location.href = TMPL + "index.php?ctl=shop&act=order_settlement&shop_info="+data_shop_arr;
        } else {
            $.toast("请先选择商品");
            return false;
        }
    });

    //买给自己
    $(document).on('click', '.J-oneself', function() {
        var shop_arr = [];
        $(".goods-item").each(function(){
            var self = $(this), input_amount = self.find("input[name='amount']");
            if(input_amount.val()>0){
                shop_arr.push({podcast_id:data.podcast_id,goods_id:input_amount.attr("data-id"),number:input_amount.val()});  
            }
        });
        
        var data_shop_arr =  JSON.stringify(shop_arr);
        if (shop_arr.length) {
            location.href = TMPL + "index.php?ctl=shop&act=order_settlement_user&shop_info="+data_shop_arr;
        } else {
            $.toast("请先选择商品");
            return false;
        }
    });

    $(document).on('click', '.J-details', function() {
        var self = $(this);
        var goods_id = self.attr("data-id");
        location.href = TMPL + "index.php?ctl=shop&act=shop_goods_details&podcast_id=" + data.podcast_id + "&goods_id=" + goods_id;

    });


    // 初始化参数
    function init_paramet() {
        // var urlinfo = window.location.href; //获取url  
        // paramet.content = decodeURI(urlinfo.split("&")[2].split("=")[1]);

        new_paramet = paramet.podcast_id ? '&podcast_id=' + paramet.podcast_id : '',
        // new_paramet = paramet.content ? new_paramet+'&content='+paramet.content : new_paramet,
        // new_paramet = paramet.page ? new_paramet+'&page='+paramet.page : new_paramet,
            ajax_url = APP_ROOT + "/wap/index.php?ctl=shop&act=shop_goods_list" + new_paramet;

    }


    //搜索关键字
    $(document).on('click', '.J-search', function(){
        var content = $("#search").val();
        location.href = TMPL + "index.php?ctl=shop&act=shop_goods_list&content=" + content + "&podcast_id=" + data.podcast_id + "&page=" + data.page;
    });

    //加入购物车
    $(document).on('click', '.J-add_shopping_cart', function(){
        var self = $(this);
        var goods_id = self.attr('data-id'), number = self.parents(".card").find("input[name=amount]").val();
        if(number>0){
            handleAjax.handle(TMPL + "index.php?ctl=shop&act=join_shopping",{goods_id:goods_id, podcast_id:data.podcast_id, number:number}).done(function(resp){
            setTimeout(function(){
               $.toast('已添加购物车');
            },1000);
        }).fail(function(err){
            $.toast(err,1000);
        });
        }else{
            $.toast("请先选择商品");
            return false;
        }
        


    });

});

$(document).on("pageInit","#page-shop-shop_order", function(e, pageId, $page) {
    init_paramet();
    var vm_paging = new Vue({
        el: "#vscope-paging",
        data: {
            total_page: total_page,
            page: page,
        }
    });

    // 无限滚动
    $($page).on('infinite', function(e) {
        infinite_scroll($page,ajax_url,".goods-list",vm_paging);
    });

    // 下拉刷新
    $($page).find(".pull-to-refresh-content").on('refresh', function(e) {
        pull_refresh($page,ajax_url,".goods-list",vm_paging,function(){
            $(".left_time").each(function(){
                var leftTime = Math.abs(parseInt($(this).attr("data-leftTime")));
                left_time(leftTime,$(this));
            });
        });
    });


    $(document).on('click', '.J-pay', function(){
        var self = $(this);
        var order_id = self.attr("data-order_id");
        var order_sn = self.attr("data-order_sn");
        window.location.href = TMPL + "index.php?ctl=pay&act=h5_pay&order_sn="+order_sn+"&order_id="+order_id;
        // $.ajax({
        //     url: APP_ROOT+"/mapi/index.php?ctl=pay&act=h5_pay",
        //     data: {itype:"shop", order_id:order_id,order_sn:order_sn},
        //     type: 'POST',
        //     dataType: 'json',
        //     success:function(data){
        //         if(data.status == 1){
        //             $.toast(data.error,1000);
        //             setTimeout(function(){
        //                 window.location.reload(); 
        //             },1000);
        //         }
        //         else{
        //             $.toast(data.error,1000);
        //         }
        //     },
        //     error:function(){
        //         $.hideIndicator();
        //         $.toast('请求失败，请检查网络',1000);
        //     }
        // });
    });
    $(document).on('click', '.J-confirm', function(){
        var self = $(this);
        var to_podcast_id = self.attr("data-to_podcast_id");
        var order_sn = self.attr("data-order_sn");
        $.confirm("是否确认收货？",function(s){
            handleAjax.handle(TMPL+"index.php?ctl=pai_podcast&act=buyer_confirm_date",{to_podcast_id:to_podcast_id,order_sn:order_sn}).done(function(resp){
                $.toast('确认收货成功',1000);
                setTimeout(function(){
                    window.location.reload();
                },1000);
            }).fail(function(err){
                $.toast(err,1000);
            });
        });
    });
    $(document).on('click', '.J-remind', function(){
        $.toast('已经提醒商家',1000);
    });
    $(document).on('click', '.J-detail', function(){
        var self = $(this);
        var order_id = self.attr("data-order_id");
        var order_sn = self.attr("data-order_sn");
        location.href = TMPL+"index.php?ctl=shop&act=virtual_shop_order_details&order_id="+order_id+"&order_sn="+order_sn;
    });

    // 初始化参数
    function init_paramet(){

        new_paramet = paramet.state ? '&state='+paramet.state : '',

        ajax_url = APP_ROOT+"/wap/index.php?ctl=shop&act=shop_order"+new_paramet;
        console.log(ajax_url);
    }
});
$(document).on("pageInit","#page-shop-shop_shopping_cart", function(e, pageId, $page) {

    all_js();
    init_paramet();
    var vm_paging = new Vue({
        el: "#vscope-paging",
        data: {
            total_page: total_page,
            page: page,
        }
    });

    // 无限滚动
    $($page).on('infinite', function(e) {
        infinite_scroll($page, ajax_url, ".goods-list", vm_paging);
    });

    // 下拉刷新
    $($page).find(".pull-to-refresh-content").on('refresh', function(e) {
        pull_refresh($page, ajax_url, ".goods-list", vm_paging, function(){
        	all_js();
        });
        $(".J-money").html(0);
        $("input[name=shopping-cart-all]").prop('checked',false);
    });

    // 初始化参数
    function init_paramet() {
        // var urlinfo = window.location.href; //获取url  
        // paramet.content = decodeURI(urlinfo.split("&")[2].split("=")[1]);

        new_paramet = paramet.page ? '&page=' + paramet.page : '',
        ajax_url = APP_ROOT + "/wap/index.php?ctl=shop&act=shop_shopping_cart&page=1";

    };
    function all_js() {
    	var shopping_cart_top = $("input[name=shopping-cart-top]"),
		shopping_cart = $("input[name=shopping-cart]"),
		shopping_cart_all = $("input[name=shopping-cart-all]");
		shopping_cart_top.click(function(){
		var self = $(this);
		if(self.is(":checked")){
			// $(".input-check-"+id).prop('checked',true);
			self.parents(".card").find("input[name=shopping-cart]").prop('checked',true);
		}else{
			// $(".input-check-"+id).prop('checked',false);
			self.parents(".card").find("input[name=shopping-cart]").prop('checked',false);
		}

		var sum = 0;
		$("input[name=shopping-cart]:checked").each(function(){
			sum += parseFloat($(this).parent().find(".input-money").val()*$(this).parent().find(".goods-numb").attr("data-id"));
		});
		sum = sum.toFixed(2);
		$(".J-money").html(sum);
		var card_length = $(".card").length;
		var checked_top_length = $("input[name=shopping-cart-top]:checked").length;
		if (checked_top_length == card_length) {
			shopping_cart_all.prop('checked',true);
		}else{
			shopping_cart_all.prop('checked',false);
		}
	});
	shopping_cart.click(function(){
		if(shopping_cart.is(":checked")){
			
		}else{
			$(this).parents(".card").find("input[name=shopping-cart-top]").prop('checked',false);
			$(shopping_cart_all).prop('checked',false);
		}
		var card_content_length = $(this).parents(".card").find(".card-content").length;
		var checked_length = $(this).parents(".card").find("input[name=shopping-cart]:checked").length;
		
		if (checked_length == card_content_length) {
			$(this).parents(".card").find("input[name=shopping-cart-top]").prop('checked',true);
		}else{
			$(this).parents(".card").find("input[name=shopping-cart-top]").prop('checked',false);
		}
		var card_length = $(".card").length;
		var checked_top_length = $("input[name=shopping-cart-top]:checked").length;
		if (checked_top_length == card_length) {
			shopping_cart_all.prop('checked',true);
		}else{
			shopping_cart_all.prop('checked',false);
		}
		var sum = 0;
		$("input[name=shopping-cart]:checked").each(function(){
			sum += parseFloat($(this).parent().find(".input-money").val()*$(this).parent().find(".goods-numb").attr("data-id"));
			
		});
		sum = sum.toFixed(2);
		$(".J-money").html(sum);
	});

	//编辑
	$(".J-edit").click(function(){
		var self = $(this);
		var txt = self.html();
		var goods_id = self.attr("data-id"), podcast_id = self.attr("data-podcast_id"), number = self.parents(".card").find("input[name=amount]").val();
		if (txt =="编辑") {
			self.html('完成');
		}else if (txt =="完成") {
			self.html('编辑');
		    handleAjax.handle(TMPL + "index.php?ctl=shop&act=update_shopping_goods", { goods_id: goods_id, podcast_id:podcast_id, number:number}).done(function(resp) {
	            $.toast(resp,1000);
	            setTimeout(function() {
	                window.location.reload();
	            });
	        }).fail(function(err) {
	            $.toast(err, 1000);
	        });
		}
		self.parents(".card").find(".goods-text").toggleClass("active");
		self.parents(".card").find(".goods-edit").toggleClass("active");
	});

	//删除
	$(".J-delete").click(function(){
		var self = $(this);
		var parents_card = self.parents(".card");
		var goods_id = self.attr("data-id"), podcast_id = self.attr("data-podcast_id"), number = self.parents(".card").find("input[name=amount]").val();
		$.confirm('是否确定删除商品?',function () {
        	handleAjax.handle(TMPL + "index.php?ctl=shop&act=delete_shopping_goods", { goods_id: goods_id, podcast_id:podcast_id, number:number}).done(function(resp) {
	            setTimeout(function() {
			          	parents_card.remove();
			          	var sum = 0;
						$("input[name=shopping-cart]:checked").each(function(){
							sum += parseFloat($(this).parent().find(".input-money").val()*$(this).parent().find(".goods-numb").attr("data-id"));
							
						});
						sum = sum.toFixed(2);
						$(".J-money").html(sum);
	            });
	        }).fail(function(err) {
	            $.toast(err, 1000);
	        });
    	});
	});

	shopping_cart_all.click(function(){
		if(shopping_cart_all.is(":checked")){
			$(".input-check").prop('checked',true);
		}else{
			$(".input-check").prop('checked',false);
		}
		var sum = 0;
		$("input[name=shopping-cart]:checked").each(function(){
			sum += parseFloat($(this).parent().find(".input-money").val()*$(this).parent().find(".goods-numb").attr("data-id"));
			
		});
		sum = sum.toFixed(2);
		$(".J-money").html(sum);
	});

	//增加购买数量
    $(".add").click(function() {
        var self = $(this);
        var goods_id = self.attr("data-id");
        var num = parseInt($(this).siblings(".input-goods-num").val()) || 0;
        if(num < 99){
           num = num + 1;
        }
        $(this).siblings(".input-goods-num").val(num);
    });
    //减少购买数量
    $(".lost").click(function() {
        var self = $(this);
        var goods_id = self.attr("data-id");
        var num = parseInt($(this).siblings(".input-goods-num").val()) || 0;
        if(num > 1){
            num = num - 1;
        }
        $(this).siblings(".input-goods-num").val(num);
    });
    $('.input-goods-num').blur(function() {
        var self = $(this);
        var goods_id = self.attr("data-id");
    });

    $('.input-goods-num').bind('input propertychange', function() {
        var self = $(this),
            self_num = self.val();
        // if (self_num) {
        //     $(".input-goods-num").not(self).val(0);
        // }
        if (self_num>99) {
            $(this).val(99);
        }
    });

    //结算
    $(document).on('click', '.J-settlement', function(){

    	var shop_arr = [];
	    $("input[name=shopping-cart]:checked").each(function(){
	    	var self = $(this);
	        var input_amount = self.parents(".card").find("input[name='input-number']");
	        var input_money = self.parents(".card").find("input[name='input-money']");
	        if(input_amount.val()>0){
	            shop_arr.push({podcast_id:input_money.attr("data-podcast_id"),goods_id:input_money.attr("data-id"),number:input_amount.val()});  
	        }
	    });

        
	    var data_shop_arr =  JSON.stringify(shop_arr);
		if (shop_arr.length) {
			location.href = TMPL + "index.php?ctl=shop&act=order_settlement_user&shop_info="+data_shop_arr;
		} else {
			$.toast("请先选择商品");
			return false;
		}

    });
    };
});
$(document).on("pageInit","#page-shop-virtual_shop_order_details", function(e, pageId, $page) {
	//付款
	$(document).on('click', '.J-pay', function(){
        window.location.href = TMPL + "index.php?ctl=pay&act=h5_pay&order_sn="+data.order_sn+"&order_id="+data.order_id;
		// $.ajax({
  //           url: APP_ROOT+"/mapi/index.php?ctl=pay&act=h5_pay&itype=shop",
  //           data: data,
  //           type: 'POST',
  //           dataType: 'json',
  //           success:function(data){
  //               if(data.status == 1){
  //                   $.toast(data.error,1000);
  //                   setTimeout(function(){
  //                   	window.location.reload(); 
  //                   },1000);
  //               }
  //               else{
  //                   $.toast(data.error,1000);
  //               }
  //           },
  //           error:function(){
  //               $.hideIndicator();
  //               $.toast('请求失败，请检查网络',1000);
  //           }
  //       });
	});
	$(document).on('click', '.J-remind', function(){
		$.toast('已经提醒商家',1000);
	});
	$(document).on('click', '#J-return_virtual_pai', function(){
      handleAjax.handle(TMPL+"index.php?ctl=pai_podcast&act=buyer_confirm_date",data).done(function(resp){
            $.toast('确认收货成功',1000);
            setTimeout(function(){
               window.location.reload(); 
            },1000);
        }).fail(function(err){
            $.toast(err,1000);
        });

	});
	$(document).on('click', '.J-buyer_to_complaint', function(){
      handleAjax.handle(TMPL+"index.php?ctl=pai_podcast&act=buyer_to_complaint",data).done(function(resp){
            $.toast('已提交申请',1000);
            setTimeout(function(){
               window.location.reload(); 
            },1000);
        }).fail(function(err){
            $.toast(err,1000);
        });

	});
});