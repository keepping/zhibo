<?php

/**
 *
 */
class CourseAction extends CommonAction
{
    public function index()
    {
        $map   = array('type' => 0);
        $id    = intval($_REQUEST['id']);
        $title = trim($_REQUEST['title']);
        if ($id) {
            $map['id'] = $id;
        }
        if ($title) {
            $map['title'] = array('like', '%' . trim($title) . '%');
        }
        $model = D('Course');
        if (!empty($model)) {
            $this->_list($model, $map);
        }
        $this->display();
    }

    public function set_effect()
    {
        $id          = intval($_REQUEST['id']);
        $ajax        = intval($_REQUEST['ajax']);
        $info        = M('Course')->where("id=" . $id)->getField("title");
        $c_is_effect = M('Course')->where("id=" . $id)->getField("is_effect"); //当前状态
        $n_is_effect = $c_is_effect == 0 ? 1 : 0; //需设置的状态
        M('Course')->where("id=" . $id)->setField("is_effect", $n_is_effect);
        save_log($info . l("SET_EFFECT_" . $n_is_effect), 1);
        clear_auto_cache("get_help_cache");
        clear_auto_cache("article_notice");
        $this->ajaxReturn($n_is_effect, l("SET_EFFECT_" . $n_is_effect), 1);
    }
    public function set_recommend()
    {
        $id             = intval($_REQUEST['id']);
        $ajax           = intval($_REQUEST['ajax']);
        $info           = M('Course')->where("id=" . $id)->getField("title");
        $c_is_recommend = M('Course')->where("id=" . $id)->getField("is_recommend"); //当前状态
        $n_is_recommend = $c_is_recommend == 0 ? 1 : 0; //需设置的状态
        M('Course')->where("id=" . $id)->setField("is_recommend", $n_is_recommend);
        save_log($info . l("SET_EFFECT_" . $n_is_recommend), 1);
        clear_auto_cache("get_help_cache");
        clear_auto_cache("article_notice");
        $this->ajaxReturn($n_is_recommend, l("SET_EFFECT_" . $n_is_recommend), 1);
    }
    public function edit()
    {
        $id = intval($_REQUEST['id']);
        $vo = M('Course')->find($id);
        $this->assign('vo', $vo);
        $this->display();
    }

    public function update()
    {
        $data        = M('Course')->create();
        $data['img'] = $_REQUEST['image'];
        //clear_auto_cache("prop_list");
        $log_info = M('Course')->where("id=" . intval($data['id']))->getField("title");
        //开始验证有效性
        $this->assign("jumpUrl", u('Course' . "/edit", array("id" => $data['id'])));
        if (!check_empty($data['title'])) {
            $this->error("请输入课程名称");
        }
        if (!check_empty($data['img'])) {
            $this->error("请上传封面");
        }
        if (!check_empty($data['content'])) {
            $this->error("请输入内容");
        }
        $data['type'] = 0;
        // 更新数据
        if ($data['id']) {
            $list = M('Course')->save($data);
        } else {
            $data['create_time'] = NOW_TIME;
            $list                = M('Course')->add($data);
        }
        if (false !== $list) {
            //成功提示
            save_log($log_info . L("UPDATE_SUCCESS"), 1);
            clear_auto_cache("prop_id", array('id' => $data['id']));
            $this->success(L("UPDATE_SUCCESS"));
        } else {
            //错误提示
            save_log($log_info . L("UPDATE_FAILED"), 0);
            $this->error(L("UPDATE_FAILED"), 0, $log_info . L("UPDATE_FAILED"));
        }
    }

    public function view()
    {
        $id    = intval($_REQUEST['id']);
        $sid   = intval($_REQUEST['sid']);
        $title = trim($_REQUEST['title']);
        $sort  = $_REQUEST['_sort'] ? 'asc' : 'desc';
        if (isset($_REQUEST['_order'])) {
            $order = $_REQUEST['_order'];
        } else {
            $order = 'id';
        }
        $model = M('CourseSeason');
        $map   = array('pid' => $id);
        if ($sid) {
            $map['id'] = $sid;
        }
        if ($title) {
            $map['title'] = array('like', '%' . trim($title) . '%');
        }
        $count = $model->where($map)->count('id');

        if ($count > 0) {
            //创建分页对象
            if (!empty($_REQUEST['listRows'])) {
                $listRows = $_REQUEST['listRows'];
            } else {
                $listRows = '';
            }
            $p = new Page($count, $listRows);
            //分页查询数据
            $voList = $model->where($map)->order("season")->limit($p->firstRow . ',' . $p->listRows)->findAll();
            foreach ($map as $key => $val) {
                if (!is_array($val)) {
                    $p->parameter .= "$key=" . urlencode($val) . "&";
                }
            }
            //分页显示

            $page = $p->show();
            //列表排序显示
            $sortImg = $sort; //排序图标
            $sortAlt = $sort == 'desc' ? l("ASC_SORT") : l("DESC_SORT"); //排序提示
            $sort    = $sort == 'desc' ? 1 : 0; //排序方式
            //模板赋值显示
            foreach ($voList as $k => $v) {
                $voList[$k]['head_image'] = get_spec_image($v['head_image']);
            }
            $this->assign('list', $voList);
            $this->assign('sort', $sort);
            $this->assign('order', $order);
            $this->assign('sortImg', $sortImg);
            $this->assign('sortType', $sortAlt);
            $this->assign("page", $page);
            $this->assign("nowPage", $p->nowPage);
        }
        $this->assign('id', $id);
        $this->display();
    }

