<?php

namespace example\Frontend\FrontendBundle\Controller;

use example\Frontend\FrontendBundle\Model\FrontendModel;

class FrontendController extends \fireice\Frontend\FrontendBasicBundle\Controller\FrontendController
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

    public function showPage($id_node, $params)
    {
        $frontend_model = $this->getModel();

        if ($frontend_model->checkAccess($id_node)) {
            $node_modules = $frontend_model->getNodeModules($id_node);

            // Определяем нужно ли показывать комментарии
            $show = false;
            $comments = array ();
            $entity = '\\'.$this->container->getParameter('project_name').'\\Modules\\ModuleCommentsBundle\\Entity\\modulecomments';
            $entity = new $entity();
            $tmp = $entity->configNode();
            foreach ($tmp['data']['modules'] as $value) {
                $comments[] = 'Module'.ucfirst($value).'Bundle';
            }
            foreach ($node_modules as $key => $val) {
                if (in_array($val, $comments)) {
                    $show = true;
                    break;
                }
            }

            $request = $this->get('request');

            // Если нужно сохранить комент, то сохраняем и редерим на стр.
            if ($show && $request->getMethod() == 'POST') {
                // Занести комент в БД
                $form = $this->createFormBuilder()
                    ->add('title', 'text')
                    ->add('comment', 'textarea')
                    ->add('answer', 'choice')
                    ->getForm();

                $form->bindRequest($request);

                $info = $frontend_model->getCommentsInfo();

                $request->query->add(array (
                    'id' => $info['id_node'],
                    'id_module' => $info['id_module'],
                    'id_row' => -1
                ));
                $request->request->add($form->getData() + array (
                    'date' => array ('data' => date("m.d.y"), 'time' => date("H:i:s")),
                    'node' => $id_node,
                    'item' => 0
                ));

                $module_act = '\\'.$this->container->getParameter('project_name').'\\Modules\\ModuleCommentsBundle\\Controller\\BackendController';
                $module_act = new $module_act();
                $module_act->setContainer($this->container);
                $module_act->createEdit();

                return $this->redirect($this->generateUrl('frontend', array ('path' => trim($request->getPathInfo(), '/'))));
            }

            // Собираем хтмл
            foreach ($node_modules as $key => $val) {
                $module_act = '\\'.$this->container->getParameter('project_name').'\\Modules\\'.$val.'\\Controller\\FrontendController';
                $module_act = new $module_act();
                $module_act->setContainer($this->container);

                $modules_html[] = $module_act->frontend($id_node, $key)->getContent();
            }

            // Хтмл комментариев (если нужно)
            if ($show) {
                $module_act = '\\'.$this->container->getParameter('project_name').'\\Modules\\ModuleCommentsBundle\\Controller\\FrontendController';
                $module_act = new $module_act();
                $module_act->setContainer($this->container);

                $modules_html[] = $module_act->frontend($id_node, false)->getContent();
            }
        } else {
            $modules_html['main'] = 'Ошибка!<br>Вы не имеете доступа к этой странице!';
        }

        $menu = array (
            'right' => $frontend_model->getMenu(1),
            'sub' => $frontend_model->getMenu($id_node),
        );
        $navigation = $frontend_model->getNavigation($id_node);
        $current_page = $navigation[count($navigation) - 1];

        return $this->render('FrontendBundle:Frontend:index.html.twig', array (
                'modules' => $modules_html,
                'menu' => $menu,
                'navigation' => $navigation,
                'current_page' => $current_page,
                'user' => $frontend_model->getUser(),
            ));
    }

}