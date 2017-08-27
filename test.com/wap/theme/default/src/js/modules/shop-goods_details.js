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