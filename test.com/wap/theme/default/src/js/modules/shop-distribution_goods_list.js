// 分销商品列表
$(document).on("pageInit","#page-shop-distribution_goods_list", function(e, pageId, $page) {
    init_paramet();
    var vm_paging = new Vue({
        el: "#vscope-paging",
        data: {
            total_page: total_page,
            page: page,
        }
    });

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

    // 无限滚动
    $($page).on('infinite', function(e) {
        infinite_scroll($page,ajax_url,".shop-list",vm_paging);
    });

    //下拉刷新
   $($page).find(".pull-to-refresh-content").on('refresh', function(e) {
        pull_refresh($page,ajax_url,".shop-list",vm_paging);
        $("#search").val('');
        var all_options = document.getElementById("goods_cate").options;
        for (i=0; i<all_options.length; i++){
          if (all_options[i].id == 0)  // 根据option标签的ID来进行判断  测试的代码这里是两个等号
          {
             all_options[i].selected = true;
          }
       }
    });
    // 初始化参数
    function init_paramet(){
        // var urlinfo = window.location.href; //获取url  
        // paramet.content = decodeURI(urlinfo.split("&")[2].split("=")[1]);
        new_paramet = paramet.options ? '&options='+paramet.options : '',
        // new_paramet = paramet.content ? new_paramet+'&content='+paramet.content : new_paramet,
        // new_paramet = paramet.page ? new_paramet+'&page='+paramet.page : new_paramet,
        ajax_url = APP_ROOT+"/wap/index.php?ctl=shop&act=distribution_goods_list"+new_paramet;
    };
    $(function(){
        var option_url1 = TMPL + "index.php?ctl=shop&act=distribution_goods_list&options=1&cate_id=" + paramet.cate_id;
        var option_url2 = TMPL + "index.php?ctl=shop&act=distribution_goods_list&options=2&cate_id=" + paramet.cate_id;
        var option_url3 = TMPL + "index.php?ctl=shop&act=distribution_goods_list&options=3&cate_id=" + paramet.cate_id;
        $(".option1").attr("href",option_url1);
        $(".option2").attr("href",option_url2);
        $(".option3").attr("href",option_url3);
    });
    //分类筛选
    $(".select").change(function(){
        var self= $('option').not(function(){ return !this.selected });
        var id = self.attr("data-id");
        location.href = TMPL + "index.php?ctl=shop&act=distribution_goods_list&cate_id=" + id + "&options=" + paramet.options + "&page=" + data.page;
    });
    if(paramet.cate_id){
        if($("#search option").attr("data-id") == paramet.cate_id){
            $(this).attr("selected", true);
        }
    }
    //搜索关键字
    $(document).on('click', '.J-search', function(){
        var content = $("#search").val();
        console.log(data);
        location.href = TMPL + "index.php?ctl=shop&act=distribution_goods_list&content=" + content + "&options=" + paramet.options + "&page=" + data.page;
    });
});
