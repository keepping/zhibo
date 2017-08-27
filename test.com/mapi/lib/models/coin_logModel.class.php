<?php
/**
 *
 */
class coin_logModel extends Model
{
    public function addLog($user_id, $game_log_id, $diamonds, $account_diamonds, $memo)
    {
        $user_id     = intval($user_id);
        $game_log_id = intval($game_log_id);
        $diamonds    = intval($diamonds);
        if (!($user_id && $game_log_id && $diamonds)) {
            return false;
        }
        $data = array(
            'user_id'          => $user_id,
            'game_log_id'      => $game_log_id,
            'diamonds'         => $diamonds,
            'account_diamonds' => $account_diamonds,
            'memo'             => $memo,
            'create_time'      => NOW_TIME,
        );
        return $this->insert($data);
    }
    public function multiAddLog($game_log_id, $result, $times, $msg = '投注中奖')
    {
        $create_time    = NOW_TIME;
        $table_coin_log = DB_PREFIX . 'coin_log';
        $table          = DB_PREFIX . 'user_game_log';
        $table_user     = DB_PREFIX . 'user';
        if (defined('OPEN_DIAMOND_GAME_MODULE') && OPEN_DIAMOND_GAME_MODULE == 1) {
            $coin_field = 'diamonds';
            // 类型 0表示充值 1表示提现 2赠送道具 3 兑换印票  4 分享获得印票 5 登录赠送积分 6 观看付费直播 7 游戏
            fanwe_require(APP_ROOT_PATH . 'mapi/lib/redis/GamesRedisService.php');
            $redis    = new GamesRedisService();
            $game_log = $redis->get($game_log_id, 'video_id');
            $video_id = intval($game_log['video_id']);

            $table_user_log = DB_PREFIX . 'user_log';
            self::$sql      = "INSERT INTO $table_user_log (
                    `log_info`,
                    `log_time`,
                    `log_admin_id`,
                    `money`,
                    `user_id`,
                    `type`,
                    `prop_id`,
                    `score`,
                    `point`,
                    `podcast_id`,
                    `diamonds`,
                    `ticket`,
                    `video_id`
                )
                SELECT
                    '{$msg}' AS `log_info`,
                    '$create_time' AS `log_time`,
                    '0' AS `log_admin_id`,
                    '0' AS `money`,
                    `user_id`,
                    '7' AS `type`,
                    '0' AS `prop_id`,
                    '0' AS `score`,
                    '0' AS `point`,
                    l.`podcast_id` AS `podcast_id`,
                    (SUM(l.`money`) * $times) AS `diamonds`,
                    '0' AS `ticket`,
                    '{$video_id}' AS `video_id`
                FROM
                    $table AS l,
                    $table_user AS u
                WHERE
                    l.user_id = u.id
                AND l.type = 1
                AND `game_log_id` = $game_log_id
                AND `bet` = $result
                GROUP BY
                    `user_id`";
            Connect::exec(self::$sql);
        } else {
            $coin_field = 'coin';
        }
        self::$sql = "INSERT INTO $table_coin_log (
                `user_id`,
                `game_log_id`,
                `diamonds`,
                `account_diamonds`,
                `memo`,
                `create_time`
            )
            SELECT
                `user_id`,
                '$game_log_id' AS `game_log_id`,
                (SUM(l.`money`) * $times) AS `diamonds`,
                u.`{$coin_field}` AS `account_diamonds`,
                '{$msg}' AS memo,
                '$create_time' AS `create_time`
            FROM
                $table AS l,
                $table_user AS u
            WHERE
                l.user_id = u.id
            AND l.type = 1
            AND `game_log_id` = $game_log_id
            AND `bet` = $result
            GROUP BY
                `user_id`";
        return Connect::exec(self::$sql);
    }
    public function returnCoin($game_log_id)
    {
        $create_time    = NOW_TIME;
        $table_coin_log = DB_PREFIX . 'coin_log';
        $table          = DB_PREFIX . 'user_game_log';
        $table_user     = DB_PREFIX . 'user';
        if (defined('OPEN_DIAMOND_GAME_MODULE') && OPEN_DIAMOND_GAME_MODULE == 1) {
            $coin_field = 'diamonds';
            // 类型 0表示充值 1表示提现 2赠送道具 3 兑换印票  4 分享获得印票 5 登录赠送积分 6 观看付费直播 7 游戏
            fanwe_require(APP_ROOT_PATH . 'mapi/lib/redis/GamesRedisService.php');
            $redis    = new GamesRedisService();
            $game_log = $redis->get($game_log_id, 'video_id');
            $video_id = intval($game_log['video_id']);

            $table_user_log = DB_PREFIX . 'user_log';
            self::$sql      = "INSERT INTO $table_user_log (
                    `log_info`,
                    `log_time`,
                    `log_admin_id`,
                    `money`,
                    `user_id`,
                    `type`,
                    `prop_id`,
                    `score`,
                    `point`,
                    `podcast_id`,
                    `diamonds`,
                    `ticket`,
                    `video_id`
                )
                SELECT
                    '游戏终止，投注返还' AS `log_info`,
                    '$create_time' AS `log_time`,
                    '0' AS `log_admin_id`,
                    '0' AS `money`,
                    `user_id`,
                    '7' AS `type`,
                    '0' AS `prop_id`,
                    '0' AS `score`,
                    '0' AS `point`,
                    l.`podcast_id` AS `podcast_id`,
                    SUM(l.`money`) AS `diamonds`,
                    '0' AS `ticket`,
                    '{$video_id}' AS `video_id`
                FROM
                    $table AS l,
                    $table_user AS u
                WHERE
                    l.user_id = u.id
                AND l.type = 1
                AND `game_log_id` = $game_log_id
                AND `bet` = $result
                GROUP BY
                    `user_id`";
            Connect::exec(self::$sql);
        } else {
            $coin_field = 'coin';
        }
        self::$sql = "INSERT INTO $table_coin_log (
                `user_id`,
                `game_log_id`,
                `diamonds`,
                `account_diamonds`,
                `memo`,
                `create_time`
            )
            SELECT
                `user_id`,
                '$game_log_id' AS `game_log_id`,
                SUM(l.`money`) AS `diamonds`,
                (u.`{$coin_field}`) AS `account_diamonds`,
                '游戏终止，投注返还' AS memo,
                '$create_time' AS `create_time`
            FROM
                $table AS l,
                $table_user AS u
            WHERE
                l.user_id = u.id
            AND `game_log_id` = $game_log_id
            GROUP BY
                `user_id`";
        return Connect::exec(self::$sql);
    }
}
