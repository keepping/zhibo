<?php
// +----------------------------------------------------------------------
// | Fanwe 方维p2p借贷系统
// +----------------------------------------------------------------------
// | Copyright (c) 2011 http://www.fanwe.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: 云淡风轻(88522820@qq.com)
// +----------------------------------------------------------------------

class family_userModule extends baseModule
{
    //家族成员列表
    public function user_list()
    {
        $root = array();
        if (!$GLOBALS['user_info']) {
            $root['error'] = "用户未登陆,请先登陆.";// es_session::id();
            $root['status'] = 0;
            $root['user_login_status'] = 0;//有这个参数： user_login_status = 0 时，表示服务端未登陆、要求登陆，操作
        } else {
            $family_id = intval($_REQUEST['family_id']);//家族ID
            $family = $GLOBALS['db']->getRow("SELECT * FROM " . DB_PREFIX . "family WHERE id=" . $family_id, true, true);//查找是否有此家族

            if ($family_id > 0 && $family) {
                $root['status'] = 1;
                $count = $GLOBALS['db']->getOne("SELECT COUNT(id) as rs_count FROM " . DB_PREFIX . "user WHERE family_id=" . $family_id, true, true);
                $root['rs_count'] = $count;//家族成员总数
                //申请人数
                $apply_count = $GLOBALS['db']->getOne("SELECT COUNT(id) as apply_count FROM " . DB_PREFIX . "family_join WHERE family_id=" . $family_id . " and status=0", true, true);
                $root['apply_count'] = $apply_count;
                //分页
                $page = intval($_REQUEST['page']);//当前页
                $page_size = 20;//分页数量
                if ($page == 0) {
                    $page = 1;
                }

                $limit = (($page - 1) * $page_size) . "," . $page_size;
                $user = $GLOBALS['db']->getAll("SELECT id as user_id,nick_name,sex,v_type,v_icon,head_image,signature,user_level,family_chieftain FROM " . DB_PREFIX . "user WHERE family_id=" . $family_id . " ORDER BY family_chieftain desc limit " . $limit, true, true);
                foreach ($user as $k => $v) {
                    $user[$k]['head_image'] = get_spec_image($v['head_image']);
                }
                $root['list'] = $user;//家族成员信息

                $has_next = ($count > $page * $page_size) ? '1' : '0';
//				if ($count > $page*$page_size){
//					$has_next = 1;
//				}else{
//					$has_next = 0;
//				}
                $root['page'] = array('page' => $page, 'has_next' => $has_next);
            }

        }
        api_ajax_return($root);
    }


    //家族成员申请列表
    public function r_user_list()
    {
        $root = array();
        if (!$GLOBALS['user_info']) {
            $root['error'] = "用户未登陆,请先登陆.";// es_session::id();
            $root['status'] = 0;
            $root['user_login_status'] = 0;//有这个参数： user_login_status = 0 时，表示服务端未登陆、要求登陆，操作
        } else {
            $family_id = intval($_REQUEST['family_id']);//家族ID
            $family = $GLOBALS['db']->getRow("SELECT * FROM " . DB_PREFIX . "family WHERE id=" . $family_id, true, true);//查找是否有此家族
            $user = $GLOBALS['db']->getRow("SELECT family_chieftain FROM " . DB_PREFIX . "user WHERE id=" . intval($GLOBALS['user_info']['id']));
            $family_chieftain = intval($user['family_chieftain']);//jai_chieftain 为1时候，身份家族长

            if ($family_id > 0 && $family_chieftain == 1 && $family) {
                $root['status'] = 1;
                $count = $GLOBALS['db']->getOne("SELECT COUNT(id) as rs_count FROM " . DB_PREFIX . "user WHERE family_id=" . $family_id, true, true);
                $root['rs_count'] = $count;//家族成员总数
                //申请人数
                $apply_count = $GLOBALS['db']->getOne("SELECT COUNT(id) as apply_count FROM " . DB_PREFIX . "family_join WHERE family_id=" . $family_id . " and status=0", true, true);
                $root['apply_count'] = $apply_count;
                //分页
                $page = intval($_REQUEST['page']);//当前页
                $page_size = 20;//分页数量
                if ($page == 0) {
                    $page = 1;
                }

                $limit = (($page - 1) * $page_size) . "," . $page_size;
                $user = $GLOBALS['db']->getAll("SELECT a.id as user_id,a.nick_name,a.sex,a.v_type,a.v_icon,a.head_image,a.signature,user_level FROM " . DB_PREFIX . "user as a," . DB_PREFIX . "family_join as b WHERE a.id=b.user_id and b.status=0 and b.family_id=" . $family_id . " limit " . $limit, true, true);
                foreach ($user as $k => $v) {
                    $user[$k]['head_image'] = get_spec_image($v['head_image']);
                }
                $root['list'] = $user;//家族成员信息
                $has_next = ($count > $page * $page_size) ? '1' : '0';
                $root['page'] = array('page' => $page, 'has_next' => $has_next);
            }

        }
        api_ajax_return($root);
    }


