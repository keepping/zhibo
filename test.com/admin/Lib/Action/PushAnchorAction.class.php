<?php
// +----------------------------------------------------------------------
// | Fanwe 方维p2p借贷系统
// +----------------------------------------------------------------------
// | Copyright (c) 2011 http://www.fanwe.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: 甘味人生(526130@qq.com)
// +----------------------------------------------------------------------

class PushAnchorAction extends CommonAction{
    public function index()
    {
        $now=get_gmtime();
        if(strim($_REQUEST['nick_name'])!=''){
            $map['nick_name'] = array('like','%'.strim($_REQUEST['nick_name']).'%');
        }
        if(strim($_REQUEST['room_id'])!='')
        {
            $map['room_id']=array('like','%'.strim($_REQUEST['room_id']).'%');
        }
        if(strim($_REQUEST['status'])!='')
        {
            $map['status']=$_REQUEST['status'];
        }
        if(strim($_REQUEST['pust_type'])!='')
        {
            $map['pust_type']=$_REQUEST['pust_type'];
        }

        $create_time_2=empty($_REQUEST['create_time_2'])?to_date($now,'Y-m-d'):strim($_REQUEST['create_time_2']);
        $create_time_2=to_timespan($create_time_2)+24*3600;
        if(trim($_REQUEST['create_time_1'])!='')
        {
            $map['create_time'] = array('between',array(to_timespan($_REQUEST['create_time_1']),$create_time_2));
        }

        if (method_exists ( $this, '_filter' )) {
            $this->_filter ( $map );
        }
        $name=$this->getActionName();
        $model = D ($name);
        if (! empty ( $model )) {
            $this->_list ( $model, $map );
        }
        $this->display ();
    }


    //彻底删除指定记录
    public function delete() {
        //彻底删除指定记录
        $ajax = intval($_REQUEST['ajax']);
        $id = $_REQUEST ['id'];
        if (isset ( $id )) {
            $condition = array ('id' => array ('in', explode ( ',', $id ) ) );
            $rel_data = M(MODULE_NAME)->where($condition)->findAll();
            foreach($rel_data as $data)
            {
                $info[] = $data['room_id'];
            }
            if($info) $info = implode(",",$info);
            $list = M(MODULE_NAME)->where ( $condition )->delete();
            if ($list!==false) {
                //删除子动画
                save_log($info.l("FOREVER_DELETE_SUCCESS"),1);
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