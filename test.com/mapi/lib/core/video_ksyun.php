<?php

fanwe_require(APP_ROOT_PATH . 'vendor/autoload.php');

use Ksyun\Service\Cdn;
use GuzzleHttp\Client;

define("KS3_API_LOG", false);
define("KS3_API_DISPLAY_LOG", false);

/**
 * 文档地址 https://v.ksyun.com/doc.html#/doc/livesdk.md
 */
class VideoKsyun
{
    private $m_config;

    function __construct($m_config)
    {
        $this->m_config = $m_config;
    }

    function getExpireTime()
    {
        $tz = 'Asia/Shanghai';
        $dt = new DateTime("now", new DateTimeZone($tz));
        // 流时长6小时之内，且主动正常断流会触发拼接
        $dt->add(new DateInterval('PT6H'));
        return $dt->getTimestamp();
    }

    public function Create($video_id)
    {
        // 流时长6小时之内，且主动正常断流会触发拼接
        $time = $this->getExpireTime();
        $nonce = md5($video_id . rand(1, 9999999));

        $signature = base64_encode(hash_hmac('sha1', "GET\{$time}\nnonce={$nonce}&vdoid={$video_id}",
            $this->m_config['ks3_secretkey'], true));
        $stream_id = $video_id . "_" . substr(md5($video_id . microtime_float()), 12);

        $params = http_build_query(array(
            'signature' => $signature,
            'accesskey' => $this->m_config['ks3_accesskey'],
            'expire' => $time,
            'nonce' => $nonce,
            'vdoid' => $video_id,
        ));

        if (empty($this->m_config['ksyun_domain'])) {
            $upstream_address = "rtmp://{$this->m_config['ksyun_app']}.uplive.ks-cdn.com/live/{$stream_id}?" . $params;

            return array(
                'stream_id' => $stream_id,
                'push_rtmp' => $upstream_address,
                'play_rtmp' => "rtmp://{$this->m_config['ksyun_app']}.rtmplive.ks-cdn.com/live/" . $stream_id,
                'play_flv' => "http://{$this->m_config['ksyun_app']}.hdllive.ks-cdn.com/live/" . $stream_id . ".flv",
                'play_hls' => "http://{$this->m_config['ksyun_app']}.hlslive.ks-cdn.com/live/" . $stream_id . "/index.m3u8",
            );
        } else {
            $upstream_address = "rtmp://uplive.{$this->m_config['ksyun_domain']}/live/{$stream_id}?" . $params;

            return array(
                'stream_id' => $stream_id,
                'push_rtmp' => $upstream_address,
                'play_rtmp' => "rtmp://rtmplive.{$this->m_config['ksyun_domain']}/live/" . $stream_id,
                'play_flv' => "http://hdllive.{$this->m_config['ksyun_domain']}/live/" . $stream_id . ".flv",
                'play_hls' => "http://hlslive.{$this->m_config['ksyun_domain']}/live/" . $stream_id . "/index.m3u8",
            );
        }
    }

    public function Query($stream_id)
    {
        $time = $this->getExpireTime();
        $signature = base64_encode(hash_hmac('sha1', "GET\n{$time}\napp=live&name=" . $stream_id,
            $this->m_config['ks3_secretkey'], true));
        $url = "http://{$this->m_config['ksyun_app']}.dashboard.ks-cdn.com/v2/stat?" . http_build_query(array(
                'signature' => $signature,
                'accesskey' => $this->m_config['ks3_accesskey'],
                'expire' => $time,
                'app' => 'live',
                'name' => $stream_id,
            ));

        $client = new Client();
        $response = $client->request('GET', $url);
        $result = json_decode((string)$response->getBody(), true);
        return array(
            'stream_status' => isset($result['app']['live'][$stream_id]) ? 1 : 0
        );
    }

    public function Stop($stream_id)
    {
        $time = $this->getExpireTime();
        $signature = base64_encode(hash_hmac('sha1', "GET\n{$time}\napp=live&method=add&name=" . $stream_id,
            $this->m_config['ks3_secretkey'], true));
        $url = "http://{$this->m_config['ksyun_app']}.dashboard.ks-cdn.com/blacklist?" . http_build_query(array(
                'signature' => $signature,
                'accesskey' => $this->m_config['ks3_accesskey'],
                'expire' => $time,
                'method' => 'add',
                'app' => 'live',
                'name' => $stream_id,
            ));

        $client = new Client();
        $response = $client->request('GET', $url);
        return $response->getStatuscode() == 200;
    }

    public function GetRecord($stream_id, $video_id)
    {
        $client = new Ks3Client($this->m_config['ks3_accesskey'], $this->m_config['ks3_secretkey'],
            "ks3-cn-beijing.ksyun.com");//!!第三个参数endpoint需要对应bucket所在region!! 详见http://ks3.ksyun.com/doc/api/index.html  Region（区域）一节
        // ks3-cn-beijing-internal.ksyun.com

        $bucket = "fanwelive2";
        $mp4_key = $video_id . ".mp4";
        $args = array(
            "Bucket" => $bucket,
            "Key" => $mp4_key,
        );

        if ($client->objectExists($args)) {
            $ks_url = $client->generatePresignedUrl(
                array(
                    "Bucket" => $bucket,
                    "Key" => $mp4_key,
                    "Options" => array(
                        "Expires" => 60 * 60 * 24 * 10,
                    )
                )
            );

            return array(
                '20' => $ks_url,
            );
        } else {
            return array();
        }
    }
}