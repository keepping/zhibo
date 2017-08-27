// 商品管理列表
$(document).on("pageInit", "#page-shop-podcasr_goods_management", function(e, pageId, $page) {

    init_paramet();
    var vm_paging = new Vue({
        el: "#vscope-paging",
        data: {
            total_page: total_page,
            page: page,
        }
    });

    // 下架商品
    $(document).on('click', '.J-podcasr_shelves_goods', function() {
        var self = $(this);
        var goods_id = self.attr("data-id");
        handleAjax.handle(TMPL + "index.php?ctl=shop&act=podcasr_shelves_goods", { goods_id: goods_id }).done(function(resp) {
            $.toast(resp, 1000);
            setTimeout(function() {
                $("#goods-item-" + goods_id).remove();
            }, 1000);
        }).fail(function(err) {
            $.toast(err, 1000);
        });
    });

    // 删除下架商品
    $(document).on('click', '.J-podcasr_delete_goods', function() {
        var self = $(this);
        var goods_id = self.attr("data-id");
        handleAjax.handle(TMPL + "index.php?ctl=shop&act=podcasr_delete_goods", { goods_id: goods_id }).done(function(resp) {
            $.toast(resp, 1000);
            setTimeout(function() {
                $("#goods-item-" + goods_id).remove();
            }, 1000);
        }).fail(function(err) {
            $.toast(err, 1000);
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
    $(document).on('click', '#J-podcasr_empty_goods', function() {
        handleAjax.handle(TMPL + "index.php?ctl=shop&act=podcasr_empty_goods").done(function(resp) {
            $.toast(resp, 1000);
            setTimeout(function() {
                var html = '<div class="tc" style="color:#999;margin-top:50%;">' +
                    '   <i class="icon iconfont" style="font-size:3rem;line-height:1;">&#xe63f;</i>' +
                    '   <div>暂无分销商品，点击马上添加哦~</div>' +
                    '</div>';
                $($page).find(".goods-list").html(html);
            }, 1000);
        }).fail(function(err) {
            $.toast(err, 1000);
        });
    });

    // 无限滚动
    $($page).on('infinite', function(e) {
        infinite_scroll($page,ajax_url,".goods-list",vm_paging);
    });

    //下拉刷新
   $($page).find(".pull-to-refresh-content").on('refresh', function(e) {
        pull_refresh($page,ajax_url,".goods-list",vm_paging);
        $("#search").val("");
    });
    // 初始化参数
    function init_paramet(){
        // var urlinfo = window.location.href; //获取url  
        // paramet.content = decodeURI(urlinfo.split("&")[2].split("=")[1]);

        new_paramet = paramet.state ? '&state='+paramet.state : '',
        // new_paramet = paramet.content ? new_paramet+'&content='+paramet.content : new_paramet,
        // new_paramet = paramet.page ? new_paramet+'&page='+paramet.page : new_paramet,
        ajax_url = APP_ROOT+"/wap/index.php?ctl=shop&act=podcasr_goods_management"+new_paramet;
    };

    $(document).on('click', '.J-search', function(){
        var content = $("#search").val();

        location.href = encodeURI(TMPL + "index.php?ctl=shop&act=podcasr_goods_management&content=" + content + "&state=" + data.state + "&page=" + data.page);
    });

});
