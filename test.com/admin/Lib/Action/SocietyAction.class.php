<?php

class SocietyAction extends CommonAction
{
    protected static function str_trim($str)
    {
        $str = strim($str);
        $str = preg_replace("@<script(.*?)</script>@is", "", $str);
        $str = preg_replace("@<iframe(.*?)</iframe>@is", "", $str);
        $str = preg_replace("@<style(.*?)</style>@is", "", $str);
        return preg_replace("@<(.*?)>@is", "", $str);
    }

    // 公会列表
    public function index()
    {
        $where = 's.user_id = u.id';
        if (isset($_REQUEST['name'])) {
            $where .= ' and s.name like \'%' . addslashes($_REQUEST['name']) . '%\'';
        }
        if (isset($_REQUEST['nick_name'])) {
            $where .= ' and u.nick_name like \'%' . addslashes($_REQUEST['nick_name']) . '%\'';
        }
        if ($_REQUEST['begin_time']) {
            $where .= ' and s.create_time>=' . strtotime($_REQUEST['begin_time']);
        }
        if ($_REQUEST['end_time']) {
            $where .= ' and s.create_time<=' . strtotime($_REQUEST['end_time']);
        }
        if (!isset($_REQUEST['status'])) {
            $_REQUEST['status'] = -1;
        }
        if ($_REQUEST['status'] != -1) {
            $where .= ' and s.status=' . intval($_REQUEST['status']);
        }else{
            $where .= ' and s.status!=4 ';
        }

        $model = M('society');
        $table = DB_PREFIX .'society s,'.DB_PREFIX .'user u';
        $count = $model->table($table)->where($where)->count();
        $p     = new Page($count, $listRows = 10);
        if ($count) {
            $field = 's.*,u.nick_name';
            $list  = $model->table($table)->where($where)->field($field)->order('s.id')->limit($p->firstRow . ',' . $p->listRows)->select();
            foreach ($list as $key => $value) {
                $list[$key]['create_time'] = to_date($value['create_time']);
                if($list[$key]['logo'] !=''){
                    $list[$key]['logo'] = get_spec_image($value['logo'],35,35);
                }
            }
        }

        $this->assign("page", $p->show());
        $this->assign("list", $list);
        $this->display();

    }

    //公会详情
    public function edit()
    {
        $id     = intval($_REQUEST['id']);
        $model  = M('society');
        $table  = DB_PREFIX .'society s,'.DB_PREFIX .'user u';
        $field  = 's.*,u.nick_name';
        $where  = 's.user_id = u.id and s.id=' . $id;
        $data   = $model->table($table)->where($where)->field($field)->find();
        if ($data) {
            $data['create_time'] = to_date($data['create_time']);
            $data['logo'] = get_spec_image($data['logo']);
        }
        $this->assign('vo', $data);
        $this->display();
    }

    //更新公会信息
    public function update()
    {

        $id     = intval($_REQUEST['id']);
        $status = intval($_REQUEST['status']);
        $memo   = self::str_trim($_REQUEST['memo']);
        $manifesto   = self::str_trim($_REQUEST['manifesto']);
        $name        = self::str_trim($_REQUEST['name']);
        $refund_rate = $_REQUEST['refund_rate'];
        if (floatval($refund_rate) < 0 || floatval($refund_rate) > 1){
            $this->error("提现比例范围为0~1");
        }elseif (floatval($refund_rate) == 0){
                $m_config = load_auto_cache('m_config');
                $refund_rate = $m_config['society_public_rate'];
        }
        $this->assign("jumpUrl", u(MODULE_NAME . "/edit", array("id" => $id)));
        if (!$id) {
            $this->error("参数错误");
        }
        $modal    = M('society');
        $society  = $modal->field('name,user_id,status')->where("id=" . $id)->find();
        $log_info = $society['name'];
        $user_id  = $society['user_id'];
        $status   = $society['status'] == '1' ? 1 : $status;
        $res      = $modal->save(array('memo' => $memo,'name'=>$name,'manifesto'=>$manifesto,'status' => $status,'refund_rate' => $refund_rate,'id' => $id));

        M('user')->save(array('society_id' => $id, 'society_chieftain' => 1, 'id' => $user_id));

        fanwe_require(APP_ROOT_PATH . 'mapi/lib/redis/BaseRedisService.php');
        fanwe_require(APP_ROOT_PATH . 'mapi/lib/redis/UserRedisService.php');
        $user_redis = new UserRedisService();
        $user_redis->update_db($user_id, array('society_id' => $id, 'family_chieftain' => 1));

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

    public function view()
    {
        $id     = intval($_REQUEST['id']);
        $model  = M('society');
        $field  = 'name';
        $where  = 'id=' . $id;
        $society = $model->where($where)->field($field)->find();
        $table  = DB_PREFIX .'society s,'.DB_PREFIX .'user u,'.DB_PREFIX .'society_apply sa';

        $where  = 'u.id =sa.user_id  and s.id ='.$id.' and  sa.society_id='.$id ;

        if (!isset($_REQUEST['status'])) {
            $_REQUEST['status'] = -1;
        }
        if ($_REQUEST['status'] != -1) {
            $where .= ' and sa.status=' . intval($_REQUEST['status']);
        } else {
            $where .= ' and sa.status<3';
        }

        $count = $model->table($table)->where($where)->count();
        $p     = new Page($count, $listRows = 20);
        if ($count) {
            $field = 's.*,u.nick_name,u.id,u.head_image,sa.society_id,sa.`status`';

            $list = $model->table($table)->where($where)->field($field)->order('s.id')->limit($p->firstRow . ',' . $p->listRows)->select();

            foreach ($list as $key => $value) {
                $list[$key]['create_time'] = to_date($value['create_time']);
                $list[$key]['head_image']  = get_spec_image($value['head_image']);
            }
        }

        $this->assign('id', $id);
        $this->assign('society', $society);
        $this->assign('list', $list);
        $this->assign("page", $p->show());

        $this->display();
    }

    //解散公会
    public function dissolve(){
        $id = intval($_REQUEST['id']);
        if (!$id) {
            $this->error("参数错误");
        }
        $model = M('society');

        $society_member =$GLOBALS['db']->getOne( "select count(id) from ".DB_PREFIX."society_apply where (status=1 or status=3) and society_id=".$id);
        if (intval($society_member) > 1){
            $this->error("公会还有其他未退出的成员");
        }else{
            $data = array();
            $data['society_id'] = 0;
            $data['society_chieftain'] = 0;
            $user_id = $model->where('id='.$id)->getField('user_id');
            M('user')->where('id='.$user_id)->save($data);
            fanwe_require(APP_ROOT_PATH . 'mapi/lib/redis/BaseRedisService.php');
            fanwe_require(APP_ROOT_PATH . 'mapi/lib/redis/UserRedisService.php');
            $user_redis = new UserRedisService();
            $user_redis->update_db($user_id, array('society_id' => 0, 'society_chieftain' => 0));

            //将请求加入公会的申请设为拒绝
            M('society_apply')->where('society_id='.$id)->setField("status",2);
            //status=4 已解散工会
            $res = $model->where("id=".$id)->setField("status",4);
            $log_info = $id;
            if ($res === false) {
                //错误提示
                save_log($log_info . "公会解散失败", 0);
                $this->error(L("公会解散失败"), 0, $log_info . L("公会解散失败"));
            } else {
                //成功提示
                save_log($log_info . L("公会解散成功"), 1);
                $this->success(L("公会解散成功"));
            }
        }
    }
}

