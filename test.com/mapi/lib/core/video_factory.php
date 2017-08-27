<?php

/* API 文档
 * 点播 https://www.qcloud.com/doc/api/257/1965
 */

class VideoFactory
{
    public function __construct($m_config = array())
    {
        if (empty($m_config)) {
            $m_config = load_auto_cache("m_config");
        }

        $this->config = array(
            'SecretId' => $m_config['qcloud_secret_id'],
            'SecretKey' => $m_config['qcloud_secret_key'],
            'RequestMethod' => 'GET',
            'DefaultRegion' => 'gz'
        );

        fanwe_require(APP_ROOT_PATH . 'system/QcloudApi/QcloudApi.php');
    }

    private function loadService($module_name)
    {
        return QcloudApi::load($module_name, $this->config);
    }


    public function GetVodRecordFiles($channel_id, $begin_time)
    {
        $m_config = load_auto_cache('m_config');
        // 0:腾讯云互动直播, 1:腾讯云直播, 2:金山云，3:星域，4:方维云
        if ($m_config['video_type'] == 2) {
            fanwe_require(APP_ROOT_PATH . 'mapi/lib/core/video_ksyun.php');
            $service = new VideoKsyun($m_config);
            return $service->GetRecord($channel_id);
        }

        if ($m_config['video_type'] == 4) {
            fanwe_require(APP_ROOT_PATH . 'mapi/lib/core/video_fanwe.php');
            $service = new VideoFanwe($m_config);
            $data = $service->GetRecord($channel_id);
            return array('totalCount' => count($data), 'urls' => $data);
        }

        if ($m_config['video_type'] == 5) {
            fanwe_require(APP_ROOT_PATH . 'mapi/lib/core/video_aliyun.php');
            $service = new VideoAliyun($m_config);
            return $service->GetRecord($channel_id);
        }
        if (!empty($m_config['qcloud_security_key'])) {
            //直播码 方式
            return $this->GetZhibomaRecordFiles($channel_id);
        } else {
            $service = $this->loadService(QcloudApi::MODULE_LIVE);
            $ret = $service->GetVodRecordFiles(array(
                'channelId' => $channel_id,
                'startTime' => to_date($begin_time, 'Y-m-d H:i:s'),
            ));
            if ($ret === false) {
                // 请求失败，解析错误信息
                $error = $service->getError();
                return array(
                    'status' => 0,
                    'error' => $error->getMessage(),
                );
            }

            return $ret;
        }
    }

    public function GetZhibomaRecordFiles($channel_id)
    {
        $m_config = load_auto_cache('m_config');
        $key = $m_config['qcloud_auth_key'];
        $t = get_gmtime() + 86400;

        $url = "http://fcgi.video.qcloud.com/common_access?" . http_build_query(array(
                'cmd' => $m_config['vodset_app_id'],
                'interface' => 'Live_Tape_GetFilelist',
                't' => $t,
                'sign' => md5($key . $t),
                'Param.s.channel_id' => $channel_id,
            ));

        $res = $this->accessService($url);

        $filesInfo = array();
        foreach ($res['output']['file_list'] as $file) {
            $filesInfo[] = array(
                'fileId' => $file['file_id'],
            );
        }

        return array('status' => 1, 'totalCount' => $res['output']['all_count'], 'filesInfo' => $filesInfo);
    }

    public function GetVodUrls($channel_id, $begin_time)
    {
        $ret = $this->GetVodRecordFiles($channel_id, $begin_time);

        if (empty($ret['filesInfo'])) {
            return array(
                'status' => 0,
                'error' => 'has no file',
            );
        }

        $file_id = $ret['filesInfo'][0]['fileId'];

        $service = $this->loadService(QcloudApi::MODULE_VOD);
        $ret = $service->DescribeVodPlayUrls(array('fileId' => $file_id));
        if ($ret === false) {
            // 请求失败，解析错误信息
            $error = $service->getError();
            return array(
                'status' => 0,
                'error' => $error->getMessage(),
            );
        }

        if (count($ret['playSet']) == 1 && $ret['playSet'][0]['definition'] == 0) {
            $ret['playSet'][0]['definition'] = 20;
        }

        $urls = array();
        foreach ($ret['playSet'] as $play) {
            $urls[$play['definition']] = $play['url'];
        }

        return array(
            'status' => 1,
            'file_id' => $file_id,
            'urls' => $urls,
        );
    }

