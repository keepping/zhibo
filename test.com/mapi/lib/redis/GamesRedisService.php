<?php

class GamesRedisService extends BaseRedisService
{
    /**
     * @var string 游戏数据前缀
     */
    public $video_games_db;

    /**
     * GamesRedisService constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->video_games_db = $this->prefix . 'games:';
    }

    public function bet($id, $bet, $money, $user_id)
    {
        $this->inc($id, "option$bet", $money);
        $this->inc($id, "option$bet" . ':' . $user_id, $money);
    }

    public function getBet($id, $bet_array, $user_id = false)
    {
        $field = array();
        foreach ($bet_array as $i) {
            $field[] = 'option' . $i;
            if ($user_id) {
                $field[] = 'option' . $i . ':' . $user_id;
            }
        }
        $data     = $this->get($id, $field);
        $bet      = array();
        $user_bet = array();
        foreach ($bet_array as $i) {
            $bet[] = intval($data['option' . $i]);
            if ($user_id) {
                $user_bet[] = intval($data['option' . $i . ':' . $user_id]);
            }
        }
        return $user_id ? [$bet, $user_bet] : $bet;
    }

    public function isLock()
    {
        $lock = $this->redis->hMGet($this->video_games_db . 'global', array('lock'));
        return $lock['lock'] > NOW_TIME;
    }

    public function lock($time = 5)
    {
        return $this->redis->hMset($this->video_games_db . 'global', array('lock' => NOW_TIME + $time));
    }

    public function unLock()
    {
        return $this->redis->hMset($this->video_games_db . 'global', array('lock' => 0));
    }

    public function isVideoLock($video_id)
    {
        $lock = $this->redis->hMGet($this->video_games_db . 'vedio:' . $video_id, array('lock'));
        return $lock['lock'] > NOW_TIME;
    }

    public function lockVideo($video_id, $time = 5)
    {
        return $this->redis->hMset($this->video_games_db . 'vedio:' . $video_id, array('lock' => NOW_TIME + $time));
    }

    public function unLockVideo($video_id)
    {
        return $this->redis->hMset($this->video_games_db . 'vedio:' . $video_id, array('lock' => 0));
    }

    /**
     * @param int   $id 游戏id
     * @param array $data
     * @return bool|int
     */
    public function set($id, $data)
    {
        $id = intval($id);
        if (!$id) {
            return false;
        }
        $redis = $this->redis->multi();
        $redis->hMset($this->video_games_db . $id, $data);
        $res = $redis->exec();
        if (isset($res[0]) && $res[0] !== false) {
            return $id;
        } else {
            return false;
        }
    }

    public function get($id, $field = '')
    {
        $id = intval($id);
        if (!$id) {
            return false;
        }
        if ($field) {
            if (is_string($field)) {
                $field = explode(',', $field);
            }
            return $this->redis->hMGet($this->video_games_db . $id, $field);
        } else {
            return $this->redis->hGetAll($this->video_games_db . $id);
        }
    }

    public function inc($id, $key, $value)
    {
        $id    = intval($id);
        $value = intval($value);
        if (!$id) {
            return false;
        }
        return $this->redis->hIncrBy($this->video_games_db . $id, $key, $value);
    }

    public function del($id)
    {
        $id = intval($id);
        if (!$id) {
            return false;
        }
        return $this->redis->hDel($this->video_games_db . $id, $this->redis->hKeys($this->video_games_db . $id));
    }
}
