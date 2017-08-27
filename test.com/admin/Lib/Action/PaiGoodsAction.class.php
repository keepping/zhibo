<?php
// +----------------------------------------------------------------------
// | Fanwe 方维众筹商业系统
// +----------------------------------------------------------------------
// | Copyright (c) 2011 http://www.fanwe.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: 甘味人生(526130@qq.com)
// +----------------------------------------------------------------------

class PaiGoodsAction extends CommonAction
{

    public function index()
    {
        parent::index();
    }
    /**
     *
     * 搜索查询
     */
    public function search()
    {
        $map = array();
        if (trim($_REQUEST['name']) != '') {
            $map['name'] = array('like', '%' . trim($_REQUEST['name']) . '%');
        }
        $log_begin_time = trim($_REQUEST['log_begin_time']) == '' ? 0 : to_timespan($_REQUEST['log_begin_time']);
        $log_end_time   = trim($_REQUEST['log_end_time']) == '' ? 0 : to_timespan($_REQUEST['log_end_time']);
        if ($log_end_time != 0 && $log_begin_time != 0) {
            $map['create_time'] = array('between', array($log_begin_time, $log_end_time));
        } elseif ($log_begin_time != 0 && $log_end_time == 0) {
            $map['create_time'] = array('gt', $log_begin_time);
        } elseif ($log_begin_time == 0 && $log_end_time != 0) {
            $map['create_time'] = array('lt', $log_begin_time);
        }
        $count = M('pai_goods')->where($map)->count();
        $p     = new Page($count, $listRows = 20);
        if ($count > 0) {
            $list = M('pai_goods')->where($map)->order('id desc')->limit($p->firstRow . ',' . $p->listRows)->select();
        }
        $page = $p->show();
        $this->assign("page", $page);
        $this->assign("list", $list);
        $this->display('index');
    }

    public function add()
    {
        $this->assign("type_list", $this->type_list);
        $this->assign("new_sort", M("Pai_goods")->max("sort") + 1);
        $this->display();
    }
    /**
     *
     * 拍品添加
     */
    public function insert()
    {
        B('FilterString');
        $ajax = intval($_REQUEST['ajax']);
        $data = $_POST;

        //开始验证有效性
        $this->assign("jumpUrl", u(MODULE_NAME . "/add"));
        if (!check_empty($data['name'])) {
            $this->error("请输入拍品名称");
        }
        if (!check_empty($data['date_time'])) {
            $this->error("请输入约会时间");
        }
        $data['date_time'] = to_timespan($data['date_time']);
        if (!check_empty($data['contact'])) {
            $this->error("请输入联系人");
        }
        if (!check_empty($data['mobile'])) {
            $this->error("请输入联系电话");
        }
        if (!check_empty($data['bz_diamonds'])) {
            $this->error("请输入竞拍保证金");
        }
        if (!check_empty($data['qp_diamonds'])) {
            $this->error("请输入起拍价");
        }
        if (!check_empty($data['jj_diamonds'])) {
            $this->error("请输入每次加价");
        }
        if (!check_empty($data['description'])) {
            $this->error("请输入拍品描述");
        }
        // 更新数据
        $log_info            = $data['name'];
        $data['create_time'] = to_timespan(get_gmtime());
        $list                = M("pai_goods")->add($data);
        if (false !== $list) {
            //load_auto_cache("index_image",'',false);
            //redis缓存
            clear_auto_cache("banner_list");
            load_auto_cache("banner_list");
            //成功提示
            save_log($log_info . L("INSERT_SUCCESS"), 1);
            $this->success(L("INSERT_SUCCESS"));
        } else {
            //错误提示
            save_log($log_info . L("INSERT_FAILED"), 0);
            $this->error(L("INSERT_FAILED"));
        }
    }
    /**
     *
     * 拍品详情
     */
    public function edit()
    {
        $this->assign("type_list", $this->type_list);
        $id = intval($_REQUEST['id']);
        if (!isset($id) || empty($id)) {
            $this->error('系统繁忙，参数不存在！');
        }
        $vo = M(MODULE_NAME)->where(array('id' => $id))->find();
        if (!empty($vo['imgs'])) {
            $imgs = json_decode($vo['imgs']);
            foreach($imgs as $k =>$v){
                $imgs[$k]=get_spec_image($v);
            }
            $this->assign('imgs', $imgs);
        }
        if ($vo['date_time'] == '0000-00-00 00:00:00') {
            $vo['date_time'] = '无';
        }
        if ($vo['order_time'] == '0000-00-00 00:00:00') {
            $vo['order_time'] = '无';
        }

        $vo['create_time'] = to_date($vo['create_time']) ?: '无';

        if ($vo['is_true'] == 0) {
            $vo['is_true'] = "虚拟";
        } else {
            $vo['is_true'] = "实物";
        }
        switch ($vo['status']) {
            case '0':
                $vo['status'] = "竞拍中";
                break;
            case '1':
                // (完成)
                $vo['status'] = "竞拍成功";
                break;
            case '2':
                $vo['status'] = "流拍";
                break;
            case '3':
                $vo['status'] = "竞拍失败";
                break;
            case '4':
                // (完成)
                $vo['status'] = "竞拍成功";
                break;

            default:
                # code...
                break;
        }
        if ($vo['status'] == "竞拍成功") {
            $user = M('PaiJoin')->where(array('pai_id' => $id, 'user_id' => $vo['user_id']))->find();
            $this->assign('user', $user);
        }

        $this->assign('vo', $vo);
        $this->display();
    }

