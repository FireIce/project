<?php

namespace Fireice\Modules\FireiceModuleSiteTreeNodeOtherBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Fireice\Modules\FireiceModuleSiteTreeNodeOtherBundle\Model\BackendModel;

class BackendController extends \fireice\FireiceSiteTree\Modules\BasicBundle\Controller\BackendController
{
	protected $model = '\\Fireice\\Modules\\FireiceModuleSiteTreeNodeOtherBundle\\Model\\BackendModel';

	public function getRights()
	{
		return array(
			array( 'name' => 'view', 'title' => 'Просмотр' ),
			array( 'name' => 'edit', 'title' => 'Правка' ),
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
