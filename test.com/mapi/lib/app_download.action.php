<?php
// +----------------------------------------------------------------------
// | Fanwe 方维o2o商业系统
// +----------------------------------------------------------------------
// | Copyright (c) 2011 http://www.fanwe.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: 云淡风轻(97139915@qq.com)
// +----------------------------------------------------------------------


class app_downloadModule  
{
	public function index()
	{
		if(isWeixin())
		{
			if (isios()){
                $image_path = get_domain().APP_ROOT."/wap/theme/default/images/app_download/";
				//$str = 'IOS版本正在开发中，敬请期待！<br>';
//				$str = $str.'1.点击右上角的按钮<br>';
//				$str = $str.'2.选择 在Safari中打开 即可下载app<br/>';
                $str = $str.'<div class="img-box"><img src="'.$image_path.'000.jpg"></div>';
//				$str = $str.'升级iOS9，app打不开怎么办？<br/>';
//				$str = $str.'1.点开App,弹出未受信任的开发者，记住弹框中冒号后面的名字。关闭，进入设置。<br/>';
//				$str = $str.'<div class="img-box"><img src="'.$image_path.'11.jpg"></div>';
//				$str = $str.'<div class="img-box"><img src="'.$image_path.'22.jpg"></div>';
//				$str = $str.'2.进入通用<br/>';
//				$str = $str.'<div class="img-box"><img src="'.$image_path.'33.jpg"></div>';
//				$str = $str.'3.进入描述文件<br/>';
//				$str = $str.'<div class="img-box"><img src="'.$image_path.'44.jpg"></div>';
//				$str = $str.'4.找到所对应的企业级应用（就是打开App,冒号后面的名字）<br/>';
//				$str = $str.'<div class="img-box"><img src="'.$image_path.'55.jpg"></div>';
//				$str = $str.'5.选择信任，进入。<br/>';
//				$str = $str.'<div class="img-box"><img src="'.$image_path.'66.jpg"></div>';
//				$str = $str.'6.点击弹出的弹框中的“信任“。<br/>';
//				$str = $str.'<div class="img-box"><img src="'.$image_path.'77.jpg"></div>';
//				$str = $str.'<div class="img-box"><img src="'.$image_path.'88.jpg"></div>';
                $html = '<!DOCTYPE html>
	<html>
	    <head>
	        <meta charset="utf-8">
	        <meta http-equiv="X-UA-Compatible" content="IE=edge">
	        <title></title>
         	<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=2.0, user-scalable=0,minimum-scale=0.5">
	        <link rel="shortcut icon" href="/favicon.ico">
	        <meta name="apple-mobile-web-app-capable" content="yes">
	        <meta name="apple-mobile-web-app-status-bar-style" content="black">
			<style>
				.img-box{
					margin-top:0px;
				}
				@media screen and (min-width: 770px) {
					.img-box {
						width:600px;
						margin-top: 0px;
					}
				}
				.img-box img{
					width:100%;
				}
				.item-title{
					font-size:18px;
					line-height: 40px;
					margin-top:10px;
					border-bottom:1px solid #ddd;
				}

			</style>
	    </head>
	    <body style="margin:0px;padding:0px;">
        '.$str.'
		</body>
	</html>
';
                header("Content-Type:text/html; charset=utf-8");
                echo $html;
                exit;

			}else{
				$str = '请使用浏览器打开下载：<br>';
				$str = $str.'1.点击右上角的按钮<br>';
				$str = $str.'2.选择 在浏览器中打开 即可下载app';

            $m_config=load_auto_cache("m_config");
            $appimg=get_spec_image($m_config['app_logo']);
            $html = '<!DOCTYPE html>
	<html>
	    <head>
	        <meta charset="utf-8">
	        <meta http-equiv="X-UA-Compatible" content="IE=edge">
	        <title></title>
         	<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=2.0, user-scalable=0,minimum-scale=0.5">
	        <link rel="shortcut icon" href="/favicon.ico">
	        <meta name="apple-mobile-web-app-capable" content="yes">
	        <meta name="apple-mobile-web-app-status-bar-style" content="black">
			<style>
				.img-box{
					margin-top:0px;
				}
				@media screen and (min-width: 770px) {
					.img-box {
						width:600px;
						margin-top: 0px;
					}
				}
				.img-box img{
					width:100%;
				}
				.item-title{
					font-size:18px;
					line-height: 40px;
					margin-top:10px;
					border-bottom:1px solid #ddd;
				}

				#weixin-tip{display:none;position:fixed;left:0;top:0;background:rgba(0,0,0,0.8);filter:alpha(opacity=80);width:100%;height:100%;z-index:100;}
                #weixin-tip p{text-align:center;margin-top:10%;padding:0 5%;position:relative;}
                #weixin-tip p img{max-width: 100%; height: auto;}
                .wxtip-txt{margin-top: 100px; margin-left:50px;color: #fff; font-size: 20px; font-weight:bolder;line-height: 1.5;}
                #weixin-tip .close{color:#fff;padding:5px;font:bold 20px/24px simsun;text-shadow:0 1px 0 #ddd;position:absolute;top:0;left:5%;}
			</style>
	    </head>
	    <body style="margin:0px;padding:0px;">
		<div style="background-image:url('.$appimg.');background-size:cover;background-repeat:no-repeat;width:150px;height:150px;margin:100px auto"></div>
	    <div style="margin-top:50px;margin-left:50px;">
	     <span>如未自动下载，<a id="J_weixin" class="android-btn" href="'.$m_config['android_filename'].'">点击链接开始下载</a></span>
	    </div>

	    <div id="weixin-tip"><p><img src="/public/images/live_weixin.png" alt="微信打开"/><span id="close" title="关闭" class="close">×</span></p>


		</body>
	<script>
		var is_weixin = (function(){return navigator.userAgent.toLowerCase().indexOf(\'micromessenger\') !== -1})();
        window.onload = function() {
            var winHeight = typeof window.innerHeight != \'undefined\' ? window.innerHeight : document.documentElement.clientHeight; //兼容IOS，不需要的可以去掉
            var btn = document.getElementById(\'J_weixin\');
            var tip = document.getElementById(\'weixin-tip\');
            var close = document.getElementById(\'close\');
            if (is_weixin) {
//                btn.onclick = function(e) {
                    tip.style.height = winHeight + \'px\'; //兼容IOS弹窗整屏
                    tip.style.display = \'block\';
//                    //return false;
//                }
                close.onclick = function() {
                    tip.style.display = \'none\';
                }
            }
        }
       </script>
	</html>
';
			header("Content-Type:text/html; charset=utf-8");
			echo $html;
			exit;

		}
        }
		else
		{
			//用户app下载地址连接
			if (isios()){
				//$down_url = app_conf("APPLE_PATH");
				$down_url = $GLOBALS['db']->getOne("select val from ".DB_PREFIX."m_config where code = 'ios_down_url'");
				if(!$down_url){
					//$down_url = SITE_DOMAIN.'/public/app.ipa';
				 echo '<!DOCTYPE html>
	<html>
	    <head>
	        <meta charset="utf-8">
	        <meta http-equiv="X-UA-Compatible" content="IE=edge">
	        <title></title>
         	<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=2.0, user-scalable=0,minimum-scale=0.5">
	        <link rel="shortcut icon" href="/favicon.ico">
	        <meta name="apple-mobile-web-app-capable" content="yes">
	        <meta name="apple-mobile-web-app-status-bar-style" content="black">
	    </head>
	    <body>
		下载地址出错
		</body>
	</html>
';
					//$down_url = SITE_DOMAIN.'/public/app.ipa';
				}
				//$down_url = SITE_DOMAIN.'/public/app.ipa';
			}else{
				//$down_url = app_conf("ANDROID_PATH");
				$down_url = $GLOBALS['db']->getOne("select val from ".DB_PREFIX."m_config where code = 'android_filename'");
				if(!$down_url){
					//$down_url = SITE_DOMAIN.'/public/app.apk';
					echo '<!DOCTYPE html>
	<html>
	    <head>
	        <meta charset="utf-8">
	        <meta http-equiv="X-UA-Compatible" content="IE=edge">
	        <title></title>
         	<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=2.0, user-scalable=0,minimum-scale=0.5">
	        <link rel="shortcut icon" href="/favicon.ico">
	        <meta name="apple-mobile-web-app-capable" content="yes">
	        <meta name="apple-mobile-web-app-status-bar-style" content="black">
	    </head>
	    <body>
		下载地址出错
		</body>
	</html>
';
					
				}
				//$down_url = SITE_DOMAIN.'/public/app.apk';
			}
			app_redirect($down_url);	
		}	
	}	
	
}
?>