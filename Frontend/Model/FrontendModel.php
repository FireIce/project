<?php

namespace project\Frontend\Model;

class FrontendModel extends \fireice\Frontend\Model\FrontendModel
{

    public function getCommentsInfo($language)
    {
        foreach ($this->sitetree['nodes'] as $key => $value) {
            foreach ($value['language'][$language]['user_modules'] as $k => $v) {
                if ($v == 'Comments') return array (
                        'id_node' => $key,
                        'id_module' => $k,
                        'language'=>$language,
                    );
            }
        }
    }

}
