<?php

namespace project\Frontend\Controller;

use project\Modules\Comments\Form\CommentsForm;
use project\Frontend\Model\FrontendModel;
use fireice\Backend\Tree\Controller\TreeController;
use project\Modules\Comments\Entity\modulecomments;
use Symfony\Component\HttpFoundation\Request;

class FrontendController extends \fireice\Frontend\Controller\FrontendController
{

    public function getModel()
    {
        if (null === $this->model) {
            $this->model = new FrontendModel(
                    $this->get('doctrine.orm.entity_manager'),
                    $this->get('acl'),
                    $this->get('cache'),
                    $this->container
            );
        }

        return $this->model;
    }

    public function showPage($idNode, $language, $params = '')
    {
        $frontendModel = $this->getModel();

        $tree = new TreeController();
        $tree->setContainer($this->container);

        $menu = array (
            'right' => $frontendModel->getMenu(1, $language),
            'sub' => $frontendModel->getMenu($idNode, $language),
        );
        $navigation = $frontendModel->getNavigation($idNode, $language);
        $currentPage = $navigation[count($navigation) - 1];

        if ($frontendModel->checkAccess($idNode)) {
            $nodeModules = $frontendModel->getNodeUsersModules($idNode, $language);

            // Определяем нужно ли показывать комментарии
            $show = false;
            $comments = array ();
            $entity = new modulecomments();
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

                $info = $frontendModel->getCommentsInfo($language);

                $request->query->add(array (
                    'id' => $info['id_node'],
                    'id_module' => $info['id_module'],
                    'language' => $info['language'],
                    'id_row' => -1
                ));
                $request->request->add($form->getData() + array (
                    'date' => array ('data' => date("m.d.y"), 'time' => date("H:i:s")),
                    'node' => $idNode,
                    'item' => 0
                ));
                $request->overrideGlobals();
                $moduleAct = new \project\Modules\Comments\Controller\BackendController();
                $moduleAct->setContainer($this->container);
                $moduleAct->createEdit();

                return $this->redirect($this->generateUrl('frontend', array ('path' => trim($request->getPathInfo(), '/'))));
            }

            // Собираем хтмл
            foreach ($nodeModules as $key => $val) {

                $frontend = $tree->getNodeModule($idNode, $language, $key)->frontend($params, array (
                    'navigation' => $navigation,
                    ));

                if ($frontend->isRedirect()) return $frontend;

                $modulesHtml[] = $frontend->getContent();
            }

            // Хтмл комментариев (если нужно)
            if ($show) {
                $modulesHtml[] = $tree->getNodeModule(395, $language, 7)->frontend($idNode, false)->getContent();
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