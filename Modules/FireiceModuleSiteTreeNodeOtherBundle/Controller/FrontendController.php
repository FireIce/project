<?php

namespace example\Modules\FireiceModuleSiteTreeNodeOtherBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class FrontendController extends \fireice\FireiceSiteTree\Modules\BasicBundle\Controller\FrontendController
{
    protected $model = '\\example\\Modules\\FireiceModuleSiteTreeNodeOtherBundle\\Model\\FrontendModel';
}
