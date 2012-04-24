<?php

namespace project\Modules\Mails\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

class FrontendController extends \project\Modules\News\Controller\FrontendController
{
    protected $model = '\\project\\Modules\\Mails\\Model\\FrontendModel';

    public function frontend($param, $other=array())
    {
        return new Response('');
    }

    public function saveMessage($feedback)
    {   
        $this->getModel()->saveMessage($this->idNode, $this->idModule, $this->language,$feedback, $this->get('acl'));
    }

}
