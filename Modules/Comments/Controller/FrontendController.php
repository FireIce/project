<?php

namespace project\Modules\Comments\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use project\Modules\Comments\Form\CommentsForm;

class FrontendController extends \fireice\Backend\Modules\Controller\FrontendController
{
    protected $model = '\\project\\Modules\\Comments\\Model\\FrontendModel';

    public function frontend($idNode, $false=array())
    {
        $model = $this->getModel();

        $data = $model->getFrontendData($idNode, 0, array());

        $form = $this->createForm(new CommentsForm($model->getAnswers()));

        return $this->render($model->getBundleName().':Frontend:index.html.twig', array (
                'data' => $data,
                'form' => $form->createView(),
                'path' => trim($this->get('request')->getPathInfo(), '/')
                )
        );
    }

}
