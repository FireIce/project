<?php

namespace example\Frontend\Model;

class FrontendModel extends \fireice\Frontend\Model\FrontendModel
{

    public function getCommentsInfo()
    {
        foreach ($this->sitetree['nodes'] as $key => $value) {
            foreach ($value['user_modules'] as $k => $v) {
                if ($v == 'Comments') return array (
                        'id_node' => $key,
                        'id_module' => $k
                    );
            }
        }
    }

}