    public function DescribeVodPlayUrls($file_id)
    {
        if (!$file_id) {
            return array(
                'status' => 0,
                'error' => 'file_id invalid',
            );
        }

        $service = $this->loadService(QcloudApi::MODULE_VOD);
        $ret = $service->DescribeVodPlayUrls(array('fileId' => $file_id));
        if ($ret === false) {
            // 请求失败，解析错误信息
            $error = $service->getError();
            return array(
                'status' => 0,
                'error' => $error->getMessage(),
            );
        }

        if (count($ret['playSet']) == 1 && $ret['playSet'][0]['definition'] == 0) {
            $ret['playSet'][0]['definition'] = 20;
        }

        $urls = array();
        foreach ($ret['playSet'] as $play) {
            $urls[$play['definition']] = $play['url'];
        }

        return array(
            'file_id' => $file_id,
            'urls' => $urls,
        );
    }

    public function StopLVBChannel($channel_id)
    {
        if (!$channel_id) {
            return array(
                'status' => 0,
            );
        }

        $m_config = load_auto_cache('m_config');

        // 0:腾讯云互动直播, 1:腾讯云直播, 2:金山云，3:星域，4:方维云
        if ($m_config['video_type'] == 2) {
            fanwe_require(APP_ROOT_PATH . 'mapi/lib/core/video_ksyun.php');
            $service = new VideoKsyun($m_config);
            return $service->Stop($channel_id);
        }

        if ($m_config['video_type'] == 4) {
            fanwe_require(APP_ROOT_PATH . 'mapi/lib/core/video_fanwe.php');
            $service = new VideoFanwe($m_config);
            return $service->Stop($channel_id);
        }

        if ($m_config['video_type'] == 5) {
            fanwe_require(APP_ROOT_PATH . 'mapi/lib/core/video_aliyun.php');
            $service = new VideoAliyun($m_config);
            return $service->Stop($channel_id);
        }

        if (!empty($m_config['qcloud_security_key'])) {
            //直播码 方式
            return $this->StopZhibomaChannel($channel_id);
        } else {
            $service = $this->loadService(QcloudApi::MODULE_LIVE);
            $ret = $service->StopLVBChannel(array('channelIds.1' => $channel_id));
            if ($ret === false) {
                // 请求失败，解析错误信息
                $error = $service->getError();
                return array(
                    'status' => 0,
                    'error' => $error->getMessage(),
                );
            }

            return array('status' => 1, 'channel_id' => $channel_id);
        }
    }

    public function StopZhibomaChannel($channel_id)
    {
        $m_config = load_auto_cache('m_config');
        $key = $m_config['qcloud_auth_key'];
        $t = get_gmtime() + 86400;

        $url = "http://fcgi.video.qcloud.com/common_access?" . http_build_query(array(
                'cmd' => $m_config['vodset_app_id'],
                'interface' => 'Live_Channel_SetStatus',
                't' => $t,
                'sign' => md5($key . $t),
                'Param.s.channel_id' => $channel_id,
                'Param.n.status' => 0,
            ));

        $this->accessService($url);

        return array('status' => 1, 'channel_id' => $channel_id);
    }

