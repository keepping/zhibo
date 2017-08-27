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
