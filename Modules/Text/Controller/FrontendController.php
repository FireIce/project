<?php

namespace project\Modules\Text\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class FrontendController extends \fireice\Backend\Modules\Controller\FrontendController
{
    protected $model = '\\project\\Modules\\Text\\Model\\FrontendModel';

    public function frontend($params, $other=array ())
    {
        $model = $this->getModel();

        $url = str_replace($params, '', trim($this->get('request')->getUri(), '/'));
        $url = trim($url, '/');

        return $this->render($model->getBundleName().':Frontend:index.html.twig', array (
                'data' => $this->load(array ('url' => $url)),
                'navigation' => $other['navigation'],
                'current' => $this->id_node
            ));
    }

}
