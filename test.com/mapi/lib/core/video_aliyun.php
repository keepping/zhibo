<?php

fanwe_require(APP_ROOT_PATH . 'system/aliyun-openapi-php-sdk/aliyun-php-sdk-core/Config.php');

use live\Request\V20161101 as Live;

/**
 * 文档地址 https://help.aliyun.com/document_detail/48207.html?spm=5176.doc35411.6.570.AeuNzq
 */
class VideoAliyun
{
    private $m_config;

    function __construct($m_config)
    {
        $this->m_config = $m_config;
    }

    private function build_auth_key($uri, $time)
    {
        $auth_key = md5("{$uri}-{$time}-0-0-" . $this->m_config['aliyun_private_key']);
        return "{$uri}?auth_key={$time}-0-0-" . $auth_key;
    }

    private function get_vhost()
    {
        $max = 10;
        $videos = $GLOBALS['db']->getAll("select vhost , count(*) as num from " . DB_PREFIX . "video_aliyun group by vhost");
        $vhost_max = array();
        foreach ($videos as $video) {
            if ($video['num'] < $max) {
                return $video['vhost'];
            }
            $vhost_max[] = $video['vhost'];
        }

        $vhosts = preg_split("/[\r\n]+/", $this->m_config['aliyun_vhost']);
        foreach ($vhosts as $vhost) {
            if (!in_array($vhost, $vhost_max)) {
                return trim($vhost);
            }
        }
        return false;
    }

    /** https://help.aliyun.com/document_detail/29957.html?spm=5176.doc35409.6.546.IMpk1g
     * @param $video_id
     * @return array
     */
    public function Create($video_id)
    {
        $vhost = $this->get_vhost();
        if (!$vhost) {
            return array('status' => 0,"error" => '推流数量已超出最大值');
        }

        $stream_id = $video_id . "_" . substr(md5($video_id . microtime_float()), 12);
        $app_name = 'live';
        $upstream_time = NOW_TIME + 8 * 3600 + 1800;
        $download_time = $upstream_time + 6 * 3600;

        $GLOBALS['db']->autoExecute(DB_PREFIX . "video_aliyun", array(
            'vhost' => $vhost,
            'stream_id' => $stream_id,
            'create_time' => NOW_TIME,
        ));

        return array(
            'status' => 1,
            'stream_id' => $stream_id,
            'push_rtmp' => "rtmp://video-center.alivecdn.com" . $this->build_auth_key("/{$app_name}/{$stream_id}",
                    $upstream_time) . "&vhost={$vhost}",
            'play_rtmp' => "rtmp://{$vhost}" . $this->build_auth_key("/{$app_name}/{$stream_id}", $download_time),
            'play_flv' => "http://{$vhost}" . $this->build_auth_key("/{$app_name}/{$stream_id}.flv", $download_time),
            'play_hls' => "http://{$vhost}" . $this->build_auth_key("/{$app_name}/{$stream_id}.m3u8", $download_time),
        );
    }

    /** https://help.aliyun.com/document_detail/35409.html?spm=5176.doc35413.6.581.U1UYO6
     * @param $stream_id
     * @return array
     */
    public function Query($stream_id)
    {
        $iClientProfile = DefaultProfile::getProfile($this->m_config['aliyun_region'],
            $this->m_config['aliyun_access_key'],
            $this->m_config['aliyun_access_secret']);
        $client = new DefaultAcsClient($iClientProfile);

        $vhost = $GLOBALS['db']->getOne("select vhost from " . DB_PREFIX . "video_aliyun where stream_id = '{$stream_id}'");
        $request = new Live\DescribeLiveStreamsOnlineListRequest();
        $request->setDomainName($vhost);
        $request->setAppName('live');

        $response = $client->getAcsResponse($request);
        $status = 0;
        foreach ($response->OnlineInfo->LiveStreamOnlineInfo as $stream) {
            if ($stream->StreamName == $stream_id) {
                $status = 1;
            }
        }
        return array(
            'stream_status' => $status
        );
    }

    /** https://help.aliyun.com/document_detail/35413.html?spm=5176.doc48207.2.6.Gk4oAS
     * @param $stream_id
     * @return bool
     */
    public function Stop($stream_id,$vhost='')
    {
        $iClientProfile = DefaultProfile::getProfile($this->m_config['aliyun_region'],
            $this->m_config['aliyun_access_key'],
            $this->m_config['aliyun_access_secret']);
        $client = new DefaultAcsClient($iClientProfile);
        if(empty($vhost)){
            $vhost = $GLOBALS['db']->getOne("select vhost from " . DB_PREFIX . "video_aliyun where stream_id = '{$stream_id}'");
        }
        try {
            $request = new Live\ForbidLiveStreamRequest();
            $request->setDomainName($vhost);
            $request->setAppName('live');
            $request->setStreamName($stream_id);
            $request->setLiveStreamType("publisher");

            $response = $client->getAcsResponse($request);
            if (!empty($response->RequestId)) {
                $GLOBALS['db']->query("delete from " . DB_PREFIX . "video_aliyun where stream_id = '{$stream_id}'");
                return true;
            } else {
                return false;
            }
        } catch (Exception $e) {
            log_err_file($e->getMessage());
            return false;
        }
    }

    public function GetRecord($stream_id)
    {
        return array();
    }
}