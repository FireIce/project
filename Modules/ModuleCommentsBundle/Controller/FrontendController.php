<?php

namespace example\Modules\ModuleCommentsBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class FrontendController extends \fireice\FireiceSiteTree\Modules\BasicBundle\Controller\FrontendController
{
    protected $model = '\\example\\Modules\\ModuleCommentsBundle\\Model\\FrontendModel';

    public function frontend($id_node, $false)
    {
        $model = $this->getModel();

        $data = $model->getFrontendData($id_node, false);
        
        $form = $this->createFormBuilder()
            ->add('title', 'text')
            ->add('comment', 'textarea')
            ->add('answer', 'choice', array ('choices' => $model->getAnswers()))
            ->getForm();

        return $this->render($model->getBundleName().':Frontend:index.html.twig', array (
                'data' => $data,
                'form' => $form->createView(),
                'path' => trim($this->get('request')->getPathInfo(), '/')
                )
        );
    }

}
