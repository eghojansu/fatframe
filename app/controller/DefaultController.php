<?php

namespace app\controller;

use app\core\Controller;
use app\core\html\Menu;

class DefaultController extends Controller
{
    public function indexAction()
    {
        $this->render('default.index');
    }

    public function dashboardAction($base)
    {
        $base['user']->denyUnlessGranted('user');

        $this->template('layout.dashboard')->render('default.dashboard');
    }
}