    //家族成员删除
    public function user_del()
    {
        $root = array();
        if (!$GLOBALS['user_info']) {
            $root['error'] = "用户未登陆,请先登陆.";// es_session::id();
            $root['status'] = 0;
            $root['user_login_status'] = 0;//有这个参数： user_login_status = 0 时，表示服务端未登陆、要求登陆，操作
        } else {
            $r_user_id = intval($_REQUEST['r_user_id']);//删除成员ID
            $info = $GLOBALS['db']->getRow("SELECT * FROM " . DB_PREFIX . "user WHERE id=" . $r_user_id, true, true);//查找是否有此ID
            $user_family = intval($info['family_id']);//用户家族ID
            $user = $GLOBALS['db']->getRow("SELECT family_chieftain FROM " . DB_PREFIX . "user WHERE id=" . intval($GLOBALS['user_info']['id']));
            $family_chieftain = intval($user['family_chieftain']);//jai_chieftain 为1时候，身份家族长

            if ($info && $family_chieftain == 1) {
                $user['family_id'] = 0;
                $delet = $GLOBALS['db']->autoExecute(DB_PREFIX . "user", $user, $mode = 'UPDATE', "id=" . $r_user_id);

                $family['status'] = 3;
                $info = $GLOBALS['db']->autoExecute(DB_PREFIX . "family_join", $family, $mode = 'UPDATE', "user_id=" . $r_user_id . " and family_id=" . $user_family);

                if ($delet && $info) {
                    $root['status'] = 1;
                    $root['error'] = "家族成员已移除";

                    fanwe_require(APP_ROOT_PATH . 'mapi/lib/redis/UserRedisService.php');
                    $user_redis = new UserRedisService();
                    $user_redis->update_db($r_user_id, array('family_id' => 0));
                }

            }

        }
        api_ajax_return($root);
    }


    //申请加入家族
    public function user_join()
    {
        $root = array();
        if (!$GLOBALS['user_info']) {
            $root['error'] = "用户未登陆,请先登陆.";// es_session::id();
            $root['status'] = 0;
            $root['user_login_status'] = 0;//有这个参数： user_login_status = 0 时，表示服务端未登陆、要求登陆，操作
        } else {
            $family_id = intval($_REQUEST['family_id']);//家族ID
            $user_id = intval($GLOBALS['user_info']['id']);//申请加入ID

            $user = $GLOBALS['db']->getRow("SELECT family_id FROM " . DB_PREFIX . "user WHERE id=" . $user_id, true, true);//查找ID是否有加入家族
            if ($user['family_id'] > 0) {
                $root['status'] = 0;
                $root['error'] = "您已经加入家族";
                api_ajax_return($root);

            }

            $user_family = $GLOBALS['db']->getRow("SELECT * FROM " . DB_PREFIX . "family_join WHERE family_id=" . $family_id . " and user_id=" . $user_id . " and status<2", true, true);//查找是否申请过此家族
            if ($user_family) {
                $root['status'] = 0;
                $root['error'] = "已经申请过此家族";
                api_ajax_return($root);
            }

            $family_info = $GLOBALS['db']->getRow("SELECT * FROM " . DB_PREFIX . "family_join WHERE family_id=" . $family_id . " and user_id=" . $user_id . " and status>1", true, true);//查找是否有申请家族记录
            $family = $GLOBALS['db']->getRow("SELECT * FROM " . DB_PREFIX . "family WHERE id=" . $family_id, true, true);//查找是否有此家族
            if ($family_info) {
                $data['status'] = 0;
                $info = $GLOBALS['db']->autoExecute(DB_PREFIX . "family_join", $data, $mode = 'UPDATE', "user_id=" . $user_id . " and family_id=" . $family_id);

                if ($info) {
                    $root['status'] = 1;
                    $root['error'] = "申请已提交";
                    $root['family_id'] = $family_id;
                }
            } elseif ($family && $user) {
                $data['family_id'] = $family_id;
                $data['user_id'] = $user_id;
                $data['create_time'] = NOW_TIME;//获取当前时间
                $data['status'] = 0;
                $data['memo'] = $user_id . "申请加入";
                $info = $GLOBALS['db']->autoExecute(DB_PREFIX . "family_join", $data, "INSERT");

                if ($info) {
                    $root['status'] = 1;
                    $root['error'] = "申请已提交";
                    $root['family_id'] = $family_id;
                }

            } else {
                $root['status'] = 0;
                $root['error'] = "家族不存在";
            }

        }
        api_ajax_return($root);
    }

