<?php

class VideoFanwe
{
    private $m_config = null;

    private $fwyun_access_key;
    private $fwyun_secret_key;

    function __construct($m_config)
    {
        $this->m_config = $m_config;
        $this->fwyun_access_key = $this->m_config['fwyun_access_key'];
        $this->fwyun_secret_key = $this->m_config['fwyun_secret_key'];

        if ($this->fwyun_secret_key == '' || $this->fwyun_access_key == '') {
            if(intval(FANWE_DEBUG)){
                log_file('fwyun_access_key','video_fanwe');
                log_file( $this->fwyun_access_key,'video_fanwe');
                log_file('fwyun_secret_key','video_fanwe');
                log_file($this->fwyun_secret_key,'video_fanwe');
            }
            ajax_return(array(
                'status' => 0,
                'error' => '·½Î¬ÔÆÕËºÅÎ´ÅäÖÃ!',
            ));
        }
    }

    public function Create($user_id, $video_id)
    {
        $result = $this->invoke(array(
            'act' => 'create',
        ));
        if (!$result['status']) {
            ajax_return(array(
                'status' => 0,
                'error' => $result['error'],
            ));
        }
        if(intval(FANWE_DEBUG)){
            log_file('Create','video_fanwe');
            log_file($result,'video_fanwe');
        }
        $data = $result['data'];
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

    public function Query($stream_id)
    {
        $result = $this->invoke(array(
            'act' => 'query',
            'stream_id' => $stream_id,
        ));
        if (!$result['status']) {
            return array(
                'status' => 0,
                'error' => $result['error'],
            );
        }
        if(intval(FANWE_DEBUG)){
            log_file('Query','video_fanwe');
            log_file($result,'video_fanwe');
        }
        $data = $result['data'];
        return array(
            'channel_id' => $stream_id,
            'status' => $data['stream_status'],
        );
    }

    public function Stop($stream_id)
    {
        $result = $this->invoke(array(
            'act' => 'stop',
            'stream_id' => $stream_id,
        ));
        if (!$result['status']) {
            return array(
                'status' => 0,
                'error' => $result['error'],
            );
        }
        if(intval(FANWE_DEBUG)){
            log_file('Stop','video_fanwe');
            log_file($result,'video_fanwe');
        }
        return $result['data'];
    }

    //²éÑ¯²éÑ¯
    public function GetRecord($stream_id)
    {
        $result = $this->invoke(array(
            'act' => 'get_record',
            'stream_id' => $stream_id,
        ));
        if (!$result['status']) {
            return array(
                'status' => 0,
                'error' => $result['error'],
            );
        }
		if(intval(FANWE_DEBUG)){
            log_file('GetRecord','video_fanwe');
            log_file($result,'video_fanwe');
        }
        return array('totalCount' => $result['data']['totalCount'], 'filesInfo' => $result['data']['filesInfo'], 'urls' => $result['data']['file_list']);

    }

    private function invoke($params)
    {
        $url = "http://ilvbt5.fanwe.net/video";
        fanwe_require(APP_ROOT_PATH . 'system/saas/SAASAPIClient.php');
        $client = new \SAASAPIClient($this->fwyun_access_key, $this->fwyun_secret_key);
        return $client->invoke($url, $params);
    }
}