<?php

class UserViewHistoryRedisService  extends BaseRedisService
{
    private $user_view_history_db;
    private $id;

    /**
     * +----------------------------------------------------------
     * 架构函数
     * +----------------------------------------------------------
     * @access public
     * +----------------------------------------------------------
     */
    public function __construct($user_id)
    {
        parent::__construct();
        $this->user_view_history_db = $this->prefix . 'user_view_history:';
        $this->id = $user_id;
    }

    public function view($room_id)
    {
        $this->redis->zAdd($this->user_view_history_db . $this->id, time(), $room_id);
    }

    public function remove($room_id)
    {
        $this->redis->zRem($this->user_view_history_db . $this->id, $room_id);
    }

    public function get_history($page = 1, $page_size = 20)
    {
        if($page < 1){
            $page = 1;
        }

        $start = ($page - 1) * $page_size;

//        $this->redis->delete($this->user_view_history_db . $this->id);

        $list = $this->redis->zRevRange($this->user_view_history_db . $this->id, $start, $start + $page_size - 1);

        fanwe_require(APP_ROOT_PATH.'mapi/lib/redis/VideoRedisService.php');
        fanwe_require(APP_ROOT_PATH . 'mapi/lib/redis/UserRedisService.php');
        $video_redis = new VideoRedisService();
        $user_redis = new UserRedisService();

        $video_list = array();
        foreach ($list as $room_id) {
            $fields = array('room_id', 'user_id', 'live_in', 'live_image', 'head_image', 'title');
            $video = $video_redis->getRow_db($room_id,$fields);
            if(empty($video['live_image'])) {
                $video['live_image'] =get_spec_image($video['head_image'],320,180,1);
            }else{
                $video['live_image'] =get_spec_image($video['live_image'],320,180,1);
            }
            if($video['room_id'] === false){
                $video['room_id'] = $room_id;
            }

            $video['watch_number'] =  $video_redis->get_video_watch_num($room_id);
            $video['video_url'] = get_video_url($room_id, $video['live_in']);
            $user = $user_redis->getRow_db($video['user_id'], array('nick_name', 'head_image'));
            $video['nick_name'] = $user['nick_name'];
            $video['thumb_head_image'] = $user['head_image'];

            if($video['user_id']===false){
                $this->remove($room_id);
            }
            $video_list[] = $video;
        }
        return $video_list;

    }

    public function count()
    {
        return intval($this->redis->zCard($this->user_view_history_db . $this->id));
    }

    public function zfollowing()
    {
        return $this->redis->zRange($this->user_view_history_db . $this->id, 0, -1);
    }
}