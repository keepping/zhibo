<?php
/**
 *
 */
class videoModel extends Model
{
    public function getLiveVideoByUserId($user_id, $field = '')
    {
        return $this->field($field)->selectOne(array('user_id' => intval($user_id), 'live_in' => 1));
    }
    public function getOneWithUser($where)
    {
        $field = [
            'v.id room_id',
            'v.sort_num',
            'v.group_id',
            'v.user_id',
            'v.city',
            'v.title',
            'v.cate_id',
            'v.live_in',
            'v.video_type',
            'v.create_type',
            'v.room_type',
            ['(v.robot_num + v.virtual_watch_number + v.watch_number) as watch_number'],
            'u.head_image',
            'u.thumb_head_image',
            'v.xpoint',
            'v.ypoint',
            'u.v_type',
            'u.v_icon',
            'u.nick_name',
            'u.user_level',
            'v.live_image',
            'v.is_live_pay',
            'v.live_pay_type',
            'v.live_fee',
            'u.create_time user_create_time',
        ];
        $where['u.id']        = ['v.user_id'];
        $where['v.live_in']   = ['in', [1, 3]];
        $where['u.mobile']    = ['not in', ['13888888888', '13999999999']];

        $room = $this->table('user u,video v')->field($field)->selectOne($where);
        if ($room) {
            $room['today_create'] = intval(date('Y-m-d') == date('Y-m-d', $room['user_create_time'] + 3600 * 8));
            if (!$room['live_image']) {
                $room['live_image'] = $room['head_image'];
            }
            if (!$room['thumb_head_image']) {
                $room['thumb_head_image'] = $room['head_image'];
            }
            $room['live_image'] = get_spec_image($room['live_image']);
            $room['head_image'] = get_spec_image($room['head_image']);

            $room['thumb_head_image'] = get_spec_image($room['thumb_head_image'], 150, 150);
        }
        return $room;
    }
}
