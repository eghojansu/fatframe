<?php

namespace app\core;

abstract class BaseDashboardController extends Controller
{
    protected $template = 'layout.dashboard';

    public function beforeroute($base, $params)
    {
        parent::beforeroute($base, $params);

        $base['user']->denyUnlessGranted('user');
    }
}
