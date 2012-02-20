<?php

namespace project\Frontend\Controller;

use project\Modules\Comments\Form\CommentsForm;
use project\Frontend\Model\FrontendModel;
use fireice\Backend\Tree\Controller\TreeController;
use project\Modules\Comments\Entity\modulecomments;

class FrontendController extends \fireice\Frontend\Controller\FrontendController
{

    public function getModel()
    {
        if (null === $this->model) {
            $this->model = new FrontendModel(
                    $this->get('doctrine.orm.entity_manager'),
                    $this->get('acl'),
                    $this->get('cache')
            );
        }

        return $this->model;
    }

    public function showPage($idNode, $params='')
    {
        $frontendModel = $this->getModel();
        
        $tree = new TreeController();
        $tree->setContainer($this->container);    
        
        $menu = array (
            'right' => $frontendModel->getMenu(1),
            'sub' => $frontendModel->getMenu($idNode),
        );
        $navigation = $frontendModel->getNavigation($idNode);
        $currentPage = $navigation[count($navigation) - 1];        

        if ($frontendModel->checkAccess($idNode)) {
            $nodeModules = $frontendModel->getNodeUsersModules($idNode);

            // Определяем нужно ли показывать комментарии
            $show = false;
            $comments = array ();
            $entity =  new modulecomments();
            $tmp = $entity->configNode();
            
            foreach ($tmp['data']['modules'] as $value) {
                $comments[] = ucfirst($value);
            }

            foreach ($nodeModules as $key => $val) {
                if (in_array($val, $comments)) {
                    $show = true;
                    break;
                }
            }

            $request = $this->get('request');

            // Если нужно сохранить комент, то сохраняем и редерим на стр.
            if ($show && $request->getMethod() == 'POST' && $request->request->has('comments')) {
                // Занести комент в БД
                $form = $this->createForm(new CommentsForm());
                $form->bindRequest($request);

                $info = $frontendModel->getCommentsInfo();
                
                $request->query->add(array (
                    'id' => $info['id_node'],
                    'id_module' => $info['id_module'],
                    'id_row' => -1
                ));
                $request->request->add($form->getData() + array (
                    'date' => array ('data' => date("m.d.y"), 'time' => date("H:i:s")),
                    'node' => $idNode,
                    'item' => 0
                ));

                $moduleAct = new \project\Modules\Comments\Controller\BackendController();
                $moduleAct->setContainer($this->container);
                $moduleAct->createEdit();

                return $this->redirect($this->generateUrl('frontend', array ('path' => trim($request->getPathInfo(), '/'))));
            }

            // Собираем хтмл
            foreach ($nodeModules as $key => $val) {
                                              
                $frontend = $tree->getNodeModule($idNode, $key)->frontend($params, array (
                    'navigation' => $navigation,
                    ));

                if ($frontend->isRedirect()) return $frontend;

                $modulesHtml[] = $frontend->getContent();                
            }

            // Хтмл комментариев (если нужно)
            if ($show) {
               $modulesHtml[] = $tree->getNodeModule(223, 7)->frontend($idNode, false)->getContent();                
            }
        } else {
            $modulesHtml['main'] = 'Ошибка!<br>Вы не имеете доступа к этой странице!';
        }

        return $this->render('FrontendBundle:Frontend:index.html.twig', array (
                'modules' => $modulesHtml,
                'menu' => $menu,
                'navigation' => $navigation,
                'current_page' => $currentPage,
                'user' => $frontendModel->getUser(),
            ));
    }

}