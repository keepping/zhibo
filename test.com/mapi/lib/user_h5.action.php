<?php
// +----------------------------------------------------------------------
// | Fanwe 方维p2p借贷系统
// +----------------------------------------------------------------------
// | Copyright (c) 2011 http://www.fanwe.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: 云淡风轻(88522820@qq.com)
// +----------------------------------------------------------------------

class user_h5Module  extends baseModule{

    public function userinfo(){

        $root = array();

        if(!$GLOBALS['user_info']){
            $root['error'] = "用户未登陆,请先登陆.";
            $root['status'] = 0;
            $root['user_login_status'] = 0;//有这个参数： user_login_status = 0 时，表示服务端未登陆、要求登陆，操作
            ajax_return($root);
        }else {
            $user_id = intval($GLOBALS['user_info']['id']);

            $podcast_id = intval($_REQUEST['podcast_id']);//主播id，fanwe_user.id
            $to_user_id = intval($_REQUEST['to_user_id']);//需要查看的用户id
            if ($to_user_id == 0){
                $to_user_id = $user_id;
            }


            $root = getuserinfo($user_id,$podcast_id,$to_user_id,$_REQUEST);
            $m_config =  load_auto_cache("m_config");//初始化手机端配置
            $root['init_version'] = intval($m_config['init_version']);//手机端配置版本号
            if($m_config['name_limit']==1){


                $user = $GLOBALS['db']->getRow("select nick_name from ".DB_PREFIX."user where id = ".$user_id);
                $nick_name=$user['nick_name'];

                //进入个人中心过滤铭感词汇
                $limit_sql =$GLOBALS['db']->getCol("SELECT name FROM ".DB_PREFIX."limit_name");
                if($GLOBALS['db']->getCol("SELECT name FROM ".DB_PREFIX."limit_name WHERE '$nick_name' like concat('%',name,'%')")){
                    $nick_name=str_replace($limit_sql,'*',$nick_name);
                }

                //判断用户名如果被过滤后为空,格式则变更为： 账号+ID
                if($nick_name==''){
                    $nick_name=('账号'.$user_id);
                }
                //更新数据库
                $sql = "update ".DB_PREFIX."user set nick_name = '$nick_name' where id=".$user_id;
                $GLOBALS['db']->query($sql);
                //更新redis
                user_deal_to_reids(array($user_id));

            }
            $root['status'] = 1;
            api_ajax_return($root);

        }

    }

}