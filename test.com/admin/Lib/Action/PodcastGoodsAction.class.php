<?php
// +----------------------------------------------------------------------
// | Fanwe 方维p2p借贷系统
// +----------------------------------------------------------------------
// | Copyright (c) 2011 http://www.fanwe.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: 甘味人生(526130@qq.com)
// +----------------------------------------------------------------------

class PodcastGoodsAction extends CommonAction{
    public function index()
    {

        if(strim($_REQUEST['name'])!=''){
            $map['name'] = array('like','%'.strim($_REQUEST['name']).'%');
        }
        if(intval($_REQUEST['user_id'])!=''){
            $map['user_id'] = intval($_REQUEST['user_id']);
        }

        //$name=$this->getActionName();
        $model = D ('podcast_goods');
        if (! empty ( $model )) {
            $this->_list ( $model, $map );
        }

        $list = $this->get('list');

        foreach($list as $k => $v){
            $list[$k]['url'] = htmlspecialchars_decode($v['url']);
            $list[$k]['imgs'] = json_decode($v['imgs'],1)[0];
        }

        $this->assign("list",$list);
        $this->display();

    }

    public function set_effect()
    {
        $id = intval($_REQUEST['id']);
        $ajax = intval($_REQUEST['ajax']);
        $info = M('podcast_goods')->where("id=".$id)->getField("name");
        $c_is_effect = M('podcast_goods')->where("id=".$id)->getField("is_effect");  //当前状态
        $n_is_effect = $c_is_effect == 0 ? 1 : 0; //需设置的状态
        M('podcast_goods')->where("id=".$id)->setField("is_effect",$n_is_effect);
        save_log($info.l("SET_EFFECT_".$n_is_effect),1);
        $this->ajaxReturn($n_is_effect,l("SET_EFFECT_".$n_is_effect),1)	;
    }


}
?>