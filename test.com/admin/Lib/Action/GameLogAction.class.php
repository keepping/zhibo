<?php
// +----------------------------------------------------------------------
// | Fanwe 方维p2p借贷系统
// +----------------------------------------------------------------------
// | Copyright (c) 2011 http://www.fanwe.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: 甘味人生(526130@qq.com)
// +----------------------------------------------------------------------

class GameLogAction extends CommonAction
{
    public function index()
    {
        $map = array();
        if ($_REQUEST['podcast_id']) {
            $map['podcast_id'] = intval($_REQUEST['podcast_id']);
        }
        if ($_REQUEST['game_id']) {
            $map['game_id'] = intval($_REQUEST['game_id']);
        }
        $name  = $this->getActionName();
        $model = D($name);
        if (!empty($model)) {
            $this->_list($model, $map);
        }
        $games = M('games')->findAll();
        $this->assign ( 'url_name', get_manage_url_name());
        $this->assign("games", $games);
        $this->display();
    }

    public function edit()
    {
        $id = intval($_REQUEST['id']);
        $vo = M(MODULE_NAME)->where(array('id' => $id))->find();

        $game_id   = $vo['game_id'];
        $game_type = array(
            1 => array(
                '豹子',
                '同花顺',
                '同花',
                '顺子',
                '对子',
                '单牌',
            ),
            2 => array(
                '五小',
                '炸弹',
                '五花',
                '四花',
                '牛牛',
                '牛九',
                '牛八',
                '牛七',
                '牛六',
                '牛五',
                '牛四',
                '牛三',
                '牛二',
                '牛一',
                '没牛',
            ),
        );
        $color = array('spade', 'heart', 'club', 'diamond');
        $cards = json_decode($vo['suit_patterns'], 1);
        foreach ($cards as $key => $value) {
            $img = '';
            foreach ($value['cards'] as $v) {
                $img .= '<img src="/admin/Tpl/default/Common/img/' . $color[$v[0]] . str_pad($v[1], 2, '0', 0) . '.gif" />';
            }
            $cards[$key]['img'] = $img;
            unset($cards[$key]['cards']);
            $cards[$key]['type'] = $game_type[$game_id][$value['type']];
        }
        $this->assign('cards', $cards);
        $this->assign('vo', $vo);
        $this->display();
    }
}
