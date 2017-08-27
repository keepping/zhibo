<?php
// +----------------------------------------------------------------------
// | Fanwe 方维p2p借贷系统
// +----------------------------------------------------------------------
// | Copyright (c) 2011 http://www.fanwe.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: 云淡风轻(88522820@qq.com)
// +----------------------------------------------------------------------

class familyModule extends baseModule
{
//家族首页1
    function index()
    {
        $root = array();
        if (!$GLOBALS['user_info']) {
            $root['error'] = "用户未登陆,请先登陆.";// es_session::id();
            $root['status'] = 0;
            $root['user_login_status'] = 0;//有这个参数： user_login_status = 0 时，表示服务端未登陆、要求登陆，操作
        } else {
            $family_id = intval($_REQUEST['family_id']);//家族ID
            $user_id = intval($GLOBALS['user_info']['id']);//创建人id
            if ($family_id != '') {
                $sql = "SELECT f.id as family_id,f.logo as family_logo,f.name as family_name,f.create_time,f.memo,f.status,f.manifesto as family_manifesto,b.user_count,f.user_id,(select nick_name from " . DB_PREFIX . "user where id=f.user_id) as nick_name from (select family_id, count(*) as user_count from " . DB_PREFIX . "user where family_id = ".$family_id." group by family_id) b LEFT JOIN " . DB_PREFIX . "family AS f on  f.id=b.family_id  WHERE f.id =".$family_id;
            }else{
                $root['error'] = "家族ID错误";
                $root['status'] = 0;
                api_ajax_return($root);
            }
            $jiainfo =$GLOBALS['db']->getRow($sql);

            foreach($jiainfo as $k=>$v){
                if($k=='family_manifesto'||$k=='family_name'){
                    $jiainfo[$k]=htmlspecialchars_decode($v);
                }
            }
            if ($jiainfo) {
                if ($jiainfo['status'] == 0) {
                    $root['error'] = '您的家族正在审核';
                    $root['status'] = 0;
                } elseif ($jiainfo['status'] == 2) {
                    $root['error'] = '您的家族审核未通过审核';
                    $root['status'] = 2;
                } elseif ($jiainfo['status'] == 1) {
                    $root['status'] = 1;
                    $root['error'] = "";
                }
                $jiainfo['family_logo'] = get_spec_image($jiainfo['family_logo']);
                $root['family_info'] = $jiainfo;
            } else {
                $root['status'] = 3;
                $root['error'] = "你的家族不存在或者已被解散";
            }
        }
        api_ajax_return($root);
    }


//家族列表
    function family_list()
    {
        $root = array();
        if (!$GLOBALS['user_info']) {
            $root['error'] = "用户未登陆,请先登陆.";// es_session::id();
            $root['status'] = 0;
            $root['user_login_status'] = 0;//有这个参数： user_login_status = 0 时，表示服务端未登陆、要求登陆，操作
        } else {
            //分页
            $user_id = intval($GLOBALS['user_info']['id']);
            $page = intval($_REQUEST['page']);//取第几页数据
            if ($page == 0 || $page == '') {
                $page = 1;
            }
            //每次20条
            $page_size = intval($_REQUEST['page_size']);//分页数量;
            if ($page_size == '') {
                $page_size = 20;
            }
            $limit = (($page - 1) * $page_size) . "," . $page_size;
            //搜索
            $jid = intval($_REQUEST['family_name']);
            $jia_name = strim($_REQUEST['family_name']);

            if (($jid != '' && $jid != 0) || $jia_name != '') {
                if ($jia_name == '') {
                    $jia_name = 'null';
                }
                //搜索列表
               $sql = "SELECT j.id as family_id,j.logo as family_logo,j.name as family_name,j.user_id,u.nick_name,j.create_time,(SELECT COUNT(id) FROM " . DB_PREFIX . "user c WHERE c.family_id=j.id) as user_count,IF ((select count(id) as is_apply from " . DB_PREFIX . "family_join as jo where jo.user_id=" . $user_id . " and jo.family_id=j.id and jo.status=0 )>0,1,IF ((select count(id) as is_apply from " . DB_PREFIX . "family_join as jo where jo.user_id=" . $user_id . " and jo.family_id=j.id and jo.status=1 )>0,2,0)) as is_apply FROM " . DB_PREFIX . "family as j left join " . DB_PREFIX . "user as u on j.user_id=u.id where j.status=1 and ( j.id = '" . $jid . "' or j.name like '%" . $jia_name . "%') limit " . $limit;
            } else {
                //默认列表
                $sql = "SELECT j.id as family_id,j.logo as family_logo,j.name as family_name,j.user_id,u.nick_name,j.create_time,(SELECT COUNT(id) FROM " . DB_PREFIX . "user c WHERE c.family_id=j.id) as user_count,IF ((select count(id) as is_apply from " . DB_PREFIX . "family_join as jo where jo.user_id=" . $user_id . " and jo.family_id=j.id and jo.status=0 )>0,1,IF ((select count(id) as is_apply from " . DB_PREFIX . "family_join as jo where jo.user_id=" . $user_id . " and jo.family_id=j.id and jo.status=1 )>0,2,0)) as is_apply FROM " . DB_PREFIX . "family as j left join " . DB_PREFIX . "user as u on j.user_id=u.id where j.status=1 limit " . $limit;
            }
            $jia_list = $GLOBALS['db']->getAll($sql, true, true);
            foreach ($jia_list as $k => $v) {
                $jia_list[$k]['family_logo'] = get_spec_image($v['family_logo']);
                $jia_list[$k]['name'] = htmlspecialchars_decode($jia_list[$k]['name']);
                $jia_list[$k]['nick_name'] = htmlspecialchars_decode($jia_list[$k]['nick_name']);
                $jia_list[$k]['create_time'] = htmlspecialchars_decode($jia_list[$k]['create_time']);

            }
            if ($jia_list) {
                $root['list'] = $jia_list;
                $rs_count = $GLOBALS['db']->getOne("SELECT count(*) FROM " . DB_PREFIX . "family where status=1", true, true);//家族数量
                if ($page == 0) {
                    $root['page'] = array('page' => $page, 'has_next' => 0);
                } else {
                    $has_next = ($rs_count > $page * $page_size) ? '1' : '0';
                    $root['page'] = array('page' => $page, 'has_next' => $has_next);
                }
//			$root['page'] = $page;
                $root['rs_count'] = $rs_count;
                $root['status'] = 1;
                $root['error'] = '';
            } else {
                $root['list'] = $jia_list;
                $root['page'] = array('page' => $page, 'has_next' => 0);
                $root['status'] = 1;
                $root['error'] = '';
            }

        }
        api_ajax_return($root);
    }

//创建家族
    function create()
    {
        $root = array();
        if (!$GLOBALS['user_info']) {
            $root['error'] = "用户未登陆,请先登陆.";// es_session::id();
            $root['status'] = 0;
            $root['user_login_status'] = 0;//有这个参数： user_login_status = 0 时，表示服务端未登陆、要求登陆，操作
        } else {
            $data['user_id'] = intval($GLOBALS['user_info']['id']);//创建人id
            $data['logo'] = strim($_REQUEST['family_logo']);//家族logo
            $data['name'] = strim($_REQUEST['family_name']);//家族名称
            $data['name']=preg_replace_callback('/[\xf0-\xf7].{3}/', function($r) { return '';},  $data['name']);//过滤表情
            $data['manifesto'] = strim($_REQUEST['family_manifesto']);//家族宣言
            $data['manifesto']=preg_replace_callback('/[\xf0-\xf7].{3}/', function($r) { return '';},  $data['manifesto']);//过滤表情
            $data['notice'] = strim($_REQUEST['family_notice']);//家族公告
            $data['notice']=preg_replace_callback('/[\xf0-\xf7].{3}/', function($r) { return '';},  $data['notice']);//过滤表情
            $data['status'] = 0;//状态 0：未审核，1：审核通过，2：拒绝通过
            $data['create_time'] = NOW_TIME;//创建时间
            $data['memo'] = "无";//备注
            //
            $data['create_date'] =to_date(NOW_TIME,'Y-m-d ');
            $data['create_y'] = to_date(NOW_TIME,'Y');
            $data['create_m'] = to_date(NOW_TIME,'m');
            $data['create_d'] = to_date(NOW_TIME,'d');
            $data['create_w'] = to_date(NOW_TIME,'W');

            if($data['logo'] == ''){
                api_ajax_return(array(
                    'status' => '0',
                    'error' => '家族LOGO不能为空'
                ));
            }elseif($data['name'] == ''){
                api_ajax_return(array(
                    'status' => '0',
                    'error' => '家族名称不能为空'
                ));
            }elseif(strlen($data['name']) > 48){
                api_ajax_return(array(
                    'status' => '0',
                    'error' => '家族名称限制15字以内'
                ));
            }elseif(strlen($data['manifesto']) > 420){
                api_ajax_return(array(
                    'status' => '0',
                    'error' => '家族宣言限制140字以内'
                ));
            }
            
            $user = $GLOBALS['db']->getRow("SELECT family_id,family_chieftain FROM " . DB_PREFIX . "user WHERE id =" . $data['user_id']);
            $family_status = $GLOBALS['db']->getRow("SELECT status FROM " . DB_PREFIX . "family WHERE id =" . $user['family_id'] . " and user_id=" . $data['user_id']);
            if ($user['family_id'] > 0 && $family_status['status'] != 2) {//用户已有家族且家族审核未被拒绝
                if ($user['family_chieftain'] == 1){//用户是家族长
                    if ($family_status['status'] == 1){
                        $root['error'] = '您已有创建成功的家族';
                    }
                    if ($family_status['status'] == 0) {
                        $root['error'] = '您已创建的家族正在审核';
                    }
                }else{//用户是家族成员
                    $root['error'] = '您已加入家族，请退出后再创建';
                }
                $root['status'] = 0;
            } else {
                // 名称校验
                $jia_name = $GLOBALS['db']->getRow("SELECT count(id) as jia_count FROM " . DB_PREFIX . "family WHERE name = '" . $data['name'] . "' and (status=1 or status=0)");
                if ($jia_name['jia_count'] > 0) {
                    $root['error'] = '家族名已存在';
                    $root['status'] = 0;
                } else {
                    $res = $GLOBALS['db']->autoExecute(DB_PREFIX . "family", $data, "INSERT");//插入数据
                    if ($res) {
//						$jia_info=$GLOBALS['db']->getRow("SELECT id FROM ".DB_PREFIX."family WHERE user_id = ".$data['user_id']." AND name=".$data['name']);//查询创建成功的家族编号
                        $family_id = $GLOBALS['db']->insert_id();
                        if ($family_id) {
                            $userdata['family_id'] = $family_id;
                            $userdata['family_chieftain'] = 1;
                        }
                        $GLOBALS['db']->autoExecute(DB_PREFIX . "user", $userdata, $mode = 'UPDATE', "id=" . $data['user_id']);
//						$GLOBALS['db']->query("update ".DB_PREFIX."user set family_id=".$jia_info['id'].",family_chieftain=1 where id=".$data['user_id']);
                        fanwe_require(APP_ROOT_PATH . 'mapi/lib/redis/UserRedisService.php');
                        $user_redis = new UserRedisService();
                        $user_redis->update_db($data['user_id'], array('family_id' => $family_id, 'family_chieftain' => 1));
                        //更新申请表
                        $apple_count=$GLOBALS['db']->getOne("SELECT COUNT(id) FROM ".DB_PREFIX ."family_join WHERE user_id = ".$data['user_id']);
                        if($apple_count>0){
                            $join['status']=2;
                            $GLOBALS['db']->autoExecute(DB_PREFIX . "family_join", $join, $mode = 'UPDATE', "user_id=" . $data['user_id']);
                        }
                        $root['error'] = '家族创建成功';
                        $root['status'] = 1;
                        $root['family_id'] = $family_id;
                    } else {
                        $root['error'] = '家族创建失败';
                        $root['status'] = 0;

                    }
                }
            }

        }
        api_ajax_return($root);
    }

//修改家族信息
    function save()
    {
        $root = array();
        if (!$GLOBALS['user_info']) {
            $root['error'] = "用户未登陆,请先登陆.";// es_session::id();
            $root['status'] = 0;
            $root['user_login_status'] = 0;//有这个参数： user_login_status = 0 时，表示服务端未登陆、要求登陆，操作
        } else {
            $jid = intval($_REQUEST['family_id']);
            $res = $GLOBALS['db']->getRow("SELECT logo,notice,manifesto FROM " . DB_PREFIX . "family WHERE id = " . $jid);
            if (!empty($_REQUEST['family_logo'])) {

                $data['logo'] = strim($_REQUEST['family_logo']);

            }
            if (!empty($_REQUEST['family_notice'])) {
                //家族公告
                if (strim($_REQUEST['family_notice']) != $res['notice']) {
                    $data['notice'] = strim($_REQUEST['family_notice']);
                    $data['notice']=preg_replace_callback('/[\xf0-\xf7].{3}/', function($r) { return '';},  $data['notice']);
                } else {
                    $data['notice'] = $res['notice'];
                }
            }

            if (!empty($_REQUEST['family_manifesto'])) {
                if(strlen($_REQUEST['family_manifesto']) > 420){
                    api_ajax_return(array(
                        'status' => '0',
                        'error' => '家族宣言限制140字以内'
                    ));
                }
                if (strim($_REQUEST['family_manifesto']) != $res['manifesto']) {
                    $data['manifesto'] = strim($_REQUEST['family_manifesto']);//家族宣言
                    $data['manifesto']=preg_replace_callback('/[\xf0-\xf7].{3}/', function($r) { return '';},  $data['manifesto']);
                } else {
                    $data['manifesto'] = $res['manifesto'];
                }
            }

            $user_id = intval($GLOBALS['user_info']['id']);//用户ID
            $user = $GLOBALS['db']->getRow("SELECT family_id,family_chieftain FROM " . DB_PREFIX . "user WHERE id=" . $user_id);
//			$jia_status=$user['jia_status'];//家族状态
            $family_chieftain = $user['family_chieftain'];//族长标志：0不是族长。1是组长
            if ($family_chieftain != 1) {//判断是否为族长
                $root['error'] = '没有权限';
                $root['status'] = 0;
                $root['family_id'] = $jid;
            } else {
                $is_refuse = $GLOBALS['db']->getOne("SELECT id FROM " . DB_PREFIX . "family WHERE user_id = " . $user_id . " and status=2 ");
                if ($is_refuse > 0) {
                    $data['name'] = strim($_REQUEST['family_name']);
                    if(strlen($data['name']) > 48){
                        api_ajax_return(array(
                            'status' => '0',
                            'error' => '家族名称限制15字以内'
                        ));
                    }

                    $data['name']=preg_replace_callback('/[\xf0-\xf7].{3}/', function($r) { return '';},  $data['name']);
                    $jia_name = $GLOBALS['db']->getRow("SELECT count(id) as jia_count FROM " . DB_PREFIX . "family WHERE name = '" . $data['name'] . "' and status=1 ");
                    if ($jia_name['jia_count'] > 0) {
                        $root['error'] = '家族名已存在';
                        $root['status'] = 0;
                    } else {
                        $data['status'] = 0;
                        $update = $GLOBALS['db']->autoExecute(DB_PREFIX . "family", $data, $mode = 'UPDATE', 'id=' . $is_refuse); //如果是被拒绝状态重新编辑更新
                        if ($update) {
                            $root['error'] = '家族信息修改成功';
                            $root['status'] = 1;
                            $root['family_id'] = $is_refuse;
                        } else {
                            $root['error'] = '家族信息修改失败';
                            $root['status'] = 0;
                        }
                    }

                } else {
                    $res = $GLOBALS['db']->autoExecute(DB_PREFIX . "family", $data, "UPDATE", 'id=' . $jid);//更新信息
                    if ($res) {
                        $root['error'] = '家族信息修改成功';
                        $root['status'] = 1;
                        $root['family_id'] = $jid;
                    } else {
                        $root['error'] = '家族信息修改失败';
                        $root['status'] = 0;
                        $root['family_id'] = $jid;
                    }
                }
            }
            api_ajax_return($root);
        }
    }
    
    // 创建家族（弹窗）
    function edit(){
        $root = array();
        api_ajax_return($root);
    }
}

?>
