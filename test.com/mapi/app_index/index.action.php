<?php
// +----------------------------------------------------------------------
// | Fanwe 方维p2p借贷系统
// +----------------------------------------------------------------------
// | Copyright (c) 2011 http://www.fanwe.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: 云淡风轻(88522820@qq.com)
// +----------------------------------------------------------------------

class indexCModule  extends baseModule
{

 	//首页
	public function index()
	{
		$root = array();
		$root['page_title'] = "首页";
		$m_config =  load_auto_cache("m_config");//初始化手机端配置
		$root['app_logo']=get_spec_image(".".substr($m_config['app_logo'],strpos($m_config['app_logo'],'/public')),170,170);//logo 170*170
		$root['app_name']=$m_config['app_name'];//应用名称
        $root['android_filename']=$m_config['android_filename'];//android下载链接
        if($m_config['ios_check_version'] != ''){
            $root['android_filename'] = '';
        }
		$root['ios_down_url']=$m_config['ios_down_url'];//ios下载链接
		$root['SITE_LICENSE']=app_conf('SITE_LICENSE');//站点版权:
		$root['NETWORK_FOR_RECORD']=app_conf('NETWORK_FOR_RECORD');//网络备案信息:
		$root['COPYRIGHT']=app_conf('COPYRIGHT');//Copyright:
		//生成二维码
		$invite_url = SITE_DOMAIN.'/appdown.php';
		$path_dir = "/public/qrcode_wx.png";
		$path_logo_dir = "/public/qrcode_log_wx.png";
		$qrcode_dir = APP_ROOT_PATH.$path_dir;
		$qrcode_dir_logo = APP_ROOT_PATH.$path_logo_dir;
		if(!is_file($qrcode_dir)||!is_file($qrcode_dir_logo)){
			get_qrcode_png($invite_url,$qrcode_dir,$qrcode_dir_logo);
		}
		//$root['qrcode_url']=get_domain().$path_logo_dir;//二维码
		$root['qrcode_url']=SITE_DOMAIN.'/mapi/index.php?ctl=app_download';//二维码
		$root['bg_page']=app_conf('BG_PAGE');//背景图
		$root['bg_app']=app_conf('BG_APP');//右侧图片//bg_app 规格: 536*730
		api_ajax_return($root);
	}
    //联系我们
    public function contact(){
		$root = array();
		$root['page_title'] = "联系我们";
		$sql = "select a.id,a.cate_id,a.title,a.content from ".DB_PREFIX."article a  left join ".DB_PREFIX."article_cate b on b.id = a.cate_id  where a.is_delete = 0 and a.is_effect = 1 and b.title = '联系我们'";
		$article = $GLOBALS['db']->getRow($sql,true,true);
		$root['content'] = $article['content'];
		api_ajax_return($root);
    }
	//隐私政策
    public function privacy(){
		$root = array();
		$root['page_title'] = "隐私政策";
		$sql = "select a.id,a.cate_id,a.title,a.content from ".DB_PREFIX."article a  left join ".DB_PREFIX."article_cate b on b.id = a.cate_id  where a.is_delete = 0 and a.is_effect = 1 and b.title = '隐私政策'";
		$article = $GLOBALS['db']->getRow($sql,true,true);
		$root['content'] = $article['content'];
		api_ajax_return($root);
    }
    //服务条款
    public function service(){
		$root = array();
		$root['page_title'] = "服务条款";
		$sql = "select a.id,a.title,a.cate_id,a.content from ".DB_PREFIX."article a left join ".DB_PREFIX."article_cate b on b.id = a.cate_id where a.is_delete = 0 and a.is_effect = 1 and b.title = '主播协议'";
		$article = $GLOBALS['db']->getRow($sql,true,true);
		$root['content'] = $article['content'];
		api_ajax_return($root);
    }
}

?>