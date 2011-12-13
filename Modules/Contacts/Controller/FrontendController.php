<?php

namespace example\Modules\Contacts\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use example\Modules\Contacts\Form\FeedbackForm;
use example\Modules\Contacts\Entity\feedback;
use fireice\Backend\Tree\Controller\TreeController;

class FrontendController extends \fireice\Backend\Modules\Controller\FrontendController
{
    protected $model = '\\example\\Modules\\Contacts\\Model\\FrontendModel';

    public function frontend($params, $other=array ())
    {
        $model = $this->getModel();

        $request = $this->get('request');

        $url = str_replace($params, '', trim($request->getUri(), '/'));
        $url = trim($url, '/');

        $feedback = new feedback();
        $form = $this->createForm(new FeedbackForm(), $feedback);

        if ($this->get('session')->get($this->get('request')->getUri().'_success') !== true) {
            if ($request->getMethod() == 'POST') {

                $form->bindRequest($request);

                if ($form->isValid()) {

                    $tree = new TreeController();
                    $tree->setContainer($this->container);
                    $settings = $tree->getNodeModule(22, 11)->load();

                    // Отправка письма
                    $message = \Swift_Message::newInstance()
                        ->setSubject('Сообщение с сайта '.$this->get('request')->getHost().' от посетителя '.$feedback->getName())
                        ->setFrom('no-reply@'.$this->get('request')->getHost())
                        ->setTo($settings['data']['email']['value'])
                        /*
                        ->setBody('Вам пришло сообщение с сайта '.$this->get('request')->getHost().' от посетителя '.$feedback->getName()
                            ."\n\rEmail посетителя: ".$feedback->getEmail()
                            ."\n\rСообщение:\n\r"
                            .$feedback->getComment());                        
                         */
                        ->setBody($this->renderView($model->getBundleName().':Frontend:mail.html.twig', array(
                            'url' => $this->get('request')->getHost(),
                            'user' => $feedback->getEmail(),
                            'user_email' => $feedback->getEmail(),
                            'message' => $feedback->getComment()
                        )));
                    
                    $this->get('mailer')->send($message);

                    $tree = new TreeController();
                    $tree->setContainer($this->container);

                    $frontend = $tree->getNodeModule(5, 10)->saveMessage($feedback);

                    $this->get('session')->set($request->getUri().'_success', true);

                    return $this->redirect($request->getUri(), 301);
                }
            }

            return $this->render($model->getBundleName().':Frontend:index.html.twig', array (
                    'message' => 'null',
                    'form' => $form->createView(),
                    'url' => $url,
                    'data' => $this->load(),
                    'navigation' => $other['navigation'],
                    'hierarchy' => $other['hierarchy'],
                    'current' => $this->id_node
                ));
        } else {

            $this->get('session')->remove($request->getUri().'_success');

            return $this->render($model->getBundleName().':Frontend:index.html.twig', array (
                    'message' => 'ok',
                    'data' => $this->load(array ('url' => $url)),
                    'navigation' => $other['navigation'],
                    'hierarchy' => $other['hierarchy'],
                    'current' => $this->id_node
                ));
        }
    }

}
