<?php

namespace example\Modules\News\Model;

class FrontendModel extends \fireice\Backend\Modules\Model\FrontendModel
{
    protected $module_name = 'news';

    public function getFrontendData($sitetree_id, $module_id, $params=array ())
    {
        $values = array ();

        foreach ($this->getPlugins() as $plugin) {
            if (!isset($values[$plugin->getValue('type')])) {
                $values[$plugin->getValue('type')] = $plugin->getData($sitetree_id, $this->getBundleName().':'.$this->getEntityName(), $module_id, self::TYPE_LIST);
            }
        }

        $data = array ();
        $plugins = $this->getPlugins();

        foreach ($plugins as $plugin) {
            foreach ($values[$plugin->getValue('type')] as $value) {
                if (!isset($data[$value['row_id']])) {
                    $data[$value['row_id']] = array (
                        'id_row' => $value['row_id'],
                        'url' => $params['url'].'/current/'.$value['row_id'],
                        'data' => array ()
                    );
                }

                if ($value['plugin_name'] == $plugin->getValue('name')) $data[$value['row_id']]['data'][$value['plugin_name']] = $plugin->getValues() + array ('value' => $value['plugin_value']);
            }

            // Если этот плагин не присутствует в массиве нужно добавить пустое знаечение
            foreach ($data as &$val) {
                if (!array_key_exists($plugin->getValue('name'), $val['data'])) $val['data'][$plugin->getValue('name')] = $plugin->getNull();
            }
            unset($val);
        }

        // Отсортируем в том порядке в каком они указаны в сущности
        foreach ($data as &$value) {
            $tmp = $value['data'];
            $value['data'] = array ();

            foreach ($plugins as $plugin) {
                $value['data'][$plugin->getValue('name')] = $tmp[$plugin->getValue('name')];
            }
        }
        unset($value);

        $parametres = array ();

        // Нужно применить настройки
        if (count($params) > 0) {

            if (!isset($params['current'])) {

                $data = $this->sort($data, false);

                if (isset($params['order']) && $params['order'] == 'desc') {
                    $data = array_reverse($data, true);
                }

                // Листалка
                if (isset($params['limit'])) {

                    $chunks = array_chunk($data, $params['limit']);
                    $count_chunks = count($chunks);

                    if ($count_chunks > 1) {
                        $parametres['pager'] = array (
                            'pages' => array (),
                            'next' => false,
                            'prev' => false,
                            'first' => false,
                            'last' => false
                        );

                        foreach ($chunks as $key => $value) {
                            $parametres['pager']['pages'][$key + 1] = array (
                                'page' => $key + 1,
                                'id' => $value[0]['id_row'],
                                'current' => false
                            );
                        }

                        // Выбор нужных записей, соответствующих странице            
                        if (isset($params['chunk'])) {
                            $page = false;
                            foreach ($chunks as $key => $value) {
                                foreach ($value as $val) {
                                    if ($val['id_row'] == $params['chunk']) {
                                        $page = $key + 1;
                                        $data = $value;
                                        $parametres['pager']['pages'][$page]['current'] = true;
                                        break 2;
                                    }
                                }
                            }

                            if (false !== $page) {
                                if ($page < $count_chunks) $parametres['pager']['next'] = $parametres['pager']['pages'][$page + 1]['id'];
                                if ($page > 1) $parametres['pager']['prev'] = $parametres['pager']['pages'][$page - 1]['id'];
                                if ($page != 1) $parametres['pager']['first'] = $parametres['pager']['pages'][1]['id'];
                                if ($page != $count_chunks) $parametres['pager']['last'] = $parametres['pager']['pages'][$count_chunks]['id'];
                            } else {
                                $data = array ();
                            }
                        } else {
                            $data = $chunks[0];
                            $parametres['pager']['pages'][1]['current'] = true;
                            $parametres['pager']['next'] = $count_chunks > 1 ? $parametres['pager']['pages'][2]['id'] : false;
                            $parametres['pager']['last'] = $count_chunks > 1 ? $parametres['pager']['pages'][$count_chunks]['id'] : false;
                        }
                    }
                }
            } else {
                $data = array ($data[$params['current']]);
            }
        }

        return array (
            'data' => $data,
            'parametres' => $parametres
        );
    }

}
