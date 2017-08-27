$(document).on("pageInit","#page-shop-shop_goods_list", function(e, pageId, $page) {
	alert(1111);
	$(document).on('click', '#add', function(){
		console.log(111);
		var num = parseInt($("#input").val()) || 0;
		$("#input").val(num + 1);
	});
	$(document).on('click', '#lost', function(){
		console.log(111);
		var num = parseInt($("#input").val()) || 0;
		num = num - 1;
		num = num < 1 ? 1 : num;
		$("#input").val(num);
	});
});