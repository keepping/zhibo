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
