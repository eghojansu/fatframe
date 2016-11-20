<?php

namespace app\core\html;

class BootstrapHorizontalForm extends BootstrapForm
{
    protected $attrs = [
        'class'=>'form-horizontal',
    ];
    protected $controlAttrs = [
        'class'=>'form-control',
    ];

    public function row($type, $name, $label = null, array $attrs = [], array $labelAttrs = [], $override = false)
    {
        $attrs += ['override'=>false];
        $labelAttrs += ['control-class'=>'col-sm-10','class'=>'col-sm-2','override'=>false];
        $controlWidth = $labelAttrs['control-class'];

        $aOverride = $attrs['override'];
        $lOverride = $labelAttrs['override'];
        unset($attrs['override'],$labelAttrs['override'],$labelAttrs['control-class']);

        $str = $this->label($label?:$name, ['for'=>$name]+$labelAttrs, $lOverride)
             . '<div class="'.$controlWidth.'">'
             . $this->$type($name, $attrs, $aOverride)
             . $this->error($name)
             . '</div>';

        return $str;
    }
}
