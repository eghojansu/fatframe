<?php

require __DIR__.'/../vendor/autoload.php';

// configuration
$base = Base::instance();
$root = $base->fixslashes(dirname(__DIR__)).'/';
$base->config($root.'dev.ini');
$config = [
    'LOCALES'=>$root.'app/dict/',
    'LOGS'=>$root.'var/logs/',
    'TEMP'=>$root.'var/tmp/',
    'UPLOADS'=>$root.'var/uploads/',
    'CACHE'=>"folder={$root}var/cache/",
    'UI'=>$root.'app/view/',
    // 'CACHE'=>true,
    'LANGUAGE'=>'id',
    'TZ'=>'Asia/Jakarta',
    'APPDIR'=>$root.'app/',
    'ROOTDIR'=>$root,
    'VIEW'=>null,
    'AUTOLOAD'=>$root,
    'PAGE'=>'page',
    'LIMIT'=>'record',
    'LIMIT_LIST'=>[5,10,20,25,50,100],
    'SORT'=>'sort',
];
$base->mset($config);
$base->config($root.'app/config/app.ini');
$base->config($root.'app/config/maps.ini');
$base->config($root.'app/config/redirects.ini');
$base->config($root.'app/config/routes.ini');

// templating
$template = Template::instance();
$filters = [
    'path'=>'fa::path',
    'view'=>'fa::view',
    'bool'=>'fa::bool',
];
foreach ($filters as $alias => $filter) {
    $alias = is_numeric($alias)?$filter:$alias;
    $template->filter($alias, $filter);
}
foreach (ext::getExtension() as $alias=>$ext) {
    $template->extend($alias, 'ext::'.$ext);
}

// clearing unneeded
unset($filter, $filters, $root, $config);

// initiates user
if ('cli' !== PHP_SAPI) {
    $base->set('user', new app\core\User(new app\entity\User));
}

// return base
return $base;
