<?php
// +----------------------------------------------------------------------
// | Fanwe 方维众筹商业系统
// +----------------------------------------------------------------------
// | Copyright (c) 2011 http://www.fanwe.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: 甘味人生(526130@qq.com)
// +----------------------------------------------------------------------
class FamilyAction extends CommonAction
{
    protected static function str_trim($str)
    {
        $str = preg_replace("@<script(.*?)</script>@is", "", $str);
        $str = preg_replace("@<iframe(.*?)</iframe>@is", "", $str);
        $str = preg_replace("@<style(.*?)</style>@is", "", $str);
        return preg_replace("@<(.*?)>@is", "", $str);
    }
    public function index()
    {
        $where = 'f.user_id = u.id';
        if (isset($_REQUEST['name'])) {
            $where .= ' and f.name like \'%' . addslashes($_REQUEST['name']) . '%\'';
        }
        if (isset($_REQUEST['nick_name'])) {
            $where .= ' and u.nick_name like \'%' . addslashes($_REQUEST['nick_name']) . '%\'';
        }
        if ($_REQUEST['begin_time']) {
            $where .= ' and f.create_time>=' . strtotime($_REQUEST['begin_time']);
        }
        if ($_REQUEST['end_time']) {
            $where .= ' and f.create_time<=' . strtotime($_REQUEST['end_time']);
        }
        if (!isset($_REQUEST['status'])) {
            $_REQUEST['status'] = -1;
        }
        if ($_REQUEST['status'] != -1) {
            $where .= ' and f.status=' . intval($_REQUEST['status']);
        }
        $model = M('family');
        $table = DB_PREFIX .'family f,'.DB_PREFIX .'user u';
        $count = $model->table($table)->where($where)->count();
        $p     = new Page($count, $listRows = 20);
        if ($count) {
            $field = 'f.*,u.nick_name';
            $list  = $model->table($table)->where($where)->field($field)->order('f.id desc')->limit($p->firstRow . ',' . $p->listRows)->select();
            foreach ($list as $key => $value) {
                $list[$key]['create_time'] = to_date($value['create_time']);
                if($list[$key]['logo'] !=''){
                    $list[$key]['logo'] = get_spec_image($value['logo']);
                }
            }
        }
        $this->assign("page", $p->show());
        $this->assign("list", $list);
        $this->display();
    }
    public function edit()
    {
        $id     = intval($_REQUEST['id']);
        $model  = M('family');
        $table  = DB_PREFIX .'family f,'.DB_PREFIX .'user u';
        $field  = 'f.*,u.nick_name';
        $where  = 'f.user_id = u.id and f.id=' . $id;
        $family = $model->table($table)->where($where)->field($field)->find();
        if ($family) {
            $family['create_time'] = to_date($family['create_time']);
            $family['logo'] = get_spec_image($family['logo']);
        }
        $this->assign('vo', $family);
        $this->display();
    }
    public function view()
    {
        $id     = intval($_REQUEST['id']);
        $model  = M('family');
        $field  = 'name';
        $where  = 'id=' . $id;
        $family = $model->where($where)->field($field)->find();
        $table  = DB_PREFIX .'family f,'.DB_PREFIX .'user u,'.DB_PREFIX .'family_join j';

        $where  = 'u.id =j.user_id  and f.id ='.$id.' and  j.family_id='.$id ;

        if (!isset($_REQUEST['status'])) {
            $_REQUEST['status'] = -1;
        }
        if ($_REQUEST['status'] != -1) {
            $where .= ' and j.status=' . intval($_REQUEST['status']);
        } else {
            $where .= ' and j.status<3';
        }




        $count = $model->table($table)->where($where)->count();
        $p     = new Page($count, $listRows = 20);
        if ($count) {
            $field = 'f.*,u.nick_name,u.id,u.head_image,j.family_id,j.`status`,f.name';

            $list = $model->table($table)->where($where)->field($field)->order('f.id desc')->limit($p->firstRow . ',' . $p->listRows)->select();

            foreach ($list as $key => $value) {
                $list[$key]['create_time'] = to_date($value['create_time']);
                $list[$key]['head_image'] = get_spec_image($value['head_image']);
            }
        }

        $this->assign('id', $id);
        $this->assign('family', $family);
        $this->assign('list', $list);
        $this->assign("page", $p->show());
        $this->display();
    }
    public function update()
    {

        $id     = intval($_REQUEST['id']);
        $status = intval($_REQUEST['status']);
        $memo   = self::str_trim($_REQUEST['memo']);
        $manifesto   = strim($_REQUEST['manifesto']);
        $name   = strim($_REQUEST['name']);
        $this->assign("jumpUrl", u(MODULE_NAME . "/edit", array("id" => $id)));
        if (!$id) {
            $this->error("参数错误");
        }
        $mod      = M('family');
        $family   = $mod->field('name,user_id,status')->where("id=" . $id)->find();
        $log_info = $family['name'];
        $user_id  = $family['user_id'];
        $status   = $family['status'] == '1' ? 1 : $status;
        $res      = $mod->save(array('memo' => $memo,'name'=>$name,'manifesto'=>$manifesto,'status' => $status, 'id' => $id));
        if (false === $res) {
            //错误提示
            save_log($log_info . L("UPDATE_FAILED"), 0);
            $this->error(L("UPDATE_FAILED"), 0, $log_info . L("UPDATE_FAILED"));
        } else {
            //成功提示
            clear_auto_cache("banner_list");
            load_auto_cache("banner_list");
            save_log($log_info . L("UPDATE_SUCCESS"), 1);
            $this->success(L("UPDATE_SUCCESS"));
        }
    }
    public function join()
    {
        $id    = intval($_REQUEST['id']);
        $model = M('family_join');
        $count = $model->where(array('id' => $id))->count();
        $p     = new Page($count, $listRows = 20);
        if ($count) {
            $table = DB_PREFIX .'family_join j,'.DB_PREFIX.'user u';
            $where = 'j.user_id = u.id';
            $field = 'j.*,u.nick_name';
            $list  = $model->table($table)->where($where)->field($field)->order('j.id desc')->limit($p->firstRow . ',' . $p->listRows)->select();
        }
        $this->assign("page", $p->show());
        $this->assign("list", $list);
        $this->display();
    }
    public function updateJoin()
    {
        $id     = intval($_REQUEST['id']);
        $status = intval($_REQUEST['status']);
        $memo   = self::str_trim($_REQUEST['memo']);
        $this->assign("jumpUrl", u(MODULE_NAME . "/editJoin", array("id" => $data['id'])));
        if (!$id) {
            $this->error("参数错误");
        }
        $log_info = M('family_join')->where("id=" . $id)->getField("id");
        $res      = M('family_join')->save($data);
        if (false === $res) {
            //错误提示
            save_log($log_info . L("UPDATE_FAILED"), 0);
            $this->error(L("UPDATE_FAILED"), 0, $log_info . L("UPDATE_FAILED"));
        } else {
            //成功提示
            clear_auto_cache("banner_list");
            load_auto_cache("banner_list");
            save_log($log_info . L("UPDATE_SUCCESS"), 1);
            $this->success(L("UPDATE_SUCCESS"));
        }
    }
    //解散家族1
    public function dissolve(){
        $id = intval($_REQUEST['id']);
        if (!$id) {
            $this->error("参数错误");
        }
        $model = M('family');

        $family_member =$GLOBALS['db']->getOne( "select count(*) from ".DB_PREFIX."family_join where status=1 and family_id=".$id);
        if (intval($family_member) > 0){
            $this->error("家族还有未退出的成员");
        }else{
            $data = array();
            $data['family_id'] = 0;
            $data['family_chieftain'] = 0;
            $user_id = $model->where('id='.$id)->getField('user_id');
            M('user')->where('id='.$user_id)->save($data);
            fanwe_require(APP_ROOT_PATH . 'mapi/lib/redis/BaseRedisService.php');
            fanwe_require(APP_ROOT_PATH . 'mapi/lib/redis/UserRedisService.php');
            $user_redis = new UserRedisService();
            $user_redis->update_db($user_id, array('family_id' => 0, 'family_chieftain' => 0));

            //将请求加入家族的申请设为拒绝
            M('family_join')->where('status=0 and family_id='.$id)->setField("status",2);

            $res = $model->where("id=".$id)->delete();
            $log_info = $id;
            if ($res === false) {
                //错误提示
                save_log($log_info . "家族解散失败", 0);
                $this->error(L("家族解散失败"), 0, $log_info . L("家族解散失败"));
            } else {
                //成功提示
                save_log($log_info . L("家族解散成功"), 1);
                $this->success(L("家族解散成功"));
            }
        }
    }
    public function test()
    {
        fanwe_require(APP_ROOT_PATH . 'mapi/lib/redis/BaseRedisService.php');
        fanwe_require(APP_ROOT_PATH . 'mapi/lib/redis/UserRedisService.php');
        $user_redis = new UserRedisService();
        $res        = $user_redis->getRow_db(100990);
        echo "<pre>";
        var_dump($res);
    }
}
