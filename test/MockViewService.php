<?php

use mindplay\kisstpl\ViewService;

class MockViewService extends ViewService
{
    public $missed_view;
    public $missed_type;

    protected function onMissingView($view, $type)
    {
        $this->missed_view = $view;
        $this->missed_type = $type;
    }
}
