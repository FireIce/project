<?php

namespace example\Modules\ModuleMainpageBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class FrontendController extends \fireice\FireiceSiteTree\Modules\BasicBundle\Controller\FrontendController
{
    protected $model = '\\example\\Modules\\ModuleMainpageBundle\\Model\\FrontendModel';    
}
