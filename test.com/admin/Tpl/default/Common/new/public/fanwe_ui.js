$(document).ready(function(){


//init_ui_select();
//选项卡切换
$(".tab li").bind("click",function(){
    $(this).siblings("li").removeClass("active");
   $(this).addClass("active");
   var idx = $(this).attr("rel");
   $(".tabcontent").css("display", "none");
   $(".tabcontent[rel='"+idx+"']").css("display", "block");
});
//多选框
$(".checkbox").click(function(){ 
    if ($(this).hasClass('checked')) {
        $(this).removeClass('checked');
    }
    else {
        $(this).addClass('checked');
    }
}); 

//初始化开关switch
$(".ui-switch").addClass("click").append('<i class="iconfont yuan"></i>');
//下拉框
$(".dropdown span").click(function(){ 
    var ul = $(".dropdown ul"); 
    $(this).parent(".dropdown").toggleClass("focus");
    if(ul.css("display")=="none"){ 
        ul.slideDown("fast"); 
    }else{ 
        ul.slideUp("fast"); 
    } 
});

/*$(document).click(function(e) {
    if(e.target != $('.dropdown ul span') ){ 
        $(".dropdown span").toggleClass("a");
       //$(".dropdown ul").slideUp("fast"); 
    }if (e.target != $(".dropdown span")) {

    }
    else{ 
       // $(".dropdown ul").slideUp("fast"); 
    } 
}); 
*/
//鼠标上覆盖移除状态转移
    $(".zhover").hover(function(){
        $(this).addClass("hover");
    },function(){
        $(this).removeClass("hover");
    });
//鼠标上点击状态改变
 $(".click").live("click",function(){
        if ($(this).hasClass('hover')) {
            $(this).removeClass('hover');
        }
        else {
            $(this).addClass('hover');
        }
    });
    $(".zhover").hover(function(){
        $(this).addClass("hover");
    },function(){
        $(this).removeClass("hover");
    }); 

    
//输入框

$(".input-wrap").bind("click",function(){
    $(this).find(".W-input").focus();
});
$(".W-input").focus(function(){
    $(this).parent(".input-wrap").addClass("input-wrap-focus");
    $(this).next(".holder-tip").hide();
    $(this).parent(".input-wrap").removeClass("input-wrap-error");
    $(this).parent(".input-wrap").removeClass("input-wrap-success");
});
$(".W-input").blur(function(){
    if ($(this).val()==""){
        $(this).next(".holder-tip").show();
    }
    $(this).parent(".input-wrap").removeClass("input-wrap-focus");
});
$(".W-input").each(function(){
    if ($(this).val()!=""){
        $(this).next(".holder-tip").hide();
    }
  });

$(".date_selector").bind("click",function(){
    alert(1);
    $(this).parent(".input-wrap").removeClass("input-wrap-error");
});

});/* $(document).ready end*/



//提示框展示状态以及提示文字
function tip(state,tip){
    if (state=="success") {
        $(".m-tip").removeClass("success").removeClass("fail").removeClass("hint");
        $(".m-tip").addClass("success").addClass("hover");
        $(".m-tip .tip span").html(tip);
        setTimeout(function(){
            $(".m-tip").removeClass("hover").removeClass("success");
        },"6000"); 
    };
    if (state=="fail") {
        $(".m-tip").removeClass("success").removeClass("fail").removeClass("hint");
        $(".m-tip").addClass("fail").addClass("hover");
        $(".m-tip .tip span").html(tip);
    };
    if (state=="hint") {
        $(".m-tip").removeClass("success").removeClass("fail").removeClass("hint");
        $(".m-tip").addClass("hint").addClass("hover");
        $(".m-tip .tip span").html(tip);
    };
    setTimeout(function() {
        cleartip();
    },6000)
}
//清除提示框状态及提示文字
function cleartip(){
    $(".m-tip").removeClass("hover").removeClass("success").removeClass("fail").removeClass("hint");
}
