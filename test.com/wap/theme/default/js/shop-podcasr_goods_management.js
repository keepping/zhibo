// 分销商品列表
$(document).on("pageInit","#page-shop-podcasr_goods_management", function(e, pageId, $page) {

    // 下架商品
	$(document).on('click', '.J-podcasr_shelves_goods', function(){
		var self = $(this);
		var goods_id = self.attr("data-id");
		handleAjax.handle(TMPL+"index.php?ctl=pai_podcast&act=podcasr_shelves_goods",{goods_id:goods_id}).done(function(resp){
            $.toast(resp,1000);
            setTimeout(function(){
               $("#goods-item-"+goods_id).remove();
            },1000);
        }).fail(function(err){
            $.toast(err,1000);
        });
	});

    // 删除下架商品
    $(document).on('click', '.J-podcasr_delete_goods', function(){
        var self = $(this);
        var goods_id = self.attr("data-id");
        handleAjax.handle(TMPL+"index.php?ctl=pai_podcast&act=podcasr_delete_goods&post_type=json&itype=shop",{goods_id:goods_id}).done(function(resp){
            $.toast(resp,1000);
            setTimeout(function(){
               $("#goods-item-"+goods_id).remove();
            },1000);
        }).fail(function(err){
            $.toast(err,1000);
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
    $(document).on('click', '#J-podcasr_empty_goods', function(){
        handleAjax.handle(TMPL+"index.php?ctl=pai_podcast&act=podcasr_empty_goods").done(function(resp){
            $.toast(resp,1000);
            setTimeout(function(){
                var html =  '<div class="tc" style="color:#999;margin-top:50%;">'+
                            '   <i class="icon iconfont" style="font-size:3rem;line-height:1;">&#xe63f;</i>'+
                            '   <div>暂无分销商品，点击马上添加哦~</div>'+
                            '</div>';
                $($page).find(".goods-list").html(html);
            },1000);
        }).fail(function(err){
            $.toast(err,1000);
        });
    });
});