    /**
     *
     * 参拍记录
     */
    public function pai_record()
    {
        $id = intval($_REQUEST['id']);
        if (!isset($id) || empty($id)) {
            $this->error('系统繁忙，参数不存在！');
        }
        $map = array('pai_id' => $id);

        $count = M('pai_join')->where($map)->count();
        $p     = new Page($count, $listRows = 20);
        if ($count > 0) {
            $max  = M('pai_join')->where($map)->max('pai_diamonds');
            $list = M('pai_join')->where($map)->limit($p->firstRow . ',' . $p->listRows)->select();
            foreach ($list as &$value) {
                $podcast_name = M('pai_goods')->where(array('id' => $value['pai_id']))->field('podcast_name')->find();
                $user         = M('user')->where(array('id' => $value['user_id']))->field('nick_name')->find();

                $value['podcast_name'] = $podcast_name['podcast_name'];
                $value['user_name']    = $user['nick_name'];
                $value['create_time']  = to_date($value['create_time']);
                $value['max']          = $max;
            }
        }
        $page = $p->show();
        $this->assign("page", $page);
        $this->assign("list", $list);
        $this->display();
    }
    /**
     *
     * 出价记录
     */
    public function price_record()
    {
        $id = intval($_REQUEST['id']);
        if (!isset($id) || empty($id)) {
            $this->error('系统繁忙，参数不存在！');
        }
        $count = M('pai_log')->where(array('pai_id' => $id))->count();
        $p     = new Page($count, $listRows = 20);
        if ($count > 0) {
            $info = M('pai_log')->where(array('pai_id' => $id))->limit($p->firstRow . ',' . $p->listRows)->select();
        }
        $page = $p->show();
        $this->assign("page", $page);
        $this->assign("list", $info);
        $this->display();
    }
    public function security_deposit_records()
    {
        $id = intval($_REQUEST['id']);
        if (!$id) {
            $this->error('系统繁忙，参数不存在！');
        }
        $mod   = M('user_diamonds_log');
        $count = $mod->where(array('pai_id' => $id, 'type' => 1))->count();
        $p     = new Page($count, $listRows = 20);
        if ($count > 0) {
            $info = $mod->table('fanwe_user_diamonds_log log, fanwe_user user')->where("log.user_id = user.id and log.pai_id = $id and log.type=1")->field('log.*,user.nick_name')->order('log.id desc')->limit($p->firstRow . ',' . $p->listRows)->select();
        }
        $page = $p->show();
        $this->assign("page", $page);
        $this->assign("list", $info);
        $this->display();
    }

    public function detail()
    {
        $this->assign("type_list", $this->type_list);
        $id = intval($_REQUEST['id']);
        if (!isset($id) || empty($id)) {
            $this->error('系统繁忙，参数不存在！');
        }
        $podcast_id = M('pai_goods')->where(array('id' => $id))->getField('podcast_id');
        $user       = M('user')->where(array('id' => $podcast_id))->field('is_authentication')->find();

        if ($user['is_authentication'] == 2) {
            $this->redirect(u("User/index", array('is_authentication' => $user['is_authentication'], 'id' => $podcast_id)));
        } else {
            $this->redirect(u("UserGeneral/index", array('is_authentication' => $user['is_authentication'], 'id' => $podcast_id)));
        }

    }

}
