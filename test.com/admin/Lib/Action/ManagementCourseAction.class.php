<?php

/**
 *
 */
class ManagementCourseAction extends CommonAction
{
    public function index()
    {
        A('Course')->index(1);
    }

    public function set_effect()
    {
        A('Course')->set_effect();
    }
    public function set_recommend()
    {
        A('Course')->set_recommend();
    }
    public function edit()
    {
        A('Course')->edit();
    }

    public function update()
    {
        A('Course')->update(1);
    }

    public function view()
    {
        A('Course')->view();
    }

    public function viewSeason()
    {
        A('Course')->viewSeason();
    }
}
