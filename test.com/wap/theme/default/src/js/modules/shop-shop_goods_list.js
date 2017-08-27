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
