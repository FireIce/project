<?php

namespace example\Modules\FireiceNodeDefault\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use example\Modules\FireiceNodeDefault\Model\BackendModel;

class BackendController extends \fireice\Backend\Modules\Controller\BackendController
{
    protected $model = '\\example\\Modules\\FireiceNodeDefault\\Model\\BackendModel';

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
