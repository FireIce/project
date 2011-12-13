<?php

namespace example\Modules\Mainpage\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use fireice\Backend\Tree\Controller\TreeController;

class FrontendController extends \fireice\Backend\Modules\Controller\FrontendController
{
    protected $model = '\\example\\Modules\\Mainpage\\Model\\FrontendModel';

}
