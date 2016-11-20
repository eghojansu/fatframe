<?php

namespace app\controller;

use nav;
use app\entity\User;
use app\form\UserForm;
use app\form\SearchForm;
use app\core\html\Pagination;
use app\core\BaseDashboardController;

class MasterController extends BaseDashboardController
{
    public function indexAction($base, $args)
    {
        $config = $this->config(__FUNCTION__, $args);

        $map = $config['map'];
        $data = $map()->listing();
        $base->mset([
            'data'=>$data,
            'search'=>new SearchForm,
            'pagination'=>new Pagination($data),
        ]);

        $this->render($config['view']);
    }

    public function readAction($base, $args)
    {
        $config = $this->config(__FUNCTION__, $args);

        $map = $config['map'];
        $base->mset([
            'item'=>$map($args['id'], true),
        ]);

        $this->render($config['view']);
    }

    public function createAction($base, $args)
    {
        $config = $this->config(__FUNCTION__, $args);

        $map = $config['map'];
        $map = $map();
        $formName = $config['form'];
        $form = new $formName($map);
        if ($form->valid()) {
            $map->insert();
            $base['user']->addMessage('success', $base['crud.created']);

            $this->redirect($config['redirect']);
        }

        $base->mset([
            'form'=>$form,
        ]);

        $this->render($config['view']);
    }

    public function updateAction($base, $args)
    {
        $config = $this->config(__FUNCTION__, $args);

        $map = $config['map'];
        $map = $map($args['id'], true);
        $formName = $config['form'];
        $form = new $formName($map);
        if ($form->valid()) {
            $map->update();
            $base['user']->addMessage('success', $base['crud.updated']);

            $this->redirect($config['redirect']);
        }

        $base->mset([
            'form'=>$form,
        ]);

        $this->render($config['view']);
    }

    public function deleteAction($base, $args)
    {
        $config = $this->config(__FUNCTION__, $args);

        $map = $config['map'];
        $map = $map($args['id'], true);
        $config['softErase']? $map->softErase() : $map->erase();
        $base['user']->addMessage('warning', $base['crud.deleted']);
        $this->redirect($config['redirect']);
    }

    private $config = [];

    public function beforeroute($base, $args)
    {
        parent::beforeroute($base, $args);

        $this->config = [
            'user'=>[
                'index'=>['view'=>'master.user.index'],
                'read'=>['view'=>'master.user.read'],
                'create'=>['view'=>'master.user.create'],
                'update'=>['view'=>'master.user.update'],
                'form'=>UserForm::class,
                'delete'=>null,
                'menu'=>'Data User',
                'map'=>function($id = false, $required = false) use ($base) {
                    $map = new User;

                    if ($id) {
                        $filter = ['id <> ?', $base['user']->getId()];
                        $map->loadByKey($id, $filter);

                        if ($required && $map->dry()) {
                            $this->notFound();
                        }
                    }

                    return $map;
                },
            ],
        ];
    }

    protected function config($method, $args)
    {
        $method = str_replace('Action', '', $method);
        $reserved = ['map','menu','form'];

        if (in_array($method, $reserved)
            || empty($this->config[$args['master']])
            || !array_key_exists($method, $this->config[$args['master']])
            ) {
            $this->notFound();
        }

        $default = [
            'redirect'=>'@crud_index',
        ];
        $config = ($this->config[$args['master']][$method]?:[])+$default;
        foreach ($reserved as $key) {
            $config[$key] = $this->config[$args['master']][$key];
        }
        nav::active($config['menu']);

        return $config;
    }
}
