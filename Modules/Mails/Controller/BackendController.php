<?php

namespace example\Modules\Mails\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use example\Modules\Mails\Model\BackendModel;

class BackendController extends \example\Modules\News\Controller\BackendController
{
    protected $model = '\\example\\Modules\\Mails\\Model\\BackendModel';

    public function getRights()
    {
        return array (
            array ('name' => 'view', 'title' => 'Просмотр'),
            array ('name' => 'edit', 'title' => 'Правка'),
            array ('name' => 'deleteitem', 'title' => 'Удаление письма'),
            array ('name' => 'proveeditor', 'title' => 'Подтвердить на уровне редактора'),
            array ('name' => 'provemaineditor', 'title' => 'Подтвердить на уровне главного редактора'),
            array ('name' => 'sendtoproveeditor', 'title' => 'Отправить на утверждение редактору'),
            array ('name' => 'sendtoprovemaineditor', 'title' => 'Отправить на утверждение главному редактору'),
            array ('name' => 'returnwriter', 'title' => 'Вернуть на доработку писателю (рядовому журналисту)'),
            array ('name' => 'returneditor', 'title' => 'Вернуть на доработку редактору'),
        );
    }

    public function getDefaultRights($group)
    {
        switch ($group) {
            case 'God':
                $rights = array ('edit');
                break;
            case 'Administrators':
                $rights = array ('edit');
                break;
            case 'Users':
                $rights = array ();
                break;
            case 'Anonymous':
                $rights = array ();
        }

        return $rights;
    }

}
