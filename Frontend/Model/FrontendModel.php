<?php

namespace example\Frontend\FrontendBundle\Model;

class FrontendModel extends \fireice\Frontend\FrontendBasicBundle\Model\FrontendModel
{

    public function getCommentsInfo()
    {
        foreach ($this->sitetree['nodes'] as $key => $value) {
            foreach ($value['user_modules'] as $k => $v) {
                if ($v == 'ModuleCommentsBundle') return array (
                        'id_node' => $key,
                        'id_module' => $k
                    );
            }
        }
    }

}
