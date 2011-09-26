<?php

namespace example\Modules\ModuleNewsBundle\Model;

class FrontendModel extends \fireice\FireiceSiteTree\Modules\BasicBundle\Model\FrontendModel
{
    protected $bundle_name = 'ModuleNewsBundle';
    protected $entity_name = 'modulenews';

    public function getFrontendData($sitetree_id, $module_id)
    {
        $values = array ();

        foreach ($this->getPlugins() as $plugin) {
            if (!isset($values[$plugin->getValue('type')])) {
                $values[$plugin->getValue('type')] = $plugin->getData($sitetree_id, $this->bundle_name.':'.$this->entity_name, $module_id, self::TYPE_LIST);
            }
        }

        $data = array ();
        $plugins = $this->getPlugins();

        foreach ($plugins as $plugin) {
            foreach ($values[$plugin->getValue('type')] as $value) {
                if (!isset($data[$value['row_id']])) {
                    $data[$value['row_id']] = array (
                        'id_row' => $value['row_id'],
                        'data' => array ()
                    );
                }

                if ($value['plugin_name'] == $plugin->getValue('name')) $data[$value['row_id']]['data'][$value['plugin_name']] = $plugin->getValues() + array ('value' => $value['plugin_value']);
            }

            // Если этот плагин не присутствует в массиве нужно добавить пустое знаечение
            foreach ($data as &$val) {
                if (!array_key_exists($plugin->getValue('name'), $val['data'])) $val['data'][$plugin->getValue('name')] = $plugin->getNull();
            }
        }

        // Отсортируем в том порядке в каком они указаны в сущности
        foreach ($data as &$value) {
            $tmp = $value['data'];
            $value['data'] = array ();

            foreach ($plugins as $plugin) {
                $value['data'][$plugin->getValue('name')] = $tmp[$plugin->getValue('name')];
            }
        }

        $data = $this->sort($data);

        return array (
            'type' => 'list',
            'data' => $data,
        );
    }

}