    //成员申请审核
    public function confirm()
    {
        $root = array();
        if (!$GLOBALS['user_info']) {
            $root['error'] = "用户未登陆,请先登陆.";// es_session::id();
            $root['status'] = 0;
            $root['user_login_status'] = 0;//有这个参数： user_login_status = 0 时，表示服务端未登陆、要求登陆，操作
        } else {
            fanwe_require(APP_ROOT_PATH . 'mapi/lib/redis/UserRedisService.php');
            $user_redis = new UserRedisService();
            $r_user_id = intval($_REQUEST['r_user_id']);//审核成员ID
            $is_agree = intval($_REQUEST['is_agree']);//审核标志，1通过，2拒绝
            $user_id = intval($GLOBALS['user_info']['id']);//用户ID
            $user = $GLOBALS['db']->getRow("SELECT family_id,family_chieftain FROM " . DB_PREFIX . "user WHERE id=" . $user_id);
            $family_id = $user['family_id'];//家族编号
//			$jia_status=$user['jia_status'];//家族状态
            $family_chieftain = $user['family_chieftain'];//族长标志：0不是族长。1是组长
            $has_family = $GLOBALS['db']->getRow("SELECT family_id,family_chieftain FROM " . DB_PREFIX . "user WHERE id=" . $r_user_id);
            $family = $GLOBALS['db']->getRow("SELECT COUNT(id) AS is_success FROM " . DB_PREFIX . "family WHERE user_id=" . $r_user_id . " and status != 2");
            if ($family_chieftain != 1) {//判断是否为族长
                $root['error'] = '没有权限';
                $root['status'] = 0;
                $root['r_user_id'] = $r_user_id;
                api_ajax_return($root);
            } else {

                $data['family_chieftain'] = 0;
                //更新用户信息
                if ($is_agree == 1) {
                    if ($has_family['family_id'] != 0 || $family['is_success'] > 0) {
                        $root['error'] = '该成员已有家族';
                        $root['status'] = 0;
                        $root['r_user_id'] = $r_user_id;
                        api_ajax_return($root);
                    } else {
                        $r_family_id=$family_id;
                        $GLOBALS['db']->query("UPDATE " . DB_PREFIX . "family_join SET status=2 WHERE family_id != " . $family_id . " and user_id=" . $r_user_id . " and status=0");
                        $GLOBALS['db']->query("UPDATE " . DB_PREFIX . "family SET status=2 WHERE user_id=" . $r_user_id . " and status=0");
                        $sql = "update " . DB_PREFIX . "user set family_id = " . $family_id . ",family_chieftain = 0 where id=" . $r_user_id;
                    }
                } elseif ($is_agree == 2) {
                    $sql = "update " . DB_PREFIX . "user set family_id = 0,family_chieftain = 0 where id = " . $r_user_id;
                    $r_family_id = 0;
                }
                $re = $GLOBALS['db']->query($sql);
                if ($re) {
                    //redis更新
                    $user_redis->update_db($r_user_id, array('family_id' => $r_family_id, 'family_chieftain' => 0));
                    $jsql = "update " . DB_PREFIX . "family_join set status = " . $is_agree . " where user_id = " . $r_user_id . " and family_id = " . $family_id . " and status=0";
                    $GLOBALS['db']->query($jsql);
                    if (empty($root)) {
                        $root['error'] = '申请已审核';
                        $root['status'] = 1;
                        $root['r_user_id'] = $r_user_id;
                        api_ajax_return($root);
                    }

                } else {
                    $root['error'] = '';
                    $root['status'] = 0;
                    $root['r_user_id'] = $r_user_id;
                    api_ajax_return($root);
                }
            }
        }
    }


    //退出家族
    public function logout()
    {

        if (!$GLOBALS['user_info']['id']) {
            //有这个参数： user_login_status = 0 时，表示服务端未登陆、要求登陆，操作
            ajax_return(array('error' => '用户未登陆,请先登陆.', 'status' => 0, 'user_login_status' => 0));
        }

        $user_id = intval($GLOBALS['user_info']['id']);//登陆用户ID

        $user_info = $GLOBALS['db']->getRow("SELECT * FROM " . DB_PREFIX . "user WHERE id=" . $user_id . " and family_chieftain=0", true, true);//查询是否是家族成员
        $user_family = intval($user_info['family_id']);//用户家族ID

        if ($user_info) {
            $user['family_id'] = 0;
            $delet = $GLOBALS['db']->autoExecute(DB_PREFIX . "user", $user, $mode = 'UPDATE', "id=" . $user_id);

            $family['status'] = 3;
            $info = $GLOBALS['db']->autoExecute(DB_PREFIX . "family_join", $family, $mode = 'UPDATE', "user_id=" . $user_id . " and family_id=" . $user_family);

            if ($delet && $info) {
                fanwe_require(APP_ROOT_PATH . 'mapi/lib/redis/UserRedisService.php');
                $user_redis = new UserRedisService();
                $user_redis->update_db($user_id, array('family_id' => 0));
                es_session::set("user_info", $user_info);
            }
            ajax_return(array('error' => '已退出家族', 'status' => 1));

        } else {
            ajax_return(array('error' => '操作的业务动作失败', 'status' => 10002));
        }

    }

}

?>
