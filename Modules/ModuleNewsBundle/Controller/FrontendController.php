<?php

namespace example\Modules\ModuleNewsBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class FrontendController extends \fireice\FireiceSiteTree\Modules\BasicBundle\Controller\FrontendController
{
    protected $model = '\\example\\Modules\\ModuleNewsBundle\\Model\\FrontendModel';
    
    public function getAvailableEndOf()
    {
        return array("|^page\\/[0-9]+$|");
    }      
}
