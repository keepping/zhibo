// 分销商品列表
$(document).on("pageInit","#page-shop-distribution_goods_list", function(e, pageId, $page) {
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
});
