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