<?php
// +----------------------------------------------------------------------
// | Fanwe 方维众筹商业系统
// +----------------------------------------------------------------------
// | Copyright (c) 2011 http://www.fanwe.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: 甘味人生(526130@qq.com)
// +----------------------------------------------------------------------

class WeiboAllegeListAction extends CommonAction{
    public function index($type='')
    {
        $weibo = $this->get_allege_list();
        $this->assign ( 'list', $weibo );
        $this->display ();
    }
    public function pending_deal(){
        $weibo = $this->get_allege_list(0);
        $this->assign ( 'weibo', $weibo );

        $this->display ();
    }
    public function already_deal(){
        $weibo = $this->get_allege_list(1);
        $this->assign ( 'weibo', $weibo );

        $this->display ();
    }

    public function get_allege_list($status=''){
        $now=get_gmtime();
        $condition = "";
        if(intval($_REQUEST['id'])>0)
        {
            $condition .= " id =". intval($_REQUEST['id']);
        }

        if(intval($_REQUEST['user_id'])>0)
        {
            $condition .= " user_id =". intval($_REQUEST['user_id']);
        }

        if(intval($_REQUEST['weibo_id'])>0)
        {
            $condition .= " id =". intval($_REQUEST['weibo_id']);
        }
        if($status!==''){
            $condition .= " status =". intval($_REQUEST['status']);
        }


        $create_time_2=empty($_REQUEST['create_time_2'])?to_date($now,'Y-m-d'):strim($_REQUEST['create_time_2']);
        $create_time_2=to_timespan($create_time_2)+24*3600;
        if(trim($_REQUEST['create_time_1'])!='' )
        {
            $map[DB_PREFIX.'weibo.create_time'] = array('between',array(to_timespan($_REQUEST['create_time_1']),$create_time_2));

            $condition .= " create_time  between (".to_timespan($_REQUEST['create_time_1']).",".$create_time_2." ) ";
        }

        $count = M('WeiboAllege')->where($condition)->count();
        $p     = new Page($count, $listRows = 20);
        //举报类型

        $weibo  =  M('WeiboAllege')->where($condition)->order('id desc')->limit($p->firstRow . ',' . $p->listRows)->findAll();
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
            $rel_data = M('WeiboAllege')->where($condition)->findAll();
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
}
?>