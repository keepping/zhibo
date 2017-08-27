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