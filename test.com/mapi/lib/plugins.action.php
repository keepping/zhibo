<?php

class pluginsModule extends baseModule
{
    public function __construct()
    {
        parent::__construct();
        fanwe_require(APP_ROOT_PATH . 'mapi/lib/redis/GamesRedisService.php');
    }

    protected static function getUserId()
    {
        $user_id = intval($GLOBALS['user_info']['id']);
        if (!$user_id) {
            api_ajax_return(array(
                'status' => 0,
                'error'  => '未登录',
            ));
        }
        return $user_id;
    }

    /**
     * 插件列表（暂时只有游戏）
     */
    public function init()
    {
        $user_id = self::getUserId();
        $table   = DB_PREFIX . 'games';
        $count   = $GLOBALS['db']->getOne("SELECT count(1) as count FROM $table");
        $list    = array();
        if ($count) {
            $field   = '`id`,`name`,`image`,`principal`';
            $list    = $GLOBALS['db']->getALL("SELECT $field FROM $table");
            $table   = DB_PREFIX . 'video';
            $video   = $GLOBALS['db']->getRow("SELECT `id` FROM  $table WHERE user_id=" . $user_id . " and live_in=1");
            $game_id = 0;
            if ($video['id']) {
                fanwe_require(APP_ROOT_PATH . 'mapi/lib/redis/VideoRedisService.php');
                $video_redis = new VideoRedisService();
                $redis       = new GamesRedisService();
                $last_game   = $video_redis->getOne_db($video['id'], 'game_log_id');
                if ($last_game) {
                    $last_game = $redis->get($last_game, 'game_id,create_time,long_time');
                    if (NOW_TIME < $last_game['create_time'] + $last_game['long_time']) {
                        $game_id = $last_game['game_id'];
                    }
                }
            }
            foreach ($list as $key => $value) {
                $list[$key]['is_active'] = intval($value['id'] == $game_id);
                $list[$key]['image'] = get_abs_img_root($value['image']);
            }
        }
        api_ajax_return(array(
            'status'   => 1,
            'rs_count' => $count,
            'list'     => $list,
        ));
    }
}
