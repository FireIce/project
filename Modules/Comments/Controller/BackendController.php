<?php

namespace project\Modules\Comments\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use project\Modules\Comments\Model\BackendModel;

class BackendController extends \project\Modules\News\Controller\BackendController
{
    protected $model = '\\project\\Modules\\Comments\\Model\\BackendModel';

    public function getRights()
    {
        return array (
            array ('name' => 'view', 'title' => 'Просмотр'),
            array ('name' => 'edit', 'title' => 'Правка'),
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
                $rights = array ('edit', 'view', 'provemaineditor', 'returneditor');
                break;
            case 'Administrators':
                $rights = array ('edit', 'view', 'proveeditor', 'returnwriter', 'sendtoprovemaineditor');
                break;
            case 'Users':
                $rights = array ('edit', 'view', 'sendtoproveeditor');
                break;
            case 'Anonymous':
                $rights = array ('view');
        }

        return $rights;
    }

}
