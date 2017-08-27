<?php
// +----------------------------------------------------------------------
// | Fanwe 方维p2p借贷系统
// +----------------------------------------------------------------------
// | Copyright (c) 2011 http://www.fanwe.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: 云淡风轻(88522820@qq.com)
// +----------------------------------------------------------------------

class index_h5Module  extends baseModule{

    //H5版首页
    public function index(){

        $root = array();

        $sex = intval($_REQUEST['sex']);//性别 0:全部, 1-男，2-女
        $cate_id = intval($_REQUEST['cate_id']);//话题id
        $city = strim($_REQUEST['city']);//城市(空为:热门)
        if($city=='热门' || $city=='null'){
            $city = '';
        }

        if ($cate_id ==0){
            //首页 轮播
            $root['banner'] = load_auto_cache("banner_list");
            if($root['banner']==false){
                $root['banner'] = array();
            }
        }else{
            //主题相关内容
            $cate = load_auto_cache("cate_id",array('id'=>$cate_id));
            if ($cate['url'] != '' && $cate['image'] != ''){
                $root['banner'] = $cate['banner'];
                $root['cate'] = $cate;
            }
        }

        $root['sex'] = $sex;//
        $root['cate_id'] = $cate_id;//
        $root['city'] = $city;//

        $m_config =  load_auto_cache("m_config");//初始化手机端配置
        $sdk_version_name = strim($_REQUEST['sdk_version_name']);
        $dev_type = strim($_REQUEST['sdk_type']);
        if($dev_type == 'ios' && $m_config['ios_check_version'] != '' && $m_config['ios_check_version'] == $sdk_version_name){
            $list = $this->check_video_list("select_video_check",array('sex_type'=>$sex,'area_type'=>$city,'cate_id'=>$cate_id));
        }else{
            $list = load_auto_cache("select_video",array('sex_type'=>$sex,'area_type'=>$city,'cate_id'=>$cate_id));
        }
        if (defined('SHOW_IS_GAMING') && SHOW_IS_GAMING) {
            fanwe_require(APP_ROOT_PATH . 'mapi/lib/redis/VideoRedisService.php');
            $video_redis = new VideoRedisService();
            foreach ($list as $key => $value) {
                $live_in = $video_redis->getOne_db(intval($value['room_id']), 'live_in') == 1;
                $list[$key]['is_gaming'] = intval($video_redis->getOne_db(intval($value['room_id']), 'game_log_id')) && $live_in ? 1 : 0;
            }
        }


        $root['list'] = $list;
        $root['status'] = 1;
        $root['has_next'] = 0;
        $root['page'] = 1;//

        $root['init_version'] = intval($m_config['init_version']);//手机端配置版本号

        api_ajax_return($root);
    }

    //获取主题
    public function theme(){
        $m_config =  load_auto_cache("m_config");//初始化手机端配置
        $theme = intval($m_config['app_theme']);

        if($theme == 1){//1: green
            $root['layout'] = 'green';
        }else {//0或其他: default
            $root['layout'] = 'default';
        }
        ajax_return($root);
    }
}