    public function Query($channel_id)
    {
        if (!$channel_id) {
            return array(
                'status' => 0,
            );
        }
        $m_config = load_auto_cache('m_config');

        // 0:腾讯云互动直播, 1:腾讯云直播, 2:金山云，3:星域，4:方维云
        if ($m_config['video_type'] == 2) {
            fanwe_require(APP_ROOT_PATH . 'mapi/lib/core/video_ksyun.php');
            $service = new VideoKsyun($m_config);
            $data = $service->Query($channel_id);
            return array(
                'channel_id' => $channel_id,
                'status' => $data['stream_status'],
            );
        }

        if ($m_config['video_type'] == 4) {
            fanwe_require(APP_ROOT_PATH . 'mapi/lib/core/video_fanwe.php');
            $service = new VideoFanwe($m_config);
            return $service->Query($channel_id);
        }


        if ($m_config['video_type'] == 5) {
            fanwe_require(APP_ROOT_PATH . 'mapi/lib/core/video_aliyun.php');
            $service = new VideoAliyun($m_config);
            $data = $service->Query($channel_id);
            return array(
                'channel_id' => $channel_id,
                'status' => $data['stream_status'],
            );
        }

        if (empty($m_config['qcloud_security_key'])) {
            $service = $this->loadService(QcloudApi::MODULE_LIVE);
            $package = array(
                'channelId' => $channel_id,
            );
            $ret = $service->DescribeLVBChannel($package);
            if ($ret === false) {
                // 请求失败，解析错误信息
                $error = $service->getError();
                return array(
                    'status' => 0,
                    'error' => $error->getMessage(),
                );
            }

            $channel_info = $ret['channelInfo'][0];
            $upstream_address = $channel_info['upstream_list'][0]['sourceAddress'];

            return array(
                'channel_id' => $channel_info['channel_id'],
                'status' => $channel_info['channel_status'],
                'upstream_address' => $upstream_address . "&record=flv",
                'downstream_address' => array(
                    'rtmp' => $channel_info['rtmp_downstream_address'],
                    'hls' => $channel_info['hls_downstream_address'],
                    'flv' => $channel_info['flv_downstream_address'],
                ),
            );
        } else {
            $key = $m_config['qcloud_auth_key'];
            $t = get_gmtime() + 86400;

            $url = "http://fcgi.video.qcloud.com/common_access?" . http_build_query(array(
                    'cmd' => $m_config['vodset_app_id'],
                    'interface' => 'Live_Channel_GetStatus',
                    't' => $t,
                    'sign' => md5($key . $t),
                    'Param.s.channel_id' => $channel_id,
                ));

            $res = $this->accessService($url);
            $channel_info = array();
            $channel_info['status'] = $res['output'][0]['status'];

            return $channel_info;
        }
    }

