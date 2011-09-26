<?php

namespace example\Frontend\FrontendBundle\Controller;

use example\Frontend\FrontendBundle\Model\FrontendModel;

class FrontendController extends \fireice\Frontend\FrontendBasicBundle\Controller\FrontendController
{

    public function showPage($id_node, $params)
    {
        $frontend_model = $this->getModel();

        if ($frontend_model->checkAccess($id_node)) {
            $node_modules = $frontend_model->getNodeModules($id_node);

            foreach ($node_modules as $key => $val) {
                $module_act = '\\'.$this->container->getParameter('project_name').'\\Modules\\'.$val.'\\Controller\\FrontendController';
                $module_act = new $module_act();
                $module_act->setContainer($this->container);

                $modules_html[] = $module_act->frontend($id_node, $key)->getContent();
            }

            // Комментарии
            $module_act = '\\'.$this->container->getParameter('project_name').'\\Modules\\ModuleCommentsBundle\\Controller\\FrontendController';
            $module_act = new $module_act();
            $module_act->setContainer($this->container);

            $modules_html[] = $module_act->frontend($id_node)->getContent();
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
                'user' => $frontend_model->getUser()
            ));
    }

}