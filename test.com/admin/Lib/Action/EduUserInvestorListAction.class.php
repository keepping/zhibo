<?php

class EduUserInvestorListAction extends CommonAction
{
    public function index()
    {
        $map = array();
        if (intval($_REQUEST['id']) > 0) {
            $map[DB_PREFIX . 'user.id'] .= intval($_REQUEST['id']);
        }
        if (trim($_REQUEST['nick_name']) != '') {
            $map[DB_PREFIX . 'user.nick_name'] = array('like', '%' . trim($_REQUEST['nick_name']) . '%');
        }
        if (trim($_REQUEST['contact']) != '') {
            $map[DB_PREFIX . 'user.contact'] = array('like', '%' . trim($_REQUEST['contact']) . '%');
        }
        if (trim($_REQUEST['mobile']) != '') {
            $map[DB_PREFIX . 'user.mobile'] = array('like', '%' . trim($_REQUEST['mobile']) . '%');
        }

        $map[DB_PREFIX . 'user.is_authentication'] = 3;
        $map[DB_PREFIX . 'user.is_effect'] = 1;
        if (method_exists($this, '_filter')) {
            $this->_filter($map);
        }
        //$name=$this->getActionName();
        $model = D('User');
        if (!empty ($model)) {
            $this->_list($model, $map);
        }
        $this->display();
    }

    public function show_content()
    {
        $id = intval($_REQUEST['id']);
        $status = intval($_REQUEST['status']);

        $user = M("user")->getById($id);
        $user['do_info'] = '审核通过';

        $user['is_investor_name'] = get_investor($user['user_type']);
        $user['investor_status_name'] = get_investor_status($user['is_authentication']);

        $user['identify_hold_image'] = get_spec_image($user['identify_hold_image']);
        $user['identify_positive_image'] = get_spec_image($user['identify_positive_image']);
        $user['identify_nagative_image'] = get_spec_image($user['identify_nagative_image']);
        if ($user['authentication_type'] == '教师') {
            $teacher = M('EduTeacher')->where(array('user_id' => $user['id']))->find();
            $user['teaching_certificate'] = get_spec_image($teacher['teaching_certificate']);
            $user['education_certificate'] = get_spec_image($teacher['education_certificate']);
        } elseif ($user['authentication_type'] == '机构') {
            $org = M('EduOrg')->where(array('user_id' => $user['id']))->find();
            $user['business_license'] = get_spec_image($org['business_license']);
        }
        $this->assign('user', $user);
        $this->assign('status', $status);
        $this->display();
    }

    public function investor_go_allow()
    {
        $id = intval($_REQUEST['id']);
        $status = intval($_REQUEST['is_authentication']);
        if ($_REQUEST['investor_send_info']) {
            $investor_send_info = strim($_REQUEST['investor_send_info']);
        }
        fanwe_require(APP_ROOT_PATH . 'mapi/lib/redis/BaseRedisService.php');
        fanwe_require(APP_ROOT_PATH . 'mapi/lib/redis/UserRedisService.php');
        $user_redis = new UserRedisService();
        $user = M("User")->getById($id);
        if ($user) {
            $user_data['id'] = $user['id'];
            $user_data['is_authentication'] = $status;
            if ($status == 3) {
                $user_data['v_explain'] = '';
                $user_data['v_icon'] = '';
            } else {
                $user_data['v_explain'] = $_REQUEST['v_explain'];
                if ($user_data['v_explain'] == '') {
                    $user_data['v_explain'] = $user['authentication_type'];
                }
                $user_data['v_icon'] = get_spec_image(M('AuthentList')->where("name='" . trim($user['authentication_type'] . "'"))->getField("icon"));
            }

            if ($investor_send_info) {
                $user_data['investor_send_info'] = $investor_send_info;
            } else {
                $user_data['investor_send_info'] = '';
            }

            $list = M("User")->save($user_data);
            if ($list !== false) {
                $is_effect = $user['is_authentication'] == 2 ? 1 : 0;
                if ($user['authentication_type'] == '教师') {
                    $GLOBALS['db']->autoExecute(DB_PREFIX . 'edu_teacher', array('is_effect' => $is_effect), 'UPDATE',
                        'user_id=' . $id);
                } elseif ($user['authentication_type'] == '机构') {
                    $GLOBALS['db']->autoExecute(DB_PREFIX . 'edu_org', array('is_effect' => $is_effect), 'UPDATE',
                        'user_id=' . $id);
                }
                save_log($user_data['id'] . "审核操作成功", 1);
            } else {
                save_log($user_data['id'] . "审核操作失败", 0);
            }
            //redis化
            $user_redis->update_db($user['id'], $user_data);
            //send_investor_status($user_data);
            $this->success("操作成功");
        } else {
            $this->error("没有该会员信息");
        }
    }


}

?>