    private function accessService($url)
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_NOBODY, false);    //对body进行输出。
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $package = curl_exec($ch);
        $res = json_decode($package, true);
        if ($res['ret']) {
            return array(
                'status' => 0,
                'error' => $res['message'],
            );
        }
        return $res;
    }

    /*
     * outputSourceType	1表示只有RTMP输出，2表示只有HLS输出，3表示两者都有
     */
    public function Create($video_id, $record = 'mp4', $user_id = 0, $is_private = 0)
    {
        $m_config = load_auto_cache('m_config');
        // 0:腾讯云互动直播, 1:腾讯云直播, 2:金山云，3:星域，4:方维云，5:阿里云
        if ($m_config['video_type'] == 2) {
            fanwe_require(APP_ROOT_PATH . 'mapi/lib/core/video_ksyun.php');
            $service = new VideoKsyun($m_config);
            $data = $service->Create($video_id);
            return array(
                'channel_id' => $data['stream_id'],
                'upstream_address' => $data['push_rtmp'],
                'downstream_address' => array(
                    'rtmp' => $data['play_rtmp'],
                    'flv' => $data['play_flv'],
                    'hls' => $data['play_hls'],
                ),
            );
        }

        if ($m_config['video_type'] == 4) {
            fanwe_require(APP_ROOT_PATH . 'mapi/lib/core/video_fanwe.php');
            $service = new VideoFanwe($m_config);
            return $service->Create($video_id);
        }

        if ($m_config['video_type'] == 5) {
            fanwe_require(APP_ROOT_PATH . 'mapi/lib/core/video_aliyun.php');
            $service = new VideoAliyun($m_config);
            $data = $service->Create($video_id);
            if($data['status'] == 0){
                ajax_return($data);
            }
            return array(
                'channel_id' => $data['stream_id'],
                'upstream_address' => $data['push_rtmp'],
                'downstream_address' => array(
                    'rtmp' => $data['play_rtmp'],
                    'flv' => $data['play_flv'],
                    'hls' => $data['play_hls'],
                ),
            );
        }
        if (!empty($m_config['qcloud_security_key'])) {
            //直播码 方式
            return $this->GetChannelInfo($video_id, 'b', $video_id, $user_id, $is_private, $record);
        } else {
            $service = $this->loadService(QcloudApi::MODULE_LIVE);
            $package = array(
                'channelName' => $video_id,
                'outputSourceType' => 3,
                'sourceList.1.name' => $video_id,
                'sourceList.1.type' => 1,
            );
            $ret = $service->CreateLVBChannel($package);
            if ($ret === false) {
                // 请求失败，解析错误信息
                $error = $service->getError();
                ajax_return(array(
                    'status' => 0,
                    'error' => $error->getMessage(),
                ));
            }

            $channel_id = $ret['channel_id'];
            $upstream_address = $ret['channelInfo']['upstream_address'];
            $downstream_address = $ret['channelInfo']['downstream_address'][0];

            //在返回的hls地址中，加入/live/这一层
            //@author　jiangzuru
            $s1 = $downstream_address['hls_downstream_address'];
            if (strpos($s1, "com/live/") === false) {
                $pos1 = strpos($s1, "com/");
                $downstream_address['hls_downstream_address'] = substr_replace($s1, "live/", $pos1 + 4, 0);
            }

            //后台开启录制 或者 非私密直播 录制视频
            $has_save_video = intval($m_config['has_save_video']);
            $save_video = '';
            if ($has_save_video && $is_private != 1) {
                $save_video = "&record=" . $record . "&record_interval=5400";
            }

            return array(
                'channel_id' => $channel_id,
                'upstream_address' => $upstream_address . $save_video,
                'downstream_address' => array(
                    'rtmp' => $downstream_address['rtmp_downstream_address'],
                    'flv' => $downstream_address['flv_downstream_address'],
                    'hls' => $downstream_address['hls_downstream_address'],
                ),
            );
        }
    }


    public function GetChannelInfo(
        $video_id,
        $layer = 'b',
        $session_id = 0,
        $user_id = 0,
        $is_private = 0,
        $record = 'mp4'
    ) {
        $m_config = load_auto_cache('m_config');
        $bizId = $m_config['qcloud_bizid'];
        $key = $m_config['qcloud_security_key'];

        $stream_id = $session_id . $layer . $user_id . "_" . substr(md5($video_id . microtime_float()), 12);

        if ($session_id == 0) {
            $session_id = $video_id;
        }


        //格式化,补足32长
        //$session_id = str_pad($session_id,32,'0',STR_PAD_LEFT);


        $time = to_date(get_gmtime() + 86400, 'Y-m-d H:i:s');
        $txTime = strtoupper(base_convert(strtotime($time), 10, 16));
        //$stream_id = bizid+"_"+stream_id  如 8888_test123456
        $stream_id = $bizId . "_" . $stream_id; //直播码
        // 24小时失效
        $ext_str = $this->get_acc_sign($key, $stream_id, 86400);

        $ext_str = "?bizid=" . $bizId . "&" . $ext_str . "&mix=layer:{$layer};session_id:{$session_id};t_id:1";
        $upstream_address = "rtmp://" . $bizId . ".livepush.myqcloud.com/live/" . $stream_id . $ext_str;
        //后台开启录制 或者 非私密直播 录制视频
        $has_save_video = intval($m_config['has_save_video']);
        $save_video = '';
        if ($has_save_video && $is_private != 1) {
            $save_video = "&record=" . $record . "&record_interval=5400";
        }


        return array(
            'channel_id' => $stream_id,
            'upstream_address' => $upstream_address . $save_video,
            'downstream_address' => array(
                'rtmp' => "rtmp://" . $bizId . ".liveplay.myqcloud.com/live/" . $stream_id,
                'flv' => "http://" . $bizId . ".liveplay.myqcloud.com/live/" . $stream_id . ".flv",
                'hls' => "http://" . $bizId . ".liveplay.myqcloud.com/live/" . $stream_id . ".m3u8"
            ),
        );
    }

    public function get_acc_sign($key, $stream_id, $len = 300)
    {
        $time = to_date(get_gmtime() + $len, 'Y-m-d H:i:s');
        //$time = '2017-01-22 23:59:59';
        $txTime = strtoupper(base_convert(strtotime($time), 10, 16));
        //txSecret的生成方法是 = MD5(KEY+ stream_id + txTime)
        $txSecret = md5($key . $stream_id . $txTime);
        $ext_str = http_build_query(array(
            "txSecret" => $txSecret,
            "txTime" => $txTime,
        ));

        return $ext_str;
    }

    public function Sign($arg_str)
    {
        $m_config = load_auto_cache("m_config");
        return base64_encode(hash_hmac('sha1', $arg_str, $m_config['qcloud_secret_key'], true));
    }

    //删除视频
    public function DeleteVodFiles($channel_id, $begin_time)
    {
        $ret = $this->GetVodRecordFiles($channel_id, $begin_time);

        $delvodset = array();
        $service = $this->loadService(QcloudApi::MODULE_VOD);
        foreach ($ret['filesInfo'] as $file_info) {
            $file_id = $file_info['fileId'];
            $delvodset[$file_id] = $service->DeleteVodFile(array('fileId' => $file_id, 'priority' => 0));
        }

        return $delvodset;
    }

    public function DeleteVodFilesByFileName($file_name)
    {
        $ret = $this->DescribeVodPlayInfo($file_name);

        $delvodset = array();
        $service = $this->loadService(QcloudApi::MODULE_VOD);
        foreach ($ret['fileSet'] as $file_info) {
            $file_id = $file_info['fileId'];
            $delvodset[$file_id] = $service->DeleteVodFile(array('fileId' => $file_id, 'priority' => 0));
        }

        return $delvodset;
    }

    public function DescribeVodPlayInfo($file_name)
    {
        $service = $this->loadService(QcloudApi::MODULE_VOD);
        $ret = $service->DescribeVodPlayInfo(array('fileName' => $file_name));
        if ($ret === false) {
            // 请求失败，解析错误信息
            $error = $service->getError();
            return array(
                'status' => 0,
                'error' => $error->getMessage(),
            );
        }
        return $ret;
    }


    public function ModifyVodInfo($file_id, $data)
    {
        $file_name = $data['id'] . '_' . to_date($data['begin_time'],
                'Y-m-d-H-i-s') . '_' . to_date($data['begin_time'], 'Y-m-d-H-i-s');
        $service = $this->loadService(QcloudApi::MODULE_VOD);
        $ret = $service->ModifyVodInfo(array(
            'fileId' => $file_id,
            'fileName' => $file_name,
        ));
        if ($ret === false) {
            // 请求失败，解析错误信息
            $error = $service->getError();
            return array(
                'status' => 0,
                'error' => $error->getMessage(),
            );
        }

        return $file_name;
    }

    /** 文档地址 https://www.qcloud.com/document/product/266/1393
     * @param $url
     */
    public function MultiPullVodFile($url, $video_id, $begin_time)
    {
        $service = $this->loadService(QcloudApi::MODULE_VOD);
        $file_name = $video_id . '_' . to_date($begin_time,
                'Y-m-d-H-i-s') . '_' . to_date($begin_time, 'Y-m-d-H-i-s');
        $ret = $service->MultiPullVodFile(array(
            'pullset.1.url' => $url,
            'pullset.1.fileName' => $file_name,
        ));
        if ($ret === false) {
            // 请求失败，解析错误信息
            $error = $service->getError();
            return array(
                'status' => 0,
                'error' => $error->getMessage(),
            );
        }

        return $ret;
    }

    /**
     * 视频拼接 https://www.qcloud.com/document/product/266/7821
     * @param unknown_type $channel_id
     * @return multitype:number NULL multitype:multitype:unknown
     */
    public function ConcatVideo($channel_id, $new_file_name)
    {
        $res = $this->GetZhibomaRecordFiles($channel_id);
        if ($res['totalCount'] > 1) {
            $params = array();
            $params['name'] = $new_file_name;
            $params['dstType.0'] = 'mp4';

            $i = 0;
            foreach ($res['filesInfo'] as $file) {
                $params['srcFileList.' . $i . '.fileId'] = $file['fileId'];
                $i = $i + 1;
            }

            $service = $this->loadService(QcloudApi::MODULE_VOD);
            $ret = $service->ConcatVideo($params);
            /*
              array (
              'codeDesc' => 'Success',
              'vodTaskId' => 'concat-d0cef54c78075e5657dc934fc1b38d98',
            )
             */
            if ($ret['codeDesc'] != 'Success') {
                // 请求失败，解析错误信息
                $error = $service->getError();
                return array(
                    'status' => 0,
                    'v_status' => 0,
                    'error' => $error->getMessage(),
                );
            } else {
                //code 错误码, 0: 成功, 其他值: 失败
                //vodTaskId 描述拼接任务的唯一id，可以通过此id查询任务状态
                return array(
                    'status' => 1,
                    'v_status' => 1,
                    'error' => '合并任务已提交,请等待合并，大致需要5分钟',
                    'vodtaskid' => $ret['vodTaskId'],
                );
            }
        } else {
            //只有一个文件时,不需要调用：合并视频功能
            return array(
                'status' => 1,
                'v_status' => 0,
                'error' => '单文件视频不需要合并',
            );
        }

    }
}