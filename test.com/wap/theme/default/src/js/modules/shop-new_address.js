$(document).on("pageInit", "#page-shop-new_address", function(e, pageId, $page) {
    $("#city-picker").cityPicker({
        
            //value: ['四川', '内江', '东兴区']
    });
    $("#city-picker").click(function(){
        $("input:not(this)").blur();
    });
    function objBlur(obj, time){
    if(typeof obj != 'string') return false;
    var obj = document.getElementById(obj),
    time = time || 300,
    docTouchend = function(event){
        if(event.target!= obj){
            setTimeout(function(){
                 obj.blur();
                document.removeEventListener('touchend', docTouchend,false);
            },time);
        }
    };
    if(obj){
        obj.addEventListener('focus', function(){
            //注释这部分是在一个页面多个这样的调用时禁止冒泡让他不要让ios默认输入框上下弹，最好写在对应页面里给对应元素写这里效率低，这种写法很差所以先注释掉下次优化再贴
            // var input = document.getElementsByTagName('input'),
            // ilength = input.length;
            // for(var i=0; i<ilength; i++){
            //     input[i].addEventListener('touchend',function(e){e.stopPropagation()},false);
            // }
            // var textarea = document.getElementsByTagName('textarea'),
            // tlength = textarea.length;
            // for(var i=0; i<tlength; i++){
            //     textarea[i].addEventListener('touchend',function(e){e.stopPropagation()},false);
            // }
            document.addEventListener('touchend', docTouchend,false);
        },false);
    }else{
        //找不到obj
    }
};

var isIPHONE = navigator.userAgent.toUpperCase().indexOf('IPHONE')!= -1;
            if(isIPHONE){
                var input = objBlur('input');
                input = null;
            }




    $('.item-input').find("input[name=consignee]").blur(function() {
        var consignee = $(this).val();
        data.consignee = consignee;
    });
    $('.item-input').find("input[name=consignee_mobile]").blur(function() {
        var consignee_mobile = $(this).val();
        data.consignee_mobile = consignee_mobile;
    });
    $('.item-input').find("input[name=consignee_address]").blur(function() {
        var consignee_address = $(this).val();
        data.consignee_address = consignee_address;
    });
    $(".J-save").click(function() {
        data.consignee_district = $("#city-picker").val();
        console.log(data.consignee_district);
        if ($.checkEmpty(data.consignee)) {
            $.toast("收货人不能为空");
            return false;
        }
        if ($.trim(data.consignee_mobile).length == 0) {
            $.toast("手机号码不能为空");
            return false;
        }
        if (!$.checkMobilePhone(data.consignee_mobile)) {
            $.toast("手机号码格式错误");
            return false;
        }
        if (!$.maxLength(data.consignee_mobile, 11, true)) {
            $.toast("手机号码长度不能超过11位");
            return false;
        }


        if ($.checkEmpty(data.consignee_address)) {
            $.toast("请输入收货地址");
            return false;
        }
        if ($.checkEmpty(data.consignee_district)) {
            $.toast("请输入收货地址");
            return false;
        }

        $.ajax({
            url: APP_ROOT + "/wap/index.php?ctl=shop&act=editaddress&post_type=json&itype=shop",
            type: "post",
            data: data,
            dataType: "json",
            beforeSend: function() {
                $.showIndicator();
                onload = function() {
                    var a = document.querySelector("a");
                    a.onclick = function() {
                        if (this.disabled) {
                            return false;
                        }
                        this.style.color = 'grey';
                        this.disabed = true;
                    };
                }
            },
            success: function(result) {
                if (result.status == 1) {
                    $.toast("操作成功");
                    history.back();
                } else {
                    $.toast(result.error);
                }
            },
            error: function() {
                $.toast(err);
            },
            complete: function() {
                $.hideIndicator();
            }

        });

    });

    

    



});