    public function viewSeason()
    {
        $id  = intval($_REQUEST['id']);
        $pid = intval($_REQUEST['pid']);
        $vo  = M('CourseSeason')->find($id);
        if ($vo) {
            $pid = $vo['pid'];
        }
        $m_config          = load_auto_cache("m_config");
        $qcloud_secret_id  = $m_config['qcloud_secret_id'];
        $qcloud_secret_key = $m_config['qcloud_secret_key'];
        if ($vo['video_url'] && !intval($vo['video_url'])) {
            $video_url = $vo['video_url'];
        } else {
            fanwe_require(APP_ROOT_PATH . 'mapi/lib/core/video_factory.php');
            $video_factory = new VideoFactory();
            $video         = $video_factory->DescribeVodPlayUrls($vo['file_id']);
            $video_url     = $video['urls'][min(array_keys($video['urls']))];
        }
        $this->assign('video_url', $video_url);
        $this->assign('pid', $pid);
        $this->assign('vo', $vo);
        $this->assign('qcloud_secret_id', $qcloud_secret_id);
        $this->assign('qcloud_secret_key', $qcloud_secret_key);
        $this->display();
    }

    public function updateSeason()
    {
        // $video_vid   = $_REQUEST['file_id'];
        $data        = M('CourseSeason')->create();
        $data['img'] = $_REQUEST['image'];
        //clear_auto_cache("prop_list");
        //开始验证有效性
        $this->assign("jumpUrl", u('Course' . "/viewSeason", array("id" => $data['id'])));
        if (!check_empty($data['title'])) {
            ajax_return(array('status' => 0, 'error' => '请输入课程名称'));
        }
        if (!check_empty($data['img'])) {
            ajax_return(array('status' => 0, 'error' => '请上传封面'));
        }
        if (!check_empty($data['content'])) {
            ajax_return(array('status' => 0, 'error' => '请输入内容'));
        }
        // $data['video_url'] = $video_vid;
        // 更新数据
        if ($data['id']) {
            $res = M('CourseSeason')->save($data);
        } else {
            $data['create_time'] = NOW_TIME;
            $res                 = M('CourseSeason')->add($data);
        }
        if (false !== $res) {
            ajax_return(array('status' => 1, 'error' => '更新成功'));
        } else {
            ajax_return(array('status' => 0, 'error' => '更新错误'));
        }
    }

    public function upload()
    {
        $result = $this->uploadFile();
        if ($result['status'] == 1) {
            ajax_return(array(
                'status'  => $result['status'],
                'success' => $result['info'],
                'url'     => get_domain() . $result['data'][0]['recpath'] . $result['data'][0]['savename'],
                'result'  => $result,
            ));
        } else {
            ajax_return(array(
                'status' => $result['status'],
                'error'  => $result['info'],
            ));
        }
    }
    public function getVideoUrlById()
    {
        $id = trim($_REQUEST['id']);
        fanwe_require(APP_ROOT_PATH . 'mapi/lib/core/video_factory.php');
        $video_factory = new VideoFactory();
        $video         = $video_factory->DescribeVodPlayUrls($id);
        $video_url     = $video['urls'][min(array_keys($video['urls']))];
        if ($video_url) {
            ajax_return(array(
                'status' => 1,
                'error'  => '',
                'url'    => $video_url,
            ));
        } else {
            ajax_return(array(
                'status' => 0,
                'error'  => '正在加载视频，请稍后',
            ));
        }
    }
    public function video_callback()
    {
        // {
        // 　　“status”:0, //返回状态,
        // 　　“message”:”” //返回消息,
        // 　　“task”:”transcode” // 文件上传完毕时为file_upload，转码结束时为transcode
        // 　　“data”:{
        // 　　“ret”:0 //错误码,
        // 　　“message”:””//消息 ,
        // 　　“file_id” :123445//文件id
        // 　　“image_video”:{
        // 　　“code”:0,
        // 　　“duration”:0,//持续时间
        // 　　“imgUrl”:{
        //   “id”: 3213,
        // 　　“url”:”www.qcloud.com/templurl.png”, //图片链接
        // 　　“vheight”:21,
        // 　　“width”:32,
        // 　　}
        // 　　}
        // 　　“message”:””,//消息
        // 　　“vid”:”231414” ,//视频id,
        // 　　“videoUrls”:[
        // 　　{
        // 　　“url”:”www.qcloucd.com/temp_video.mp4”,
        // 　　“md5”:”fdasfdsafsadf”,//md5值
        // 　　“sha”:”dasfdsfas”,//文件sha值,
        // 　　“size”:123 ,//大小
        // 　　“update_time”:”2015 08 49 12:0:0”, //更新时间
        // 　　“vbirate”:231,//比特率
        // 　　“vheight”: 480 ,/ /视频高度
        // 　　“vwidth”:800 ,//视频宽度
        //
        //  }
        // 　　]
        // 　　“player_code” :{
        //  “h5”:”” ,//html5播放器代码,
        // 　　“flash”:””,//flash播放器代码,
        // 　　“iframe”:””,//iframe播放器代码
        // 　　}
        // 　　}
        // }
        //

        fanwe_require(APP_ROOT_PATH . 'mapi/lib/tools/PushLog.class.php');
        PushLog::log($_REQUEST);
    }
}
