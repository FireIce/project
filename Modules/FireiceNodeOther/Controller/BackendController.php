<?php

namespace project\Modules\FireiceNodeOther\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use project\Modules\FireiceNodeOther\Model\BackendModel;

class BackendController extends \fireice\Backend\Modules\Controller\BackendController
{
    protected $model = '\\project\\Modules\\FireiceNodeOther\\Model\\BackendModel';

    public function getRights()
    {
        return array (
            array ('name' => 'view', 'title' => 'Просмотр'),
            array ('name' => 'edit', 'title' => 'Правка'),
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
