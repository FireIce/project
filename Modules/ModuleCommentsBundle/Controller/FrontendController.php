<?php

namespace example\Modules\ModuleCommentsBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class FrontendController extends \fireice\FireiceSiteTree\Modules\BasicBundle\Controller\FrontendController
{
    protected $model = '\\example\\Modules\\ModuleCommentsBundle\\Model\\FrontendModel';

    public function frontend($id_node, $module_id=false)
    {
        //echo 'sdfsdfs'; exit;
        
        $model = $this->getModel();

        return $this->render($model->getBundleName().':Frontend:index.html.twig', array ('data' => $model->getFrontendData($id_node, $module_id)));
    }

}
