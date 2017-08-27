<?php

/**
 *
 */
class PageviewsStatisticsAction extends CommonAction
{
    public function index()
    {
        $model     = M('CourseSeasonLog');
        $c_s_model = M('CourseSeason');
        // 今日最佳
        $date = to_date(NOW_TIME, 'Y-m-d');
        $res  = $model->where(array('create_date' => $date))->order(array('view_times'))->limit(10)->select();
        foreach ($res as $key => $value) {
            $season = $c_s_model->find($value['course_season_id']);

            $res[$key]['title']     = $season['title'];
            $res[$key]['long_time'] = $season['long_time'] * $value['view_times'];
        }
        $this->assign('today', $res);
        // 近7日数据
        $date = date("Y-m-d", strtotime("-7 day"));
        $res  = $model->where(array('create_date' => array('EGT', $date)))->order(array('view_times'))->limit(10)->select();
        foreach ($res as $key => $value) {
            $season = $c_s_model->find($value['course_season_id']);

            $res[$key]['title']     = $season['title'];
            $res[$key]['long_time'] = $season['long_time'] * $value['view_times'];
        }
        $this->assign('seven_day', $res);
        $this->display();
    }
}
