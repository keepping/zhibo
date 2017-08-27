<?php
/**
 *
 */
class game_logModel extends Model
{
    public function getList($field = '', $where = '', $order = '', $limit = 20)
    {
        return $this->field($field)->order($order)->limit($limit)->select($where);
    }
    public function addLog($podcast_id, $long_time, $game_id, $banker_id = 0)
    {
        $podcast_id = intval($podcast_id);
        $long_time  = intval($long_time);
        $game_id    = intval($game_id);
        $banker_id  = intval($banker_id);
        $data       = array(
            'podcast_id'      => $podcast_id,
            'long_time'       => $long_time,
            'game_id'         => $game_id,
            'banker_id'       => $banker_id,
            'create_time'     => NOW_TIME,
            'create_date'     => to_date(NOW_TIME, 'Y-m-d H:i:s'),
            'create_time_ymd' => to_date(NOW_TIME, 'Y-m-d'),
            'create_time_y'   => to_date(NOW_TIME, 'Y'),
            'create_time_m'   => to_date(NOW_TIME, 'm'),
            'create_time_d'   => to_date(NOW_TIME, 'd'),
        );
        return $this->insert($data);
    }
    public function stop($id)
    {
        return $this->update(array('long_time' => 0), array('id' => intval($id)));
    }
    public function multiAddLog($game_log_id, $result, $bet, $suit_patterns, $podcast_income, $income)
    {
        $table     = DB_PREFIX . 'game_log';
        self::$sql = "UPDATE $table
            SET `status` = 2,
             `result` = $result,
             `suit_patterns` = '$suit_patterns',
             `bet` = '$bet',
             `podcast_income` = $podcast_income,
             `income` = $income
            WHERE
                `id` = $game_log_id";
        return Connect::exec(self::$sql);
    }
}
