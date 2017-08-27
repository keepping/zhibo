<?php

class EduOfflineOrderAction extends CommonAction
{
    public function index()
    {
        $user_id = intval($_REQUEST['user_id']);
        if ($user_id > 0) {
            $this->assign("user_id", $user_id);
        }

        $title = strim($_REQUEST['title']);
        if (!empty($title)) {
            $classes = D('EduClassOffline')->where(array(
                'title' => array(
                    'like',
                    '%' . $title . '%'
                )
            ))->field('id')->findAll();

            $class_ids = array();
            foreach ($classes as $class) {
                $class_ids[] = $class['id'];
            }

            $this->assign("default_map", array('class_id' => array('in', $class_ids)));
        }
        parent::index();
    }
}