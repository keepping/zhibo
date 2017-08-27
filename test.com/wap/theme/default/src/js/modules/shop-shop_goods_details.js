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