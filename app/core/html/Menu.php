<?php

namespace app\core\html;

use fa;

class Menu extends HTML
{
    protected $parent;
    protected $menu;
    protected $hide;
    protected $option;
    protected $activeRoute;
    protected $activeClass = 'active';
    protected $childActive = false;
    protected $childs = [];
    protected $default = [];

    public function __construct($menu = null, array $option = null, Menu $parent = null, $hide = false)
    {
        $this->hide = $hide;
        $this->menu = $menu;
        $this->option = ($option?:[])+[
            'route'=>null,
            'args'=>[],
            'url'=>null,
            'divider'=>false,
            'prefix'=>'',
            'suffix'=>'',
            'identifier'=>false,
            'link'=>[],
            'attr'=>[],
            'list'=>[],
        ];
        $this->parent = $parent;
    }

    public function add($menu, array $option = null, $hide = false)
    {
        $child = new Menu($menu, $option, $this, $hide);
        $child
            ->setDefault($this->default)
            ->setActiveClass($this->activeClass)
            ->setActiveRoute($this->activeRoute)
        ;
        $this->childs[$menu] =& $child;

        return $child;
    }

    public function addDivider(array $option = [], $hide = false)
    {
        $option += [
            'divider'=>true,
        ];

        $this->add(null, $option, $hide);

        return $this;
    }

    public function getName()
    {
        return $this->menu;
    }

    public function getOption()
    {
        return $this->option;
    }

    public function getParent()
    {
        return $this->parent;
    }

    public function hasChild()
    {
        return count($this->childs) > 0;
    }

    public function setDefault(array $default)
    {
        $this->default = $default + [
            'list'=>[],
            'link'=>[],
            'divider'=>['class'=>'divider','role'=>'separator'],
        ];

        return $this;
    }

    public function setActiveClass($class)
    {
        $this->activeClass = $class;

        return $this;
    }

    public function setActiveRoute($route)
    {
        $this->activeRoute = $route;

        return $this;
    }

    public function getDefault()
    {
        return $this->default;
    }

    public function getActiveClass()
    {
        return $this->activeClass;
    }

    public function isHidden()
    {
        return $this->hide;
    }

    public function isActive()
    {
        return $this->option['identifier']?$this->menu === $this->activeRoute :
            $this->option['route'] === $this->activeRoute;
    }

    public function hasActiveChild()
    {
        if (!$this->childActive) {
            foreach ($this->childs as $menu => $child) {
                if ($child->isActive()) {
                    return $this->childActive = true;
                }
            }
        }

        return false;
    }

    public function render()
    {
        $str = '';
        foreach ($this->childs as $menu => $child) {
            if ($child->isHidden()) {
                continue;
            }

            $default = $child->getDefault();
            $option = $child->getOption();

            if ($option['divider']) {
                $listContent = '';
                $option['list'] = self::mergeAttributes($option['list'], $default['divider']);
            } else {
                $active = $child->isActive() || $child->hasActiveChild();

                $option['link'] += [
                    'href'=>$option['route']?fa::path($option['route'], $option['args']):$option['url'],
                ] + $default['link'];
                $option['list'] += [
                ] + $default['list'];

                if ($active) {
                    $option['list'] = self::mergeAttributes($option['list'], ['class'=>$this->getActiveClass()]);
                }

                $listContent = trim(self::element('a', $option['prefix'].$menu.$option['suffix'], $option['link']).PHP_EOL.$child->render());
            }

            $str .= self::element('li', $listContent, $option['list']).PHP_EOL;
        }

        return $str?self::element('ul', PHP_EOL.$str, $this->option['attr']):'';
    }
}
