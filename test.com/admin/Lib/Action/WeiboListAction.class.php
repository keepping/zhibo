<?php
// +----------------------------------------------------------------------
// | Fanwe 方维众筹商业系统
// +----------------------------------------------------------------------
// | Copyright (c) 2011 http://www.fanwe.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: 甘味人生(526130@qq.com)
// +----------------------------------------------------------------------

class WeiboListAction extends CommonAction{
    public function index($type='')
    {

        $weibo = $this->get_weibo_list();
        $this->assign ( 'list', $weibo );

        $this->display ();
    }
    public function imagetext(){
        $weibo = $this->get_weibo_list('imagetext');
        $this->assign ( 'list', $weibo );

        $this->display ();
    }
    public function video(){
        $weibo = $this->get_weibo_list('video');
        $this->assign ( 'list', $weibo );

        $this->display ();
    }
    public function weixin(){
        $weibo = $this->get_weibo_list('weixin');
        $this->assign ( 'list', $weibo );

        $this->display ();
    }
    public function photo(){
        $weibo = $this->get_weibo_list('photo');
        $this->assign ( 'list', $weibo );

        $this->display ();
    }
    public function red_photo(){
        $weibo = $this->get_weibo_list('red_photo');
        $this->assign ( 'list', $weibo );

        $this->display ();
    }
    public function goods(){
        $weibo = $this->get_weibo_list('goods');
        $this->assign ( 'list', $weibo );

        $this->display ();
    }


    public function get_weibo_list($type='',$is_recommend=0){
        $now=get_gmtime();
        $condition = ' 1 = 1 ';

        if(intval($_REQUEST['weibo_id'])>0)
        {
            $condition .= "  and id =". intval($_REQUEST['weibo_id']);
        }

        if(intval($_REQUEST['user_id'])>0)
        {
            $condition .= "  and user_id =". intval($_REQUEST['user_id']);
        }

        if(!$type){
            $type = $_REQUEST['type'];
        }
        if(trim($type))
        {
            $condition .= "  and type ='". trim($type)."'";
        }

        if(intval($is_recommend)){
            $condition .= " and is_recommend =". intval($is_recommend);
        }

        $create_time_2=empty($_REQUEST['create_time_2'])?to_date($now,'Y-m-d'):strim($_REQUEST['create_time_2']);
        $create_time_2=to_timespan($create_time_2)+24*3600;
        $create_time_2 = to_date($create_time_2,'Y-m-d H:i:s');
        if(trim($_REQUEST['create_time_1'])!='' )
        {
//            $map[DB_PREFIX.'weibo.create_time'] = array('between',array(to_timespan($_REQUEST['create_time_1']),$create_time_2));

            $condition .= " and create_time>'".$_REQUEST['create_time_1']."' and create_time<'".$create_time_2."'";
        }
//        echo $condition;
        log_result($condition);

        $count = M('Weibo')->where($condition)->count();
        $p     = new Page($count, $listRows = 20);
        //举报类型

        $weibo  =  M('Weibo')->where($condition)->order('id desc')->limit($p->firstRow . ',' . $p->listRows)->findAll();

        $page = $p->show();
        $this->assign("page", $page);
        return $weibo;
    }


    public function foreverdelete() {
        //彻底删除指定记录
        $ajax = intval($_REQUEST['ajax']);
        $id = $_REQUEST ['id'];
        if (isset ( $id )) {
            $condition = array ('id' => array ('in', explode ( ',', $id ) ) );
            $rel_data = M('Weibo')->where($condition)->findAll();
            foreach($rel_data as $data)
            {
                $info[] = $data['user_id'].'的动态'.$data['id'];
            }
            if($info) $info = implode(",",$info);
            $list = M('Weibo')->where ( $condition )->delete();
            //删除相关预览图
//				foreach($rel_data as $data)
//				{
//					@unlink(get_real_path().$data['preview']);
//				}
            if ($list!==false) {
                save_log($info.l("FOREVER_DELETE_SUCCESS"),1);
                clear_auto_cache("get_help_cache");
                $this->success (l("FOREVER_DELETE_SUCCESS"),$ajax);
            } else {
                save_log($info.l("FOREVER_DELETE_FAILED"),0);
                $this->error (l("FOREVER_DELETE_FAILED"),$ajax);
            }
        } else {
            $this->error (l("INVALID_OPERATION"),$ajax);
        }
    }

    //推荐微博
    public function  weibo_recommend(){
        $weibo = $this->get_weibo_list('',1);
        $this->assign ( 'list', $weibo );

        $this->display ();
    }

    //设置推荐
    public function set_recommend()
    {
        $id = intval($_REQUEST['id']);
        $c_is_effect = M('Weibo')->where("id=".$id)->getField("is_recommend");  //当前状态
        $n_is_effect = $c_is_effect == 0 ? 1 : 0; //需设置的状态
        $result=M('Weibo')->where("id=".$id)->setField("is_recommend",$n_is_effect);
       // save_log("房间号".$id.l("SET_RECOMMEND_".$n_is_effect),1);
        $this->ajaxReturn($n_is_effect,l("SET_BAN_".$n_is_effect),1);

    }

    //设置推荐
    public function set_effect()
    {
        $id = intval($_REQUEST['id']);
        $c_is_effect = M('Weibo')->where("id=".$id)->getField("status");  //当前状态
        $n_is_effect = $c_is_effect == 0 ? 1 : 0; //需设置的状态
        $result=M('Weibo')->where("id=".$id)->setField("status",$n_is_effect);
        $this->ajaxReturn($n_is_effect,l("SET_BAN_".$n_is_effect),1);

    }
}
?>