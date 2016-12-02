<?php

namespace app\command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use fa;

class CreateControllerCommand extends AbstractCommand
{
    public function configure()
    {
        $this
            ->setName('controller:create')
            ->setDescription('Create controller')
            ->addOption('namespace', 's', InputOption::VALUE_REQUIRED, 'Controller namespace')
            ->addOption('del', 'd', InputOption::VALUE_NONE, 'Delete all existed controller')
            ->addOption('no-create', 'o', InputOption::VALUE_NONE, 'Do not create controller')
            ->addArgument('filter', InputArgument::IS_ARRAY | InputArgument::OPTIONAL, 'Controller to exclude out', [])
        ;
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->configureIO($input, $output);

        $base = $this->base();
        $finder = new Finder;
        $path = $base->get('ROOTDIR').'app/entity/';
        $controllers = $finder->files()->in($path);
        $filter = $this->input->getArgument('filter');
        foreach ($controllers as $controller) {
            $basename = $controller->getBasename('.php');
            if (in_array($basename, $filter)) {
                continue;
            }
            $this->createForm($basename);
        }

        $this->reallyDone('Controller created');
    }

    private function createForm($controller)
    {
        $base = $this->base();
        $basepath = $base->get('ROOTDIR');
        $path = 'app/controller/';
        if ($ns = $this->input->getOption('namespace')) {
            $path .= $ns;
        }
        $controllerName = $controller.'Controller';

        $file = $basepath.$path.$controllerName.'.php';

        if ($this->input->getOption('del') && file_exists($file)) {
            unlink($file);
        }

        if ($this->input->getOption('no-create')) {
            return;
        }

        if (!is_dir($basepath.$path)) {
            @mkdir($basepath.$path);
        }
        $content = <<<'CONTENT'
<?php

namespace {namespace};

use app\core\BaseDashboardController;
use app\core\html\Pagination;
use app\entity\{entity};
use app\form\{entity}Form;
use app\form\SearchForm;
use nav;

class {entity}Controller extends BaseDashboardController
{
    public function indexAction($base, $args)
    {
        $data = $this->model()->listing();
        $base->mset([
            'data'=>$data,
            'search'=>new SearchForm,
            'pagination'=>new Pagination($data),
        ]);

        $this->render('master.{view}.index');
    }

    public function createAction($base, $args)
    {
        $map = $this->model();
        $form = new {entity}Form($map);
        if ($form->valid()) {
            $map->updateTimestamp()->insert();
            $base['user']->addMessage('success', $base['crud.created']);

            $this->redirect('@'.$base['index']);
        }

        $base->mset([
            'form'=>$form,
        ]);

        $this->render('master.{view}.create');
    }

    public function updateAction($base, $args)
    {
        $map = $this->model($args['id'], true);
        $form = new {entity}Form($map);
        if ($form->valid()) {
            $map->updateTimestamp()->update();
            $base['user']->addMessage('success', $base['crud.updated']);

            $this->redirect('@'.$base['index']);
        }

        $base->mset([
            'form'=>$form,
        ]);

        $this->render('master.{view}.update');
    }

    public function deleteAction($base, $args)
    {
        $map = $this->model($args['id'], true);
        $map->erase();
        $base['user']->addMessage('warning', $base['crud.deleted']);
        $this->redirect('@'.$base['index']);
    }

    private function model($id = false, $required = false)
    {
        $map = new {entity};

        if ($id) {
            $map->loadByKey($id);

            if ($required && $map->dry()) {
                $this->notFound();
            }
        }

        return $map;
    }

    public function beforeroute($base, $args)
    {
        parent::beforeroute($base, $args);
        $base['user']->denyUnlessGranted('admin');
        $base->mset([
            'index'=>'{route}_index',
            'update'=>'{route}_update',
            'delete'=>'{route}_delete',
            'create'=>'{route}_create',
            ]);
        nav::active($base['index']);
    }
}

CONTENT;

        $namespace = strtr(trim($path, '/'), '/', '\\');
        $route = $base->snakecase(lcfirst($controller));
        $view = str_replace('_', '-', $route);

        $hints = [
            '{namespace}',
            '{entity}',
            '{view}',
            '{route}',
        ];
        $replace = [
            $namespace,
            $controller,
            $view,
            $route,
        ];

        $routeFile = $base->get('ROOTDIR').'app/config/routes.ini';
        if (($routeContent = @file_get_contents($routeFile)) && false === strpos($routeContent, '@'.$route.'_index')) {
            $routeData = <<<'ROUTE'

GET @{route}_index: /dashboard/master/{view} = {namespace}\{entity}Controller->indexAction
GET|POST @{route}_create: /dashboard/master/{view}/create = {namespace}\{entity}Controller->createAction
GET|POST @{route}_update: /dashboard/master/{view}/@id/update = {namespace}\{entity}Controller->updateAction
GET @{route}_delete: /dashboard/master/{view}/@id/delete = {namespace}\{entity}Controller->deleteAction

ROUTE;
            file_put_contents($routeFile, str_replace($hints, $replace, $routeData), FILE_APPEND);
        }

        return file_exists($file)?null:file_put_contents($file, str_replace($hints, $replace, $content));
    }
}
