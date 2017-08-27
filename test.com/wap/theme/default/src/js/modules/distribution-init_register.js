var vm_login = new Vue({
    el: '#vscope-login',
    data: {
        mobile: '',
        verify_coder: '',
        is_disabled: false,
        code_lefttime: 0,
        code_timeer: null
    },
    methods: {
        login: function(ajax_url){
            var self = this;
            if(! self.check()){
                return false;
            }
            // 登录
            var query = new Object();
            query.mobile = self.mobile;
            query.verify_coder = self.verify_coder;
            $.ajax({
                url:ajax_url,
                data:query,
                type:"POST",
                dataType:"json",
                success:function(result){
                    if(result.status == 1){
                        //返回分享页
                        if(result.error){
                            $.toast(result.error,1000);
                            if(result.is_url){
                                setTimeout(function(){
                                    location.href= result.url;
                                },1000);
                            }
                        }else{
                            if(result.is_url){
                               location.href= result.url;
                            }
                        }
                    }
                    else{
                        if(result.error){
                            $.toast(result.error,1000);
                            if(result.is_url){
                                setTimeout(function(){
                                    location.href= result.url;
                                },1000);
                            }
                        }
                        else{
                            $.toast("操作失败");
                        }
                    }
                }
            });
        },
        check: function(val, event){
        // 验证表单
            var self = this;
            if($.trim(self.mobile).length == 0)
            {         
                $.toast("手机号码不能为空");
                return false;
            }
            if(!$.checkMobilePhone(self.mobile))
            {   
                $.toast("手机号码格式错误");
                return false;
            }
            if(!$.maxLength(self.mobile,11,true))
            {     
                $.toast("长度不能超过11位");
                return false;
            }
            else{
                return true;
            }
        },
        send_code: function(event){
        // 发送验证码
            var self = this;
            if(self.is_disabled){
                $.toast("发送速度太快了");
                return false; 
            }
            else{
                var thiscountdown=$("#j-send-code"); 
                var query = new Object();
                query.mobile = self.mobile;
                $.ajax({
                    url:APP_ROOT+"/mapi/index.php?ctl=login&act=send_mobile_verify",
                    data:query,
                    type:"POST",
                    dataType:"json",
                    success:function(result){
                        if(result.status == 1){    
                            countdown = 60;
                            // 验证码倒计时
                            vm_login.code_lefttime = 60;
                            self.code_lefttime_fuc("#j-send-code", self.code_lefttime);
                            // $.showSuccess(result.info);
                            return false;
                        }
                        else{
                            $.toast(result.error);
                            return false;
                        }
                  }
                });
            }
        },
        code_lefttime_fuc: function(verify_name,code_lefttime){
        // 验证码倒计时
            var self = this;
            clearTimeout(self.code_timeer);
            $(verify_name).html("重新发送 "+code_lefttime);
            code_lefttime--;
            if(code_lefttime >0){
                $(verify_name).attr("disabled","disabled");
                self.is_disabled=true;
                vm_login.code_timeer = setTimeout(function(){self.code_lefttime_fuc(verify_name,code_lefttime);},1000);
            }
            else{
                code_lefttime = 60;
                self.is_disabled=false;
                $(verify_name).removeAttr("disabled");
                $(verify_name).html("发送验证码");
            }
        }
    }
});