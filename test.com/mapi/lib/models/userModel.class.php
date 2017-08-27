<?php
/**
 *
 */
class userModel extends Model
{
    public function getOneById($id, $field = '')
    {
        return $this->field($field)->selectOne(array('id' => intval($id)));
    }
    public function coin($user_id, $coin = false, $coin_field = false)
    {
        if ($coin_field == false) {
            if (defined('OPEN_DIAMOND_GAME_MODULE') && OPEN_DIAMOND_GAME_MODULE == 1) {
                $coin_field = 'diamonds';
            } else {
                $coin_field = 'coin';
            }
        }
        $user_id = intval($user_id);
        if (!$user_id) {
            return false;
        }
        fanwe_require(APP_ROOT_PATH . 'mapi/lib/redis/UserRedisService.php');
        $user_redis = new UserRedisService();
        if ($coin === false) {
            return intval($user_redis->getOne_db($user_id, $coin_field));
        } else {
            $coin  = intval($coin);
            $where = array('id' => $user_id);
            if ($coin < 0) {
                $where[$coin_field] = array('>=', -$coin);
            }
            $res = $this->update(array($coin_field => array($coin_field . ' + ' . $coin)), $where);
            if ($res) {
                $user_redis->inc_field($user_id, $coin_field, $coin);
            }
            return $res;
        }
    }
    public function multiAddCoin($game_log_id, $result, $times)
    {
        $coin_field = 'coin';
        if (defined('OPEN_DIAMOND_GAME_MODULE') && OPEN_DIAMOND_GAME_MODULE == 1) {
            $coin_field = 'diamonds';
        }
        $table      = DB_PREFIX . 'user_game_log';
        $table_user = DB_PREFIX . 'user';
        self::$sql  =
            "UPDATE $table_user AS a
                INNER JOIN (
                    SELECT
                        SUM(`money`) AS `win`,
                        `user_id`
                    FROM
                        $table
                    WHERE
                        `game_log_id` = $game_log_id
                    AND `bet` = $result
                    GROUP BY
                        `user_id`
                ) AS b ON a.id = b.user_id
                SET a.{$coin_field} = a.{$coin_field} + b.win * $times";
        return Connect::exec(self::$sql);
    }
    public function returnCoin($game_log_id)
    {
        $coin_field = 'coin';
        if (defined('OPEN_DIAMOND_GAME_MODULE') && OPEN_DIAMOND_GAME_MODULE == 1) {
            $coin_field = 'diamonds';
        }
        $table      = DB_PREFIX . 'user_game_log';
        $table_user = DB_PREFIX . 'user';
        self::$sql  =
            "UPDATE $table_user a
            INNER JOIN (
                SELECT
                    SUM(`money`) AS `win`,
                    `user_id`
                FROM
                    $table
                WHERE
                    `game_log_id` = $game_log_id
                GROUP BY
                    `user_id`
            ) AS b ON a.id = b.user_id
            SET a.{$coin_field} = a.{$coin_field} + b.win";
        return Connect::exec(self::$sql);
    }
    /**
     * 获取邀请码
     * @param  [type] $user_id [description]
     * @return [type]          [description]
     */
    public function getInvitationCode($user_id)
    {
        $user_id = intval($user_id);
        $user    = $this->field('create_time,invitation_code')->selectOne(['id' => $user_id]);
        if (!$user) {
            return false;
        }
        if ($user['invitation_code']) {
            return $user['invitation_code'];
        }
        $users = $this->field('create_time,id')->select(['invitation_code' => '']);
        foreach ($users as $v) {
            $this->update(['invitation_code' => substr(md5($v['id'] . ':' . $v['create_time']), -16)], ['id' => $v['id']]);
        }
        return substr(md5($user_id . ':' . $user['create_time']), -16);
    }
    public function getInvitationId($user_id)
    {
        $user_id = intval($user_id);
        $user    = $this->field('invitation_id')->selectOne(['id' => $user_id]);
        if ($user['invitation_id']) {
            return intval($user['invitation_id']);
        }
        return false;
    }
    /**
     * 通过邀请码设置邀请人
     * @param [type] $user_id         [description]
     * @param [type] $invitation_code [description]
     */
    public function getInvitationBycode($invitation_code)
    {
        $user = $this->field('id')->selectOne(['invitation_code' => $invitation_code]);
        if (!$user) {
            return false;
        }
        return intval($user['id']);
    }
}
