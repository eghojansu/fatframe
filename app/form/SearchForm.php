<?php

namespace app\form;

use app\core\html\BootstrapForm;

class SearchForm extends BootstrapForm
{
    protected $attrs = ['class'=>'form-inline'];
    protected $labelAttrs = ['class'=>'sr-only'];
    protected $method = 'GET';
}
