<?php
/**
 *
 */
class user_game_logModel extends Model
{
    public function multiAddLog($game_log_id, $result, $times, $podcast_id)
    {
        $create_time = NOW_TIME;
        $create_date = to_date($create_time, 'Y-m-d H:i:s');
        $table       = DB_PREFIX . 'user_game_log';
        self::$sql   =
            "INSERT INTO $table (
                `game_log_id`,
                `user_id`,
                `money`,
                `bet`,
                `podcast_id`,
                `create_time`,
                `create_date`,
                `type`
            ) SELECT
                '$game_log_id' AS `game_log_id`,
                `user_id`,
                (SUM(l.`money`) * $times) AS `money`,
                '0' AS `bet`,
                '$podcast_id' AS `podcast_id`,
                '$create_time' AS `create_time`,
                '$create_date' AS `create_date`,
                '2' AS `type`
            FROM
                $table AS l
            WHERE
                l.type = 1
            AND `game_log_id` = $game_log_id
            AND `bet` = $result
            GROUP BY
                `user_id`";
        return Connect::exec(self::$sql);
    }
    public function addLog($game_log_id, $podcast_id, $money, $user_id = false, $bet = 0, $type = 2)
    {
        $create_time = NOW_TIME;
        $create_date = to_date($create_time, 'Y-m-d H:i:s');
        if ($user_id === false) {
            $user_id = $podcast_id;
        }
        $data = array(
            'game_log_id' => $game_log_id,
            'user_id'     => $user_id,
            'podcast_id'  => $podcast_id,
            'money'       => $money,
            'bet'         => $bet,
            'create_time' => $create_time,
            'create_date' => $create_date,
            'type'        => $type,
        );
        return $this->insert($data);
    }
}
