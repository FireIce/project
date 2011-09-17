<?php

namespace Fireice\Modules\ModuleNewsBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Fireice\Modules\ModuleNewsBundle\Model\BackendModel;

class BackendController extends \fireice\FireiceSiteTree\Modules\BasicBundle\Controller\BackendController
{
	protected $model = '\\Fireice\\Modules\\ModuleNewsBundle\\Model\\BackendModel';
    
	public function getRowData($sitetree_id, $module_id, $row_id)
	{
		return $this->getModel()->getRowData($sitetree_id, $module_id, $row_id);
	}     
    
	public function deleteRow()
	{
		$this->getModel()->deleteRow( $this->get( 'request' ), $this->get( 'security.context' ) );
	}     
    
    public function updateOrders()
    {
        $this->getModel()->updateOrders( $this->get( 'request' ), $this->get( 'security.context' ) );    
    }

	public function getRights()
	{
		return array(
			array( 'name' => 'view',                  'title' => 'Просмотр' ),
			array( 'name' => 'edit',                  'title' => 'Правка новости' ),
            array( 'name' => 'proveeditor',           'title' => 'Подтвердить на уровне редактора'),
            array( 'name' => 'provemaineditor',       'title' => 'Подтвердить на уровне главного редактора'),
            array( 'name' => 'sendtoproveeditor',     'title' => 'Отправить на утверждение редактору'),
            array( 'name' => 'sendtoprovemaineditor', 'title' => 'Отправить на утверждение главному редактору'),
            array( 'name' => 'returnwriter',          'title' => 'Вернуть на доработку писателю (рядовому журналисту)'),
            array( 'name' => 'returneditor',          'title' => 'Вернуть на доработку редактору'),
		);        
	}

	public function getDefaultRights( $group )
	{
		switch( $group )
		{
			case 'God':
				$rights = array( 'edit' );
				break;
			case 'Administrators':
				$rights = array( 'edit' );
				break;
			case 'Users':
				$rights = array( );
				break;
			case 'Anonymous':
				$rights = array( );
		}

		return $rights;
	}

}
